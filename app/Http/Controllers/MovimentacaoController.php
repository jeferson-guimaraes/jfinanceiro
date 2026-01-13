<?php

namespace App\Http\Controllers;

use App\Enums\TipoMovimentacaoEnum;
use App\Http\Requests\Movimentacoes\StoreMovimentacaoRequest;
use App\Models\Categoria;
use App\Models\Movimentacao;
use App\Models\Parcela;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class MovimentacaoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }


    /**
     * Renderiza a view para criar uma nova movimentação.
     *
     * Retorna uma view com as categorias filtradas por tipo.
     *
     * @return Response
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
     *
     * @param StoreMovimentacaoRequest $request
     * @return RedirectResponse
     */
    public function store(StoreMovimentacaoRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['data'] = $validated['data_movimentacao'];
        unset($validated['data_movimentacao']);

        $movimentacao =$request->user()->movimentacoes()->create($validated);

        if ($validated['tipo'] === TipoMovimentacaoEnum::GASTO_FUTURO->value) {
            $idMovimentacao = $movimentacao->latest('id')->first()->id;
            $dataVencimentoParcela = $validated['data_vencimento'];

            for($parcela = 1; $parcela <= $validated['parcelas']; $parcela++) {
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
    public function edit(Movimentacao $movimentacao)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    // public function update(Request $request, Movimentacao $movimentacao)
    // {
    //     
    // }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Movimentacao $movimentacao)
    {
        //
    }
}
