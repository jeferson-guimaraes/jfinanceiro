<?php

namespace App\Http\Controllers;

use App\Http\Requests\Movimentacoes\StoreMovimentacaoRequest;
use App\Http\Requests\Movimentacoes\UpdateMovimentacaoRequest;
use App\Models\Movimentacao;
use App\Services\MovimentacaoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class MovimentacaoController extends Controller
{
    /**
     * Índice da página de movimentações.
     *
     * Esta função recebe uma instância do serviço MovimentacaoService e busca as movimentações.
     * Em seguida, retorna uma renderização da página 'movimentacoes/Index' com as movimentações.
     *
     * @param MovimentacaoService $movimentacaoService A instância do serviço MovimentacaoService.
     * @return \Inertia\Response A renderização da página 'movimentacoes/Index' com as movimentações.
     */
    public function index(MovimentacaoService $movimentacaoService)
    {
        $movimentacoes = $movimentacaoService->getMovimentacoes();

        return Inertia::render('movimentacoes/Index', $movimentacoes);
    }

    /**
     * Renderiza a view para criar uma nova movimentação.
     *
     * Retorna uma view com as categorias filtradas por tipo.
     */
    public function create(MovimentacaoService $movimentacaoService): Response
    {
        $data = $movimentacaoService->getCreateMovimentacaoData();

        return Inertia::render('movimentacoes/Create', array_merge($data, [
            'tipo' => request('tipo'),
        ]));
    }

    /**
     * Salva uma nova movimentação no banco de dados.
     *
     * A requisição deve ser uma instância de StoreMovimentacaoRequest.
     */
    public function store(StoreMovimentacaoRequest $request, MovimentacaoService $movimentacaoService): RedirectResponse
    {
        $movimentacaoService->storeMovimentacao($request->validated());

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
    public function edit(Movimentacao $movimentacao, MovimentacaoService $movimentacaoService): Response
    {
        if ($movimentacao->user_id !== Auth::id()) {
            abort(403);
        }

        $data = $movimentacaoService->getCreateMovimentacaoData();

        return Inertia::render('movimentacoes/Edit', array_merge([
            'movimentacao' => $movimentacao->load('categoria'),
        ], $data));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMovimentacaoRequest $request, Movimentacao $movimentacao, MovimentacaoService $movimentacaoService): RedirectResponse
    {
        if ($movimentacao->user_id !== Auth::id()) {
            abort(403);
        }

        $movimentacaoService->updateMovimentacao($movimentacao, $request->validated());

        return redirect()->route('movimentacoes.index')->with('success', 'Movimentação atualizada com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Movimentacao $movimentacao, MovimentacaoService $movimentacaoService): RedirectResponse
    {
        if ($movimentacao->user_id !== Auth::id()) {
            abort(403);
        }

        $movimentacaoService->destroyMovimentacao($movimentacao);

        return redirect()->route('movimentacoes.index')->with('success', 'Movimentação excluída com sucesso!');
    }
}
