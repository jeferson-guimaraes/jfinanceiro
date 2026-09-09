<?php

namespace Tests\Feature\Usuarios;

use App\Enums\RoleEnum;
use App\Enums\StatusEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsuarioIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_pode_acessar_listagem_de_usuarios(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Admin Sistema',
        ]);
        User::factory()->user()->create([
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
        ]);

        $response = $this->actingAs($admin)->get(route('usuarios.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('usuarios/Index')
            ->has('usuarios.data', 2)
            ->has('filters')
            ->where('filters.per_page', 30)
        );
    }

    public function test_usuario_comum_nao_pode_acessar_listagem_de_usuarios(): void
    {
        $usuario = User::factory()->user()->create();

        $response = $this->actingAs($usuario)->get(route('usuarios.index'));

        $response->assertForbidden();
    }

    public function test_visitante_e_redirecionado_para_login(): void
    {
        $response = $this->get(route('usuarios.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_filtra_usuarios_por_busca(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Admin Sistema',
            'email' => 'admin@example.com',
        ]);

        User::factory()->user()->create([
            'name' => 'João Busca',
            'email' => 'joao@example.com',
        ]);

        User::factory()->user()->create([
            'name' => 'Outro Usuario',
            'email' => 'outro@example.com',
        ]);

        $response = $this->actingAs($admin)->get(route('usuarios.index', [
            'search' => 'João',
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('usuarios/Index')
            ->has('usuarios.data', 1)
            ->where('usuarios.data.0.name', 'João Busca')
            ->where('filters.search', 'João')
        );
    }

    public function test_filtra_usuarios_por_role(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->user()->count(2)->create();

        $response = $this->actingAs($admin)->get(route('usuarios.index', [
            'role' => RoleEnum::ADMIN->value,
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('usuarios/Index')
            ->has('usuarios.data', 1)
            ->where('usuarios.data.0.role', RoleEnum::ADMIN->value)
            ->where('filters.role', RoleEnum::ADMIN->value)
        );
    }

    public function test_filtra_usuarios_por_status(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->user()->create();
        $inativo = User::factory()->user()->inactive()->create([
            'name' => 'Usuario Inativo',
        ]);

        $response = $this->actingAs($admin)->get(route('usuarios.index', [
            'status' => StatusEnum::INATIVO->value,
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('usuarios/Index')
            ->has('usuarios.data', 1)
            ->where('usuarios.data.0.id', $inativo->id)
            ->where('usuarios.data.0.status', StatusEnum::INATIVO->value)
            ->where('filters.status', StatusEnum::INATIVO->value)
        );
    }
}
