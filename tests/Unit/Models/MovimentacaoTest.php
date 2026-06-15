<?php

namespace Tests\Unit\Models;

use App\Enums\TipoMovimentacaoEnum;
use App\Models\Movimentacao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MovimentacaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_do_usuario()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Movimentacao::factory()->count(3)->create(['user_id' => $user1->id]);
        Movimentacao::factory()->count(2)->create(['user_id' => $user2->id]);

        $this->assertEquals(3, Movimentacao::doUsuario($user1->id)->count());
        $this->assertEquals(2, Movimentacao::doUsuario($user2->id)->count());
    }

    public function test_scope_do_mes()
    {
        $user = User::factory()->create();

        Movimentacao::factory()->create([
            'user_id' => $user->id,
            'data' => '2026-05-15',
        ]);

        Movimentacao::factory()->create([
            'user_id' => $user->id,
            'data' => '2026-05-20',
        ]);

        Movimentacao::factory()->create([
            'user_id' => $user->id,
            'data' => '2026-06-01',
        ]);

        $this->assertEquals(2, Movimentacao::doMes(5, 2026)->count());
        $this->assertEquals(1, Movimentacao::doMes(6, 2026)->count());
    }

    public function test_scope_ganhos()
    {
        $user = User::factory()->create();

        Movimentacao::factory()->count(2)->create([
            'user_id' => $user->id,
            'tipo' => TipoMovimentacaoEnum::GANHO,
        ]);

        Movimentacao::factory()->create([
            'user_id' => $user->id,
            'tipo' => TipoMovimentacaoEnum::GASTO,
        ]);

        $this->assertEquals(2, Movimentacao::ganhos()->count());
    }

    public function test_scope_gastos()
    {
        $user = User::factory()->create();

        Movimentacao::factory()->count(3)->create([
            'user_id' => $user->id,
            'tipo' => TipoMovimentacaoEnum::GASTO,
        ]);

        Movimentacao::factory()->create([
            'user_id' => $user->id,
            'tipo' => TipoMovimentacaoEnum::GANHO,
        ]);

        $this->assertEquals(3, Movimentacao::gastos()->count());
    }

    public function test_scope_gastos_futuros()
    {
        $user = User::factory()->create();

        Movimentacao::factory()->count(4)->create([
            'user_id' => $user->id,
            'tipo' => TipoMovimentacaoEnum::GASTO_FUTURO,
        ]);

        Movimentacao::factory()->create([
            'user_id' => $user->id,
            'tipo' => TipoMovimentacaoEnum::GASTO,
        ]);

        $this->assertEquals(4, Movimentacao::gastosFuturos()->count());
    }
}
