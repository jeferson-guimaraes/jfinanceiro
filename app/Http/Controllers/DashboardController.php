<?php

namespace App\Http\Controllers;

use App\Models\Movimentacao;
use App\Models\Parcela;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $now = Carbon::now();

        // 1. Stats
        $ganhosMes = Movimentacao::where('user_id', $user->id)
            ->where('tipo', 'ganho')
            ->whereMonth('data', $now->month)
            ->whereYear('data', $now->year)
            ->sum('valor');

        $gastosMes = Movimentacao::where('user_id', $user->id)
            ->where('tipo', 'gasto')
            ->whereMonth('data', $now->month)
            ->whereYear('data', $now->year)
            ->sum('valor');
            
        $aPagarFuturo = Parcela::whereHas('movimentacao', function ($query) use ($user) {
                $query->where('user_id', $user->id)->where('tipo', 'gasto futuro');
            })
            ->where('pago', false)
            ->sum('valor');

        $stats = [
            ['title' => 'Saldo Atual', 'value' => ($ganhosMes - $gastosMes), 'icon' => 'wallet', 'color' => 'text-emerald-600', 'description' => 'Saldo do mês'],
            ['title' => 'Ganhos (Mês)', 'value' => $ganhosMes, 'icon' => 'trendingUp', 'color' => 'text-blue-600', 'description' => 'Total recebido'],
            ['title' => 'Gastos (Mês)', 'value' => $gastosMes, 'icon' => 'trendingDown', 'color' => 'text-rose-600', 'description' => 'Total gasto'],
            ['title' => 'A Pagar (Futuro)', 'value' => $aPagarFuturo, 'icon' => 'calendar', 'color' => 'text-amber-600', 'description' => 'Parcelas pendentes'],
        ];

        // 2. Transações
        $recentTransactions = Movimentacao::where('user_id', $user->id)
            ->orderBy('data', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($m) {
                return [
                    'id' => $m->id,
                    'description' => $m->descricao,
                    'category' => $m->categoria->nome,
                    'value' => $m->valor,
                    'type' => $m->tipo === 'ganho' ? 'GANHO' : 'GASTO',
                    'date' => Carbon::parse($m->data)->format('d/m/Y'),
                    'status' => $m->tipo === 'ganho' ? 'Recebido' : 'Pago' // Simplificação
                ];
            });

        // 3. Gastos por Categoria
        $gastosTotaisMes = $gastosMes;
        $categoriesSummary = Movimentacao::where('user_id', $user->id)
            ->where('tipo', 'gasto')
            ->whereMonth('data', $now->month)
            ->whereYear('data', $now->year)
            ->selectRaw('categoria_id, sum(valor) as total')
            ->groupBy('categoria_id')
            ->with('categoria')
            ->get()
            ->map(function ($c) use ($gastosTotaisMes) {
                return [
                    'name' => $c->categoria->nome,
                    'value' => $c->total,
                    'percentage' => $gastosTotaisMes > 0 ? round(($c->total / $gastosTotaisMes) * 100) : 0,
                    'color' => 'bg-slate-500' // Placeholder para cor dinâmica
                ];
            });

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentTransactions' => $recentTransactions,
            'categoriesSummary' => $categoriesSummary,
        ]);
    }
}
