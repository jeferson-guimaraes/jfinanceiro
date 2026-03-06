<?php

namespace App\Http\Controllers;

use App\Enums\TipoMovimentacaoEnum;
use App\Http\Requests\Movimentacoes\StoreMovimentacaoRequest;
use App\Http\Requests\Movimentacoes\UpdateMovimentacaoRequest;
use App\Models\Categoria;
use App\Models\Movimentacao;
use App\Models\Parcela;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class MovimentacaoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $dataInicio = request('data_inicio');
        $dataFim = request('data_fim');
        $mes = request('mes') ?? Carbon::now()->month;
        $ano = request('ano') ?? Carbon::now()->year;

        $ganhosGastos = Movimentacao::where('user_id', Auth::id())
            ->whereIn('tipo', [TipoMovimentacaoEnum::GANHO->value, TipoMovimentacaoEnum::GASTO->value])
            ->with('categoria')
            ->orderBy('data', 'desc');

        if ($dataInicio) {
            $ganhosGastos->where('data', '>=', $dataInicio);
        }

        if ($dataFim) {
            $ganhosGastos->where('data', '<=', $dataFim);
        }

        $ganhosGastos = $ganhosGastos->get();

        $parcelasFuturas = collect();

        if ($mes && $ano) {
            $primeiroDia = Carbon::createFromDate($ano, $mes, 1)->startOfMonth();
            $ultimoDia = $primeiroDia->copy()->endOfMonth();

            $parcelasFuturas = Parcela::whereHas('movimentacao', function ($query) {
                $query->where('user_id', Auth::id());
            })
                ->whereBetween('data_vencimento', [$primeiroDia, $ultimoDia])
                ->with(['movimentacao' => function ($query) {
                    $query->with('categoria')
                        ->withCount([
                            'parcelas as parcelas_pagas' => function ($q) {
                                $q->where('pago', true);
                            }
                        ]);
                }])
                ->orderBy('data_vencimento')
                ->get();
        }

        return Inertia::render('movimentacoes/Index', [
            'movimentacoes' => $ganhosGastos,
            'parcelasFuturas' => $parcelasFuturas,
        ]);
    }

    /**
     * Renderiza a view para criar uma nova movimentação.
     *
     * Retorna uma view com as categorias filtradas por tipo.
     */
    public function create(): Response
    {
        $user = Auth::user();

        $categorias = Categoria::where('user_id', $user->id)
            ->orWhere('user_id', null)
            ->orderBy('nome')
            ->get();

        $categoriasGanhos = $categorias->where('tipo', TipoMovimentacaoEnum::GANHO->value)->values();
        $categoriasGastos = $categorias->where('tipo', TipoMovimentacaoEnum::GASTO->value)->values();
        $categoriasGastosFuturos = $categorias->where('tipo', TipoMovimentacaoEnum::GASTO_FUTURO->value)->values();

        return Inertia::render('movimentacoes/Create', [
            'categoriasGanhos' => $categoriasGanhos,
            'categoriasGastos' => $categoriasGastos,
            'categoriasGastosFuturos' => $categoriasGastosFuturos,
        ]);
    }

    /**
     * Salva uma nova movimentação no banco de dados.
     *
     * A requisição deve ser uma instância de StoreMovimentacaoRequest.
     */
    public function store(StoreMovimentacaoRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['data'] = $validated['data_movimentacao'];
        unset($validated['data_movimentacao']);

        $movimentacao = $request->user()->movimentacoes()->create($validated);

        if ($validated['tipo'] === TipoMovimentacaoEnum::GASTO_FUTURO->value) {
            $idMovimentacao = $movimentacao->latest('id')->first()->id;
            $dataVencimentoParcela = $validated['data_vencimento'];

            for ($parcela = 1; $parcela <= $validated['parcelas']; $parcela++) {
                Parcela::query()->create([
                    'movimentacao_id' => $idMovimentacao,
                    'numero' => $parcela,
                    'valor' => $validated['valor_parcelas'],
                    'data_vencimento' => $dataVencimentoParcela,
                ]);
                $dataVencimentoParcela = Carbon::parse($dataVencimentoParcela)->addDays(30);
            }
        }

        return redirect()->route('movimentacoes.create')->with('success', 'Movimentação criada com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Movimentacao $movimentacao)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Movimentacao $movimentacao): Response
    {
        $user = Auth::user();

        if ($movimentacao->user_id !== $user->id) {
            abort(403);
        }

        $categorias = Categoria::where('user_id', $user->id)
            ->orWhere('user_id', null)
            ->orderBy('nome')
            ->get();

        $categoriasGanhos = $categorias->where('tipo', TipoMovimentacaoEnum::GANHO->value)->values();
        $categoriasGastos = $categorias->where('tipo', TipoMovimentacaoEnum::GASTO->value)->values();
        $categoriasGastosFuturos = $categorias->where('tipo', TipoMovimentacaoEnum::GASTO_FUTURO->value)->values();

        return Inertia::render('movimentacoes/Edit', [
            'movimentacao' => $movimentacao->load('categoria'),
            'categoriasGanhos' => $categoriasGanhos,
            'categoriasGastos' => $categoriasGastos,
            'categoriasGastosFuturos' => $categoriasGastosFuturos,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMovimentacaoRequest $request, Movimentacao $movimentacao): RedirectResponse
    {
        if ($movimentacao->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validated();
        $validated['data'] = $validated['data_movimentacao'];
        unset($validated['data_movimentacao']);

        $movimentacao->update($validated);

        return redirect()->route('movimentacoes.index')->with('success', 'Movimentação atualizada com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Movimentacao $movimentacao): RedirectResponse
    {
        if ($movimentacao->user_id !== Auth::id()) {
            abort(403);
        }

        $movimentacao->delete();

        return redirect()->route('movimentacoes.index')->with('success', 'Movimentação excluída com sucesso!');
    }
}
