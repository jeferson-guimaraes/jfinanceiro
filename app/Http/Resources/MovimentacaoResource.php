<?php

namespace App\Http\Resources;

use App\Models\Movimentacao;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Movimentacao
 */
class MovimentacaoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo->value,
            'descricao' => $this->descricao,
            'valor' => (float) $this->valor,
            'data' => $this->data?->format('Y-m-d') ?? (string) $this->data,
            'categoria' => $this->whenLoaded('categoria', fn () => $this->categoria?->nome),
            // `parcelas` é também uma coluna (quantidade); a relação é lida explicitamente.
            'quantidade_parcelas' => $this->getAttribute('parcelas'),
            'parcelas' => $this->when($this->relationLoaded('parcelas'), fn () => $this->getRelation('parcelas')
                ->sortBy('numero')
                ->values()
                ->map(fn ($parcela) => [
                    'numero' => $parcela->numero,
                    'valor' => (float) $parcela->valor,
                    'data_vencimento' => $parcela->data_vencimento?->format('Y-m-d'),
                    'pago' => (bool) $parcela->pago,
                ])),
        ];
    }
}
