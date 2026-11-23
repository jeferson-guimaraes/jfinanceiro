<?php

namespace Tests\Feature;

use App\Enums\TipoMovimentacaoEnum;
use App\Models\Categoria;
use App\Models\Movimentacao;
use App\Models\Parcela;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-15 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
    }

    public function test_sem_periodo_usa_o_mes_atual(): void
    {
        $user = User::factory()->create();
        $this->criarMovimentacao($user, TipoMovimentacaoEnum::GANHO, 1000, '2026-11-05');
        $this->criarMovimentacao($user, TipoMovimentacaoEnum::GASTO, 300, '2026-11-10');
        $this->criarMovimentacao($user, TipoMovimentacaoEnum::GANHO, 500, '2026-10-20');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('periodo.data_inicio', '2026-11-01')
                ->where('periodo.data_fim', '2026-11-30')
                ->where('stats.0.value', 700)
                ->where('stats.1.value', 1000)
                ->where('stats.2.value', 300)
                ->has('recentTransactions', 2)
            );
    }

    public function test_periodo_personalizado_filtra_totais_transacoes_e_categorias(): void
    {
        $user = User::factory()->create();
        $mercado = Categoria::factory()->create(['nome' => 'Mercado', 'tipo' => TipoMovimentacaoEnum::GASTO->value, 'user_id' => $user->id]);
        $this->criarMovimentacao($user, TipoMovimentacaoEnum::GANHO, 2000, '2026-09-01');
        $this->criarMovimentacao($user, TipoMovimentacaoEnum::GASTO, 400, '2026-10-31', $mercado);
        $this->criarMovimentacao($user, TipoMovimentacaoEnum::GASTO, 999, '2026-08-31');
        $this->criarMovimentacao($user, TipoMovimentacaoEnum::GANHO, 999, '2026-11-01');

        $this->actingAs($user)
            ->get(route('dashboard', ['data_inicio' => '2026-09-01', 'data_fim' => '2026-10-31']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('periodo.data_inicio', '2026-09-01')
                ->where('periodo.data_fim', '2026-10-31')
                ->where('stats.0.value', 1600)
                ->where('stats.1.value', 2000)
                ->where('stats.2.value', 400)
                ->has('recentTransactions', 2)
                ->has('categoriesSummary', 1)
                ->where('categoriesSummary.0.name', 'Mercado')
                ->where('categoriesSummary.0.percentage', 100)
            );
    }

    public function test_a_pagar_do_periodo_considera_so_parcelas_pendentes_que_vencem_no_periodo(): void
    {
        $user = User::factory()->create();
        $compra = $this->criarMovimentacao($user, TipoMovimentacaoEnum::GASTO_FUTURO, 300, '2026-10-01');
        Parcela::factory()->create(['movimentacao_id' => $compra->id, 'valor' => 100, 'data_vencimento' => '2026-10-10', 'pago' => false]);
        Parcela::factory()->create(['movimentacao_id' => $compra->id, 'valor' => 100, 'data_vencimento' => '2026-10-20', 'pago' => true]);
        Parcela::factory()->create(['movimentacao_id' => $compra->id, 'valor' => 100, 'data_vencimento' => '2026-12-10', 'pago' => false]);

        $this->actingAs($user)
            ->get(route('dashboard', ['data_inicio' => '2026-10-01', 'data_fim' => '2026-10-31']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.3.title', 'A Pagar')
                ->where('stats.3.value', 100)
            );
    }

    public function test_visao_geral_considera_todo_o_historico_independente_do_periodo(): void
    {
        $user = User::factory()->create(['created_at' => '2024-03-10 12:00:00']);
        $this->criarMovimentacao($user, TipoMovimentacaoEnum::GANHO, 5000, '2024-04-01');
        $this->criarMovimentacao($user, TipoMovimentacaoEnum::GASTO, 1200, '2025-06-15');
        $this->criarMovimentacao($user, TipoMovimentacaoEnum::GASTO, 300, '2026-11-10');
        $compra = $this->criarMovimentacao($user, TipoMovimentacaoEnum::GASTO_FUTURO, 600, '2026-11-01');
        Parcela::factory()->create(['movimentacao_id' => $compra->id, 'valor' => 200, 'data_vencimento' => '2026-12-05', 'pago' => false]);
        Parcela::factory()->create(['movimentacao_id' => $compra->id, 'valor' => 200, 'data_vencimento' => '2027-01-05', 'pago' => false]);
        Parcela::factory()->create(['movimentacao_id' => $compra->id, 'valor' => 200, 'data_vencimento' => '2026-11-05', 'pago' => true]);

        $this->actingAs($user)
            ->get(route('dashboard', ['data_inicio' => '2026-11-01', 'data_fim' => '2026-11-30']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('geral.desde', '10/03/2024')
                ->where('geral.stats.0.value', 3500)
                ->where('geral.stats.1.value', 5000)
                ->where('geral.stats.2.value', 1500)
                ->where('geral.stats.3.value', 400)
                ->where('geral.stats.4.value', 200)
                ->has('geral.categoriesSummary', 2)
            );
    }

    public function test_nao_considera_dados_de_outros_usuarios(): void
    {
        $user = User::factory()->create();
        $outro = User::factory()->create();
        $this->criarMovimentacao($outro, TipoMovimentacaoEnum::GANHO, 1000, '2026-11-05');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.1.value', 0)
                ->where('geral.stats.1.value', 0)
                ->has('recentTransactions', 0)
            );
    }

    public function test_recarga_parcial_do_periodo_nao_recalcula_a_visao_geral(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard', ['data_inicio' => '2026-01-01', 'data_fim' => '2026-12-31']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('geral')
                ->reloadOnly(['periodo', 'stats'], fn (Assert $reload) => $reload
                    ->where('periodo.data_inicio', '2026-01-01')
                    ->missing('geral')
                )
            );
    }

    public function test_data_final_anterior_a_inicial_e_rejeitada(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->get(route('dashboard', ['data_inicio' => '2026-11-10', 'data_fim' => '2026-11-01']))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors(['data_fim' => 'A data final deve ser igual ou posterior à data inicial.']);
    }

    public function test_periodo_incompleto_e_rejeitado(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->get(route('dashboard', ['data_inicio' => '2026-11-10']))
            ->assertSessionHasErrors(['data_fim' => 'Informe a data final do período.']);
    }

    public function test_data_em_formato_invalido_e_rejeitada(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->get(route('dashboard', ['data_inicio' => '10/11/2026', 'data_fim' => '2026-11-30']))
            ->assertSessionHasErrors(['data_inicio' => 'A data inicial é inválida.']);
    }

    private function criarMovimentacao(User $user, TipoMovimentacaoEnum $tipo, float $valor, string $data, ?Categoria $categoria = null): Movimentacao
    {
        $categoria ??= Categoria::factory()->create(['tipo' => $tipo->value, 'user_id' => $user->id]);

        return Movimentacao::factory()->create([
            'user_id' => $user->id,
            'categoria_id' => $categoria->id,
            'tipo' => $tipo->value,
            'valor' => $valor,
            'data' => $data,
            'parcelas' => 1,
        ]);
    }
}
