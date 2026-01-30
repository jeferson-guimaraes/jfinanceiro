<?php

use App\Http\Controllers\MovimentacaoController;
use App\Http\Controllers\CategoriaController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', fn () => Inertia::render('Dashboard'))
        ->name('dashboard');

    Route::prefix('movimentacoes')->name('movimentacoes.')->group(function () {
        Route::get('create', [MovimentacaoController::class, 'create'])
            ->name('create');

        Route::post('/', [MovimentacaoController::class, 'store'])
            ->name('store');
    });

    Route::prefix('movimentacoes/categorias')->name('movimentacoes.categorias.')->group(function () {
        Route::get('/', [CategoriaController::class, 'index'])   
            ->name('index');

        Route::get('create', [CategoriaController::class, 'create'])
            ->name('create');

        Route::post('/', [CategoriaController::class, 'store'])
            ->name('store');

        Route::delete('/', [CategoriaController::class, 'destroyMany'])
            ->name('destroyMany');
    });
});

require __DIR__.'/settings.php';