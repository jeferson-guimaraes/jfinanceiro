<?php

namespace App\Http\Controllers;

use App\Http\Requests\Movimentacoes\Categorias\CategoriasRequest;
use App\Models\Categoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoriaController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $search = $request->input('search');
        $tipo = $request->input('tipo', 'ganho');

        $categorias = Categoria::query()
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereNull('user_id');
            })
            ->when($search, function ($query, $search) {
                $query->where('nome', 'like', "%{$search}%");
            })
            ->where('tipo', $tipo)
            ->orderBy('nome')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('movimentacoes/categorias/Index', [
            'listaCategorias' => $categorias,
            'filters' => ['search' => $search, 'tipo' => $tipo],
        ]);
    }

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
     * @return RedirectResponse|JsonResponse
     */
    public function store(CategoriasRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();

        $user = $request->user();
        $data['user_id'] = $user->id;

        Categoria::create($data);

        if (isset($data['origem']) && $data['origem'] === 'modal') {
            return redirect()->back();
        }

        return redirect()->route('movimentacoes.categorias.create')->with('success', 'Categoria criada com sucesso!');
    }
}
