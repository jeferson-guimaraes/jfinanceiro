<?php

namespace Tests\Feature\Movimentacoes;

use App\Enums\TipoMovimentacaoEnum;
use App\Models\Movimentacao;
use App\Models\Parcela;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * O frontend trata período vazio como equivalente ao mês atual ao decidir se uma seleção
 * salva na sessão ainda vale. Estes testes garantem que o backend aplica esse mesmo padrão.
 */
class FiltrosPadraoTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-15 10:00:00');

        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_listagem_sem_datas_retorna_apenas_o_mes_atual(): void
    {
        $doMes = $this->criaMovimentacao('2026-10-03', TipoMovimentacaoEnum::GASTO);
        $this->criaMovimentacao('2026-09-28', TipoMovimentacaoEnum::GASTO);
        $this->criaMovimentacao('2026-11-02', TipoMovimentacaoEnum::GANHO);

        $response = $this->actingAs($this->user)->get(route('movimentacoes.index', ['tipo' => 'todos']));

        $response->assertInertia(fn ($page) => $page
            ->has('movimentacoes.data', 1)
            ->where('movimentacoes.data.0.id', $doMes->id)
        );
    }

    public function test_listagem_sem_datas_equivale_ao_periodo_explicito_do_mes_atual(): void
    {
        $this->criaMovimentacao('2026-10-01', TipoMovimentacaoEnum::GANHO);
        $this->criaMovimentacao('2026-10-31', TipoMovimentacaoEnum::GASTO);
        $this->criaMovimentacao('2026-09-30', TipoMovimentacaoEnum::GASTO);

        $semDatas = $this->actingAs($this->user)
            ->get(route('movimentacoes.index', ['tipo' => 'todos']))
            ->viewData('page')['props'];

        $comDatas = $this->actingAs($this->user)
            ->get(route('movimentacoes.index', [
                'tipo' => 'todos',
                'data_inicio' => '2026-10-01',
                'data_fim' => '2026-10-31',
            ]))
            ->viewData('page')['props'];

        $this->assertSame(
            collect($comDatas['movimentacoes']['data'])->pluck('id')->sort()->values()->all(),
            collect($semDatas['movimentacoes']['data'])->pluck('id')->sort()->values()->all(),
        );
        $this->assertEquals($comDatas['totais'], $semDatas['totais']);
    }

    public function test_listagem_respeita_periodo_explicito(): void
    {
        $this->criaMovimentacao('2026-10-03', TipoMovimentacaoEnum::GASTO);
        $deJaneiro = $this->criaMovimentacao('2026-01-10', TipoMovimentacaoEnum::GASTO);

        $response = $this->actingAs($this->user)->get(route('movimentacoes.index', [
            'tipo' => 'gasto',
            'data_inicio' => '2026-01-05',
            'data_fim' => '2026-02-10',
        ]));

        $response->assertInertia(fn ($page) => $page
            ->has('movimentacoes.data', 1)
            ->where('movimentacoes.data.0.id', $deJaneiro->id)
        );
    }

    public function test_gastos_futuros_sem_mes_e_ano_retornam_parcelas_do_mes_atual(): void
    {
        $movimentacao = $this->criaMovimentacao('2026-09-20', TipoMovimentacaoEnum::GASTO_FUTURO, ['parcelas' => 3]);
        $doMes = $this->criaParcela($movimentacao, 2, '2026-10-20');
        $this->criaParcela($movimentacao, 1, '2026-09-20');
        $this->criaParcela($movimentacao, 3, '2026-11-20');

        $response = $this->actingAs($this->user)->get(route('movimentacoes.index', ['tipo' => 'gasto futuro']));

        $response->assertInertia(fn ($page) => $page
            ->has('parcelasFuturas.data', 1)
            ->where('parcelasFuturas.data.0.id', $doMes->id)
        );
    }

    public function test_gastos_futuros_respeitam_mes_e_ano_informados(): void
    {
        $movimentacao = $this->criaMovimentacao('2026-09-20', TipoMovimentacaoEnum::GASTO_FUTURO, ['parcelas' => 2]);
        $this->criaParcela($movimentacao, 1, '2026-10-20');
        $deMarco = $this->criaParcela($movimentacao, 2, '2027-03-20');

        $response = $this->actingAs($this->user)->get(route('movimentacoes.index', [
            'tipo' => 'gasto futuro',
            'mes' => '3',
            'ano' => '2027',
        ]));

        $response->assertInertia(fn ($page) => $page
            ->has('parcelasFuturas.data', 1)
            ->where('parcelasFuturas.data.0.id', $deMarco->id)
        );
    }

    /**
     * @param  array<string, mixed>  $atributos
     */
    private function criaMovimentacao(string $data, TipoMovimentacaoEnum $tipo, array $atributos = []): Movimentacao
    {
        return Movimentacao::factory()->for($this->user)->create(array_merge([
            'data' => $data,
            'tipo' => $tipo->value,
        ], $atributos));
    }

    private function criaParcela(Movimentacao $movimentacao, int $numero, string $vencimento): Parcela
    {
        return Parcela::factory()->create([
            'movimentacao_id' => $movimentacao->id,
            'numero' => $numero,
            'data_vencimento' => $vencimento,
        ]);
    }
}
