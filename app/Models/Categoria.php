<?php

namespace App\Models;

use App\Enums\TipoMovimentacaoEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Categoria extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'tipo',
        'user_id',
    ];

    protected $casts = [
        'tipo' => TipoMovimentacaoEnum::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
