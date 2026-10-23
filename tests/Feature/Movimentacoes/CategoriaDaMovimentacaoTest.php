<?php

namespace Tests\Feature\Movimentacoes;

use App\Enums\TipoMovimentacaoEnum;
use App\Models\Categoria;
use App\Models\Movimentacao;
use App\Models\Parcela;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Garante que uma movimentação só use categoria do próprio usuário (ou global) e do mesmo tipo.
 */
class CategoriaDaMovimentacaoTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    private function dadosDeGasto(int $categoriaId): array
    {
        return [
            'descricao' => 'Mercado',
            'valor' => 45,
            'data_movimentacao' => '2026-10-01',
            'categoria_id' => $categoriaId,
            'tipo' => TipoMovimentacaoEnum::GASTO->value,
        ];
    }

    public function test_nao_aceita_categoria_de_outro_usuario(): void
    {
        $deOutro = Categoria::factory()->create([
            'user_id' => User::factory()->create()->id,
            'tipo' => TipoMovimentacaoEnum::GASTO->value,
        ]);

        $this->actingAs($this->user)
            ->post(route('movimentacoes.store'), $this->dadosDeGasto($deOutro->id))
            ->assertSessionHasErrors('categoria_id');

        $this->assertDatabaseCount('movimentacoes', 0);
    }

    public function test_aceita_categoria_global(): void
    {
        $global = Categoria::factory()->create(['user_id' => null, 'tipo' => TipoMovimentacaoEnum::GASTO->value]);

        $this->actingAs($this->user)
            ->post(route('movimentacoes.store'), $this->dadosDeGasto($global->id))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('movimentacoes', ['categoria_id' => $global->id, 'user_id' => $this->user->id]);
    }

    public function test_nao_aceita_categoria_de_outro_tipo(): void
    {
        $deGanho = Categoria::factory()->create(['user_id' => $this->user->id, 'tipo' => TipoMovimentacaoEnum::GANHO->value]);

        $this->actingAs($this->user)
            ->post(route('movimentacoes.store'), $this->dadosDeGasto($deGanho->id))
            ->assertSessionHasErrors('categoria_id');
    }

    public function test_nao_aceita_data_anterior_ao_ano_2000(): void
    {
        $categoria = Categoria::factory()->create(['user_id' => $this->user->id, 'tipo' => TipoMovimentacaoEnum::GASTO->value]);

        $this->actingAs($this->user)
            ->post(route('movimentacoes.store'), ['data_movimentacao' => '0025-10-05'] + $this->dadosDeGasto($categoria->id))
            ->assertSessionHasErrors('data_movimentacao');
    }

    public function test_edicao_nao_aceita_categoria_nem_parcela_de_outro_usuario(): void
    {
        $outro = User::factory()->create();
        $categoriaOutro = Categoria::factory()->create(['user_id' => $outro->id, 'tipo' => TipoMovimentacaoEnum::GASTO_FUTURO->value]);
        $parcelaOutro = Parcela::factory()->create([
            'movimentacao_id' => Movimentacao::factory()->create(['user_id' => $outro->id, 'categoria_id' => $categoriaOutro->id])->id,
        ]);

        $categoria = Categoria::factory()->create(['user_id' => $this->user->id, 'tipo' => TipoMovimentacaoEnum::GASTO_FUTURO->value]);
        $movimentacao = Movimentacao::factory()->create([
            'user_id' => $this->user->id,
            'categoria_id' => $categoria->id,
            'tipo' => TipoMovimentacaoEnum::GASTO_FUTURO->value,
        ]);

        $this->actingAs($this->user)
            ->patch(route('movimentacoes.update', $movimentacao), [
                'descricao' => 'TV',
                'valor' => 100,
                'data_movimentacao' => '2026-10-01',
                'categoria_id' => $categoriaOutro->id,
                'tipo' => TipoMovimentacaoEnum::GASTO_FUTURO->value,
                'data_vencimento' => '2026-11-01',
                'parcelas_editadas' => [['id' => $parcelaOutro->id, 'valor' => 50, 'data_vencimento' => '2026-11-01']],
            ])
            ->assertSessionHasErrors(['categoria_id', 'parcelas_editadas.0.id']);
    }
}
