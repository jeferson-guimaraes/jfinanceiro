<?php

namespace Tests\Feature\Movimentacoes;

use App\Models\Movimentacao;
use App\Models\Parcela;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagamentoParcelaTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_pagar_parcela_com_sucesso()
    {
        $movimentacao = Movimentacao::factory()->for($this->user)->create(['tipo' => 'gasto futuro', 'parcelas' => 3]);
        Parcela::factory()->count(3)->create(['movimentacao_id' => $movimentacao->id]);

        $response = $this->actingAs($this->user)->post(route('movimentacoes.pagar', $movimentacao), [
            'quantidade_parcelas' => 1,
            'data_pagamento' => now()->format('Y-m-d'),
            'valor_total_pago' => 50.00,
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('parcelas', ['movimentacao_id' => $movimentacao->id, 'pago' => true]);
        // Valida que sem descrição personalizada, usa a descrição original
        $this->assertDatabaseHas('movimentacoes', [
            'descricao' => $movimentacao->descricao,
            'tipo' => 'gasto'
        ]);
    }

    public function test_pagar_parcela_com_descricao_personalizada()
    {
        $movimentacao = Movimentacao::factory()->for($this->user)->create(['tipo' => 'gasto futuro', 'parcelas' => 3]);
        Parcela::factory()->count(3)->create(['movimentacao_id' => $movimentacao->id]);

        $descricaoPersonalizada = 'Pagamento com Bônus';

        $response = $this->actingAs($this->user)->post(route('movimentacoes.pagar', $movimentacao), [
            'quantidade_parcelas' => 1,
            'data_pagamento' => now()->format('Y-m-d'),
            'valor_total_pago' => 50.00,
            'descricao' => $descricaoPersonalizada,
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('movimentacoes', [
            'descricao' => "{$descricaoPersonalizada}: {$movimentacao->descricao}",
            'tipo' => 'gasto'
        ]);
    }

    public function test_nao_pode_pagar_parcela_de_outro_usuario()
    {
        $outroUsuario = User::factory()->create();
        $movimentacao = Movimentacao::factory()->for($outroUsuario)->create();

        $response = $this->actingAs($this->user)->post(route('movimentacoes.pagar', $movimentacao), [
            'quantidade_parcelas' => 1,
            'data_pagamento' => now()->format('Y-m-d'),
            'valor_total_pago' => 50.00,
        ]);

        $response->assertStatus(403);
    }

    public function test_validacao_de_dados_invalidos()
    {
        $movimentacao = Movimentacao::factory()->for($this->user)->create();

        $response = $this->actingAs($this->user)->post(route('movimentacoes.pagar', $movimentacao), [
            'quantidade_parcelas' => -1, // Inválido
            'data_pagamento' => 'data-invalida',
        ]);

        $response->assertSessionHasErrors(['quantidade_parcelas', 'data_pagamento']);
    }
}
