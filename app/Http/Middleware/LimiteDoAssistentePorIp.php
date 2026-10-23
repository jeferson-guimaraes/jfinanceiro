<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Limite por IP na API do assistente, aplicado ANTES da autenticação (o throttle padrão só roda depois dela,
 * então tentativas com token inválido não seriam contidas). 120 requisições por minuto por IP.
 */
class LimiteDoAssistentePorIp
{
    public const MAXIMO_POR_MINUTO = 120;

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $chave = 'assistente-ip:'.$request->ip();

        if (RateLimiter::tooManyAttempts($chave, self::MAXIMO_POR_MINUTO)) {
            return response()->json(['message' => 'Muitas requisições. Tente novamente mais tarde.'], 429)
                ->header('Retry-After', (string) RateLimiter::availableIn($chave));
        }

        RateLimiter::hit($chave, 60);

        return $next($request);
    }
}
