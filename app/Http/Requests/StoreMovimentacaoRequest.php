<?php

namespace App\Http\Requests;

use App\Enums\TipoMovimentacaoEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMovimentacaoRequest extends FormRequest
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
            'categoria_id' => ['required', 'exists:categorias,id'],
            'data' => ['required', 'date'],
            'descricao' => ['required', 'string', 'max:255'],
            'valor' => ['required', 'numeric'],
            'tipo' => ['required', Rule::in(array_column(TipoMovimentacaoEnum::cases(), 'value'))],
            'parcelas' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
