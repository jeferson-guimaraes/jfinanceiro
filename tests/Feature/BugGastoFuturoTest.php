<?php

namespace Tests\Feature;

use App\Enums\TipoMovimentacaoEnum;
use App\Models\Categoria;
use App\Models\Movimentacao;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BugGastoFuturoTest extends TestCase
{
    use RefreshDatabase;

    public function test_nao_permite_alterar_para_gasto_futuro_sem_data_vencimento()
    {
        $user = User::factory()->create();
        $categoria = Categoria::factory()->create(['tipo' => TipoMovimentacaoEnum::GASTO->value, 'user_id' => $user->id]);
        $categoriaFutura = Categoria::factory()->create(['tipo' => TipoMovimentacaoEnum::GASTO_FUTURO->value, 'user_id' => $user->id]);

        $movimentacao = Movimentacao::factory()->create([
            'user_id' => $user->id,
            'categoria_id' => $categoria->id,
            'tipo' => TipoMovimentacaoEnum::GASTO,
            'valor' => 100.00,
            'data' => '2023-01-01',
            'parcelas' => 1,
        ]);

        $response = $this->actingAs($user)->patch(route('movimentacoes.update', $movimentacao), [
            'categoria_id' => $categoriaFutura->id,
            'data_movimentacao' => '2023-01-01',
            'descricao' => 'Teste Alteração',
            'valor' => 100.00,
            'tipo' => TipoMovimentacaoEnum::GASTO_FUTURO->value,
            'parcelas' => 1,
            // 'data_vencimento' omitida
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['data_vencimento']);
    }

    public function test_mantem_valor_correto_ao_alterar_para_gasto_futuro()
    {
        $user = User::factory()->create();
        $categoria = Categoria::factory()->create(['tipo' => TipoMovimentacaoEnum::GASTO->value, 'user_id' => $user->id]);
        $categoriaFutura = Categoria::factory()->create(['tipo' => TipoMovimentacaoEnum::GASTO_FUTURO->value, 'user_id' => $user->id]);

        $movimentacao = Movimentacao::factory()->create([
            'user_id' => $user->id,
            'categoria_id' => $categoria->id,
            'tipo' => TipoMovimentacaoEnum::GASTO,
            'valor' => 100.00,
            'data' => '2023-01-01',
            'parcelas' => 1,
        ]);

        $vencimento = Carbon::now()->addDays(10)->format('Y-m-d');

        $response = $this->actingAs($user)->patch(route('movimentacoes.update', $movimentacao), [
            'categoria_id' => $categoriaFutura->id,
            'data_movimentacao' => '2023-01-01',
            'descricao' => 'Teste Alteração',
            'valor' => 150.00, // Alterando o valor também
            'tipo' => TipoMovimentacaoEnum::GASTO_FUTURO->value,
            'parcelas' => 3,
            'data_vencimento' => $vencimento,
        ]);

        $response->assertStatus(302);
        $movimentacao->refresh();

        // 1. Valor total deve ser 150.00
        $this->assertEquals(150.00, (float) $movimentacao->valor);

        // 2. Deve ter 3 parcelas
        $this->assertEquals(3, $movimentacao->parcelas()->count());

        // 3. Cada parcela deve ter valor 50.00 (150 / 3)
        $parcelas = $movimentacao->parcelas()->get();
        foreach ($parcelas as $parcela) {
            $this->assertEquals(50.00, (float) $parcela->valor);
        }

        // 4. Primeira parcela deve ter o vencimento correto
        $this->assertEquals($vencimento, $movimentacao->parcelas()->orderBy('numero')->first()->data_vencimento->format('Y-m-d'));
    }
}
