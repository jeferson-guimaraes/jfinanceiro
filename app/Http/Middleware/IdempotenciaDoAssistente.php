<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Idempotência dos POST da API do assistente (evita lançamento/pagamento duplicado em reenvios do bot).
 *
 * Exige o header `Idempotency-Key`. Com uma trava por (token, chave), processa uma vez e guarda a resposta 2xx
 * por 24 h junto com o hash do corpo: um reenvio idêntico recebe a mesma resposta sem gravar de novo; a mesma
 * chave com outro corpo é recusada (422); um reenvio enquanto o primeiro ainda roda recebe 409.
 */
class IdempotenciaDoAssistente
{
    public const VALIDADE_SEGUNDOS = 86400;

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $chave = (string) $request->header('Idempotency-Key');

        if (! preg_match('/^[A-Za-z0-9_-]{8,100}$/', $chave)) {
            return response()->json(['message' => 'Envie o header Idempotency-Key (8 a 100 caracteres: letras, números, - ou _).'], 422);
        }

        $base = 'assistente-idem:'.$request->user()->currentAccessToken()->getKey().':'.$chave;
        $hashCorpo = hash('sha256', $request->method().' '.$request->fullUrl().' '.$request->getContent());

        $trava = Cache::lock($base.':trava', 120);

        if (! $trava->get()) {
            return response()->json(['message' => 'Esta solicitação já está sendo processada.', 'code' => 'em_processamento'], 409)
                ->header('Retry-After', '5');
        }

        try {
            $guardada = Cache::get($base);

            if ($guardada !== null) {
                return $guardada['hash'] === $hashCorpo
                    ? response()->json($guardada['corpo'], $guardada['status'])->header('Idempotent-Replayed', 'true')
                    : response()->json(['message' => 'Esta Idempotency-Key já foi usada com outros dados.'], 422);
            }

            $resposta = $next($request);

            if ($resposta->isSuccessful()) {
                Cache::put($base, [
                    'hash' => $hashCorpo,
                    'status' => $resposta->getStatusCode(),
                    'corpo' => json_decode((string) $resposta->getContent(), true),
                ], self::VALIDADE_SEGUNDOS);
            }

            return $resposta;
        } finally {
            $trava->release();
        }
    }
}
