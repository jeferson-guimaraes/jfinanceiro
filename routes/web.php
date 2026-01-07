<?php

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
    Route::get('dashboard', fn () => Inertia::render('Dashboard'))
        ->name('dashboard');

    Route::prefix('movimentacoes')->name('movimentacoes.')->group(function () {
        Route::get('create', [MovimentacaoController::class, 'create'])
            ->name('create');

        Route::post('/', [MovimentacaoController::class, 'store'])
            ->name('store');
    });
});


require __DIR__.'/settings.php';