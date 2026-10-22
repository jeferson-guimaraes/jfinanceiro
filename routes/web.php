<?php

use App\Http\Controllers\AvaliacaoController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MovimentacaoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UsuarioSenhaController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('avaliar', [AvaliacaoController::class, 'create'])->name('avaliacoes.create');
Route::post('avaliar', [AvaliacaoController::class, 'store'])
    ->middleware('throttle:avaliacoes')
    ->name('avaliacoes.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::prefix('usuarios')->name('usuarios.')->middleware('admin')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');

        Route::patch('{usuario}/senha', UsuarioSenhaController::class)
            ->name('senha.update')
            ->whereNumber('usuario');
    });

    Route::prefix('avaliacoes')->name('avaliacoes.')->middleware('admin')->group(function () {
        Route::get('/', [AvaliacaoController::class, 'index'])->name('index');

        Route::patch('{avaliacao}/aprovar', [AvaliacaoController::class, 'aprovar'])
            ->name('aprovar')
            ->whereNumber('avaliacao');

        Route::patch('{avaliacao}/reprovar', [AvaliacaoController::class, 'reprovar'])
            ->name('reprovar')
            ->whereNumber('avaliacao');
    });

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
