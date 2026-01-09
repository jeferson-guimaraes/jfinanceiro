<?php

use App\Http\Controllers\CategoriaController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', fn () => Inertia::render('Dashboard'))
        ->name('dashboard');

    Route::prefix('movimentacoes/categorias')->name('movimentacoes.categorias.')->group(function () {
        Route::get('/', function () {
            return Inertia::render('movimentacoes/categorias/Index');
        })->name('index');

        Route::get('create', [CategoriaController::class, 'create'])
            ->name('create');

        Route::post('/', [CategoriaController::class, 'store'])
            ->name('store');
    });
});

require __DIR__.'/settings.php';
