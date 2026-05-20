<?php

namespace Database\Factories;

use App\Models\Movimentacao;
use App\Models\Parcela;
use Illuminate\Database\Eloquent\Factories\Factory;

class ParcelaFactory extends Factory
{
    protected $model = Parcela::class;

    public function definition(): array
    {
        return [
            'movimentacao_id' => Movimentacao::factory(),
            'numero' => $this->faker->numberBetween(1, 12),
            'valor' => $this->faker->randomFloat(2, 10, 1000),
            'data_vencimento' => $this->faker->date(),
            'pago' => false,
        ];
    }
}
