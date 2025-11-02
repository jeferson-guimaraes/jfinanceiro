<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tela_de_registro_de_usuario_pode_ser_renderizada()
    {
        $response = $this->get(route('register'));

        $response->assertStatus(200);
    }

    public function test_novos_usuarios_podem_ser_registrados()
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'user',
            'status' => 'ativo',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_usuario_admin_pode_ser_registrado()
    {
        $this->withoutExceptionHandling();
        $response = $this->post(route('register.store'), [
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'status' => 'ativo',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
