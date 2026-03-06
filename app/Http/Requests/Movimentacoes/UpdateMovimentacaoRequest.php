<?php

namespace App\Http\Requests\Movimentacoes;

use App\Enums\TipoMovimentacaoEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMovimentacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'categoria_id' => ['required', 'exists:categorias,id'],
            'data_movimentacao' => ['required', 'date'],
            'descricao' => ['required', 'string', 'max:255'],
            'valor' => ['required', 'numeric', 'min:0.01'],
            'tipo' => ['required', Rule::in(array_column(TipoMovimentacaoEnum::cases(), 'value'))],
        ];
    }
}
