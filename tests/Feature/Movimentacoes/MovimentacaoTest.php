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

        $response->assertRedirect(route('movimentacoes.create'));
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

        $response->assertRedirect(route('movimentacoes.create'));
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

        $response->assertRedirect(route('movimentacoes.create'));
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
}
