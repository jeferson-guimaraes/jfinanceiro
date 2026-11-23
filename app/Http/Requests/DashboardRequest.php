<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Período do resumo do dashboard. Sem datas, o dashboard usa o mês atual.
 */
class DashboardRequest extends FormRequest
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
            'data_inicio' => ['nullable', 'required_with:data_fim', 'date_format:Y-m-d', 'after_or_equal:2000-01-01'],
            'data_fim' => ['nullable', 'required_with:data_inicio', 'date_format:Y-m-d', 'after_or_equal:data_inicio'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'data_inicio.required_with' => 'Informe a data inicial do período.',
            'data_inicio.date_format' => 'A data inicial é inválida.',
            'data_inicio.after_or_equal' => 'A data inicial deve ser a partir de 01/01/2000.',
            'data_fim.required_with' => 'Informe a data final do período.',
            'data_fim.date_format' => 'A data final é inválida.',
            'data_fim.after_or_equal' => 'A data final deve ser igual ou posterior à data inicial.',
        ];
    }

    /**
     * Início do período pedido ou o primeiro dia do mês atual.
     */
    public function dataInicio(): Carbon
    {
        return $this->filled('data_inicio')
            ? Carbon::parse($this->validated('data_inicio'))->startOfDay()
            : Carbon::now()->startOfMonth();
    }

    /**
     * Fim do período pedido ou o último dia do mês atual.
     */
    public function dataFim(): Carbon
    {
        return $this->filled('data_fim')
            ? Carbon::parse($this->validated('data_fim'))->endOfDay()
            : Carbon::now()->endOfMonth();
    }
}
