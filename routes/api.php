<?php

use App\Http\Controllers\Api\AssistenteFinanceiroController;
use Illuminate\Support\Facades\Route;

// API dos assistentes (Logan e afins): token Sanctum por usuário/assistente; sem editar nem excluir.
Route::prefix('assistente')
    ->middleware(['assistente.limite-ip', 'auth:sanctum', 'assistente.ativo', 'throttle:assistente-api'])
    ->name('api.assistente.')
    ->group(function () {
        Route::middleware('abilities:financeiro:read')->group(function () {
            Route::get('categorias', [AssistenteFinanceiroController::class, 'categorias'])->name('categorias');
            Route::get('parcelas/pendentes', [AssistenteFinanceiroController::class, 'parcelasPendentes'])->name('parcelas.pendentes');
            Route::get('resumo', [AssistenteFinanceiroController::class, 'resumo'])->name('resumo');
        });

        Route::middleware(['abilities:financeiro:write', 'assistente.idempotencia'])->group(function () {
            Route::post('movimentacoes', [AssistenteFinanceiroController::class, 'lancar'])->name('movimentacoes.lancar');
            Route::post('movimentacoes/{movimentacao}/pagar', [AssistenteFinanceiroController::class, 'pagar'])
                ->whereNumber('movimentacao')
                ->name('movimentacoes.pagar');
        });
    });
