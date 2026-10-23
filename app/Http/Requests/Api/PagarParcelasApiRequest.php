<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Pagamento de parcelas pelo assistente: por padrão 1 parcela, hoje (horário de Brasília), pelo valor da(s) parcela(s).
 */
class PagarParcelasApiRequest extends FormRequest
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
        return [
            'quantidade_parcelas' => ['sometimes', 'integer', 'min:1', 'max:120'],
            'data_pagamento' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:2000-01-01'],
            'valor_total_pago' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'descricao' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Dados no formato do MovimentacaoService::pagarParcelas, com os padrões aplicados.
     *
     * @return array<string, mixed>
     */
    public function dadosDoPagamento(): array
    {
        return array_filter([
            'quantidade_parcelas' => (int) $this->validated('quantidade_parcelas', 1),
            'data_pagamento' => $this->validated('data_pagamento', now('America/Sao_Paulo')->toDateString()),
            'valor_total_pago' => $this->validated('valor_total_pago'),
            'descricao' => $this->validated('descricao'),
        ], fn ($valor) => $valor !== null);
    }
}
