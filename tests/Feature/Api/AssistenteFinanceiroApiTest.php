<?php

namespace Tests\Feature\Api;

use App\Enums\TipoMovimentacaoEnum;
use App\Models\Categoria;
use App\Models\Movimentacao;
use App\Models\Parcela;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AssistenteFinanceiroApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Categoria $alimentacao;

    private Categoria $eletronicos;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Globais "Outros" de cada tipo (como no CategoriaSeeder) + categorias do usuário.
        foreach (TipoMovimentacaoEnum::cases() as $tipo) {
            Categoria::factory()->create(['user_id' => null, 'nome' => 'Outros', 'tipo' => $tipo->value]);
        }
        $this->alimentacao = Categoria::factory()->create(['user_id' => $this->user->id, 'nome' => 'Alimentação', 'tipo' => TipoMovimentacaoEnum::GASTO->value]);
        $this->eletronicos = Categoria::factory()->create(['user_id' => $this->user->id, 'nome' => 'Eletrônicos', 'tipo' => TipoMovimentacaoEnum::GASTO_FUTURO->value]);
    }

    private function comoAssistente(array $abilities = ['financeiro:read', 'financeiro:write']): static
    {
        Sanctum::actingAs($this->user, $abilities);

        return $this;
    }

    private function lancar(array $dados, ?string $chave = null)
    {
        return $this->withHeader('Idempotency-Key', $chave ?? (string) Str::uuid())
            ->postJson('/api/assistente/movimentacoes', $dados);
    }

    private function compraParcelada(array $extra = []): Movimentacao
    {
        $movimentacao = Movimentacao::factory()->create([
            'user_id' => $this->user->id,
            'categoria_id' => $this->eletronicos->id,
            'tipo' => TipoMovimentacaoEnum::GASTO_FUTURO->value,
            'valor' => 750,
            'parcelas' => 3,
            'descricao' => 'TV',
        ] + $extra);

        foreach ([1, 2, 3] as $numero) {
            Parcela::factory()->create([
                'movimentacao_id' => $movimentacao->id,
                'numero' => $numero,
                'valor' => 250,
                'data_vencimento' => now()->startOfMonth()->addMonths($numero - 2)->addDays(9)->toDateString(),
                'pago' => false,
            ]);
        }

        return $movimentacao;
    }

    // --- autenticação e permissões -------------------------------------------------------------------

    public function test_exige_token_e_responde_json_sem_header_accept(): void
    {
        $this->get('/api/assistente/resumo')->assertUnauthorized()->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_token_real_expirado_e_revogado_sao_recusados(): void
    {
        $valido = $this->user->createToken('assistente:logan', ['financeiro:read'])->plainTextToken;
        $this->withToken($valido)->getJson('/api/assistente/resumo')->assertOk();

        $expirado = $this->user->createToken('x', ['financeiro:read'], now()->subDay())->plainTextToken;
        $this->app['auth']->forgetGuards();
        $this->withToken($expirado)->getJson('/api/assistente/resumo')->assertUnauthorized();

        PersonalAccessToken::query()->delete();
        $this->app['auth']->forgetGuards();
        $this->withToken($valido)->getJson('/api/assistente/resumo')->assertUnauthorized();
    }

    public function test_sessao_web_nao_acessa_a_api(): void
    {
        $this->actingAs($this->user)->getJson('/api/assistente/resumo')->assertUnauthorized();
    }

    public function test_usuario_inativo_e_recusado(): void
    {
        $this->user->update(['status' => 'inativo']);
        $this->comoAssistente()->getJson('/api/assistente/resumo')->assertForbidden();
    }

    public function test_token_somente_leitura_nao_lanca_nem_paga(): void
    {
        $this->comoAssistente(['financeiro:read']);
        $this->lancar(['tipo' => 'gasto', 'valor' => 10, 'data' => '2026-10-01', 'descricao' => 'X'])->assertForbidden();
        $this->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson("/api/assistente/movimentacoes/{$this->compraParcelada()->id}/pagar")
            ->assertForbidden();
    }

    // --- lançamentos ---------------------------------------------------------------------------------

    public function test_lanca_gasto_com_categoria_pelo_nome_sem_diferenciar_maiusculas(): void
    {
        $this->comoAssistente()
            ->lancar(['tipo' => 'gasto', 'valor' => 45, 'data' => '2026-10-23', 'descricao' => 'Mercado', 'categoria' => 'alimentação'])
            ->assertCreated()
            ->assertJsonPath('data.tipo', 'gasto')
            ->assertJsonPath('data.valor', 45)
            ->assertJsonPath('data.categoria', 'Alimentação');

        $this->assertDatabaseHas('movimentacoes', ['user_id' => $this->user->id, 'categoria_id' => $this->alimentacao->id, 'descricao' => 'Mercado']);
    }

    public function test_categoria_omitida_usa_outros_e_desconhecida_devolve_opcoes(): void
    {
        $this->comoAssistente()
            ->lancar(['tipo' => 'ganho', 'valor' => 3000, 'data' => '2026-10-05', 'descricao' => 'Verzel'])
            ->assertCreated()
            ->assertJsonPath('data.categoria', 'Outros');

        $this->lancar(['tipo' => 'gasto', 'valor' => 10, 'data' => '2026-10-05', 'descricao' => 'X', 'categoria' => 'Viagem'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('categoria')
            ->assertJsonFragment(['categoria' => ['Categoria "Viagem" não encontrada. Opções: Alimentação, Outros.']]);
    }

    public function test_categoria_propria_tem_preferencia_sobre_a_global_e_de_outro_usuario_nao_vale(): void
    {
        $propria = Categoria::factory()->create(['user_id' => $this->user->id, 'nome' => 'Outros', 'tipo' => 'gasto']);
        $deOutro = Categoria::factory()->create(['user_id' => User::factory()->create()->id, 'nome' => 'Lazer', 'tipo' => 'gasto']);

        $this->comoAssistente()
            ->lancar(['tipo' => 'gasto', 'valor' => 10, 'data' => '2026-10-05', 'descricao' => 'X'])
            ->assertCreated();
        $this->assertDatabaseHas('movimentacoes', ['descricao' => 'X', 'categoria_id' => $propria->id]);

        $this->lancar(['tipo' => 'gasto', 'valor' => 10, 'data' => '2026-10-05', 'descricao' => 'Y', 'categoria_id' => $deOutro->id])
            ->assertUnprocessable()->assertJsonValidationErrors('categoria_id');
        $this->lancar(['tipo' => 'gasto', 'valor' => 10, 'data' => '2026-10-05', 'descricao' => 'Z', 'categoria' => 'Lazer'])
            ->assertUnprocessable()->assertJsonValidationErrors('categoria');
    }

    public function test_compra_parcelada_monta_parcelas_no_servidor_com_soma_exata(): void
    {
        $this->comoAssistente()
            ->lancar([
                'tipo' => 'gasto futuro', 'valor' => 1000, 'data' => '2026-01-20', 'descricao' => 'Notebook',
                'categoria' => 'Eletrônicos', 'parcelas' => 3, 'primeiro_vencimento' => '2026-01-31',
            ])
            ->assertCreated()
            ->assertJsonPath('data.quantidade_parcelas', 3)
            ->assertJsonPath('data.parcelas.0.valor', 333.33)
            ->assertJsonPath('data.parcelas.2.valor', 333.34)
            // Mensal sem pular mês: 31/01 → 28/02 → 31/03
            ->assertJsonPath('data.parcelas.1.data_vencimento', '2026-02-28')
            ->assertJsonPath('data.parcelas.2.data_vencimento', '2026-03-31');

        $movimentacao = Movimentacao::where('descricao', 'Notebook')->first();
        $this->assertEqualsWithDelta(1000.00, (float) $movimentacao->parcelas()->sum('valor'), 0.001);
    }

    public function test_validacoes_do_lancamento(): void
    {
        $this->comoAssistente();
        $base = ['tipo' => 'gasto futuro', 'valor' => 500, 'data' => '2026-10-01', 'descricao' => 'Sofá', 'parcelas' => 5];

        $this->lancar($base)->assertUnprocessable()
            ->assertJsonValidationErrors(['primeiro_vencimento' => 'Informe a data de vencimento da primeira parcela.']);
        $this->lancar($base + ['primeiro_vencimento' => '2026-09-01'])->assertUnprocessable()->assertJsonValidationErrors('primeiro_vencimento');
        $this->lancar(['tipo' => 'gasto', 'valor' => 5, 'data' => '0025-10-01', 'descricao' => 'X'])->assertUnprocessable()->assertJsonValidationErrors('data');
        $this->lancar(['tipo' => 'gasto', 'valor' => 5, 'data' => '2026-10-01', 'descricao' => 'X', 'parcelas' => 3])
            ->assertUnprocessable()->assertJsonValidationErrors('parcelas');
        $this->lancar(['valor' => 0.02, 'primeiro_vencimento' => '2026-11-10'] + $base)->assertUnprocessable()
            ->assertJsonValidationErrors(['valor' => 'O valor precisa ser de pelo menos 1 centavo por parcela.']);
        $this->assertDatabaseCount('movimentacoes', 0);
    }

    // --- idempotência --------------------------------------------------------------------------------

    public function test_reenvio_com_a_mesma_chave_nao_duplica(): void
    {
        $this->comoAssistente();
        $dados = ['tipo' => 'gasto', 'valor' => 45, 'data' => '2026-10-23', 'descricao' => 'Mercado', 'categoria' => 'Alimentação'];

        $primeira = $this->lancar($dados, 'chave-mercado-1')->assertCreated();
        $this->lancar($dados, 'chave-mercado-1')
            ->assertCreated()
            ->assertHeader('Idempotent-Replayed', 'true')
            ->assertJsonPath('data.id', $primeira->json('data.id'));

        $this->assertDatabaseCount('movimentacoes', 1);

        $this->lancar(['valor' => 54] + $dados, 'chave-mercado-1')->assertUnprocessable();
        $this->withHeaders(['Idempotency-Key' => ''])->postJson('/api/assistente/movimentacoes', $dados)->assertUnprocessable();
    }

    public function test_reenvio_simultaneo_recebe_409(): void
    {
        $this->comoAssistente();
        $tokenId = $this->user->currentAccessToken()->getKey();
        Cache::lock("assistente-idem:{$tokenId}:chave-ocupada:trava", 120)->get();

        $this->lancar(['tipo' => 'gasto', 'valor' => 1, 'data' => '2026-10-23', 'descricao' => 'X'], 'chave-ocupada')
            ->assertStatus(409)
            ->assertJsonPath('code', 'em_processamento')
            ->assertHeader('Retry-After', '5');
        $this->assertDatabaseCount('movimentacoes', 0);
    }

    // --- pendentes, pagamento e resumo --------------------------------------------------------------

    public function test_pendentes_incluem_atrasadas_e_excluem_pagas_e_de_outro_usuario(): void
    {
        $compra = $this->compraParcelada();
        $compra->parcelas()->where('numero', 2)->update(['pago' => true]);
        Movimentacao::factory()->create(['tipo' => 'gasto futuro'])->parcelas()->create(['numero' => 1, 'valor' => 9, 'data_vencimento' => now()->toDateString(), 'pago' => false]);

        $this->comoAssistente()
            ->getJson('/api/assistente/parcelas/pendentes?ate='.now()->endOfMonth()->toDateString())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.numero', 1)
            ->assertJsonPath('data.0.total_parcelas', 3)
            ->assertJsonPath('data.0.movimentacao.descricao', 'TV');
    }

    public function test_paga_proxima_parcela_pela_soma_e_recusa_quando_nao_ha_pendente(): void
    {
        $compra = $this->compraParcelada();
        $this->comoAssistente();
        $pagar = fn (array $dados = []) => $this->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson("/api/assistente/movimentacoes/{$compra->id}/pagar", $dados);

        $pagar(['quantidade_parcelas' => 2])->assertOk()->assertJsonPath('data.tipo', 'gasto')->assertJsonPath('data.valor', 500);
        $this->assertSame(2, $compra->parcelas()->where('pago', true)->count());

        $pagar(['quantidade_parcelas' => 2])->assertStatus(409)->assertJsonPath('message', 'Só há 1 parcela(s) pendente(s) nesta compra.');
        $pagar(['valor_total_pago' => 260])->assertOk()->assertJsonPath('data.valor', 260);
        $pagar()->assertStatus(409)
            ->assertJsonPath('message', 'Esta compra não tem parcelas pendentes.')
            ->assertJsonPath('code', 'regra_de_dominio');
    }

    public function test_pagar_ganho_ou_de_outro_usuario(): void
    {
        $ganho = Movimentacao::factory()->create(['user_id' => $this->user->id, 'categoria_id' => $this->alimentacao->id, 'tipo' => 'ganho']);
        $deOutro = Movimentacao::factory()->create(['tipo' => 'gasto futuro']);
        $this->comoAssistente();

        $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson("/api/assistente/movimentacoes/{$ganho->id}/pagar")->assertStatus(409);
        $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson("/api/assistente/movimentacoes/{$deOutro->id}/pagar")
            ->assertNotFound()
            ->assertExactJson(['message' => 'Registro não encontrado.']);
    }

    public function test_resumo_do_mes(): void
    {
        $this->compraParcelada();
        Movimentacao::factory()->create(['user_id' => $this->user->id, 'categoria_id' => $this->alimentacao->id, 'tipo' => 'gasto', 'valor' => 100, 'data' => now()->toDateString()]);
        Movimentacao::factory()->create(['user_id' => $this->user->id, 'tipo' => 'ganho', 'valor' => 3000, 'data' => now()->toDateString()]);

        $this->comoAssistente()
            ->getJson('/api/assistente/resumo?mes='.now()->format('Y-m'))
            ->assertOk()
            ->assertJsonPath('data.mes', now()->format('Y-m'))
            ->assertJsonPath('data.ganhos', 3000)
            ->assertJsonPath('data.gastos', 100)
            ->assertJsonPath('data.saldo', 2900)
            ->assertJsonPath('data.a_pagar', 250)
            ->assertJsonPath('data.gastos_por_categoria.0.categoria', 'Alimentação');
    }

    public function test_erro_inesperado_nao_expoe_trace_mesmo_com_debug(): void
    {
        Config::set('app.debug', true);
        $this->mock(\App\Services\DashboardService::class)->shouldReceive('getTotaisDoMes')->andThrow(new \RuntimeException('SQLSTATE segredo'));

        $this->comoAssistente()->get('/api/assistente/resumo')->assertStatus(500)->assertExactJson(['message' => 'Erro interno do servidor.']);
    }

    public function test_limite_de_requisicoes(): void
    {
        $this->comoAssistente();
        for ($i = 0; $i < 60; $i++) {
            $this->getJson('/api/assistente/categorias')->assertOk();
        }
        $this->getJson('/api/assistente/categorias')->assertTooManyRequests()->assertHeader('Retry-After');
    }

    public function test_limite_por_ip_contem_tentativas_com_token_invalido(): void
    {
        for ($i = 0; $i < \App\Http\Middleware\LimiteDoAssistentePorIp::MAXIMO_POR_MINUTO; $i++) {
            $this->withToken('invalido-'.$i)->getJson('/api/assistente/categorias')->assertUnauthorized();
        }

        $this->withToken('invalido')->getJson('/api/assistente/categorias')->assertTooManyRequests()->assertHeader('Retry-After');
    }

    public function test_comando_de_token(): void
    {
        $this->artisan('assistente:token', ['email' => $this->user->email])->assertSuccessful();
        $this->artisan('assistente:token', ['email' => $this->user->email])->assertSuccessful();
        $this->artisan('assistente:token', ['email' => $this->user->email, '--somente-leitura' => true])->assertSuccessful();

        $tokens = PersonalAccessToken::orderBy('id')->get();
        $this->assertCount(2, $tokens);
        $this->assertSame(['financeiro:read', 'financeiro:write'], $tokens[0]->abilities);
        $this->assertSame(['financeiro:read'], $tokens[1]->abilities);
        $this->assertSame('assistente:logan:leitura', $tokens[1]->name);
        $this->assertTrue($tokens[0]->expires_at->between(now()->addDays(179), now()->addDays(181)));

        $this->artisan('assistente:token', ['email' => 'ninguem@exemplo.com'])->assertFailed();
    }
}
