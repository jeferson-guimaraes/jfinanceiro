<?php

namespace App\Http\Requests\Usuarios;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class AlterarSenhaUsuarioRequest extends FormRequest
{
    /**
     * Só administradores alteram senhas por aqui, e só de usuários padrão. A senha de outros
     * administradores, inclusive a própria, continua sendo trocada nas configurações da conta.
     */
    public function authorize(): bool
    {
        $usuario = $this->route('usuario');

        return $this->user()?->isAdmin() === true
            && $usuario instanceof User
            && $usuario->role === RoleEnum::USER;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            'senha_administrador' => ['required', 'string', 'current_password'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.required' => 'Informe a nova senha.',
            'password.min' => 'A nova senha precisa ter pelo menos :min caracteres.',
            'password.confirmed' => 'A confirmação não confere com a nova senha.',
            'senha_administrador.required' => 'Informe a sua senha para confirmar a alteração.',
            'senha_administrador.current_password' => 'A sua senha está incorreta.',
        ];
    }
}
