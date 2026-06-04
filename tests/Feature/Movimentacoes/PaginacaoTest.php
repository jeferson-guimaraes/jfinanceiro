<?php

namespace Tests\Feature\Movimentacoes;

use App\Models\Movimentacao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginacaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_listagem_de_movimentacoes_usa_padrao_de_50_itens_por_pagina(): void
    {
        $user = User::factory()->create();
        Movimentacao::factory()->count(60)->create([
            'user_id' => $user->id,
            'tipo' => \App\Enums\TipoMovimentacaoEnum::GASTO->value,
            'data' => now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user)->get(route('movimentacoes.index'));

        $response->assertStatus(200);
        
        $response->assertInertia(fn ($page) => $page
            ->where('movimentacoes.per_page', 50)
            ->has('movimentacoes.data', 50)
        );
    }
}
