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
 * Os testes rodam em SQLite, cujo LIKE diferencia acentos e só ignora maiúsculas em ASCII.
 * Em produção o MySQL usa utf8mb4_unicode_ci, que ignora acentos e maiúsculas. Por isso os
 * nomes usados aqui não têm acento e nenhum teste afirma comportamento sobre acentuação.
 */
class BuscaMovimentacoesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Categoria $alimentacao;

    private Categoria $transporte;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-15 10:00:00');

        $this->user = User::factory()->create();
        $this->alimentacao = Categoria::factory()->create(['user_id' => $this->user->id, 'nome' => 'Alimentacao', 'tipo' => TipoMovimentacaoEnum::GASTO->value]);
        $this->transporte = Categoria::factory()->create(['user_id' => $this->user->id, 'nome' => 'Transporte', 'tipo' => TipoMovimentacaoEnum::GASTO->value]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_busca_por_descricao_continua_funcionando(): void
    {
        $mercado = $this->criaGasto('Compra no mercado', 45.90, $this->alimentacao);
        $this->criaGasto('Uber para o trabalho', 32.00, $this->transporte);

        $this->assertSame([$mercado->id], $this->idsEncontrados('mercado'));
    }

    public function test_busca_por_nome_da_categoria(): void
    {
        $mercado = $this->criaGasto('Compra no mercado', 45.90, $this->alimentacao);
        $padaria = $this->criaGasto('Padaria', 12.50, $this->alimentacao);
        $this->criaGasto('Uber para o trabalho', 32.00, $this->transporte);

        $this->assertEqualsCanonicalizing([$mercado->id, $padaria->id], $this->idsEncontrados('aliment'));
    }

    public function test_busca_por_valor_aceita_formatos_brasileiro_e_com_ponto(): void
    {
        $mercado = $this->criaGasto('Compra no mercado', 45.90, $this->alimentacao);
        $this->criaGasto('Uber para o trabalho', 32.00, $this->transporte);

        foreach (['45,90', '45,9', '45.90', 'R$ 45,90', 'R$45,90'] as $termo) {
            $this->assertSame([$mercado->id], $this->idsEncontrados($termo), "Termo: {$termo}");
        }
    }

    public function test_busca_por_valor_com_separador_de_milhar(): void
    {
        $aluguel = $this->criaGasto('Aluguel', 1234.56, $this->transporte);
        $this->criaGasto('Uber para o trabalho', 32.00, $this->transporte);

        $this->assertSame([$aluguel->id], $this->idsEncontrados('1.234,56'));
    }

    public function test_busca_por_valor_inteiro(): void
    {
        $uber = $this->criaGasto('Uber para o trabalho', 32.00, $this->transporte);
        $this->criaGasto('Compra no mercado', 45.90, $this->alimentacao);

        $this->assertSame([$uber->id], $this->idsEncontrados('32'));
    }

    public function test_varias_palavras_precisam_corresponder_cada_uma_a_algum_campo(): void
    {
        $mercado = $this->criaGasto('Compra no mercado', 45.90, $this->alimentacao);
        $this->criaGasto('Mercado livre frete', 20.00, $this->transporte);
        $this->criaGasto('Padaria', 12.50, $this->alimentacao);

        $this->assertSame([$mercado->id], $this->idsEncontrados('mercado alimentacao'));
    }

    public function test_busca_ignora_espacos_extras_e_simbolo_isolado(): void
    {
        $mercado = $this->criaGasto('Compra no mercado', 45.90, $this->alimentacao);
        $this->criaGasto('Uber para o trabalho', 32.00, $this->transporte);

        $this->assertSame([$mercado->id], $this->idsEncontrados('   mercado   R$  '));
    }

    public function test_busca_sem_correspondencia_retorna_vazio(): void
    {
        $this->criaGasto('Compra no mercado', 45.90, $this->alimentacao);

        $this->assertSame([], $this->idsEncontrados('inexistente'));
    }

    public function test_busca_nao_retorna_movimentacoes_de_outro_usuario(): void
    {
        $outroUsuario = User::factory()->create();
        $categoriaAlheia = Categoria::factory()->create(['user_id' => $outroUsuario->id, 'nome' => 'Alimentacao']);
        Movimentacao::factory()->for($outroUsuario)->create([
            'categoria_id' => $categoriaAlheia->id,
            'descricao' => 'Compra no mercado',
            'valor' => 45.90,
            'tipo' => TipoMovimentacaoEnum::GASTO->value,
            'data' => '2026-10-10',
        ]);
        $meu = $this->criaGasto('Compra no mercado', 45.90, $this->alimentacao);

        foreach (['mercado', 'alimentacao', '45,90'] as $termo) {
            $this->assertSame([$meu->id], $this->idsEncontrados($termo), "Termo: {$termo}");
        }
    }

    public function test_busca_respeita_o_periodo_selecionado(): void
    {
        $doMes = $this->criaGasto('Compra no mercado', 45.90, $this->alimentacao);
        $this->criaGasto('Compra no mercado', 45.90, $this->alimentacao, '2026-09-10');

        $this->assertSame([$doMes->id], $this->idsEncontrados('mercado'));
    }

    public function test_totais_consideram_a_busca(): void
    {
        $this->criaGasto('Compra no mercado', 40.00, $this->alimentacao);
        $this->criaGasto('Padaria', 12.50, $this->alimentacao);
        $this->criaGasto('Uber para o trabalho', 32.00, $this->transporte);

        $this->actingAs($this->user)
            ->get(route('movimentacoes.index', ['tipo' => 'gasto', 'busca' => 'alimentacao']))
            ->assertInertia(fn ($page) => $page->where('totais.total', 52.5));
    }

    public function test_futuras_busca_por_categoria_da_compra(): void
    {
        $cartao = Categoria::factory()->create(['user_id' => $this->user->id, 'nome' => 'Cartao Nubank', 'tipo' => TipoMovimentacaoEnum::GASTO_FUTURO->value]);
        $parcelaTv = $this->criaParcelaFutura('Televisao', 1200.00, 400.00, $cartao);
        $this->criaParcelaFutura('Geladeira', 3000.00, 1000.00, $this->alimentacao);

        $this->assertSame([$parcelaTv->id], $this->idsParcelasEncontradas('nubank'));
    }

    public function test_futuras_busca_pelo_valor_da_parcela_ou_da_compra(): void
    {
        $parcelaTv = $this->criaParcelaFutura('Televisao', 1200.00, 400.00, $this->transporte);
        $this->criaParcelaFutura('Geladeira', 3000.00, 1000.00, $this->alimentacao);

        $this->assertSame([$parcelaTv->id], $this->idsParcelasEncontradas('400,00'));
        $this->assertSame([$parcelaTv->id], $this->idsParcelasEncontradas('1.200'));
    }

    public function test_futuras_combinam_palavras_entre_descricao_e_valor(): void
    {
        $parcelaTv = $this->criaParcelaFutura('Televisao', 1200.00, 400.00, $this->transporte);
        $this->criaParcelaFutura('Televisao', 900.00, 300.00, $this->transporte);

        $this->assertSame([$parcelaTv->id], $this->idsParcelasEncontradas('televisao 400'));
    }

    public function test_busca_na_aba_todos_filtra_ganhos_despesas_e_totais(): void
    {
        $salario = Categoria::factory()->create(['user_id' => $this->user->id, 'nome' => 'Salario', 'tipo' => TipoMovimentacaoEnum::GANHO->value]);
        $mercado = $this->criaGasto('Compra no mercado', 40.00, $this->alimentacao);
        $vale = Movimentacao::factory()->for($this->user)->create([
            'categoria_id' => $salario->id,
            'descricao' => 'Vale mercado',
            'valor' => 300.00,
            'tipo' => TipoMovimentacaoEnum::GANHO->value,
            'data' => '2026-10-05',
        ]);
        $this->criaGasto('Uber para o trabalho', 32.00, $this->transporte);

        $response = $this->actingAs($this->user)->get(route('movimentacoes.index', ['tipo' => 'todos', 'busca' => 'mercado']));

        $props = $response->viewData('page')['props'];
        $this->assertEqualsCanonicalizing([$mercado->id, $vale->id], collect($props['movimentacoes']['data'])->pluck('id')->all());
        $response->assertInertia(fn ($page) => $page
            ->where('totais.ganhos', 300)
            ->where('totais.despesas', 40)
            ->where('totais.total', 260)
        );
    }

    public function test_busca_por_categoria_global_sem_dono(): void
    {
        $global = Categoria::factory()->create(['user_id' => null, 'nome' => 'Outros', 'tipo' => TipoMovimentacaoEnum::GASTO->value]);
        $avulso = $this->criaGasto('Presente', 80.00, $global);
        $this->criaGasto('Compra no mercado', 45.90, $this->alimentacao);

        $this->assertSame([$avulso->id], $this->idsEncontrados('outros'));
    }

    public function test_termo_que_parece_valor_mas_nao_e_casa_apenas_pela_descricao(): void
    {
        $pedido = $this->criaGasto('Pedido 45,901', 10.00, $this->alimentacao);
        $this->criaGasto('Compra no mercado', 45.90, $this->alimentacao);

        $this->assertSame([$pedido->id], $this->idsEncontrados('45,901'));
    }

    public function test_numero_acima_do_valor_maximo_nao_e_tratado_como_valor(): void
    {
        $this->criaGasto('Estorno', 0.00, $this->alimentacao);
        $this->criaGasto('Compra no mercado', 45.90, $this->alimentacao);

        $this->assertSame([], $this->idsEncontrados(str_repeat('9', 400)));
        $this->assertSame([], $this->idsEncontrados('100000000'));
    }

    public function test_busca_considera_no_maximo_dez_palavras(): void
    {
        $mercado = $this->criaGasto('Compra no mercado', 45.90, $this->alimentacao);

        $dezPalavrasQueCasam = trim(str_repeat('mercado ', 10));

        $this->assertSame([$mercado->id], $this->idsEncontrados($dezPalavrasQueCasam.' inexistente'));
        $this->assertSame([], $this->idsEncontrados('inexistente '.$dezPalavrasQueCasam));
    }

    public function test_futuras_busca_nao_retorna_parcelas_de_outro_usuario(): void
    {
        $outroUsuario = User::factory()->create();
        $categoriaAlheia = Categoria::factory()->create(['user_id' => $outroUsuario->id, 'nome' => 'Cartao']);
        $compraAlheia = Movimentacao::factory()->for($outroUsuario)->create([
            'categoria_id' => $categoriaAlheia->id,
            'descricao' => 'Televisao',
            'valor' => 1200.00,
            'tipo' => TipoMovimentacaoEnum::GASTO_FUTURO->value,
            'parcelas' => 3,
            'data' => '2026-09-20',
        ]);
        Parcela::factory()->create([
            'movimentacao_id' => $compraAlheia->id,
            'numero' => 1,
            'valor' => 400.00,
            'data_vencimento' => '2026-10-20',
        ]);
        $minha = $this->criaParcelaFutura('Televisao', 1200.00, 400.00, $this->transporte);

        foreach (['400', '1.200', 'televisao', 'televisao 400'] as $termo) {
            $this->assertSame([$minha->id], $this->idsParcelasEncontradas($termo), "Termo: {$termo}");
        }
    }

    private function criaGasto(string $descricao, float $valor, Categoria $categoria, string $data = '2026-10-10'): Movimentacao
    {
        return Movimentacao::factory()->for($this->user)->create([
            'categoria_id' => $categoria->id,
            'descricao' => $descricao,
            'valor' => $valor,
            'tipo' => TipoMovimentacaoEnum::GASTO->value,
            'data' => $data,
        ]);
    }

    private function criaParcelaFutura(string $descricao, float $valorTotal, float $valorParcela, Categoria $categoria): Parcela
    {
        $movimentacao = Movimentacao::factory()->for($this->user)->create([
            'categoria_id' => $categoria->id,
            'descricao' => $descricao,
            'valor' => $valorTotal,
            'tipo' => TipoMovimentacaoEnum::GASTO_FUTURO->value,
            'parcelas' => 3,
            'data' => '2026-09-20',
        ]);

        return Parcela::factory()->create([
            'movimentacao_id' => $movimentacao->id,
            'numero' => 1,
            'valor' => $valorParcela,
            'data_vencimento' => '2026-10-20',
        ]);
    }

    /**
     * @return list<int>
     */
    private function idsEncontrados(string $busca): array
    {
        $props = $this->actingAs($this->user)
            ->get(route('movimentacoes.index', ['tipo' => 'gasto', 'busca' => $busca]))
            ->viewData('page')['props'];

        return collect($props['movimentacoes']['data'])->pluck('id')->sort()->values()->all();
    }

    /**
     * @return list<int>
     */
    private function idsParcelasEncontradas(string $busca): array
    {
        $props = $this->actingAs($this->user)
            ->get(route('movimentacoes.index', ['tipo' => 'gasto futuro', 'busca' => $busca]))
            ->viewData('page')['props'];

        return collect($props['parcelasFuturas']['data'])->pluck('id')->sort()->values()->all();
    }
}
