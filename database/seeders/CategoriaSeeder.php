<?php

namespace Database\Seeders;

use App\Enums\TipoMovimentacaoEnum;
use App\Models\Categoria;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categorias = [
            [
                'id' => 1,
                'nome' => 'Outros',
                'tipo' => TipoMovimentacaoEnum::GASTO,
                'user_id' => null,
            ],
            [
                'id' => 2,
                'nome' => 'Outros',
                'tipo' => TipoMovimentacaoEnum::GANHO,
                'user_id' => null,
            ],
            [
                'id' => 3,
                'nome' => 'Outros',
                'tipo' => TipoMovimentacaoEnum::GASTO_FUTURO,
                'user_id' => null,
            ],
        ];

        foreach ($categorias as $categoria) {
            Categoria::updateOrCreate(['id' => $categoria['id']], $categoria);
        }
    }
}
