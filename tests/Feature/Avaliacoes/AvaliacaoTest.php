<?php

namespace Tests\Feature\Avaliacoes;

use App\Http\Controllers\AvaliacaoController;
use App\Mail\NovaAvaliacaoMail;
use App\Models\Avaliacao;
use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Rules\RecaptchaValido;
use Database\Seeders\AvaliacaoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use Tests\TestCase;

class AvaliacaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        config(['mail.contato.address' => 'contato@jfinanceiro.com.br']);
    }

    /**
     * @param  array<string, mixed>  $sobrescritas
     * @return array<string, mixed>
     */
    private function dadosValidos(array $sobrescritas = []): array
    {
        return array_merge([
            'nome' => 'Maria Silva',
            'nota' => 5,
            'mensagem' => 'Uso todos os dias para controlar minhas contas.',
            'publicacao_permitida' => true,
            'recaptcha_token' => 'token-do-navegador',
        ], $sobrescritas);
    }

    private function configuraRecaptcha(): void
    {
        config([
            'app.url' => 'https://jfinanceiro.com.br',
            'services.recaptcha.secret_key' => 'segredo-de-teste',
            'services.recaptcha.score_minimo' => 0.5,
            'services.recaptcha.hostnames' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $resposta
     */
    private function googleResponde(array $resposta): void
    {
        Http::fake([RecaptchaValido::URL_VERIFICACAO => Http::response($resposta)]);
    }

    public function test_pagina_de_avaliacao_e_publica(): void
    {
        config(['services.recaptcha.site_key' => 'chave-publica']);

        $this->get(route('avaliacoes.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('avaliacoes/Create')
                ->where('nomeSugerido', null)
                ->where('recaptchaSiteKey', 'chave-publica')
            );
    }

    public function test_pagina_sugere_o_nome_do_usuario_logado(): void
    {
        $user = User::factory()->create(['name' => 'Joao Pereira']);

        $this->actingAs($user)
            ->get(route('avaliacoes.create'))
            ->assertInertia(fn ($page) => $page->where('nomeSugerido', 'Joao Pereira'));
    }

    public function test_visitante_envia_avaliacao_e_equipe_recebe_email(): void
    {
        $response = $this->post(route('avaliacoes.store'), $this->dadosValidos());

        $response->assertRedirect(route('avaliacoes.create'));
        $response->assertSessionHas('success', 'Obrigado pela sua avaliação!');

        $avaliacao = Avaliacao::sole();
        $this->assertSame('Maria Silva', $avaliacao->nome);
        $this->assertSame(5, $avaliacao->nota);
        $this->assertTrue($avaliacao->publicacao_permitida);
        $this->assertNull($avaliacao->aprovada_em);
        $this->assertNull($avaliacao->user_id);

        Mail::assertSent(NovaAvaliacaoMail::class, function (NovaAvaliacaoMail $mail) use ($avaliacao) {
            return $mail->hasTo('contato@jfinanceiro.com.br')
                && $mail->avaliacao->is($avaliacao)
                && $mail->hasSubject('Nova avaliação no JFinanceiro (5 estrelas)');
        });
    }

    public function test_usuario_logado_tem_a_avaliacao_vinculada_a_conta(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('avaliacoes.store'), $this->dadosValidos());

        $this->assertSame($user->id, Avaliacao::sole()->user_id);
    }

    public function test_avaliacao_sem_permissao_de_publicacao_e_salva_mesmo_assim(): void
    {
        $this->post(route('avaliacoes.store'), $this->dadosValidos(['publicacao_permitida' => false]))
            ->assertSessionHasNoErrors();

        $this->assertFalse(Avaliacao::sole()->publicacao_permitida);
    }

    public function test_email_mostra_texto_do_visitante_sem_formatacao(): void
    {
        $avaliacao = Avaliacao::factory()->create([
            'nome' => 'Maria <b>Silva</b>',
            'mensagem' => 'Veja [aqui](https://phishing.example) e **isso**',
        ]);

        $html = (new NovaAvaliacaoMail($avaliacao))->render();

        $this->assertStringNotContainsString('href="https://phishing.example"', $html);
        $this->assertStringNotContainsString('<b>Silva</b>', $html);
        $this->assertStringNotContainsString('<strong>isso</strong>', $html);
        $this->assertStringContainsString('phishing', $html);
    }

    public function test_texto_do_visitante_mantem_pontuacao_comum_e_neutraliza_markdown(): void
    {
        $this->assertSame('Muito bom. Recomendo, nota 10!', NovaAvaliacaoMail::textoLiteral('Muito bom. Recomendo, nota 10!'));
        $this->assertSame('Custa R$ 10.50 - barato', NovaAvaliacaoMail::textoLiteral('Custa R$ 10.50 - barato'));
        $this->assertSame('Ver \\[site\\]\\(https://x.example\\)', NovaAvaliacaoMail::textoLiteral('Ver [site](https://x.example)'));
        $this->assertSame('!\\[foto\\]\\(https://x.example/a.png\\)', NovaAvaliacaoMail::textoLiteral('![foto](https://x.example/a.png)'));
        $this->assertSame('\\# Título', NovaAvaliacaoMail::textoLiteral('# Título'));
        $this->assertSame('1\\. item', NovaAvaliacaoMail::textoLiteral('1. item'));
        $this->assertSame('linha 1<br>'."\n".'\\- item', NovaAvaliacaoMail::textoLiteral("linha 1\n- item"));
    }

    public function test_falha_no_envio_do_email_nao_perde_a_avaliacao(): void
    {
        Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP fora do ar'));

        $this->post(route('avaliacoes.store'), $this->dadosValidos())
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseCount('avaliacoes', 1);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function dadosInvalidos(): array
    {
        return [
            'sem nome' => [['nome' => ''], 'nome'],
            'sem sobrenome' => [['nome' => 'Maria'], 'nome'],
            'nome longo' => [['nome' => 'Maria '.str_repeat('a', 120)], 'nome'],
            'nota zero' => [['nota' => 0], 'nota'],
            'nota seis' => [['nota' => 6], 'nota'],
            'sem nota' => [['nota' => null], 'nota'],
            'mensagem curta' => [['mensagem' => 'Bom'], 'mensagem'],
            'mensagem longa' => [['mensagem' => str_repeat('a', 1001)], 'mensagem'],
            'sem permissao informada' => [['publicacao_permitida' => null], 'publicacao_permitida'],
            'campo armadilha preenchido' => [['site' => 'https://spam.example'], 'site'],
        ];
    }

    /**
     * @param  array<string, mixed>  $sobrescritas
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('dadosInvalidos')]
    public function test_rejeita_dados_invalidos(array $sobrescritas, string $campo): void
    {
        $this->post(route('avaliacoes.store'), $this->dadosValidos($sobrescritas))
            ->assertSessionHasErrors($campo);

        $this->assertDatabaseCount('avaliacoes', 0);
        Mail::assertNothingSent();
    }

    public function test_recaptcha_aprovado_permite_o_envio(): void
    {
        $this->configuraRecaptcha();
        $this->googleResponde(['success' => true, 'action' => 'avaliacao', 'score' => 0.9, 'hostname' => 'jfinanceiro.com.br']);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
            ->post(route('avaliacoes.store'), $this->dadosValidos())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('avaliacoes', 1);
        Http::assertSent(fn (Request $request) => $request->url() === RecaptchaValido::URL_VERIFICACAO
            && $request['secret'] === 'segredo-de-teste'
            && $request['response'] === 'token-do-navegador'
            && $request['remoteip'] === '203.0.113.7');
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function respostasReprovadas(): array
    {
        return [
            'nota abaixo do minimo' => [['success' => true, 'action' => 'avaliacao', 'score' => 0.3, 'hostname' => 'jfinanceiro.com.br']],
            'acao diferente' => [['success' => true, 'action' => 'login', 'score' => 0.9, 'hostname' => 'jfinanceiro.com.br']],
            'token invalido' => [['success' => false, 'error-codes' => ['invalid-input-response']]],
            'resposta sem nota' => [['success' => true, 'action' => 'avaliacao', 'hostname' => 'jfinanceiro.com.br']],
            'gerado em outro dominio' => [['success' => true, 'action' => 'avaliacao', 'score' => 0.9, 'hostname' => 'site-copiado.example']],
            'resposta sem dominio' => [['success' => true, 'action' => 'avaliacao', 'score' => 0.9]],
        ];
    }

    /**
     * @param  array<string, mixed>  $resposta
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('respostasReprovadas')]
    public function test_recaptcha_reprovado_bloqueia_o_envio(array $resposta): void
    {
        $this->configuraRecaptcha();
        $this->googleResponde($resposta);

        $this->post(route('avaliacoes.store'), $this->dadosValidos())
            ->assertSessionHasErrors(['recaptcha_token' => RecaptchaValido::MENSAGEM]);

        $this->assertDatabaseCount('avaliacoes', 0);
    }

    public function test_recaptcha_aceita_o_dominio_com_e_sem_www(): void
    {
        $this->configuraRecaptcha();

        $this->assertSame(
            ['jfinanceiro.com.br', 'www.jfinanceiro.com.br'],
            RecaptchaValido::hostnamesPermitidos(),
        );

        $this->googleResponde(['success' => true, 'action' => 'avaliacao', 'score' => 0.9, 'hostname' => 'www.jfinanceiro.com.br']);
        $this->post(route('avaliacoes.store'), $this->dadosValidos())->assertSessionHasNoErrors();

        $this->assertDatabaseCount('avaliacoes', 1);
    }

    public function test_dominios_configurados_substituem_o_padrao(): void
    {
        $this->configuraRecaptcha();
        config(['services.recaptcha.hostnames' => 'app.jfinanceiro.com.br, localhost']);

        $this->assertSame(['app.jfinanceiro.com.br', 'localhost'], RecaptchaValido::hostnamesPermitidos());
    }

    public function test_formulario_invalido_nao_consulta_o_google(): void
    {
        $this->configuraRecaptcha();
        Http::fake();

        $this->post(route('avaliacoes.store'), $this->dadosValidos(['nome' => 'Maria']))
            ->assertSessionHasErrors('nome')
            ->assertSessionDoesntHaveErrors('recaptcha_token');

        Http::assertNothingSent();
    }

    public function test_recaptcha_sem_token_bloqueia_o_envio(): void
    {
        $this->configuraRecaptcha();
        Http::fake();

        $this->post(route('avaliacoes.store'), $this->dadosValidos(['recaptcha_token' => null]))
            ->assertSessionHasErrors('recaptcha_token');

        Http::assertNothingSent();
        $this->assertDatabaseCount('avaliacoes', 0);
    }

    public function test_falha_de_rede_no_recaptcha_bloqueia_o_envio(): void
    {
        $this->configuraRecaptcha();
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $this->post(route('avaliacoes.store'), $this->dadosValidos())
            ->assertSessionHasErrors('recaptcha_token');

        $this->assertDatabaseCount('avaliacoes', 0);
    }

    public function test_erro_do_servidor_do_google_bloqueia_o_envio(): void
    {
        $this->configuraRecaptcha();
        Http::fake([RecaptchaValido::URL_VERIFICACAO => Http::response('erro', 500)]);

        $this->post(route('avaliacoes.store'), $this->dadosValidos())
            ->assertSessionHasErrors('recaptcha_token');
    }

    public function test_em_producao_sem_secret_configurado_a_verificacao_reprova(): void
    {
        config(['services.recaptcha.secret_key' => null]);
        Http::fake();
        $ambienteOriginal = $this->app['env'];
        $this->app['env'] = 'production';

        try {
            $validador = Validator::make(
                ['recaptcha_token' => 'token-do-navegador'],
                ['recaptcha_token' => [new RecaptchaValido('avaliacao')]],
            );

            $this->assertTrue($validador->fails());
            $this->assertSame(RecaptchaValido::MENSAGEM, $validador->errors()->first('recaptcha_token'));
        } finally {
            $this->app['env'] = $ambienteOriginal;
        }

        Http::assertNothingSent();
    }

    public function test_em_ambiente_de_testes_sem_secret_a_verificacao_e_dispensada(): void
    {
        config(['services.recaptcha.secret_key' => null]);

        $validador = Validator::make(
            ['recaptcha_token' => null],
            ['recaptcha_token' => [new RecaptchaValido('avaliacao')]],
        );

        $this->assertFalse($validador->fails());
    }

    public function test_limita_tres_avaliacoes_salvas_por_hora_por_ip(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->post(route('avaliacoes.store'), $this->dadosValidos())->assertSessionHasNoErrors();
        }

        $this->from(route('avaliacoes.create'))
            ->post(route('avaliacoes.store'), $this->dadosValidos())
            ->assertRedirect(route('avaliacoes.create'))
            ->assertSessionHasErrors(['envio' => AvaliacaoController::MENSAGEM_LIMITE]);

        $this->assertDatabaseCount('avaliacoes', 3);
        Mail::assertSentCount(3);
    }

    public function test_erros_de_preenchimento_nao_consomem_o_limite_de_avaliacoes(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('avaliacoes.store'), $this->dadosValidos(['nome' => 'Maria']))->assertSessionHasErrors('nome');
        }

        $this->post(route('avaliacoes.store'), $this->dadosValidos())->assertSessionHasNoErrors();

        $this->assertDatabaseCount('avaliacoes', 1);
    }

    public function test_limita_vinte_tentativas_por_hora_por_ip(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->post(route('avaliacoes.store'), $this->dadosValidos(['nome' => 'Maria']));
        }

        $this->from(route('avaliacoes.create'))
            ->post(route('avaliacoes.store'), $this->dadosValidos())
            ->assertRedirect(route('avaliacoes.create'))
            ->assertSessionHasErrors(['envio' => AppServiceProvider::MENSAGEM_LIMITE_TENTATIVAS]);

        $this->assertDatabaseCount('avaliacoes', 0);
    }

    public function test_landing_nao_exibe_avaliacoes_com_apenas_uma_publicavel(): void
    {
        Avaliacao::factory()->publicavel()->create();
        Avaliacao::factory()->pendente()->count(3)->create();

        $this->get(route('home'))->assertInertia(fn ($page) => $page->has('avaliacoes', 0));
    }

    public function test_landing_exibe_avaliacoes_a_partir_de_duas_publicaveis(): void
    {
        Avaliacao::factory()->publicavel()->create(['nome' => 'Maria Aparecida Silva', 'nota' => 5]);
        Avaliacao::factory()->publicavel()->create(['nome' => 'Joao Souza', 'nota' => 4]);

        $response = $this->get(route('home'));

        $response->assertInertia(fn ($page) => $page->has('avaliacoes', 2));
        $nomes = collect($response->viewData('page')['props']['avaliacoes'])->pluck('nome')->sort()->values()->all();
        $this->assertSame(['Joao S.', 'Maria S.'], $nomes);
    }

    public function test_landing_ignora_avaliacoes_pendentes_ou_sem_permissao(): void
    {
        $publicaveis = Avaliacao::factory()->publicavel()->count(2)->create();
        Avaliacao::factory()->pendente()->create();
        Avaliacao::factory()->create(['publicacao_permitida' => false, 'aprovada_em' => now()]);

        $ids = collect($this->get(route('home'))->viewData('page')['props']['avaliacoes'])->pluck('id')->sort()->values()->all();

        $this->assertSame($publicaveis->pluck('id')->sort()->values()->all(), $ids);
    }

    public function test_landing_exibe_no_maximo_as_seis_mais_recentes(): void
    {
        $antiga = Avaliacao::factory()->publicavel()->create(['created_at' => now()->subYear()]);
        Avaliacao::factory()->publicavel()->count(6)->create();

        $ids = collect($this->get(route('home'))->viewData('page')['props']['avaliacoes'])->pluck('id');

        $this->assertCount(6, $ids);
        $this->assertNotContains($antiga->id, $ids);
    }

    public function test_landing_nao_expoe_nome_completo_nem_conta(): void
    {
        $user = User::factory()->create();
        Avaliacao::factory()->publicavel()->count(2)->create(['user_id' => $user->id, 'nome' => 'Maria Aparecida Silva']);

        $avaliacao = $this->get(route('home'))->viewData('page')['props']['avaliacoes'][0];

        $this->assertSame(['id', 'nome', 'nota', 'mensagem'], array_keys($avaliacao));
        $this->assertSame('Maria S.', $avaliacao['nome']);
    }

    public function test_seeder_cadastra_as_avaliacoes_iniciais_aprovadas_sem_duplicar(): void
    {
        $this->seed(AvaliacaoSeeder::class);
        $this->seed(AvaliacaoSeeder::class);

        $this->assertDatabaseCount('avaliacoes', 2);
        $this->assertSame(2, Avaliacao::publicaveis()->count());
        $this->assertSame([5, 5], Avaliacao::pluck('nota')->all());

        $nomes = collect($this->get(route('home'))->viewData('page')['props']['avaliacoes'])->pluck('nome')->sort()->values()->all();
        $this->assertSame(['Gabriele R.', 'Jeferson G.'], $nomes);
    }

    public function test_admin_ve_a_lista_de_avaliacoes(): void
    {
        $admin = User::factory()->admin()->create();
        Avaliacao::factory()->count(3)->create();

        $this->actingAs($admin)
            ->get(route('avaliacoes.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('avaliacoes/Index')
                ->has('avaliacoes.data', 3)
            );
    }

    public function test_admin_aprova_e_retira_avaliacao_do_site(): void
    {
        $admin = User::factory()->admin()->create();
        $avaliacao = Avaliacao::factory()->pendente()->create();

        $this->actingAs($admin)->patch(route('avaliacoes.aprovar', $avaliacao))->assertRedirect();
        $this->assertNotNull($avaliacao->fresh()->aprovada_em);

        $this->actingAs($admin)->patch(route('avaliacoes.reprovar', $avaliacao))->assertRedirect();
        $this->assertNull($avaliacao->fresh()->aprovada_em);
    }

    public function test_admin_nao_aprova_avaliacao_sem_permissao_de_publicacao(): void
    {
        $admin = User::factory()->admin()->create();
        $avaliacao = Avaliacao::factory()->create(['publicacao_permitida' => false]);

        $this->actingAs($admin)->patch(route('avaliacoes.aprovar', $avaliacao))->assertStatus(422);

        $this->assertNull($avaliacao->fresh()->aprovada_em);
    }

    public function test_usuario_comum_nao_acessa_a_moderacao(): void
    {
        $user = User::factory()->create();
        $avaliacao = Avaliacao::factory()->pendente()->create();

        $this->actingAs($user)->get(route('avaliacoes.index'))->assertForbidden();
        $this->actingAs($user)->patch(route('avaliacoes.aprovar', $avaliacao))->assertForbidden();

        $this->assertNull($avaliacao->fresh()->aprovada_em);
    }

    public function test_usuario_comum_nao_retira_avaliacao_do_site(): void
    {
        $user = User::factory()->create();
        $avaliacao = Avaliacao::factory()->publicavel()->create();

        $this->actingAs($user)->patch(route('avaliacoes.reprovar', $avaliacao))->assertForbidden();

        $this->assertNotNull($avaliacao->fresh()->aprovada_em);
    }

    public function test_visitante_nao_consegue_forjar_conta_nem_aprovacao(): void
    {
        $outroUsuario = User::factory()->create();

        $this->post(route('avaliacoes.store'), $this->dadosValidos([
            'user_id' => $outroUsuario->id,
            'aprovada_em' => now()->toDateTimeString(),
        ]))->assertSessionHasNoErrors();

        $avaliacao = Avaliacao::sole();
        $this->assertNull($avaliacao->user_id);
        $this->assertNull($avaliacao->aprovada_em);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function nomesParaAbreviar(): array
    {
        return [
            'nome e sobrenome' => ['Maria Silva', 'Maria S.'],
            'nome composto' => ['Ana Maria da Silva Jr.', 'Ana J.'],
            'espacos extras' => ['  Joao    Pereira  ', 'Joao P.'],
            'sobrenome acentuado' => ['Jeferson Ávila', 'Jeferson Á.'],
            'sobrenome minusculo' => ['gabriele ribeiro', 'gabriele R.'],
            'nome unico' => ['Maria', 'Maria'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('nomesParaAbreviar')]
    public function test_nome_abreviado_para_exibicao_publica(string $nome, string $esperado): void
    {
        $this->assertSame($esperado, (new Avaliacao(['nome' => $nome]))->nomeAbreviado());
    }

    public function test_visitante_nao_acessa_a_moderacao(): void
    {
        $this->get(route('avaliacoes.index'))->assertRedirect(route('login'));
    }
}
