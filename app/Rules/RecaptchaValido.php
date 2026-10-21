<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Valida um token do reCAPTCHA v3 junto ao Google.
 *
 * Aprova quando a verificação é bem-sucedida, a ação e o domínio conferem e a nota atinge o
 * mínimo configurado. Qualquer falha de rede ou resposta inesperada reprova o envio. Sem secret
 * configurado, a verificação só é dispensada nos ambientes local e de testes.
 */
class RecaptchaValido implements ValidationRule
{
    public const URL_VERIFICACAO = 'https://www.google.com/recaptcha/api/siteverify';

    public const MENSAGEM = 'Não foi possível confirmar que você não é um robô. Tente novamente.';

    /**
     * Executa a regra mesmo quando o token vem vazio ou ausente, para que um envio sem token
     * seja reprovado em vez de ignorado.
     */
    public bool $implicit = true;

    public function __construct(
        public string $acaoEsperada,
        public ?string $ipDoVisitante = null,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secret = config('services.recaptcha.secret_key');

        if (blank($secret)) {
            if (! app()->environment(['local', 'testing'])) {
                $fail(self::MENSAGEM);
            }

            return;
        }

        if (! is_string($value) || $value === '' || ! $this->isTokenAprovado($secret, $value)) {
            $fail(self::MENSAGEM);
        }
    }

    private function isTokenAprovado(string $secret, string $token): bool
    {
        try {
            $resposta = Http::asForm()
                ->connectTimeout(2)
                ->timeout(4)
                ->post(self::URL_VERIFICACAO, array_filter([
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => $this->ipDoVisitante,
                ]));
        } catch (ConnectionException) {
            return false;
        }

        if (! $resposta->successful()) {
            return false;
        }

        $scoreMinimo = (float) config('services.recaptcha.score_minimo', 0.5);

        return $resposta->json('success') === true
            && $resposta->json('action') === $this->acaoEsperada
            && in_array($resposta->json('hostname'), self::hostnamesPermitidos(), true)
            && is_numeric($resposta->json('score'))
            && (float) $resposta->json('score') >= $scoreMinimo;
    }

    /**
     * Domínios em que o token pode ter sido gerado. Vêm de RECAPTCHA_HOSTNAMES ou, sem essa
     * configuração, do domínio do APP_URL com e sem "www.".
     *
     * @return list<string>
     */
    public static function hostnamesPermitidos(): array
    {
        $configurados = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) config('services.recaptcha.hostnames')),
        )));

        if ($configurados !== []) {
            return $configurados;
        }

        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return [];
        }

        $semWww = preg_replace('/^www\./', '', $host);

        return array_values(array_unique([$host, $semWww, 'www.'.$semWww]));
    }
}
