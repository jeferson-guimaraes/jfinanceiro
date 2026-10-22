<?php

namespace App\Http\Controllers;

use App\Http\Requests\Usuarios\AlterarSenhaUsuarioRequest;
use App\Models\User;
use App\Services\UsuarioService;
use Illuminate\Http\RedirectResponse;

class UsuarioSenhaController extends Controller
{
    /**
     * Altera a senha de um usuário padrão a pedido de um administrador.
     */
    public function __invoke(AlterarSenhaUsuarioRequest $request, User $usuario, UsuarioService $usuarioService): RedirectResponse
    {
        $usuarioService->alterarSenhaPeloAdministrador($usuario, $request->validated('password'), $request->user());

        return back()->with('success', "Senha de {$usuario->name} alterada. O usuário foi desconectado de todos os dispositivos.");
    }
}
