<?php

namespace Database\Seeders;

use App\Models\Avaliacao;
use Illuminate\Database\Seeder;

/**
 * Cadastra as avaliações iniciais do JFinanceiro, já aprovadas para o site.
 *
 * É idempotente: rodar de novo não duplica as avaliações. Em produção, rode apenas este
 * seeder (php artisan db:seed --class=AvaliacaoSeeder), nunca o DatabaseSeeder.
 */
class AvaliacaoSeeder extends Seeder
{
    /**
     * @var list<array{nome: string, nota: int, mensagem: string}>
     */
    public const AVALIACOES = [
        [
            'nome' => 'Jeferson Guimarães',
            'nota' => 5,
            'mensagem' => 'Com essa aplicação eu registro todas as movimentações e consigo saber exatamente para onde vai cada centavo. E com as movimentações futuras eu consigo me planejar e saber quanto terei de gastos nos próximos meses.',
        ],
        [
            'nome' => 'Gabriele Ribeiro',
            'nota' => 5,
            'mensagem' => 'Esse sistema é muito bom, ele me ajuda a gerenciar meu dinheiro e saber com o que mais estou gastando. As movimentações futuras são maravilhosas, não esqueço mais de pagar nenhuma conta.',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::AVALIACOES as $avaliacao) {
            Avaliacao::firstOrCreate(
                ['nome' => $avaliacao['nome'], 'mensagem' => $avaliacao['mensagem']],
                [
                    'nota' => $avaliacao['nota'],
                    'publicacao_permitida' => true,
                    'aprovada_em' => now(),
                ],
            );
        }
    }
}
