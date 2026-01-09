<?php

namespace Tests\Feature\Movimentacoes;

use App\Enums\TipoMovimentacaoEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoriaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_usuario_logado_pode_cria_categoria_de_ganho_com_sucesso(): void
    {
        $response = $this->actingAs($this->user)->post(route('movimentacoes.categorias.store'), [
            'nome' => 'Salário',
            'tipo' => TipoMovimentacaoEnum::GANHO->value,
        ]);

        $response->assertRedirect(route('movimentacoes.categorias.create'));
        $response->assertSessionHas('success', 'Categoria criada com sucesso!');

        $this->assertDatabaseHas('categorias', [
            'user_id' => $this->user->id,
            'nome' => 'Salário',
            'tipo' => TipoMovimentacaoEnum::GANHO->value,
        ]);
    }

    public function test_usuario_logado_pode_cria_categoria_de_gasto_com_sucesso(): void
    {
        $response = $this->actingAs($this->user)->post(route('movimentacoes.categorias.store'), [
            'nome' => 'Alimentação',
            'tipo' => TipoMovimentacaoEnum::GASTO->value,
        ]);

        $response->assertRedirect(route('movimentacoes.categorias.create'));
        $response->assertSessionHas('success', 'Categoria criada com sucesso!');

        $this->assertDatabaseHas('categorias', [
            'user_id' => $this->user->id,
            'nome' => 'Alimentação',
            'tipo' => TipoMovimentacaoEnum::GASTO->value,
        ]);
    }

    public function test_usuario_logado_pode_cria_categoria_de_gasto_futuro_com_sucesso(): void
    {
        $response = $this->actingAs($this->user)->post(route('movimentacoes.categorias.store'), [
            'nome' => 'Viagem',
            'tipo' => TipoMovimentacaoEnum::GASTO_FUTURO->value,
        ]);

        $response->assertRedirect(route('movimentacoes.categorias.create'));
        $response->assertSessionHas('success', 'Categoria criada com sucesso!');

        $this->assertDatabaseHas('categorias', [
            'user_id' => $this->user->id,
            'nome' => 'Viagem',
            'tipo' => TipoMovimentacaoEnum::GASTO_FUTURO->value,
        ]);
    }

    public function test_nao_pode_criar_categoria_sem_nome(): void
    {
        $response = $this->actingAs($this->user)->post(route('movimentacoes.categorias.store'), [
            'tipo' => TipoMovimentacaoEnum::GANHO->value,
        ]);

        $response->assertSessionHasErrors('nome');
    }

    public function test_nao_pode_criar_categoria_sem_tipo(): void
    {
        $response = $this->actingAs($this->user)->post(route('movimentacoes.categorias.store'), [
            'nome' => 'Salário',
        ]);

        $response->assertSessionHasErrors('tipo');
    }

    public function test_nao_pode_criar_categoria_com_tipo_invalido(): void
    {
        $response = $this->actingAs($this->user)->post(route('movimentacoes.categorias.store'), [
            'nome' => 'Salário',
            'tipo' => 'tipo_invalido',
        ]);

        $response->assertSessionHasErrors('tipo');
    }

    public function test_bloqueia_criacao_de_categoria_para_usuario_nao_autenticado(): void
    {
        $response = $this->post(route('movimentacoes.categorias.store'), [
            'nome' => 'Salário',
            'tipo' => TipoMovimentacaoEnum::GANHO->value,
        ]);

        $response->assertRedirect('/login');
    }
}
