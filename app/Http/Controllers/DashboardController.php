<?php

namespace App\Http\Controllers;

use App\Http\Requests\DashboardRequest;
use App\Services\DashboardService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Exibe o dashboard: resumo do período escolhido (padrão: mês atual) e a visão geral desde o cadastro.
     */
    public function index(DashboardRequest $request, DashboardService $dashboardService): Response
    {
        $user = $request->user();
        $dadosDoPeriodo = $dashboardService->getDadosDoPeriodo($user, $request->dataInicio(), $request->dataFim());

        return Inertia::render('Dashboard', [
            ...$dadosDoPeriodo,
            'geral' => fn () => $dashboardService->getVisaoGeral($user),
        ]);
    }
}
