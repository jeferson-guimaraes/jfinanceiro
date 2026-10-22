<?php

namespace Tests\Feature\Usuarios;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AlterarSenhaUsuarioTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->usuario = User::factory()->user()->create(['name' => 'Maria Silva']);
    }

    /**
     * @param  array<string, string>  $sobrescritas
     * @return array<string, string>
     */
    private function dados(array $sobrescritas = []): array
    {
        return array_merge([
            'password' => 'NovaSenha@2026',
            'password_confirmation' => 'NovaSenha@2026',
            'senha_administrador' => 'password',
        ], $sobrescritas);
    }

    public function test_admin_altera_a_senha_de_usuario_padrao(): void
    {
        $tokenAnterior = $this->usuario->remember_token;

        $this->actingAs($this->admin)
            ->from(route('usuarios.index'))
            ->patch(route('usuarios.senha.update', $this->usuario), $this->dados())
            ->assertRedirect(route('usuarios.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Senha de Maria Silva alterada. O usuário foi desconectado de todos os dispositivos.');

        $this->usuario->refresh();
        $this->assertTrue(Hash::check('NovaSenha@2026', $this->usuario->password));
        $this->assertFalse(Hash::check('password', $this->usuario->password));
        $this->assertNotSame($tokenAnterior, $this->usuario->remember_token);
    }

    public function test_usuario_e_desconectado_de_todos_os_dispositivos(): void
    {
        config(['session.driver' => 'database']);

        foreach ([$this->usuario->id, $this->usuario->id, $this->admin->id] as $indice => $userId) {
            DB::table('sessions')->insert([
                'id' => "sessao-{$indice}",
                'user_id' => $userId,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'teste',
                'payload' => '',
                'last_activity' => now()->timestamp,
            ]);
        }

        $this->actingAs($this->admin)
            ->patch(route('usuarios.senha.update', $this->usuario), $this->dados())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('sessions', ['user_id' => $this->usuario->id]);
        $this->assertDatabaseHas('sessions', ['id' => 'sessao-2', 'user_id' => $this->admin->id]);
    }

    public function test_alteracao_fica_registrada_no_log(): void
    {
        Log::spy();

        $this->actingAs($this->admin)
            ->patch(route('usuarios.senha.update', $this->usuario), $this->dados())
            ->assertSessionHasNoErrors();

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Senha de usuário alterada por administrador.', [
                'usuario_id' => $this->usuario->id,
                'administrador_id' => $this->admin->id,
            ]);
    }

    public function test_senha_do_administrador_incorreta_impede_a_alteracao(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('usuarios.senha.update', $this->usuario), $this->dados(['senha_administrador' => 'errada']))
            ->assertSessionHasErrors(['senha_administrador' => 'A sua senha está incorreta.']);

        $this->assertTrue(Hash::check('password', $this->usuario->fresh()->password));
    }

    /**
     * @return array<string, array{array<string, string>, string}>
     */
    public static function dadosInvalidos(): array
    {
        return [
            'sem nova senha' => [['password' => '', 'password_confirmation' => ''], 'password'],
            'senha curta' => [['password' => 'curta', 'password_confirmation' => 'curta'], 'password'],
            'confirmacao diferente' => [['password_confirmation' => 'OutraSenha@2026'], 'password'],
            'sem senha do administrador' => [['senha_administrador' => ''], 'senha_administrador'],
        ];
    }

    /**
     * @param  array<string, string>  $sobrescritas
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('dadosInvalidos')]
    public function test_rejeita_dados_invalidos(array $sobrescritas, string $campo): void
    {
        $this->actingAs($this->admin)
            ->patch(route('usuarios.senha.update', $this->usuario), $this->dados($sobrescritas))
            ->assertSessionHasErrors($campo);

        $this->assertTrue(Hash::check('password', $this->usuario->fresh()->password));
    }

    public function test_admin_nao_altera_a_senha_de_outro_administrador(): void
    {
        $outroAdmin = User::factory()->admin()->create();

        $this->actingAs($this->admin)
            ->patch(route('usuarios.senha.update', $outroAdmin), $this->dados())
            ->assertForbidden();

        $this->assertTrue(Hash::check('password', $outroAdmin->fresh()->password));
    }

    public function test_admin_nao_altera_a_propria_senha_por_aqui(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('usuarios.senha.update', $this->admin), $this->dados())
            ->assertForbidden();

        $this->assertTrue(Hash::check('password', $this->admin->fresh()->password));
    }

    public function test_usuario_padrao_nao_altera_a_senha_de_outro_usuario(): void
    {
        $outroUsuario = User::factory()->user()->create();

        $this->actingAs($this->usuario)
            ->patch(route('usuarios.senha.update', $outroUsuario), $this->dados())
            ->assertForbidden();

        $this->assertTrue(Hash::check('password', $outroUsuario->fresh()->password));
    }

    public function test_visitante_e_redirecionado_para_o_login(): void
    {
        $this->patch(route('usuarios.senha.update', $this->usuario), $this->dados())
            ->assertRedirect(route('login'));
    }

    public function test_usuario_inexistente_retorna_404(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('usuarios.senha.update', 999999), $this->dados())
            ->assertNotFound();
    }
}
