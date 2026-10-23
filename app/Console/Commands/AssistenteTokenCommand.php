<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Emite (ou rotaciona) o token de API de um assistente para um usuário.
 *
 * Um token por (usuário, nome): emitir de novo revoga o anterior de mesmo nome. Com --somente-leitura o token
 * só consulta (para rotinas automáticas); sem a opção, também lança e paga.
 */
class AssistenteTokenCommand extends Command
{
    public const VALIDADE_DIAS = 180;

    public const ABILITY_LEITURA = 'financeiro:read';

    public const ABILITY_ESCRITA = 'financeiro:write';

    protected $signature = 'assistente:token
        {email : E-mail do usuário dono dos dados}
        {--nome=logan : Nome do assistente (um token por nome e usuário)}
        {--somente-leitura : Token que só consulta, sem lançar nem pagar}';

    protected $description = 'Gera o token de API de um assistente (válido por 180 dias), revogando o anterior de mesmo nome';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('Usuário não encontrado.');

            return self::FAILURE;
        }

        $somenteLeitura = (bool) $this->option('somente-leitura');
        $nome = 'assistente:'.$this->option('nome').($somenteLeitura ? ':leitura' : '');
        $abilities = $somenteLeitura ? [self::ABILITY_LEITURA] : [self::ABILITY_LEITURA, self::ABILITY_ESCRITA];

        // Revogação e emissão juntas: se a criação falhar, o token anterior continua válido.
        [$revogados, $token] = DB::transaction(fn () => [
            $user->tokens()->where('name', $nome)->delete(),
            $user->createToken($nome, $abilities, now()->addDays(self::VALIDADE_DIAS)),
        ]);

        if ($revogados > 0) {
            $this->warn("{$revogados} token(s) anterior(es) \"{$nome}\" revogado(s).");
        }

        $this->info("Token \"{$nome}\" (".implode(', ', $abilities).') — copie agora, ele não será exibido de novo:');
        $this->line($token->plainTextToken);
        $this->line('Expira em: '.$token->accessToken->expires_at?->format('d/m/Y'));

        return self::SUCCESS;
    }
}
