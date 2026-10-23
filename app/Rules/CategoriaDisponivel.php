<?php

namespace App\Rules;

use App\Enums\TipoMovimentacaoEnum;
use App\Models\Categoria;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

/**
 * Valida se a categoria pode ser usada pelo usuário autenticado na movimentação.
 *
 * A categoria precisa existir, ser do próprio usuário ou global (`user_id` nulo) e, quando o tipo da
 * movimentação é informado, ter o mesmo tipo. Exceção: um GASTO aceita categoria de GASTO FUTURO, porque o
 * pagamento de parcela lança o gasto com a categoria da compra. Impede usar categoria de outro usuário.
 */
class CategoriaDisponivel implements ValidationRule
{
    public const MENSAGEM = 'Selecione uma categoria válida para este tipo de movimentação.';

    public function __construct(
        public ?string $tipo = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $disponivel = Categoria::query()
            ->whereKey($value)
            ->where(fn ($query) => $query->where('user_id', Auth::id())->orWhereNull('user_id'))
            ->when($this->tipo, fn ($query) => $query->whereIn('tipo', $this->tiposAceitos()))
            ->exists();

        if (! $disponivel) {
            $fail(self::MENSAGEM);
        }
    }

    /**
     * @return list<string>
     */
    private function tiposAceitos(): array
    {
        return $this->tipo === TipoMovimentacaoEnum::GASTO->value
            ? [TipoMovimentacaoEnum::GASTO->value, TipoMovimentacaoEnum::GASTO_FUTURO->value]
            : [$this->tipo];
    }
}
