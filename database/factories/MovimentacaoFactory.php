<?php

namespace Database\Factories;

use App\Enums\TipoMovimentacaoEnum;
use App\Models\Categoria;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Movimentacao>
 */
class MovimentacaoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'categoria_id' => Categoria::factory(),
            'user_id' => User::factory(),
            'data' => $this->faker->date(),
            'descricao' => $this->faker->sentence(),
            'valor' => $this->faker->randomFloat(2, 10, 1000),
            'tipo' => TipoMovimentacaoEnum::GANHO->value,
        ];
    }
}
