<?php

namespace Tests\Feature\Movimentacoes;

use App\Enums\TipoMovimentacaoEnum;
use App\Models\Categoria;
use App\Models\Movimentacao;
use App\Models\Parcela;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Busca com "historico": ignora o período (datas e mês/ano), mas mantém aba, busca e situação.
 */
class BuscaNoHistoricoTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Categoria $saude;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-23 10:00:00');

        $this->user = User::factory()->create();
        $this->saude = Categoria::factory()->create(['user_id' => $this->user->id, 'nome' => 'Saude', 'tipo' => TipoMovimentacaoEnum::GASTO->value]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_sem_historico_a_busca_continua_restrita_ao_periodo(): void
    {
        $this->criaMovimentacao('Taxa de enfermagem', 120, '2025-03-10');

        $props = $this->props(['tipo' => 'gasto', 'busca' => 'enfermagem']);

        $this->assertSame([], $this->ids($props['movimentacoes']));
    }

    public function test_com_historico_encontra_gasto_de_qualquer_data(): void
    {
        $antiga = $this->criaMovimentacao('Taxa de enfermagem', 120, '2024-03-10');
        $recente = $this->criaMovimentacao('Taxa de enfermagem', 130, '2026-11-02');
        $this->criaMovimentacao('Farmacia', 50, '2025-01-05');

        $props = $this->props(['tipo' => 'gasto', 'busca' => 'enfermagem', 'historico' => 1]);

        $this->assertSame([$recente->id, $antiga->id], collect($props['movimentacoes']['data'])->pluck('id')->all());
        $this->assertEquals(250, $props['totais']['total']);
        $this->assertTrue($props['filters']['historico']);
    }

    public function test_historico_ignora_datas_enviadas_junto(): void
    {
        $antiga = $this->criaMovimentacao('Taxa de enfermagem', 120, '2024-03-10');

        $props = $this->props([
            'tipo' => 'gasto',
            'busca' => 'enfermagem',
            'historico' => 1,
            'data_inicio' => '2026-11-01',
            'data_fim' => '2026-11-30',
        ]);

        $this->assertSame([$antiga->id], $this->ids($props['movimentacoes']));
    }

    public function test_historico_mantem_a_divisao_por_tipo(): void
    {
        $gasto = $this->criaMovimentacao('Taxa de enfermagem', 120, '2024-03-10');
        $ganho = $this->criaMovimentacao('Reembolso taxa de enfermagem', 120, '2024-04-10', TipoMovimentacaoEnum::GANHO);

        $this->assertSame([$gasto->id], $this->ids($this->props(['tipo' => 'gasto', 'busca' => 'enfermagem', 'historico' => 1])['movimentacoes']));
        $this->assertSame([$ganho->id], $this->ids($this->props(['tipo' => 'ganho', 'busca' => 'enfermagem', 'historico' => 1])['movimentacoes']));

        $todos = $this->props(['tipo' => 'todos', 'busca' => 'enfermagem', 'historico' => 1]);
        $this->assertSame([$ganho->id, $gasto->id], $this->ids($todos['movimentacoes']));
        $this->assertEquals(120, $todos['totais']['ganhos']);
        $this->assertEquals(120, $todos['totais']['despesas']);
    }

    public function test_historico_encontra_parcelas_futuras_de_qualquer_vencimento_respeitando_a_situacao(): void
    {
        $compra = $this->criaMovimentacao('Taxa de enfermagem parcelada', 300, '2025-01-05', TipoMovimentacaoEnum::GASTO_FUTURO);
        $paga = Parcela::factory()->create(['movimentacao_id' => $compra->id, 'numero' => 1, 'valor' => 100, 'data_vencimento' => '2025-02-05', 'pago' => true]);
        $pendente = Parcela::factory()->create(['movimentacao_id' => $compra->id, 'numero' => 2, 'valor' => 100, 'data_vencimento' => '2027-03-05', 'pago' => false]);

        $todas = $this->props(['tipo' => 'gasto futuro', 'busca' => 'enfermagem', 'historico' => 1]);
        $this->assertSame([$paga->id, $pendente->id], $this->ids($todas['parcelasFuturas']));
        $this->assertEquals(200, $todas['totais']['total']);
        $this->assertEquals(100, $todas['totais']['pago']);

        $pagas = $this->props(['tipo' => 'gasto futuro', 'busca' => 'enfermagem', 'historico' => 1, 'situacao' => 'pagas']);
        $this->assertSame([$paga->id], $this->ids($pagas['parcelasFuturas']));
    }

    public function test_historico_nao_mostra_dados_de_outros_usuarios(): void
    {
        $outro = User::factory()->create();
        Movimentacao::factory()->for($outro)->create([
            'categoria_id' => Categoria::factory()->create(['user_id' => $outro->id, 'tipo' => TipoMovimentacaoEnum::GASTO->value])->id,
            'descricao' => 'Taxa de enfermagem',
            'tipo' => TipoMovimentacaoEnum::GASTO->value,
            'data' => '2024-03-10',
        ]);

        $props = $this->props(['tipo' => 'gasto', 'busca' => 'enfermagem', 'historico' => 1]);

        $this->assertSame([], $this->ids($props['movimentacoes']));
    }

    public function test_cadastro_preserva_o_historico_no_redirecionamento(): void
    {
        $this->actingAs($this->user)
            ->post(route('movimentacoes.store', ['tipo' => 'gasto', 'historico' => 1, 'busca' => 'enfermagem']), [
                'categoria_id' => $this->saude->id,
                'data_movimentacao' => '2026-11-20',
                'descricao' => 'Taxa de enfermagem',
                'valor' => 120,
                'tipo' => TipoMovimentacaoEnum::GASTO->value,
                'parcelas' => 1,
            ])
            ->assertRedirect(route('movimentacoes.create', ['busca' => 'enfermagem', 'tipo' => 'gasto', 'historico' => 1]));
    }

    private function criaMovimentacao(string $descricao, float $valor, string $data, TipoMovimentacaoEnum $tipo = TipoMovimentacaoEnum::GASTO): Movimentacao
    {
        $categoria = $tipo === TipoMovimentacaoEnum::GASTO
            ? $this->saude
            : Categoria::factory()->create(['user_id' => $this->user->id, 'tipo' => $tipo->value]);

        return Movimentacao::factory()->for($this->user)->create([
            'categoria_id' => $categoria->id,
            'descricao' => $descricao,
            'valor' => $valor,
            'tipo' => $tipo->value,
            'data' => $data,
        ]);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function props(array $query): array
    {
        return $this->actingAs($this->user)
            ->get(route('movimentacoes.index', $query))
            ->assertOk()
            ->viewData('page')['props'];
    }

    /**
     * @param  array{data: list<array{id: int}>}  $paginado
     * @return list<int>
     */
    private function ids(array $paginado): array
    {
        return collect($paginado['data'])->pluck('id')->all();
    }
}
