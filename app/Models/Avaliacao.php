<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Avaliação do sistema enviada por um visitante ou usuário.
 *
 * Só é exibida no site quando o autor permitiu a publicação e um administrador aprovou.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $nome
 * @property int $nota
 * @property string $mensagem
 * @property bool $publicacao_permitida
 * @property \Illuminate\Support\Carbon|null $aprovada_em
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $user
 */
class Avaliacao extends Model
{
    /** @use HasFactory<\Database\Factories\AvaliacaoFactory> */
    use HasFactory;

    /**
     * Quantidade mínima de avaliações publicáveis para a seção aparecer na página inicial.
     */
    public const MINIMO_PARA_LANDING = 2;

    /**
     * Quantidade máxima de avaliações exibidas na página inicial.
     */
    public const LIMITE_NA_LANDING = 6;

    protected $table = 'avaliacoes';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'nome',
        'nota',
        'mensagem',
        'publicacao_permitida',
        'aprovada_em',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nota' => 'integer',
            'publicacao_permitida' => 'boolean',
            'aprovada_em' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Avaliações que podem aparecer no site: autor permitiu e um administrador aprovou.
     *
     * @param  Builder<Avaliacao>  $query
     */
    public function scopePublicaveis(Builder $query): void
    {
        $query->where('publicacao_permitida', true)
            ->whereNotNull('aprovada_em')
            ->latest();
    }

    /**
     * Avaliações mais recentes para a página inicial, já no formato público, sem o nome completo
     * nem a conta do autor. Devolve uma lista vazia enquanto não houver o mínimo publicável.
     *
     * @return list<array{id: int, nome: string, nota: int, mensagem: string}>
     */
    public static function paraLanding(): array
    {
        $avaliacoes = static::query()->publicaveis()->limit(self::LIMITE_NA_LANDING)->get();

        if ($avaliacoes->count() < self::MINIMO_PARA_LANDING) {
            return [];
        }

        return $avaliacoes
            ->map(fn (Avaliacao $avaliacao) => [
                'id' => $avaliacao->id,
                'nome' => $avaliacao->nomeAbreviado(),
                'nota' => $avaliacao->nota,
                'mensagem' => $avaliacao->mensagem,
            ])
            ->values()
            ->all();
    }

    /**
     * Nome para exibição pública: primeiro nome e inicial do último sobrenome ("Maria S.").
     */
    public function nomeAbreviado(): string
    {
        $partes = preg_split('/\s+/u', trim($this->nome), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($partes) < 2) {
            return $partes[0] ?? '';
        }

        return $partes[0].' '.mb_strtoupper(mb_substr(end($partes), 0, 1)).'.';
    }

    public function isAprovada(): bool
    {
        return $this->aprovada_em !== null;
    }
}
