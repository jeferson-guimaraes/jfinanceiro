<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
