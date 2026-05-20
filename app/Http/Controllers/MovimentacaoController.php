<?php

namespace App\Http\Controllers;

use App\Http\Requests\Movimentacoes\PagarParcelaRequest;
use App\Http\Requests\Movimentacoes\PagarParcelasMassaRequest;
use App\Http\Requests\Movimentacoes\StoreMovimentacaoRequest;
use App\Http\Requests\Movimentacoes\UpdateMovimentacaoRequest;
use App\Models\Movimentacao;
use App\Services\MovimentacaoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class MovimentacaoController extends Controller
{
    /**
     * Exibe a listagem de movimentações.
     *
     * @param MovimentacaoService $movimentacaoService
     * @return Response
     */
    public function index(MovimentacaoService $movimentacaoService)
    {
        $movimentacoes = $movimentacaoService->getMovimentacoes();

        return Inertia::render('movimentacoes/Index', $movimentacoes);
    }

    /**
     * Exibe o formulário para criação de uma nova movimentação.
     *
     * @param MovimentacaoService $movimentacaoService
     * @return Response
     */
    public function create(MovimentacaoService $movimentacaoService): Response
    {
        $data = $movimentacaoService->getCreateMovimentacaoData();

        return Inertia::render('movimentacoes/Create', array_merge($data, [
            'tipo' => request('tipo'),
        ]));
    }

    /**
     * Armazena uma nova movimentação no banco de dados.
     *
     * @param StoreMovimentacaoRequest $request
     * @param MovimentacaoService $movimentacaoService
     * @return RedirectResponse
     */
    public function store(StoreMovimentacaoRequest $request, MovimentacaoService $movimentacaoService): RedirectResponse
    {
        $movimentacaoService->storeMovimentacao($request->validated());

        return redirect()->route('movimentacoes.create')
            ->with('tipo', $request->validated('tipo'))->with('success', 'Movimentação criada com sucesso!');
    }

    /**
     * Exibe a movimentação especificada.
     *
     * @param Movimentacao $movimentacao
     * @return void
     */
    public function show(Movimentacao $movimentacao)
    {
        //
    }

    /**
     * Exibe o formulário para edição da movimentação especificada.
     *
     * @param Movimentacao $movimentacao
     * @param MovimentacaoService $movimentacaoService
     * @return Response
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
     * Atualiza a movimentação especificada no banco de dados.
     *
     * @param UpdateMovimentacaoRequest $request
     * @param Movimentacao $movimentacao
     * @param MovimentacaoService $movimentacaoService
     * @return RedirectResponse
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
     * Remove a movimentação especificada do banco de dados.
     *
     * @param Movimentacao $movimentacao
     * @param MovimentacaoService $movimentacaoService
     * @return RedirectResponse
     */
    public function destroy(Movimentacao $movimentacao, MovimentacaoService $movimentacaoService): RedirectResponse
    {
        if ($movimentacao->user_id !== Auth::id()) {
            abort(403);
        }

        $movimentacaoService->destroyMovimentacao($movimentacao);

        return redirect()->route('movimentacoes.index')->with('success', 'Movimentação excluída com sucesso!');
    }

    /**
     * Remove várias movimentações do banco de dados.
     *
     * @param Request $request
     * @param MovimentacaoService $movimentacaoService
     * @return RedirectResponse
     */
    public function destroyMany(Request $request, MovimentacaoService $movimentacaoService): RedirectResponse
    {
        $validated = $request->validate([
            'movimentacoes_ids' => 'required|array',
            'movimentacoes_ids.*' => 'exists:movimentacoes,id',
        ]);

        $movimentacaoService->destroyManyMovimentacoes($validated['movimentacoes_ids']);

        return redirect()->route('movimentacoes.index')->with('success', 'Movimentações excluídas com sucesso!');
    }

    /**
     * Processa o pagamento de parcelas de uma movimentação.
     *
     * @param PagarParcelaRequest $request
     * @param Movimentacao $movimentacao
     * @param MovimentacaoService $movimentacaoService
     * @return RedirectResponse
     */
    public function pagarParcelas(PagarParcelaRequest $request, Movimentacao $movimentacao, MovimentacaoService $movimentacaoService): RedirectResponse
    {
        if ($movimentacao->user_id !== Auth::id()) {
            abort(403);
        }

        $movimentacaoService->pagarParcelas($movimentacao, $request->validated());

        return redirect()->back()->with('success', 'Pagamento realizado com sucesso!');
    }

    /**
     * Processa o pagamento de parcelas de várias movimentações.
     *
     * @param PagarParcelasMassaRequest $request
     * @param MovimentacaoService $movimentacaoService
     * @return RedirectResponse
     */
    public function pagarParcelasMassa(PagarParcelasMassaRequest $request, MovimentacaoService $movimentacaoService): RedirectResponse
    {
        $movimentacaoService->pagarParcelasMassa($request->validated('movimentacao_ids'), $request->validated());

        return redirect()->back()->with('success', 'Pagamentos realizados com sucesso!');
    }
}
