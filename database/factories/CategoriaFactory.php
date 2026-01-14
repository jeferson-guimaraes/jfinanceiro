<?php

namespace Database\Factories;

use App\Enums\TipoMovimentacaoEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Categoria>
 */
class CategoriaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => $this->faker->word,
            'tipo' => $this->faker->randomElement(TipoMovimentacaoEnum::class),
            'user_id' => User::factory(),
        ];
    }
}
