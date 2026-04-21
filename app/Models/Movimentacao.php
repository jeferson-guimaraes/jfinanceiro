<?php

namespace App\Models;

use App\Enums\TipoMovimentacaoEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $categoria_id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon $data
 * @property string $descricao
 * @property numeric $valor
 * @property TipoMovimentacaoEnum $tipo
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Models\Parcela> $parcelas
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Categoria $categoria
 * @property-read int|null $parcelas_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Movimentacao newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Movimentacao newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Movimentacao query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Movimentacao whereCategoriaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Movimentacao whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Movimentacao whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Movimentacao whereDescricao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Movimentacao whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Movimentacao whereParcelas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Movimentacao whereTipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Movimentacao whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Movimentacao whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Movimentacao whereValor($value)
 * @mixin \Eloquent
 */
class Movimentacao extends Model
{
    use HasFactory;

    protected $table = 'movimentacoes';

    protected $fillable = [
        'categoria_id',
        'user_id',
        'data',
        'descricao',
        'valor',
        'tipo',
        'parcelas',
    ];

    protected $casts = [
        'tipo' => TipoMovimentacaoEnum::class,
        'data' => 'date:Y-m-d',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parcelas(): HasMany
    {
        return $this->hasMany(Parcela::class);
    }

}