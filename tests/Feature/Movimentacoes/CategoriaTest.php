<?php

namespace Tests\Feature\Movimentacoes;

use App\Enums\TipoMovimentacaoEnum;
use App\Models\Categoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
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

    public function test_usuario_logado_pode_criar_categoria_de_ganho_com_sucesso(): void
    {
        $this->withoutExceptionHandling();
        $response = $this->actingAs($this->user)->post(route('movimentacoes.categorias.store'), [
            'user_id' => $this->user->id,
            'nome' => 'Salário',
            'tipo' => TipoMovimentacaoEnum::GANHO->value,
        ]);

        $response->assertRedirect(route('movimentacoes.categorias.index'));
        $response->assertSessionHas('success', 'Categoria criada com sucesso!');

        $this->assertDatabaseHas('categorias', [
            'user_id' => $this->user->id,
            'nome' => 'Salário',
            'tipo' => TipoMovimentacaoEnum::GANHO->value,
        ]);
    }

    public function test_usuario_logado_pode_criar_categoria_de_gasto_com_sucesso(): void
    {
        $response = $this->actingAs($this->user)->post(route('movimentacoes.categorias.store'), [
            'nome' => 'Alimentação',
            'tipo' => TipoMovimentacaoEnum::GASTO->value,
        ]);

        $response->assertRedirect(route('movimentacoes.categorias.index'));
        $response->assertSessionHas('success', 'Categoria criada com sucesso!');

        $this->assertDatabaseHas('categorias', [
            'user_id' => $this->user->id,
            'nome' => 'Alimentação',
            'tipo' => TipoMovimentacaoEnum::GASTO->value,
        ]);
    }

    public function test_usuario_logado_pode_criar_categoria_de_gasto_futuro_com_sucesso(): void
    {
        $response = $this->actingAs($this->user)->post(route('movimentacoes.categorias.store'), [
            'nome' => 'Viagem',
            'tipo' => TipoMovimentacaoEnum::GASTO_FUTURO->value,
        ]);

        $response->assertRedirect(route('movimentacoes.categorias.index'));
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

    public function test_filtra_categorias_do_tipo_ganho(): void
    {
        Categoria::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'tipo' => TipoMovimentacaoEnum::GANHO,
        ]);
        Categoria::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'tipo' => TipoMovimentacaoEnum::GASTO,
        ]);

        $this->actingAs($this->user)
            ->get('/movimentacoes/categorias?tipo=ganho')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('movimentacoes/categorias/Index')
                ->has('listaCategorias.data', 2)
                ->where('filters.tipo', 'ganho')
                ->where('listaCategorias.data.0.tipo', TipoMovimentacaoEnum::GANHO->value)
            );
    }

    public function test_filtra_categorias_do_tipo_gasto_futuro(): void
    {
        Categoria::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'tipo' => TipoMovimentacaoEnum::GANHO,
        ]);
        Categoria::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'tipo' => TipoMovimentacaoEnum::GASTO,
        ]);
        Categoria::factory()->count(4)->create([
            'user_id' => $this->user->id,
            'tipo' => TipoMovimentacaoEnum::GASTO_FUTURO,
        ]);

        $this->actingAs($this->user)
            ->get('/movimentacoes/categorias?tipo=gasto%20futuro')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('movimentacoes/categorias/Index')
                ->has('listaCategorias.data', 4)
                ->where('filters.tipo', 'gasto futuro')
                ->where('listaCategorias.data.0.tipo', TipoMovimentacaoEnum::GASTO_FUTURO->value)
            );
    }

    public function test_filtra_categorias_do_tipo_gasto(): void
    {
        Categoria::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'tipo' => TipoMovimentacaoEnum::GANHO,
        ]);
        Categoria::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'tipo' => TipoMovimentacaoEnum::GASTO,
        ]);

        $this->actingAs($this->user)
            ->get('/movimentacoes/categorias?tipo=gasto')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('movimentacoes/categorias/Index')
                ->has('listaCategorias.data', 2)
                ->where('filters.tipo', 'gasto')
                ->where('listaCategorias.data.0.tipo', TipoMovimentacaoEnum::GASTO->value)
            );
    }

    public function test_filtra_categorias_por_busca(): void
    {
        $categoria1 = Categoria::factory()->create([
            'user_id' => $this->user->id,
            'tipo' => TipoMovimentacaoEnum::GANHO,
            'nome' => 'Salário',
        ]);
        $categoria2 = Categoria::factory()->create([
            'user_id' => $this->user->id,
            'tipo' => TipoMovimentacaoEnum::GANHO,
            'nome' => 'Investimento',
        ]);

        $this->actingAs($this->user)
            ->get('/movimentacoes/categorias?search=Sal')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('movimentacoes/categorias/Index')
                ->has('listaCategorias.data', 1)
                ->where('filters.search', 'Sal')
                ->where('listaCategorias.data.0.nome', $categoria1->nome)
            );
    }

    public function test_exclui_categoria_com_sucesso(): void
    {
        $categoria = Categoria::factory()->create([
            'user_id' => $this->user->id,
            'tipo' => TipoMovimentacaoEnum::GANHO,
        ]);

        $response = $this->from(route('movimentacoes.categorias.index'))
            ->actingAs($this->user)
            ->delete(route('movimentacoes.categorias.destroy', $categoria));

        $response->assertRedirect(route('movimentacoes.categorias.index'));
        $response->assertSessionHas('success', 'Categoria excluída com sucesso!');

        $this->assertDatabaseMissing('categorias', [
            'id' => $categoria->id,
        ]);
    }

    public function test_exclui_varias_categorias_com_sucesso(): void
    {
        $categoria1 = Categoria::factory()->create([
            'user_id' => $this->user->id,
            'tipo' => TipoMovimentacaoEnum::GANHO,
        ]);

        $categoria2 = Categoria::factory()->create([
            'user_id' => $this->user->id,
            'tipo' => TipoMovimentacaoEnum::GANHO,
        ]);

        $response = $this->from(route('movimentacoes.categorias.index'))
            ->actingAs($this->user)
            ->delete(route('movimentacoes.categorias.destroyMany'), [
                'categorias_ids' => [
                    $categoria1->id,
                    $categoria2->id,
                ],
            ]);

        $response->assertRedirect(route('movimentacoes.categorias.index'));
        $response->assertSessionHas('success', 'Categorias excluídas com sucesso!');

        $this->assertDatabaseMissing('categorias', [
            'id' => $categoria1->id,
        ]);

        $this->assertDatabaseMissing('categorias', [
            'id' => $categoria2->id,
        ]);
    }
}
