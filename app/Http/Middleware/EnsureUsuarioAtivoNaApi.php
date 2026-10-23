<?php

namespace App\Http\Middleware;

use App\Enums\StatusEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Na API do assistente: recusa usuário inativo e responde mensagens de validação em português.
 */
class EnsureUsuarioAtivoNaApi
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale('pt_BR');

        if ($request->user()?->status !== StatusEnum::ATIVO) {
            abort(403, 'Usuário inativo.');
        }

        return $next($request);
    }
}
