<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMovimentacaoRequest;
use App\Models\Movimentacao;
use Illuminate\Http\RedirectResponse;
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
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('movimentacoes/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMovimentacaoRequest $request): RedirectResponse
    {
        $request->user()->movimentacoes()->create($request->validated());

        return redirect()->route('dashboard');
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