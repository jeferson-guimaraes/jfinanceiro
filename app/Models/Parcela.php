<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Parcela extends Model
{
    protected $fillable = [
        'movimentacao_id',
        'numero',
        'valor',
        'data_vencimento',
        'data_pagamento',
        'pago'
    ];

    protected $casts = [
        'data_vencimento' => 'date:Y-m-d',
        'data_pagamento' => 'date:Y-m-d',
        'pago' => 'boolean'
    ];

    public function movimentacao(): BelongsTo
    {
        return $this->belongsTo(Movimentacao::class);
    }
}
