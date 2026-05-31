<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Exibe o dashboard principal.
     *
     * @param DashboardService $dashboardService
     * @return Response
     */
    public function index(DashboardService $dashboardService): Response
    {
        $data = $dashboardService->getDashboardData(Auth::user());

        return Inertia::render('Dashboard', $data);
    }
}
