<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Exibe o dashboard principal.
     */
    public function index(DashboardService $dashboardService): Response
    {
        $data = $dashboardService->getDashboardData(Auth::user());

        return Inertia::render('Dashboard', $data);
    }
}
