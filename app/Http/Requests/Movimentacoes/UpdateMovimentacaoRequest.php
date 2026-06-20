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
            'parcelas' => ['nullable', 'integer', 'min:1'],
            'data_vencimento' => [Rule::requiredIf($this->tipo === TipoMovimentacaoEnum::GASTO_FUTURO->value), 'nullable', 'date'],
            'parcelas_editadas' => ['nullable', 'array'],
            'parcelas_editadas.*.id' => ['required', 'exists:parcelas,id'],
            'parcelas_editadas.*.valor' => ['required', 'numeric', 'min:0.01'],
            'parcelas_editadas.*.data_vencimento' => ['required', 'date'],
        ];
    }
}
