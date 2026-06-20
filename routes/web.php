<?php

use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MovimentacaoController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::prefix('movimentacoes')->name('movimentacoes.')->group(function () {
        Route::get('index', [MovimentacaoController::class, 'index'])
            ->name('index');

        Route::get('create', [MovimentacaoController::class, 'create'])
            ->name('create');

        Route::post('/', [MovimentacaoController::class, 'store'])
            ->name('store');

        Route::get('{movimentacao}/edit', [MovimentacaoController::class, 'edit'])
            ->name('edit')
            ->whereNumber('movimentacao');

        Route::patch('{movimentacao}', [MovimentacaoController::class, 'update'])
            ->name('update')
            ->whereNumber('movimentacao');

        Route::delete('{movimentacao}', [MovimentacaoController::class, 'destroy'])
            ->name('destroy')
            ->whereNumber('movimentacao');

        Route::delete('/', [MovimentacaoController::class, 'destroyMany'])
            ->name('destroyMany');

        Route::post('{movimentacao}/pagar', [MovimentacaoController::class, 'pagarParcelas'])
            ->name('pagar')
            ->whereNumber('movimentacao');

        Route::post('pagar-massa', [MovimentacaoController::class, 'pagarParcelasMassa'])
            ->name('pagarMassa');
    });

    Route::prefix('movimentacoes/categorias')->name('movimentacoes.categorias.')->group(function () {
        Route::get('/', [CategoriaController::class, 'index'])
            ->name('index');

        Route::get('create', [CategoriaController::class, 'create'])
            ->name('create');

        Route::post('/', [CategoriaController::class, 'store'])
            ->name('store');

        Route::get('{categoria}/edit', [CategoriaController::class, 'edit'])
            ->name('edit');

        Route::patch('{categoria}', [CategoriaController::class, 'update'])
            ->name('update');

        Route::delete('/', [CategoriaController::class, 'destroyMany'])
            ->name('destroyMany');

        Route::delete('{categoria}', [CategoriaController::class, 'destroy'])
            ->name('destroy');
    });
});

require __DIR__.'/settings.php';
