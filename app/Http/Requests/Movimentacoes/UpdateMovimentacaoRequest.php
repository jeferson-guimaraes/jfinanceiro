<?php

namespace App\Http\Requests\Movimentacoes;

use App\Enums\TipoMovimentacaoEnum;
use App\Rules\CategoriaDisponivel;
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
            'categoria_id' => ['required', 'integer', new CategoriaDisponivel($this->input('tipo'))],
            'data_movimentacao' => ['required', 'date', 'after_or_equal:2000-01-01'],
            'descricao' => ['required', 'string', 'max:255'],
            'valor' => ['required', 'numeric', 'min:0.01'],
            'tipo' => ['required', Rule::in(array_column(TipoMovimentacaoEnum::cases(), 'value'))],
            'parcelas' => ['nullable', 'integer', 'min:1'],
            'data_vencimento' => [Rule::requiredIf($this->tipo === TipoMovimentacaoEnum::GASTO_FUTURO->value), 'nullable', 'date'],
            'parcelas_editadas' => ['nullable', 'array'],
            'parcelas_editadas.*.id' => [
                'required',
                Rule::exists('parcelas', 'id')->where('movimentacao_id', $this->route('movimentacao')?->id),
            ],
            'parcelas_editadas.*.valor' => ['required', 'numeric', 'min:0.01'],
            'parcelas_editadas.*.data_vencimento' => ['required', 'date', 'after_or_equal:2000-01-01'],
        ];
    }
}
