<?php

namespace App\Http\Requests\Api;

use App\Enums\TipoMovimentacaoEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Lançamento pelo assistente. Na compra parcelada o servidor monta as parcelas a partir do valor total, da
 * quantidade e do 1º vencimento (obrigatório).
 */
class LancarMovimentacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        $gastoFuturo = $this->input('tipo') === TipoMovimentacaoEnum::GASTO_FUTURO->value;

        return [
            'tipo' => ['required', Rule::enum(TipoMovimentacaoEnum::class)],
            'valor' => array_merge(['required', 'numeric', 'min:0.01', 'max:99999999.99'], $gastoFuturo && is_numeric($this->input('parcelas'))
                ? ['min:'.number_format(max((int) $this->input('parcelas'), 1) * 0.01, 2, '.', '')]
                : []),
            'data' => ['required', 'date_format:Y-m-d', 'after_or_equal:2000-01-01'],
            'descricao' => ['required', 'string', 'max:255'],
            'categoria_id' => ['nullable', 'integer'],
            'categoria' => ['nullable', 'string', 'max:150'],
            'parcelas' => $gastoFuturo ? ['required', 'integer', 'min:1', 'max:120'] : ['prohibited'],
            'primeiro_vencimento' => $gastoFuturo
                ? ['required', 'date_format:Y-m-d', 'after_or_equal:data']
                : ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'primeiro_vencimento.required' => 'Informe a data de vencimento da primeira parcela.',
            'primeiro_vencimento.after_or_equal' => 'O vencimento da primeira parcela não pode ser anterior à data da compra.',
            'parcelas.required' => 'Informe em quantas parcelas foi a compra.',
            'parcelas.prohibited' => 'Parcelas só existem em gasto futuro (compra parcelada).',
            'primeiro_vencimento.prohibited' => 'Vencimento só existe em gasto futuro (compra parcelada).',
            'data.after_or_equal' => 'Informe uma data a partir do ano 2000.',
            'valor.min' => 'O valor precisa ser de pelo menos 1 centavo por parcela.',
        ];
    }
}
