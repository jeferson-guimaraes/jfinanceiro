<?php

namespace App\Http\Resources;

use App\Models\Parcela;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Parcela
 */
class ParcelaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero' => $this->numero,
            'total_parcelas' => $this->whenLoaded('movimentacao', fn () => $this->movimentacao->parcelas),
            'valor' => (float) $this->valor,
            'data_vencimento' => $this->data_vencimento?->format('Y-m-d'),
            'pago' => (bool) $this->pago,
            'data_pagamento' => $this->data_pagamento?->format('Y-m-d'),
            'movimentacao' => $this->whenLoaded('movimentacao', fn () => [
                'id' => $this->movimentacao->id,
                'descricao' => $this->movimentacao->descricao,
                'categoria' => $this->movimentacao->categoria?->nome,
                'valor_total' => (float) $this->movimentacao->valor,
            ]),
        ];
    }
}
