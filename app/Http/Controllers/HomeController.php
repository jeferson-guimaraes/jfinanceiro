<?php

namespace App\Http\Controllers;

use App\Models\Avaliacao;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class HomeController extends Controller
{
    /**
     * Exibe a página inicial pública.
     */
    public function __invoke(): Response
    {
        return Inertia::render('Welcome', [
            'canRegister' => Features::enabled(Features::registration()),
            'avaliacoes' => Avaliacao::paraLanding(),
        ]);
    }
}
