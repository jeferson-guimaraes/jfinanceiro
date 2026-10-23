<?php

use App\Exceptions\MovimentacaoInvalidaException;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUsuarioAtivoNaApi;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\IdempotenciaDoAssistente;
use App\Http\Middleware\LimiteDoAssistentePorIp;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'abilities' => CheckAbilities::class,
            'assistente.ativo' => EnsureUsuarioAtivoNaApi::class,
            'assistente.idempotencia' => IdempotenciaDoAssistente::class,
            'assistente.limite-ip' => LimiteDoAssistentePorIp::class,
        ]);

        // O Laravel reordena os middlewares pela prioridade (auth vem cedo); o limite por IP precisa vir antes dele.
        $middleware->prependToPriorityList(before: AuthenticatesRequests::class, prepend: LimiteDoAssistentePorIp::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // A API do assistente responde sempre em JSON (sem redirect para /login), mesmo sem o header Accept.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/assistente/*') || $request->expectsJson()
        );

        // Regra de domínio violada (ex.: pagar sem parcela pendente): 409 na API; mensagem na tela no web.
        $exceptions->render(function (MovimentacaoInvalidaException $e, Request $request) {
            return $request->is('api/assistente/*')
                ? response()->json(['message' => $e->getMessage(), 'code' => 'regra_de_dominio'], 409)
                : back()->with('error', $e->getMessage());
        });

        // Erro inesperado na API do assistente: nunca expõe trace, SQL ou caminhos, mesmo com APP_DEBUG ligado.
        // (AuthorizationException e ModelNotFoundException já chegam convertidas em exceções HTTP.)
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/assistente/*')
                || $e instanceof ValidationException
                || $e instanceof AuthenticationException) {
                return null;
            }

            if ($e instanceof HttpExceptionInterface) {
                $mensagem = match ($e->getStatusCode()) {
                    403 => $e->getMessage() === 'Usuário inativo.' ? $e->getMessage() : 'Este token não tem permissão para esta ação.',
                    404 => 'Registro não encontrado.',
                    405 => 'Método não permitido.',
                    429 => 'Muitas requisições. Tente novamente mais tarde.',
                    503 => 'Sistema em manutenção. Tente novamente em instantes.',
                    default => $e->getMessage() ?: 'Erro na requisição.',
                };

                return response()->json(['message' => $mensagem], $e->getStatusCode(), $e->getHeaders());
            }

            return response()->json(['message' => 'Erro interno do servidor.'], 500);
        });
    })->create();
