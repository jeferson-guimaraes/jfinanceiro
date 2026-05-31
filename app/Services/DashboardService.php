<?php

namespace App\Services;

use App\Enums\TipoMovimentacaoEnum;
use App\Models\Movimentacao;
use App\Models\Parcela;
use App\Models\User;
use Carbon\Carbon;

class DashboardService
{
    /**
     * Obtém todos os dados necessários para o dashboard de um usuário.
     *
     * @param User $user Usuário autenticado.
     * @return array{stats: array, recentTransactions: array, categoriesSummary: array}
     */
    public function getDashboardData(User $user): array
    {
        $now = Carbon::now();

        $stats = $this->getStats($user, $now);
        $recentTransactions = $this->getRecentTransactions($user);
        $categoriesSummary = $this->getCategoriesSummary($user, $now);

        return [
            'stats' => $stats,
            'recentTransactions' => $recentTransactions,
            'categoriesSummary' => $categoriesSummary,
        ];
    }

    /**
     * Calcula as estatísticas financeiras do mês.
     *
     * @param User $user Usuário autenticado.
     * @param Carbon $now Data atual para referência do mês/ano.
     * @return array Lista de estatísticas formatada para o frontend.
     */
    private function getStats(User $user, Carbon $now): array
    {
        $ganhosMes = (float) Movimentacao::doUsuario($user->id)
            ->ganhos()
            ->doMes($now->month, $now->year)
            ->sum('valor');

        $gastosMes = (float) Movimentacao::doUsuario($user->id)
            ->gastos()
            ->doMes($now->month, $now->year)
            ->sum('valor');

        $aPagarFuturo = (float) Parcela::whereHas('movimentacao', function ($query) use ($user) {
            $query->doUsuario($user->id)->gastosFuturos();
        })
            ->where('pago', false)
            ->sum('valor');

        return [
            ['title' => 'Saldo Atual', 'value' => ($ganhosMes - $gastosMes), 'icon' => 'wallet', 'color' => 'text-emerald-600', 'description' => 'Saldo do mês'],
            ['title' => 'Ganhos (Mês)', 'value' => $ganhosMes, 'icon' => 'trendingUp', 'color' => 'text-blue-600', 'description' => 'Total recebido'],
            ['title' => 'Gastos (Mês)', 'value' => $gastosMes, 'icon' => 'trendingDown', 'color' => 'text-rose-600', 'description' => 'Total gasto'],
            ['title' => 'A Pagar (Futuro)', 'value' => $aPagarFuturo, 'icon' => 'calendar', 'color' => 'text-amber-600', 'description' => 'Parcelas pendentes'],
        ];
    }

    /**
     * Busca as transações mais recentes.
     *
     * @param User $user Usuário autenticado.
     * @return array Lista de movimentações formatada para o frontend.
     */
    private function getRecentTransactions(User $user): array
    {
        return Movimentacao::doUsuario($user->id)
            ->with('categoria')
            ->whereIn('tipo', [TipoMovimentacaoEnum::GANHO, TipoMovimentacaoEnum::GASTO])
            ->orderBy('data', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($m) {
                $isGanho = $m->tipo === TipoMovimentacaoEnum::GANHO;
                return [
                    'id' => $m->id,
                    'description' => $m->descricao,
                    'category' => $m->categoria->nome,
                    'value' => (float) $m->valor,
                    'type' => $isGanho ? 'GANHO' : 'GASTO',
                    'date' => Carbon::parse($m->data)->format('d/m/Y'),
                    'status' => $isGanho ? 'Recebido' : 'Pago'
                ];
            })
            ->toArray();
    }

    /**
     * Gera um resumo de gastos por categoria no mês atual.
     *
     * @param User $user Usuário autenticado.
     * @param Carbon $now Data atual para referência do mês/ano.
     * @return array Resumo de gastos por categoria com porcentagem.
     */
    private function getCategoriesSummary(User $user, Carbon $now): array
    {
        $gastosTotaisMes = (float) Movimentacao::doUsuario($user->id)
            ->gastos()
            ->doMes($now->month, $now->year)
            ->sum('valor');

        return Movimentacao::doUsuario($user->id)
            ->gastos()
            ->doMes($now->month, $now->year)
            ->selectRaw('categoria_id, sum(valor) as total')
            ->groupBy('categoria_id')
            ->with('categoria')
            ->get()
            ->map(function ($c) use ($gastosTotaisMes) {
                $total = (float) $c->total;
                return [
                    'name' => $c->categoria->nome,
                    'value' => $total,
                    'percentage' => $gastosTotaisMes > 0 ? round(($total / $gastosTotaisMes) * 100) : 0,
                    'color' => 'bg-slate-500'
                ];
            })
            ->toArray();
    }
}
