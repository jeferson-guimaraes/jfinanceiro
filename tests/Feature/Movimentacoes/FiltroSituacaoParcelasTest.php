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

class FiltroSituacaoParcelasTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Parcela $paga;

    private Parcela $naoPaga;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-15 10:00:00');

        $this->user = User::factory()->create();

        $this->paga = $this->criaParcela('Televisao', 400.00, true);
        $this->naoPaga = $this->criaParcela('Geladeira', 250.00, false);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_filtro_pagas_lista_apenas_parcelas_pagas(): void
    {
        $this->assertSame([$this->paga->id], $this->idsListados(['situacao' => 'pagas']));
    }

    public function test_filtro_nao_pagas_lista_apenas_parcelas_pendentes(): void
    {
        $this->assertSame([$this->naoPaga->id], $this->idsListados(['situacao' => 'nao_pagas']));
    }

    /**
     * @return array<string, array{array<string, string>}>
     */
    public static function situacoesQueListamTudo(): array
    {
        return [
            'todas' => [['situacao' => 'todas']],
            'sem filtro' => [[]],
            'valor invalido' => [['situacao' => 'qualquer-coisa']],
        ];
    }

    /**
     * @param  array<string, string>  $parametros
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('situacoesQueListamTudo')]
    public function test_sem_filtro_de_situacao_lista_todas_as_parcelas(array $parametros): void
    {
        $this->assertEqualsCanonicalizing([$this->paga->id, $this->naoPaga->id], $this->idsListados($parametros));
    }

    public function test_totais_do_mes_nao_dependem_do_filtro_de_situacao(): void
    {
        foreach (['todas', 'pagas', 'nao_pagas'] as $situacao) {
            $this->actingAs($this->user)
                ->get(route('movimentacoes.index', ['tipo' => 'gasto futuro', 'situacao' => $situacao]))
                ->assertInertia(fn ($page) => $page
                    ->where('totais.total', 650)
                    ->where('totais.pago', 400)
                    ->where('totais.pendente', 250)
                );
        }
    }

    public function test_totais_seguem_a_busca_mas_nao_a_situacao(): void
    {
        $this->criaParcela('Notebook', 300.00, true);
        $this->criaParcela('Notebook', 120.00, false);

        foreach (['todas', 'pagas', 'nao_pagas'] as $situacao) {
            $this->actingAs($this->user)
                ->get(route('movimentacoes.index', ['tipo' => 'gasto futuro', 'busca' => 'notebook', 'situacao' => $situacao]))
                ->assertInertia(fn ($page) => $page
                    ->where('totais.total', 420)
                    ->where('totais.pago', 300)
                    ->where('totais.pendente', 120)
                );
        }
    }

    public function test_situacao_enviada_como_lista_e_tratada_como_todas(): void
    {
        $this->actingAs($this->user)
            ->get(route('movimentacoes.index', ['tipo' => 'gasto futuro']).'&situacao[]=pagas')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.situacao', 'todas')
                ->has('parcelasFuturas.data', 2)
            );
    }

    public function test_situacao_e_ignorada_fora_da_aba_futuras(): void
    {
        $gasto = Movimentacao::factory()->for($this->user)->create([
            'descricao' => 'Mercado',
            'valor' => 80,
            'tipo' => TipoMovimentacaoEnum::GASTO->value,
            'data' => '2026-10-05',
        ]);

        foreach (['todos', 'gasto'] as $tipo) {
            $this->actingAs($this->user)
                ->get(route('movimentacoes.index', ['tipo' => $tipo, 'situacao' => 'pagas']))
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->has('movimentacoes.data', 1)
                    ->where('movimentacoes.data.0.id', $gasto->id)
                );
        }
    }

    public function test_links_de_paginacao_mantem_a_situacao(): void
    {
        foreach (range(1, 3) as $i) {
            $this->criaParcela("Extra {$i}", 10.00 * $i, false);
        }

        $response = $this->actingAs($this->user)
            ->get(route('movimentacoes.index', ['tipo' => 'gasto futuro', 'situacao' => 'nao_pagas', 'per_page' => 2]));

        $paginacao = $response->viewData('page')['props']['parcelasFuturas'];

        $this->assertSame(4, $paginacao['total']);
        $this->assertStringContainsString('situacao=nao_pagas', $paginacao['next_page_url']);
    }

    public function test_redirect_apos_cadastro_preserva_a_situacao(): void
    {
        $categoria = Categoria::factory()->create([
            'user_id' => $this->user->id,
            'tipo' => TipoMovimentacaoEnum::GANHO->value,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('movimentacoes.store', ['situacao' => 'pagas']), [
                'categoria_id' => $categoria->id,
                'data_movimentacao' => '2026-10-10',
                'descricao' => 'Salario',
                'valor' => 1000,
                'tipo' => TipoMovimentacaoEnum::GANHO->value,
            ]);

        $response->assertRedirect();
        $this->assertStringContainsString('situacao=pagas', $response->headers->get('Location'));
    }

    public function test_filtro_de_situacao_combina_com_a_busca(): void
    {
        $outraPaga = $this->criaParcela('Notebook', 300.00, true);

        $this->assertSame([$outraPaga->id], $this->idsListados(['situacao' => 'pagas', 'busca' => 'notebook']));
        $this->assertSame([], $this->idsListados(['situacao' => 'nao_pagas', 'busca' => 'notebook']));
    }

    public function test_situacao_escolhida_volta_nos_filtros_da_pagina(): void
    {
        $this->actingAs($this->user)
            ->get(route('movimentacoes.index', ['tipo' => 'gasto futuro', 'situacao' => 'nao_pagas']))
            ->assertInertia(fn ($page) => $page->where('filters.situacao', 'nao_pagas'));

        $this->actingAs($this->user)
            ->get(route('movimentacoes.index', ['tipo' => 'gasto futuro', 'situacao' => 'invalida']))
            ->assertInertia(fn ($page) => $page->where('filters.situacao', 'todas'));
    }

    public function test_filtro_de_situacao_nao_mostra_parcelas_de_outro_usuario(): void
    {
        $outroUsuario = User::factory()->create();
        $compraAlheia = Movimentacao::factory()->for($outroUsuario)->create([
            'descricao' => 'Televisao',
            'valor' => 400,
            'tipo' => TipoMovimentacaoEnum::GASTO_FUTURO->value,
            'parcelas' => 1,
            'data' => '2026-10-01',
        ]);
        Parcela::factory()->create([
            'movimentacao_id' => $compraAlheia->id,
            'numero' => 1,
            'valor' => 400,
            'pago' => true,
            'data_vencimento' => '2026-10-20',
        ]);

        $this->assertSame([$this->paga->id], $this->idsListados(['situacao' => 'pagas']));
    }

    private function criaParcela(string $descricao, float $valor, bool $paga): Parcela
    {
        $movimentacao = Movimentacao::factory()->for($this->user)->create([
            'descricao' => $descricao,
            'valor' => $valor,
            'tipo' => TipoMovimentacaoEnum::GASTO_FUTURO->value,
            'parcelas' => 1,
            'data' => '2026-10-01',
        ]);

        return Parcela::factory()->create([
            'movimentacao_id' => $movimentacao->id,
            'numero' => 1,
            'valor' => $valor,
            'pago' => $paga,
            'data_pagamento' => $paga ? '2026-10-10' : null,
            'data_vencimento' => '2026-10-20',
        ]);
    }

    /**
     * @param  array<string, string>  $parametros
     * @return list<int>
     */
    private function idsListados(array $parametros): array
    {
        $props = $this->actingAs($this->user)
            ->get(route('movimentacoes.index', ['tipo' => 'gasto futuro', ...$parametros]))
            ->viewData('page')['props'];

        return collect($props['parcelasFuturas']['data'])->pluck('id')->sort()->values()->all();
    }
}
