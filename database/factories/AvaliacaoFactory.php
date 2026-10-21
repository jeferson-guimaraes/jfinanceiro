<?php

namespace Database\Factories;

use App\Models\Avaliacao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Avaliacao>
 */
class AvaliacaoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'nome' => $this->faker->firstName().' '.$this->faker->lastName(),
            'nota' => $this->faker->numberBetween(1, 5),
            'mensagem' => $this->faker->sentence(12),
            'publicacao_permitida' => false,
            'aprovada_em' => null,
        ];
    }

    /**
     * Avaliação com publicação permitida e já aprovada por um administrador.
     */
    public function publicavel(): static
    {
        return $this->state(fn () => [
            'publicacao_permitida' => true,
            'aprovada_em' => now(),
        ]);
    }

    /**
     * Avaliação com publicação permitida aguardando aprovação.
     */
    public function pendente(): static
    {
        return $this->state(fn () => [
            'publicacao_permitida' => true,
            'aprovada_em' => null,
        ]);
    }
}
