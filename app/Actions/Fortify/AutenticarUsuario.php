<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Autentica o usuário no login, aceitando também as senhas do sistema antigo.
 *
 * Senhas legadas foram gravadas como MD5 (32 caracteres hexadecimais). Quando a senha digitada
 * confere com o MD5, ela é regravada em Bcrypt sem que o usuário perceba. Um hash legado nunca
 * passa por Hash::check(), que no Laravel recusa algoritmos diferentes de Bcrypt com uma exceção;
 * por isso uma senha errada de usuário antigo resulta em "credenciais inválidas", e não em erro.
 */
class AutenticarUsuario
{
    public function __invoke(Request $request): ?User
    {
        $usuario = User::query()->where('email', $request->input('email'))->first();
        $senha = $request->input('password');

        if ($usuario === null || ! is_string($senha) || ! is_string($usuario->password)) {
            return null;
        }

        if ($this->isSenhaLegada($usuario->password)) {
            return $this->autenticarComSenhaLegada($usuario, $senha);
        }

        if (Hash::info($usuario->password)['algoName'] !== 'bcrypt') {
            Log::warning('Login recusado: senha do usuário em formato não reconhecido.', ['usuario_id' => $usuario->id]);

            return null;
        }

        return Hash::check($senha, $usuario->password) ? $usuario : null;
    }

    private function isSenhaLegada(string $hash): bool
    {
        return preg_match('/^[a-f0-9]{32}$/i', $hash) === 1;
    }

    /**
     * Confere a senha pelo MD5 em tempo constante e, se conferir, migra para Bcrypt.
     */
    private function autenticarComSenhaLegada(User $usuario, string $senha): ?User
    {
        if (! hash_equals(strtolower($usuario->password), md5($senha))) {
            return null;
        }

        $usuario->forceFill(['password' => $senha])->save();

        return $usuario;
    }
}
