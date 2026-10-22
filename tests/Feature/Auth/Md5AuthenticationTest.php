<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class Md5AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_com_senha_md5_pode_autenticar_e_migrar_hash()
    {
        $password = 'secret123';
        $md5Password = md5($password);

        // Cria um usuário manualmente com a senha em MD5
        // Nota: O cast 'hashed' no Model pode tentar hashear o valor ao salvar.
        // Precisamos usar forceFill para garantir que o MD5 seja salvo literalmente se possível,
        // ou desabilitar o cast temporariamente se ele interferir.
        $user = User::factory()->withoutTwoFactor()->create();

        // Vamos usar DB directly para garantir que o valor seja MD5 puro no banco
        \DB::table('users')->where('id', $user->id)->update(['password' => $md5Password]);

        $user->refresh();
        $this->assertEquals($md5Password, $user->password);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => $password,
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));

        // Verifica se a senha foi migrada
        $user->refresh();
        $this->assertNotEquals($md5Password, $user->password);
        $this->assertTrue(Hash::check($password, $user->password));
    }

    public function test_usuario_com_senha_md5_e_senha_errada_recebe_credenciais_invalidas(): void
    {
        $user = User::factory()->withoutTwoFactor()->create();
        \DB::table('users')->where('id', $user->id)->update(['password' => md5('secret123')]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'senha-errada',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertSame(md5('secret123'), $user->fresh()->password);
    }

    public function test_usuario_com_md5_em_maiusculas_autentica_e_migra(): void
    {
        $user = User::factory()->withoutTwoFactor()->create();
        \DB::table('users')->where('id', $user->id)->update(['password' => strtoupper(md5('secret123'))]);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'secret123'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('secret123', $user->fresh()->password));
    }

    public function test_senha_em_formato_desconhecido_recusa_o_login_sem_erro(): void
    {
        Log::spy();
        $user = User::factory()->withoutTwoFactor()->create();
        \DB::table('users')->where('id', $user->id)->update(['password' => sha1('secret123')]);

        $this->from(route('login'))
            ->post(route('login.store'), ['email' => $user->email, 'password' => 'secret123'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        Log::shouldHaveReceived('warning')->once()->with(
            'Login recusado: senha do usuário em formato não reconhecido.',
            ['usuario_id' => $user->id],
        );
    }

    public function test_usuario_migrado_com_senha_errada_recebe_credenciais_invalidas(): void
    {
        $user = User::factory()->withoutTwoFactor()->create(['password' => 'secret123']);

        $this->from(route('login'))
            ->post(route('login.store'), ['email' => $user->email, 'password' => 'senha-errada'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_email_inexistente_recebe_credenciais_invalidas(): void
    {
        $this->from(route('login'))
            ->post(route('login.store'), ['email' => 'ninguem@example.com', 'password' => 'secret123'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_usuario_com_senha_hash_padrao_pode_autenticar_normalmente()
    {
        $password = 'secret123';
        $user = User::factory()->withoutTwoFactor()->create([
            'password' => $password, // O cast 'hashed' cuidará disso
        ]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => $password,
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));

        // A senha deve permanecer hasheada corretamente
        $user->refresh();
        $this->assertTrue(Hash::check($password, $user->password));
    }
}
