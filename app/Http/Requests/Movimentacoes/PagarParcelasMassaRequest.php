<?php

namespace App\Http\Requests\Movimentacoes;

use Illuminate\Foundation\Http\FormRequest;

class PagarParcelasMassaRequest extends FormRequest
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
            'movimentacao_ids' => ['required', 'array'],
            'movimentacao_ids.*' => ['exists:movimentacoes,id'],
            'quantidade_parcelas' => ['required', 'integer', 'min:1'],
            'data_pagamento' => ['required', 'date'],
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
            'movimentacao_ids.required' => 'Nenhuma movimentação selecionada.',
            'movimentacao_ids.array' => 'Os IDs das movimentações devem ser um array.',
            'movimentacao_ids.*.exists' => 'Uma ou mais movimentações selecionadas não existem.',
            'quantidade_parcelas.required' => 'A quantidade de parcelas é obrigatória.',
            'quantidade_parcelas.integer' => 'A quantidade de parcelas deve ser um número inteiro.',
            'quantidade_parcelas.min' => 'A quantidade de parcelas deve ser pelo menos 1.',
            'data_pagamento.required' => 'A data do pagamento é obrigatória.',
            'data_pagamento.date' => 'A data do pagamento deve ser uma data válida.',
        ];
    }
}
