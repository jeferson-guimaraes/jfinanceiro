<?php

namespace App\Http\Controllers;

use App\Enums\TipoMovimentacaoEnum;
use App\Http\Requests\Movimentacoes\Categorias\CategoriasRequest;
use App\Models\Categoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CategoriaController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $search = $request->input('search');
        $tipo = $request->input('tipo', 'ganho');
        $per_page = $request->input('per_page', 10);

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
            ->paginate($per_page)
            ->withQueryString();

        return Inertia::render('movimentacoes/categorias/Index', [
            'listaCategorias' => $categorias,
            'filters' => ['search' => $search, 'tipo' => $tipo, 'per_page' => $per_page],
        ]);
    }

    /**
     * Renderiza a view para criar uma nova categoria.
     */
    public function create(): Response
    {
        return Inertia::render('movimentacoes/categorias/Create', [
            'tipo' => request('tipo'),
        ]);
    }

    /**
     * Armazena uma nova categoria no banco de dados.
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

        return redirect()->route('movimentacoes.categorias.index')->with('success', 'Categoria criada com sucesso!');
    }

    /**
     * Renderiza a view para editar uma categoria.
     */
    public function edit(Categoria $categoria): Response
    {
        return Inertia::render('movimentacoes/categorias/Edit', [
            'categoria' => $categoria,
        ]);
    }

    /**
     * Atualiza uma categoria no banco de dados.
     */
    public function update(CategoriasRequest $request, Categoria $categoria): RedirectResponse
    {
        $categoria->update($request->validated());

        return redirect()
            ->route('movimentacoes.categorias.index')
            ->with('success', 'Categoria atualizada com sucesso!');
    }

    /**
     * Remove uma categoria do banco de dados.
     */
    public function destroy(Categoria $categoria): RedirectResponse
    {
        // If the category has associated movimentacoes, reassign them to the appropriate default category
        if ($categoria->movimentacoes()->exists()) {
            $categoria->movimentacoes()->update([
                'categoria_id' => DB::raw(
                    "
                        CASE tipo
                            WHEN '".TipoMovimentacaoEnum::GASTO->value."' THEN 1
                            WHEN '".TipoMovimentacaoEnum::GANHO->value."' THEN 2
                            WHEN '".TipoMovimentacaoEnum::GASTO_FUTURO->value."' THEN 3
                            ELSE categoria_id
                        END
                    "
                ),
            ]);
        }

        $categoria->delete();

        return redirect()
            ->back()
            ->with('success', 'Categoria excluída com sucesso!');
    }

    /**
     * Remove multiplas categorias do banco de dados.
     */
    public function destroyMany(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'categorias_ids' => 'required|array',
            'categorias_ids.*' => 'exists:categorias,id',
        ]);

        $categorias = Categoria::whereIn('id', $validated['categorias_ids'])->get();

        foreach ($categorias as $categoria) {
            if ($categoria->movimentacoes()->exists()) {
                $categoria->movimentacoes()->update([
                    'categoria_id' => DB::raw("
                        CASE tipo
                            WHEN '".TipoMovimentacaoEnum::GASTO->value."' THEN 1
                            WHEN '".TipoMovimentacaoEnum::GANHO->value."' THEN 2
                            WHEN '".TipoMovimentacaoEnum::GASTO_FUTURO->value."' THEN 3
                            ELSE categoria_id
                        END
                    "),
                ]);
            }
        }

        Categoria::whereIn('id', $validated['categorias_ids'])->delete();

        return redirect()
            ->back()
            ->with('success', 'Categorias excluídas com sucesso!');
    }
}
