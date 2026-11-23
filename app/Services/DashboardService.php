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
     * Dados do resumo de um período (seção do topo do dashboard).
     *
     * @param  User  $user  Usuário autenticado.
     * @param  Carbon  $inicio  Primeiro dia do período.
     * @param  Carbon  $fim  Último dia do período.
     * @return array{periodo: array{data_inicio: string, data_fim: string}, stats: array, recentTransactions: array, categoriesSummary: array}
     */
    public function getDadosDoPeriodo(User $user, Carbon $inicio, Carbon $fim): array
    {
        $totais = $this->getTotais($user, $inicio, $fim);

        return [
            'periodo' => [
                'data_inicio' => $inicio->toDateString(),
                'data_fim' => $fim->toDateString(),
            ],
            'stats' => $this->getStatsDoPeriodo($totais),
            'recentTransactions' => $this->getRecentTransactions($user, $inicio, $fim),
            'categoriesSummary' => $this->getCategoriesSummary($totais['gastos_por_categoria']),
        ];
    }

    /**
     * Visão geral do usuário desde o cadastro (seção permanente do dashboard).
     *
     * @param  User  $user  Usuário autenticado.
     * @return array{desde: string, stats: array, categoriesSummary: array}
     */
    public function getVisaoGeral(User $user): array
    {
        $totais = $this->getTotais($user);
        $proximoMes = Carbon::now()->addMonthNoOverflow();
        $aPagarProximoMes = $this->getAPagarDoPeriodo($user, $proximoMes->copy()->startOfMonth(), $proximoMes->copy()->endOfMonth());

        return [
            'desde' => $user->created_at->format('d/m/Y'),
            'stats' => [
                $this->stat('Saldo Acumulado', $totais['saldo'], 'wallet', $totais['saldo'] >= 0 ? 'text-emerald-600' : 'text-rose-600', 'Ganhos menos gastos'),
                $this->stat('Ganhos Totais', $totais['ganhos'], 'trendingUp', 'text-emerald-600', 'Total recebido'),
                $this->stat('Gastos Totais', $totais['gastos'], 'trendingDown', 'text-rose-600', 'Total gasto'),
                $this->stat('Total a Pagar', $totais['a_pagar'], 'creditCard', 'text-rose-600', 'Todas as parcelas pendentes'),
                $this->stat('A Pagar (Próximo Mês)', $aPagarProximoMes, 'calendar', 'text-rose-600', 'Vencimentos de '.$proximoMes->translatedFormat('F')),
            ],
            'categoriesSummary' => $this->getCategoriesSummary($totais['gastos_por_categoria']),
        ];
    }

    /**
     * Totais financeiros de um mês, em números crus (usado pela API do assistente).
     *
     * @param  User  $user  Dono dos dados.
     * @param  Carbon  $mes  Qualquer data dentro do mês desejado.
     * @return array{ganhos: float, gastos: float, saldo: float, a_pagar: float, gastos_por_categoria: list<array{categoria_id: int, categoria: string, total: float, percentual: int}>}
     */
    public function getTotaisDoMes(User $user, Carbon $mes): array
    {
        return $this->getTotais($user, $mes->copy()->startOfMonth(), $mes->copy()->endOfMonth());
    }

    /**
     * Totais financeiros em números crus: do período informado ou, sem período, de todo o histórico.
     *
     * "A pagar" considera as parcelas pendentes de gasto futuro que vencem no período (sem período, todas as
     * pendentes). Gastos por categoria seguem a ordem de maior total.
     *
     * @param  User  $user  Dono dos dados.
     * @param  Carbon|null  $inicio  Primeiro dia do período.
     * @param  Carbon|null  $fim  Último dia do período.
     * @return array{ganhos: float, gastos: float, saldo: float, a_pagar: float, gastos_por_categoria: list<array{categoria_id: int, categoria: string, total: float, percentual: int}>}
     */
    public function getTotais(User $user, ?Carbon $inicio = null, ?Carbon $fim = null): array
    {
        $query = fn () => Movimentacao::doUsuario($user->id)
            ->when($inicio && $fim, fn ($q) => $q->doPeriodo($inicio, $fim));

        $ganhos = (float) $query()->ganhos()->sum('valor');
        $gastos = (float) $query()->gastos()->sum('valor');

        $gastosPorCategoria = $query()
            ->gastos()
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
            'a_pagar' => $this->getAPagarDoPeriodo($user, $inicio, $fim),
            'gastos_por_categoria' => $gastosPorCategoria,
        ];
    }

    /**
     * Soma das parcelas pendentes de gasto futuro que vencem no período (ou todas, sem período).
     */
    private function getAPagarDoPeriodo(User $user, ?Carbon $inicio = null, ?Carbon $fim = null): float
    {
        return (float) Parcela::whereHas('movimentacao', function ($query) use ($user) {
            $query->doUsuario($user->id)->gastosFuturos();
        })
            ->where('pago', false)
            ->when($inicio && $fim, fn ($query) => $query
                ->whereDate('data_vencimento', '>=', $inicio->toDateString())
                ->whereDate('data_vencimento', '<=', $fim->toDateString()))
            ->sum('valor');
    }

    /**
     * Estatísticas do período formatadas para o frontend.
     *
     * @param  array{ganhos: float, gastos: float, saldo: float, a_pagar: float}  $totais
     * @return list<array{title: string, value: float, icon: string, color: string, description: string}>
     */
    private function getStatsDoPeriodo(array $totais): array
    {
        $saldo = $totais['saldo'];

        return [
            $this->stat('Saldo do Período', $saldo, 'wallet', $saldo >= 0 ? 'text-emerald-600' : 'text-rose-600', 'Ganhos menos gastos'),
            $this->stat('Ganhos', $totais['ganhos'], 'trendingUp', 'text-emerald-600', 'Total recebido'),
            $this->stat('Gastos', $totais['gastos'], 'trendingDown', 'text-rose-600', 'Total gasto'),
            $this->stat('A Pagar', $totais['a_pagar'], 'calendar', 'text-rose-600', 'Parcelas pendentes que vencem no período'),
        ];
    }

    /**
     * @return array{title: string, value: float, icon: string, color: string, description: string}
     */
    private function stat(string $title, float $value, string $icon, string $color, string $description): array
    {
        return compact('title', 'value', 'icon', 'color', 'description');
    }

    /**
     * Últimas movimentações (ganhos e gastos) do período.
     *
     * @return list<array{id: int, description: string, category: string, value: float, type: string, date: string, status: string}>
     */
    private function getRecentTransactions(User $user, Carbon $inicio, Carbon $fim): array
    {
        return Movimentacao::doUsuario($user->id)
            ->with('categoria')
            ->whereIn('tipo', [TipoMovimentacaoEnum::GANHO, TipoMovimentacaoEnum::GASTO])
            ->doPeriodo($inicio, $fim)
            ->orderBy('data', 'desc')
            ->orderBy('id', 'desc')
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
            ->all();
    }

    /**
     * Gastos por categoria formatados para o frontend.
     *
     * @param  list<array{categoria: string, total: float, percentual: int}>  $gastosPorCategoria
     * @return list<array{name: string, value: float, percentage: int, color: string}>
     */
    private function getCategoriesSummary(array $gastosPorCategoria): array
    {
        return collect($gastosPorCategoria)
            ->map(fn (array $c) => [
                'name' => $c['categoria'],
                'value' => $c['total'],
                'percentage' => $c['percentual'],
                'color' => 'bg-slate-500',
            ])
            ->all();
    }
}
