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

    public function test_pagamento_em_massa_rejeita_movimentacao_de_outro_usuario(): void
    {
        $outroUsuario = User::factory()->create();
        $movimentacaoAlheia = Movimentacao::factory()->for($outroUsuario)->create(['tipo' => 'gasto futuro', 'parcelas' => 1]);
        $parcelaAlheia = Parcela::factory()->create(['movimentacao_id' => $movimentacaoAlheia->id, 'numero' => 1, 'pago' => false]);

        $response = $this->actingAs($this->user)->post(route('movimentacoes.pagarMassa'), [
            'movimentacao_ids' => [$movimentacaoAlheia->id],
            'quantidade_parcelas' => 1,
            'data_pagamento' => now()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors(['movimentacao_ids.0']);
        $this->assertFalse($parcelaAlheia->fresh()->pago);
        $this->assertDatabaseCount('movimentacoes', 1);
    }

    public function test_pagamento_em_massa_com_movimentacao_excluida_nao_paga_nenhuma(): void
    {
        $valida = Movimentacao::factory()->for($this->user)->create(['tipo' => 'gasto futuro', 'parcelas' => 1]);
        $parcelaValida = Parcela::factory()->create(['movimentacao_id' => $valida->id, 'numero' => 1, 'pago' => false]);

        $excluida = Movimentacao::factory()->for($this->user)->create(['tipo' => 'gasto futuro', 'parcelas' => 1]);
        $idExcluido = $excluida->id;
        $excluida->delete();

        $response = $this->actingAs($this->user)->post(route('movimentacoes.pagarMassa'), [
            'movimentacao_ids' => [$valida->id, $idExcluido],
            'quantidade_parcelas' => 1,
            'data_pagamento' => now()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors(['movimentacao_ids.1']);
        $this->assertFalse($parcelaValida->fresh()->pago);
        $this->assertDatabaseMissing('movimentacoes', ['tipo' => 'gasto']);
    }

    public function test_pagamento_em_massa_paga_a_proxima_parcela_pendente(): void
    {
        $movimentacao = Movimentacao::factory()->for($this->user)->create(['tipo' => 'gasto futuro', 'parcelas' => 3]);
        $primeira = Parcela::factory()->create(['movimentacao_id' => $movimentacao->id, 'numero' => 1, 'valor' => 100, 'pago' => true]);
        $segunda = Parcela::factory()->create(['movimentacao_id' => $movimentacao->id, 'numero' => 2, 'valor' => 110, 'pago' => false]);
        $terceira = Parcela::factory()->create(['movimentacao_id' => $movimentacao->id, 'numero' => 3, 'valor' => 120, 'pago' => false]);

        $response = $this->actingAs($this->user)->post(route('movimentacoes.pagarMassa'), [
            'movimentacao_ids' => [$movimentacao->id],
            'quantidade_parcelas' => 1,
            'data_pagamento' => now()->format('Y-m-d'),
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertTrue($primeira->fresh()->pago);
        $this->assertTrue($segunda->fresh()->pago);
        $this->assertFalse($terceira->fresh()->pago);
        $this->assertDatabaseHas('movimentacoes', ['tipo' => 'gasto', 'valor' => 110]);
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
