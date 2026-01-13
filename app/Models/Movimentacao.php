<?php

namespace App\Models;

use App\Enums\TipoMovimentacaoEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Movimentacao extends Model
{
    use HasFactory;

    protected $table = 'movimentacoes';

    protected $fillable = [
        'categoria_id',
        'cliente_id',
        'data',
        'descricao',
        'valor',
        'tipo',
        'parcelas',
    ];

    protected $casts = [
        'tipo' => TipoMovimentacaoEnum::class,
        'data' => 'date',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cliente_id');
    }

    public function parcelas(): HasMany
    {
        return $this->hasMany(Parcela::class);
    }

}