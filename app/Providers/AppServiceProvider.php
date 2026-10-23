<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public const MENSAGEM_LIMITE_TENTATIVAS = 'Muitas tentativas de envio em pouco tempo. Aguarde alguns minutos e tente novamente.';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configuraLimiteDeAvaliacoes();
        $this->configuraLimiteDoAssistente();
    }

    /**
     * Limita as tentativas de envio do formulário público de avaliação a 20 por hora por IP,
     * contando também os envios inválidos, para conter abusos. O limite de avaliações salvas
     * fica no controller. Ao estourar, devolve o visitante ao formulário com uma mensagem.
     */
    private function configuraLimiteDeAvaliacoes(): void
    {
        RateLimiter::for('avaliacoes', function (Request $request): Limit {
            return Limit::perHour(20)
                ->by($request->ip())
                ->response(fn () => back()
                    ->withInput()
                    ->withErrors(['envio' => self::MENSAGEM_LIMITE_TENTATIVAS]));
        });
    }

    /**
     * Limita a API do assistente a 60 requisições por minuto por token (ou por IP, sem token).
     */
    private function configuraLimiteDoAssistente(): void
    {
        RateLimiter::for('assistente-api', function (Request $request): Limit {
            $tokenId = $request->user()?->currentAccessToken()?->getKey();

            return Limit::perMinute(60)->by($tokenId ? 'token:'.$tokenId : 'ip:'.$request->ip());
        });
    }
}
