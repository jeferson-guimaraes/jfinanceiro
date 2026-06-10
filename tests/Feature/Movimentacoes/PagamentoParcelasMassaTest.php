<?php

namespace Tests\Feature\Movimentacoes;

use App\Models\Movimentacao;
use App\Models\Parcela;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagamentoParcelasMassaTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_pagamento_em_massa_com_sucesso()
    {
        $mov1 = Movimentacao::factory()->for($this->user)->create(['tipo' => 'gasto futuro', 'parcelas' => 3]);
        Parcela::factory()->create(['movimentacao_id' => $mov1->id, 'valor' => 100, 'pago' => false]);

        $mov2 = Movimentacao::factory()->for($this->user)->create(['tipo' => 'gasto futuro', 'parcelas' => 3]);
        Parcela::factory()->create(['movimentacao_id' => $mov2->id, 'valor' => 200, 'pago' => false]);

        $response = $this->actingAs($this->user)->post(route('movimentacoes.pagarMassa'), [
            'movimentacao_ids' => [$mov1->id, $mov2->id],
            'quantidade_parcelas' => 1,
            'data_pagamento' => now()->format('Y-m-d'),
        ]);

        $response->assertStatus(302);
        // Agora salva apenas a descrição original quando não informada a personalizada
        $this->assertDatabaseHas('movimentacoes', ['descricao' => $mov1->descricao, 'tipo' => 'gasto']);
        $this->assertDatabaseHas('movimentacoes', ['descricao' => $mov2->descricao, 'tipo' => 'gasto']);
    }

    public function test_pagamento_em_massa_com_descricao_personalizada()
    {
        $mov1 = Movimentacao::factory()->for($this->user)->create(['tipo' => 'gasto futuro', 'parcelas' => 3]);
        Parcela::factory()->create(['movimentacao_id' => $mov1->id, 'valor' => 100, 'pago' => false]);

        $mov2 = Movimentacao::factory()->for($this->user)->create(['tipo' => 'gasto futuro', 'parcelas' => 3]);
        Parcela::factory()->create(['movimentacao_id' => $mov2->id, 'valor' => 200, 'pago' => false]);

        $descricaoPersonalizada = 'Pagamento em Massa Ref 01';

        $response = $this->actingAs($this->user)->post(route('movimentacoes.pagarMassa'), [
            'movimentacao_ids' => [$mov1->id, $mov2->id],
            'quantidade_parcelas' => 1,
            'data_pagamento' => now()->format('Y-m-d'),
            'descricao' => $descricaoPersonalizada,
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('movimentacoes', ['descricao' => "{$descricaoPersonalizada} - {$mov1->descricao}", 'tipo' => 'gasto']);
        $this->assertDatabaseHas('movimentacoes', ['descricao' => "{$descricaoPersonalizada} - {$mov2->descricao}", 'tipo' => 'gasto']);
    }

    public function test_validacao_de_massa_com_ids_invalidos()
    {
        $response = $this->actingAs($this->user)->post(route('movimentacoes.pagarMassa'), [
            'movimentacao_ids' => [9999], // ID inexistente
            'quantidade_parcelas' => 1,
            'data_pagamento' => now()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors(['movimentacao_ids.0']);
    }

    public function test_validacao_de_massa_com_dados_obrigatorios_ausentes()
    {
        $response = $this->actingAs($this->user)->post(route('movimentacoes.pagarMassa'), [
            'movimentacao_ids' => [],
            // falta quantidade e data
        ]);

        $response->assertSessionHasErrors(['movimentacao_ids', 'quantidade_parcelas', 'data_pagamento']);
    }
}
