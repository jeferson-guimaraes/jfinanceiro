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
     * @param  User  $user  Usuário autenticado.
     * @return array{stats: array, recentTransactions: array, categoriesSummary: array}
     */
    public function getDashboardData(User $user): array
    {
        $now = Carbon::now();
        $totais = $this->getTotaisDoMes($user, $now);

        $stats = $this->getStats($user, $now, $totais);
        $recentTransactions = $this->getRecentTransactions($user);
        $categoriesSummary = $this->getCategoriesSummary($totais);

        return [
            'stats' => $stats,
            'recentTransactions' => $recentTransactions,
            'categoriesSummary' => $categoriesSummary,
        ];
    }

    /**
     * Totais financeiros de um mês, em números crus (fonte única para o dashboard e para a API do assistente).
     *
     * "A pagar" considera só as parcelas pendentes de gasto futuro que vencem no mês (regra do "A Pagar" do
     * dashboard). Gastos por categoria seguem a ordem de maior total.
     *
     * @param  User  $user  Dono dos dados.
     * @param  Carbon  $mes  Qualquer data dentro do mês desejado.
     * @return array{ganhos: float, gastos: float, saldo: float, a_pagar: float, gastos_por_categoria: list<array{categoria_id: int, categoria: string, total: float, percentual: int}>}
     */
    public function getTotaisDoMes(User $user, Carbon $mes): array
    {
        $ganhos = (float) Movimentacao::doUsuario($user->id)
            ->ganhos()
            ->doMes($mes->month, $mes->year)
            ->sum('valor');

        $gastos = (float) Movimentacao::doUsuario($user->id)
            ->gastos()
            ->doMes($mes->month, $mes->year)
            ->sum('valor');

        $aPagar = $this->getAPagarDoMes($user, $mes);

        $gastosPorCategoria = Movimentacao::doUsuario($user->id)
            ->gastos()
            ->doMes($mes->month, $mes->year)
            ->selectRaw('categoria_id, sum(valor) as total')
            ->groupBy('categoria_id')
            ->with('categoria')
            ->get()
            ->map(fn ($c) => [
                'categoria_id' => (int) $c->categoria_id,
                'categoria' => $c->categoria->nome,
                'total' => (float) $c->total,
                'percentual' => $gastos > 0 ? (int) round(((float) $c->total / $gastos) * 100) : 0,
            ])
            ->sortBy([['total', 'desc'], ['categoria', 'asc']])
            ->values()
            ->all();

        return [
            'ganhos' => $ganhos,
            'gastos' => $gastos,
            'saldo' => $ganhos - $gastos,
            'a_pagar' => $aPagar,
            'gastos_por_categoria' => $gastosPorCategoria,
        ];
    }

    /**
     * Soma das parcelas pendentes de gasto futuro que vencem no mês.
     */
    private function getAPagarDoMes(User $user, Carbon $mes): float
    {
        return (float) Parcela::whereHas('movimentacao', function ($query) use ($user) {
            $query->doUsuario($user->id)->gastosFuturos();
        })
            ->where('pago', false)
            ->whereMonth('data_vencimento', $mes->month)
            ->whereYear('data_vencimento', $mes->year)
            ->sum('valor');
    }

    /**
     * Calcula as estatísticas financeiras do mês.
     *
     * @param  User  $user  Usuário autenticado.
     * @param  Carbon  $now  Data atual para referência do mês/ano.
     * @param  array  $totais  Totais do mês atual (getTotaisDoMes).
     * @return array Lista de estatísticas formatada para o frontend.
     */
    private function getStats(User $user, Carbon $now, array $totais): array
    {
        $proximoMes = $now->copy()->addMonth();
        $aPagarProximoMes = $this->getAPagarDoMes($user, $proximoMes);
        $saldo = $totais['saldo'];

        return [
            ['title' => 'Saldo Atual', 'value' => $saldo, 'icon' => 'wallet', 'color' => $saldo >= 0 ? 'text-emerald-600' : 'text-rose-600', 'description' => 'Saldo do mês'],
            ['title' => 'Ganhos (Mês)', 'value' => $totais['ganhos'], 'icon' => 'trendingUp', 'color' => 'text-emerald-600', 'description' => 'Total recebido'],
            ['title' => 'Gastos (Mês)', 'value' => $totais['gastos'], 'icon' => 'trendingDown', 'color' => 'text-rose-600', 'description' => 'Total gasto'],
            ['title' => 'A Pagar (Mês Atual)', 'value' => $totais['a_pagar'], 'icon' => 'calendar', 'color' => 'text-rose-600', 'description' => 'Vencimentos de '.$now->translatedFormat('F')],
            ['title' => 'A Pagar (Próximo Mês)', 'value' => $aPagarProximoMes, 'icon' => 'calendar', 'color' => 'text-rose-600', 'description' => 'Vencimentos de '.$proximoMes->translatedFormat('F')],
        ];
    }

    /**
     * Busca as transações mais recentes.
     *
     * @param  User  $user  Usuário autenticado.
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
                    'status' => $isGanho ? 'Recebido' : 'Pago',
                ];
            })
            ->toArray();
    }

    /**
     * Gera um resumo de gastos por categoria no mês atual.
     *
     * @param  array  $totais  Totais do mês atual (getTotaisDoMes).
     * @return array Resumo de gastos por categoria com porcentagem.
     */
    private function getCategoriesSummary(array $totais): array
    {
        return collect($totais['gastos_por_categoria'])
            ->map(fn (array $c) => [
                'name' => $c['categoria'],
                'value' => $c['total'],
                'percentage' => $c['percentual'],
                'color' => 'bg-slate-500',
            ])
            ->toArray();
    }
}
