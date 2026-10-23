<?php

namespace App\Http\Requests\Api;

use App\Enums\TipoMovimentacaoEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filtros das consultas do assistente (categorias, parcelas pendentes e resumo do mês).
 */
class ConsultaAssistenteRequest extends FormRequest
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
            'tipo' => ['sometimes', Rule::enum(TipoMovimentacaoEnum::class)],
            'ate' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:2000-01-01'],
            'mes' => ['sometimes', 'date_format:Y-m'],
        ];
    }
}
