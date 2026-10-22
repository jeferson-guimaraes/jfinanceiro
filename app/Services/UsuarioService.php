<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UsuarioService
{
    /**
     * Define uma nova senha para o usuário, a pedido de um administrador.
     *
     * O usuário é desconectado de todos os dispositivos: as sessões salvas no banco são
     * apagadas e o token de "lembrar de mim" é trocado, para que a senha antiga deixe de
     * dar acesso imediatamente. A alteração fica registrada no log para auditoria.
     */
    public function alterarSenhaPeloAdministrador(User $usuario, string $novaSenha, User $administrador): void
    {
        $usuario->forceFill([
            'password' => $novaSenha,
            'remember_token' => Str::random(60),
        ])->save();

        $this->encerrarSessoes($usuario);

        Log::info('Senha de usuário alterada por administrador.', [
            'usuario_id' => $usuario->id,
            'administrador_id' => $administrador->id,
        ]);
    }

    /**
     * Apaga as sessões do usuário quando elas ficam no banco. Com outros drivers não há como
     * localizar as sessões por usuário; nesse caso a troca do remember_token ainda invalida o
     * "lembrar de mim".
     */
    private function encerrarSessoes(User $usuario): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::connection(config('session.connection'))
            ->table(config('session.table', 'sessions'))
            ->where('user_id', $usuario->id)
            ->delete();
    }
}
