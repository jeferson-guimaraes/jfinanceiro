<?php

namespace App\Http\Requests\Avaliacoes;

use App\Rules\RecaptchaValido;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator as ValidatorFactory;
use Illuminate\Validation\Validator;

class StoreAvaliacaoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'min:3', 'max:120', 'regex:/^\S+(\s+\S+)+$/u'],
            'nota' => ['required', 'integer', 'between:1,5'],
            'mensagem' => ['required', 'string', 'min:10', 'max:1000'],
            'publicacao_permitida' => ['required', 'boolean'],
            'site' => ['prohibited'],
        ];
    }

    /**
     * Verifica o reCAPTCHA só depois que o restante do formulário passou na validação, para
     * não consultar o Google em envios que já seriam recusados.
     *
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $verificacao = ValidatorFactory::make(
                    ['recaptcha_token' => $this->input('recaptcha_token')],
                    ['recaptcha_token' => [new RecaptchaValido('avaliacao', $this->ip())]],
                );

                if ($verificacao->fails()) {
                    $validator->errors()->add('recaptcha_token', RecaptchaValido::MENSAGEM);
                }
            },
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
            'nome.required' => 'Informe seu nome completo.',
            'nome.min' => 'Informe seu nome completo.',
            'nome.max' => 'O nome pode ter no máximo 120 caracteres.',
            'nome.regex' => 'Informe nome e sobrenome.',
            'nota.required' => 'Escolha uma nota de 1 a 5 estrelas.',
            'nota.integer' => 'Escolha uma nota de 1 a 5 estrelas.',
            'nota.between' => 'Escolha uma nota de 1 a 5 estrelas.',
            'mensagem.required' => 'Conte um pouco sobre sua experiência.',
            'mensagem.min' => 'A mensagem precisa ter pelo menos 10 caracteres.',
            'mensagem.max' => 'A mensagem pode ter no máximo 1000 caracteres.',
            'publicacao_permitida.required' => 'Informe se permite publicar sua avaliação.',
            'publicacao_permitida.boolean' => 'Informe se permite publicar sua avaliação.',
            'site.prohibited' => RecaptchaValido::MENSAGEM,
        ];
    }
}
