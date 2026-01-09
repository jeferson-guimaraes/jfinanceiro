<?php

namespace App\Http\Controllers;

use App\Http\Requests\Movimentacoes\Categorias\CategoriasRequest;
use App\Models\Categoria;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CategoriaController extends Controller
{
    /**
     * Renderiza a view para criar uma nova categoria.
     *
     * @return Response
     */
    public function create(): Response
    {
        return Inertia::render('movimentacoes/categorias/Create');
    }

    /**
     * Armazena uma nova categoria no banco de dados.
     *
     * @param CategoriasRequest $request
     * @return RedirectResponse
     */
    public function store(CategoriasRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = $request->user();
        $data['user_id'] = $user->id;

        Categoria::create($data);

        return redirect()->route('movimentacoes.categorias.create')->with('success', 'Categoria criada com sucesso!');
    }
}
