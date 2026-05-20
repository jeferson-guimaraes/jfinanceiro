<?php

namespace App\Http\Requests\Movimentacoes;

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
            'data_movimentacao' => ['required', 'date'],
            'descricao' => ['required', 'string', 'max:255'],
            'valor' => ['required', 'numeric', 'min:0.01'],
            'tipo' => ['required', Rule::in(array_column(TipoMovimentacaoEnum::cases(), 'value'))],
            'parcelas' => [
                Rule::requiredIf($this->input('tipo') === TipoMovimentacaoEnum::GASTO_FUTURO->value),
                'nullable',
                'integer',
                'min:1'
            ],
            'valor_parcelas' => [
                Rule::requiredIf($this->input('tipo') === TipoMovimentacaoEnum::GASTO_FUTURO->value),
                'numeric',
                'min:0.01'
            ],
            'data_vencimento' => [
                Rule::requiredIf($this->input('tipo') === TipoMovimentacaoEnum::GASTO_FUTURO->value),
                'nullable',
                'date',
                'after_or_equal:data_movimentacao'
            ],
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
            'data_vencimento.after_or_equal' => 'A data de vencimento da primeira parcela não pode ser anterior à data da compra.',
        ];
    }
}
