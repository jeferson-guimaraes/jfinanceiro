<?php

namespace Tests\Feature\Movimentacoes;

use App\Enums\TipoMovimentacaoEnum;
use App\Models\Categoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MovimentacaoTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Categoria $categoriaGanho;
    private Categoria $categoriaGasto;
    private Categoria $categoriaGastoFuturo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->categoriaGanho = Categoria::factory()->create([
            'user_id' => $this->user->id,
            'tipo' => TipoMovimentacaoEnum::GANHO->value,
        ]);

        $this->categoriaGasto = Categoria::factory()->create([
            'user_id' => $this->user->id,
            'tipo' => TipoMovimentacaoEnum::GASTO->value,
        ]);

        $this->categoriaGastoFuturo = Categoria::factory()->create([
            'user_id' => $this->user->id,
            'tipo' => TipoMovimentacaoEnum::GASTO_FUTURO->value,
        ]);
    }

    public function test_cria_movimentacao_do_tipo_ganho_com_sucesso(): void
    {
        $movimentacao = [
            'descricao' => 'Aluguel',
            'valor' => 1500,
            'data_movimentacao' => '2026-01-05',
            'categoria_id' => $this->categoriaGanho->id,
            'tipo' => TipoMovimentacaoEnum::GANHO->value,
        ];

        $response = $this->actingAs($this->user)->post(route('movimentacoes.store'), $movimentacao);

        $response->assertRedirect(route('movimentacoes.create', ['tipo' => TipoMovimentacaoEnum::GANHO->value]));
        $response->assertSessionHas('success', 'Movimentação criada com sucesso!');

        $this->assertDatabaseHas('movimentacoes', [
            'user_id' => $this->user->id,
            'descricao' => 'Aluguel',
            'valor' => 1500,
            'data' => '2026-01-05',
            'categoria_id' => $this->categoriaGanho->id,
            'tipo' => TipoMovimentacaoEnum::GANHO->value,
        ]);
    }

    public function test_cria_movimentacao_do_tipo_gasto_com_sucesso(): void
    {
        $movimentacao = [
            'descricao' => 'Aluguel',
            'valor' => 1500,
            'data_movimentacao' => '2026-01-05',
            'categoria_id' => $this->categoriaGasto->id,
            'tipo' => TipoMovimentacaoEnum::GASTO->value,
        ];

        $response = $this->actingAs($this->user)->post(route('movimentacoes.store'), $movimentacao);

        $response->assertRedirect(route('movimentacoes.create', ['tipo' => TipoMovimentacaoEnum::GASTO->value]));
        $response->assertSessionHas('success', 'Movimentação criada com sucesso!');

        $this->assertDatabaseHas('movimentacoes', [
            'user_id' => $this->user->id,
            'descricao' => 'Aluguel',
            'valor' => 1500,
            'data' => '2026-01-05',
            'categoria_id' => $this->categoriaGasto->id,
            'tipo' => TipoMovimentacaoEnum::GASTO->value,
        ]);
    }

    public function test_cria_movimentacao_do_tipo_gasto_futuro_com_sucesso(): void
    {
        $movimentacao = [
            'descricao' => 'Plano de saúde',
            'valor' => 500,
            'data_movimentacao' => '2026-01-10',
            'categoria_id' => $this->categoriaGastoFuturo->id,
            'tipo' => TipoMovimentacaoEnum::GASTO_FUTURO->value,
            'parcelas' => 12,
            'data_vencimento' => '2026-01-15',
            'valor_parcelas' => 41.67,
        ];

        $response = $this->actingAs($this->user)->post(route('movimentacoes.store'), $movimentacao);

        $response->assertRedirect(route('movimentacoes.create', ['tipo' => TipoMovimentacaoEnum::GASTO_FUTURO->value]));
        $response->assertSessionHas('success', 'Movimentação criada com sucesso!');

        $this->assertDatabaseHas('movimentacoes', [
            'user_id' => $this->user->id,
            'descricao' => 'Plano de saúde',
            'valor' => 500,
            'data' => '2026-01-10',
            'categoria_id' => $this->categoriaGastoFuturo->id,
            'tipo' => TipoMovimentacaoEnum::GASTO_FUTURO->value,
        ]);
    }
    public function test_nao_pode_criar_movimentacao_sem_data(): void
    {
        $movimentacao = [
            'descricao' => 'Aluguel',
            'valor' => 1500,
            'categoria_id' => $this->categoriaGanho->id,
            'tipo' => TipoMovimentacaoEnum::GANHO->value,
        ];

        $response = $this->actingAs($this->user)->post(route('movimentacoes.store'), $movimentacao);

        $response->assertSessionHasErrors('data_movimentacao');
    }

    public function test_nao_pode_criar_movimentacao_sem_descricao(): void
    {
        $movimentacao = [
            'valor' => 1500,
            'data_movimentacao' => '2026-01-05',
            'categoria_id' => $this->categoriaGanho->id,
            'tipo' => TipoMovimentacaoEnum::GANHO->value,
        ];

        $response = $this->actingAs($this->user)->post(route('movimentacoes.store'), $movimentacao);

        $response->assertSessionHasErrors('descricao');
    }

    public function test_nao_pode_criar_movimentacao_sem_valor(): void
    {
        $movimentacao = [
            'descricao' => 'Aluguel',
            'data_movimentacao' => '2026-01-05',
            'categoria_id' => $this->categoriaGanho->id,
            'tipo' => TipoMovimentacaoEnum::GANHO->value,
        ];

        $response = $this->actingAs($this->user)->post(route('movimentacoes.store'), $movimentacao);

        $response->assertSessionHasErrors('valor');
    }

    public function test_nao_pode_criar_movimentacao_sem_categoria(): void
    {
        $movimentacao = [
            'descricao' => 'Aluguel',
            'valor' => 1500,
            'data_movimentacao' => '2026-01-05',
            'tipo' => TipoMovimentacaoEnum::GANHO->value,
        ];

        $response = $this->actingAs($this->user)->post(route('movimentacoes.store'), $movimentacao);

        $response->assertSessionHasErrors('categoria_id');
    }

    public function test_nao_pode_criar_movimentacao_sem_tipo(): void
    {
        $movimentacao = [
            'descricao' => 'Aluguel',
            'valor' => 1500,
            'data_movimentacao' => '2026-01-05',
            'categoria_id' => $this->categoriaGanho->id,
        ];

        $response = $this->actingAs($this->user)->post(route('movimentacoes.store'), $movimentacao);

        $response->assertSessionHasErrors('tipo');
    }

    public function test_nao_pode_criar_movimentacao_tipo_gasto_futuro_sem_data_vencimento(): void
    {
        $movimentacao = [
            'descricao' => 'Plano de saúde',
            'valor' => 500,
            'data_movimentacao' => '2026-01-10',
            'categoria_id' => $this->categoriaGastoFuturo->id,
            'tipo' => TipoMovimentacaoEnum::GASTO_FUTURO->value,
            'parcelas' => 12,
            'valor_parcelas' => 41.67,
        ];

        $response = $this->actingAs($this->user)->post(route('movimentacoes.store'), $movimentacao);

        $response->assertSessionHasErrors('data_vencimento');
    }

    public function test_nao_pode_criar_movimentacao_tipo_gasto_futuro_sem_parcelas(): void
    {
        $movimentacao = [
            'descricao' => 'Plano de saúde',
            'valor' => 500,
            'data_movimentacao' => '2026-01-10',
            'categoria_id' => $this->categoriaGastoFuturo->id,
            'tipo' => TipoMovimentacaoEnum::GASTO_FUTURO->value,
            'data_vencimento' => '2026-01-15',
            'valor_parcelas' => 41.67,
        ];

        $response = $this->actingAs($this->user)->post(route('movimentacoes.store'), $movimentacao);

        $response->assertSessionHasErrors('parcelas');
    }

    public function test_nao_pode_criar_movimentacao_tipo_gasto_futuro_sem_valor_das_parcelas(): void
    {
        $movimentacao = [
            'descricao' => 'Plano de saúde',
            'valor' => 500,
            'data_movimentacao' => '2026-01-10',
            'categoria_id' => $this->categoriaGastoFuturo->id,
            'tipo' => TipoMovimentacaoEnum::GASTO_FUTURO->value,
            'parcelas' => 12,
            'data_vencimento' => '2026-01-15',
        ];

        $response = $this->actingAs($this->user)->post(route('movimentacoes.store'), $movimentacao);

        $response->assertSessionHasErrors('valor_parcelas');
    }

    public function test_exclui_varias_movimentacoes_com_sucesso(): void
    {
        $movimentacao1 = \App\Models\Movimentacao::factory()->create([
            'user_id' => $this->user->id
        ]);

        $movimentacao2 = \App\Models\Movimentacao::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->from(route('movimentacoes.index'))
            ->actingAs($this->user)
            ->delete(route('movimentacoes.destroyMany'), [
                'movimentacoes_ids' => [
                    $movimentacao1->id,
                    $movimentacao2->id,
                ],
            ]);

        $response->assertRedirect(route('movimentacoes.index'));

        $response->assertSessionHas(
            'success',
            'Movimentações excluídas com sucesso!'
        );

        $this->assertDatabaseMissing('movimentacoes', [
            'id' => $movimentacao1->id,
        ]);

        $this->assertDatabaseMissing('movimentacoes', [
            'id' => $movimentacao2->id,
        ]);
    }

    public function test_atualiza_movimentacao_ganho_com_sucesso(): void
    {
        $movimentacao = \App\Models\Movimentacao::factory()->create([
            'user_id' => $this->user->id,
            'categoria_id' => $this->categoriaGanho->id,
            'tipo' => TipoMovimentacaoEnum::GANHO->value,
            'valor' => 1500,
            'descricao' => 'Salário antigo',
            'data' => '2026-01-01',
        ]);

        $dadosAtualizacao = [
            'categoria_id' => $this->categoriaGanho->id,
            'data_movimentacao' => '2026-01-05',
            'descricao' => 'Salário novo',
            'valor' => 1800,
            'tipo' => TipoMovimentacaoEnum::GANHO->value,
        ];

        $response = $this->actingAs($this->user)->patch(route('movimentacoes.update', $movimentacao->id), $dadosAtualizacao);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Movimentação atualizada com sucesso!');
        
        $this->assertDatabaseHas('movimentacoes', [
            'id' => $movimentacao->id,
            'descricao' => 'Salário novo',
            'valor' => 1800,
            'data' => '2026-01-05',
        ]);
    }

    public function test_atualiza_gasto_futuro_ajusta_vencimento_inicial_em_cascata(): void
    {
        $movimentacao = \App\Models\Movimentacao::factory()->create([
            'user_id' => $this->user->id,
            'categoria_id' => $this->categoriaGastoFuturo->id,
            'tipo' => TipoMovimentacaoEnum::GASTO_FUTURO->value,
            'valor' => 300,
            'parcelas' => 3,
            'data' => '2026-01-01',
        ]);

        $p1 = \App\Models\Parcela::factory()->create(['movimentacao_id' => $movimentacao->id, 'numero' => 1, 'valor' => 100, 'data_vencimento' => '2026-01-10']);
        $p2 = \App\Models\Parcela::factory()->create(['movimentacao_id' => $movimentacao->id, 'numero' => 2, 'valor' => 100, 'data_vencimento' => '2026-02-10']);
        $p3 = \App\Models\Parcela::factory()->create(['movimentacao_id' => $movimentacao->id, 'numero' => 3, 'valor' => 100, 'data_vencimento' => '2026-03-10']);

        $dadosAtualizacao = [
            'categoria_id' => $this->categoriaGastoFuturo->id,
            'data_movimentacao' => '2026-01-01',
            'descricao' => $movimentacao->descricao,
            'valor' => 300,
            'tipo' => TipoMovimentacaoEnum::GASTO_FUTURO->value,
            'parcelas' => 3,
            'data_vencimento' => '2026-05-15',
        ];

        $response = $this->actingAs($this->user)->patch(route('movimentacoes.update', $movimentacao->id), $dadosAtualizacao);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('parcelas', ['id' => $p1->id, 'data_vencimento' => '2026-05-15']);
        $this->assertDatabaseHas('parcelas', ['id' => $p2->id, 'data_vencimento' => '2026-06-15']);
        $this->assertDatabaseHas('parcelas', ['id' => $p3->id, 'data_vencimento' => '2026-07-15']);
    }

    public function test_atualiza_gasto_futuro_aumenta_quantidade_de_parcelas(): void
    {
        $movimentacao = \App\Models\Movimentacao::factory()->create([
            'user_id' => $this->user->id,
            'categoria_id' => $this->categoriaGastoFuturo->id,
            'tipo' => TipoMovimentacaoEnum::GASTO_FUTURO->value,
            'valor' => 200,
            'parcelas' => 2,
            'data' => '2026-01-01',
        ]);

        $p1 = \App\Models\Parcela::factory()->create(['movimentacao_id' => $movimentacao->id, 'numero' => 1, 'valor' => 100, 'data_vencimento' => '2026-01-10']);
        $p2 = \App\Models\Parcela::factory()->create(['movimentacao_id' => $movimentacao->id, 'numero' => 2, 'valor' => 100, 'data_vencimento' => '2026-02-10']);

        $dadosAtualizacao = [
            'categoria_id' => $this->categoriaGastoFuturo->id,
            'data_movimentacao' => '2026-01-01',
            'descricao' => $movimentacao->descricao,
            'valor' => 200,
            'tipo' => TipoMovimentacaoEnum::GASTO_FUTURO->value,
            'parcelas' => 4,
            'data_vencimento' => '2026-01-10',
        ];

        $response = $this->actingAs($this->user)->patch(route('movimentacoes.update', $movimentacao->id), $dadosAtualizacao);

        $response->assertRedirect();
        
        $this->assertDatabaseCount('parcelas', 4);
        $this->assertDatabaseHas('parcelas', ['movimentacao_id' => $movimentacao->id, 'numero' => 3, 'valor' => 100, 'data_vencimento' => '2026-03-10']);
        $this->assertDatabaseHas('parcelas', ['movimentacao_id' => $movimentacao->id, 'numero' => 4, 'valor' => 100, 'data_vencimento' => '2026-04-10']);
        
        $this->assertDatabaseHas('movimentacoes', ['id' => $movimentacao->id, 'valor' => 400, 'parcelas' => 4]);
    }

    public function test_atualiza_gasto_futuro_diminui_quantidade_de_parcelas(): void
    {
        $movimentacao = \App\Models\Movimentacao::factory()->create([
            'user_id' => $this->user->id,
            'categoria_id' => $this->categoriaGastoFuturo->id,
            'tipo' => TipoMovimentacaoEnum::GASTO_FUTURO->value,
            'valor' => 300,
            'parcelas' => 3,
            'data' => '2026-01-01',
        ]);

        $p1 = \App\Models\Parcela::factory()->create(['movimentacao_id' => $movimentacao->id, 'numero' => 1, 'valor' => 100, 'data_vencimento' => '2026-01-10']);
        $p2 = \App\Models\Parcela::factory()->create(['movimentacao_id' => $movimentacao->id, 'numero' => 2, 'valor' => 100, 'data_vencimento' => '2026-02-10']);
        $p3 = \App\Models\Parcela::factory()->create(['movimentacao_id' => $movimentacao->id, 'numero' => 3, 'valor' => 100, 'data_vencimento' => '2026-03-10']);

        $dadosAtualizacao = [
            'categoria_id' => $this->categoriaGastoFuturo->id,
            'data_movimentacao' => '2026-01-01',
            'descricao' => $movimentacao->descricao,
            'valor' => 300,
            'tipo' => TipoMovimentacaoEnum::GASTO_FUTURO->value,
            'parcelas' => 2,
            'data_vencimento' => '2026-01-10',
        ];

        $response = $this->actingAs($this->user)->patch(route('movimentacoes.update', $movimentacao->id), $dadosAtualizacao);

        $response->assertRedirect();
        
        $this->assertDatabaseCount('parcelas', 2);
        $this->assertDatabaseMissing('parcelas', ['id' => $p3->id]);
        
        $this->assertDatabaseHas('movimentacoes', ['id' => $movimentacao->id, 'valor' => 200, 'parcelas' => 2]);
    }

    public function test_atualiza_gasto_futuro_edita_valores_individuais_de_parcelas(): void
    {
        $movimentacao = \App\Models\Movimentacao::factory()->create([
            'user_id' => $this->user->id,
            'categoria_id' => $this->categoriaGastoFuturo->id,
            'tipo' => TipoMovimentacaoEnum::GASTO_FUTURO->value,
            'valor' => 200,
            'parcelas' => 2,
            'data' => '2026-01-01',
        ]);

        $p1 = \App\Models\Parcela::factory()->create(['movimentacao_id' => $movimentacao->id, 'numero' => 1, 'valor' => 100, 'data_vencimento' => '2026-01-10']);
        $p2 = \App\Models\Parcela::factory()->create(['movimentacao_id' => $movimentacao->id, 'numero' => 2, 'valor' => 100, 'data_vencimento' => '2026-02-10']);

        $dadosAtualizacao = [
            'categoria_id' => $this->categoriaGastoFuturo->id,
            'data_movimentacao' => '2026-01-01',
            'descricao' => $movimentacao->descricao,
            'valor' => 200,
            'tipo' => TipoMovimentacaoEnum::GASTO_FUTURO->value,
            'parcelas' => 2,
            'data_vencimento' => '2026-01-10',
            'parcelas_editadas' => [
                [
                    'id' => $p1->id,
                    'valor' => 150.50,
                    'data_vencimento' => '2026-01-12',
                ],
                [
                    'id' => $p2->id,
                    'valor' => 180.00,
                    'data_vencimento' => '2026-02-15',
                ],
            ]
        ];

        $response = $this->actingAs($this->user)->patch(route('movimentacoes.update', $movimentacao->id), $dadosAtualizacao);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('parcelas', ['id' => $p1->id, 'valor' => 150.50, 'data_vencimento' => '2026-01-12']);
        $this->assertDatabaseHas('parcelas', ['id' => $p2->id, 'valor' => 180.00, 'data_vencimento' => '2026-02-15']);
        
        $this->assertDatabaseHas('movimentacoes', ['id' => $movimentacao->id, 'valor' => 330.50]);
    }

    public function test_atualiza_tipo_muda_de_gasto_futuro_para_gasto_deleta_parcelas(): void
    {
        $movimentacao = \App\Models\Movimentacao::factory()->create([
            'user_id' => $this->user->id,
            'categoria_id' => $this->categoriaGastoFuturo->id,
            'tipo' => TipoMovimentacaoEnum::GASTO_FUTURO->value,
            'valor' => 200,
            'parcelas' => 2,
            'data' => '2026-01-01',
        ]);

        $p1 = \App\Models\Parcela::factory()->create(['movimentacao_id' => $movimentacao->id, 'numero' => 1, 'valor' => 100, 'data_vencimento' => '2026-01-10']);
        $p2 = \App\Models\Parcela::factory()->create(['movimentacao_id' => $movimentacao->id, 'numero' => 2, 'valor' => 100, 'data_vencimento' => '2026-02-10']);

        $dadosAtualizacao = [
            'categoria_id' => $this->categoriaGasto->id,
            'data_movimentacao' => '2026-01-01',
            'descricao' => 'Gasto comum agora',
            'valor' => 200,
            'tipo' => TipoMovimentacaoEnum::GASTO->value,
        ];

        $response = $this->actingAs($this->user)->patch(route('movimentacoes.update', $movimentacao->id), $dadosAtualizacao);

        $response->assertRedirect();
        
        $this->assertDatabaseCount('parcelas', 0);
        $this->assertDatabaseHas('movimentacoes', [
            'id' => $movimentacao->id,
            'tipo' => TipoMovimentacaoEnum::GASTO->value,
            'parcelas' => 1,
        ]);
    }
}
