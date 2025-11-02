<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pagina_de_configuracoes_de_dois_fatores_pode_ser_renderizada()
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two-factor authentication is not enabled.');
        }

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $user = User::factory()->withoutTwoFactor()->create();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('two-factor.show'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/TwoFactor')
                ->where('twoFactorEnabled', false)
            );
    }

    public function test_pagina_de_configuracoes_de_dois_fatores_exige_confirmacao_de_senha_quando_ativada()
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two-factor authentication is not enabled.');
        }

        $user = User::factory()->create();

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $response = $this->actingAs($user)
            ->get(route('two-factor.show'));

        $response->assertRedirect(route('password.confirm'));
    }

    public function test_página_de_configuracoes_de_dois_fatores_nao_exige_confirmacao_de_senha_quando_desativada()
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two-factor authentication is not enabled.');
        }

        $user = User::factory()->create();

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => false,
        ]);

        $this->actingAs($user)
            ->get(route('two-factor.show'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/TwoFactor')
            );
    }

    public function test_pagina_de_configuracoes_de_dois_fatores_retorna_uma_resposta_proibida_quando_os_dois_fatores_estão_desativados()
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two-factor authentication is not enabled.');
        }

        config(['fortify.features' => []]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('two-factor.show'))
            ->assertForbidden();
    }
}
