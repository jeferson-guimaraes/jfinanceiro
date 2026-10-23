<?php

namespace App\Services;

use App\Enums\TipoMovimentacaoEnum;
use App\Models\Categoria;
use App\Models\Movimentacao;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Operações da API do assistente (Logan e afins), sempre em nome do usuário dono do token.
 *
 * Só adapta a entrada do assistente para as regras de domínio do MovimentacaoService (que continua sendo a única
 * fonte de lógica financeira): resolve a categoria pelo nome e monta as parcelas da compra no servidor.
 */
class AssistenteFinanceiroService
{
    public const CATEGORIA_PADRAO = 'Outros';

    public function __construct(
        private MovimentacaoService $movimentacaoService,
    ) {}

    /**
     * Categorias que o usuário pode usar num tipo: as dele e as globais (as dele primeiro, depois por nome).
     *
     * @return Collection<int, Categoria>
     */
    public function categoriasDisponiveis(User $user, ?TipoMovimentacaoEnum $tipo = null): Collection
    {
        return Categoria::query()
            ->where(fn ($query) => $query->where('user_id', $user->id)->orWhereNull('user_id'))
            ->when($tipo, fn ($query) => $query->where('tipo', $tipo->value))
            ->orderByRaw('user_id is null')
            ->orderBy('nome')
            ->get();
    }

    /**
     * Lança um ganho, gasto ou gasto futuro (compra parcelada) a partir dos dados validados da API.
     *
     * @param  array{tipo: string, valor: float|string, data: string, descricao: string, categoria_id?: int|null, categoria?: string|null, parcelas?: int|null, primeiro_vencimento?: string|null}  $dados
     *
     * @throws ValidationException Quando a categoria informada pelo nome não existe para o tipo.
     */
    public function lancar(User $user, array $dados): Movimentacao
    {
        $tipo = TipoMovimentacaoEnum::from($dados['tipo']);
        $valor = (float) $dados['valor'];

        $validated = [
            'categoria_id' => $this->resolverCategoria($user, $tipo, $dados)->id,
            'data_movimentacao' => $dados['data'],
            'descricao' => $dados['descricao'],
            'valor' => $valor,
            'tipo' => $tipo->value,
        ];

        if ($tipo === TipoMovimentacaoEnum::GASTO_FUTURO) {
            $quantidade = (int) $dados['parcelas'];
            $validated += [
                'parcelas' => $quantidade,
                'data_vencimento' => $dados['primeiro_vencimento'],
                'valores_parcelas' => $this->movimentacaoService->montarValoresParcelas($valor, $quantidade),
                'vencimentos_parcelas' => $this->movimentacaoService->montarVencimentosParcelas(
                    Carbon::parse($dados['primeiro_vencimento']),
                    $quantidade,
                ),
            ];
        } else {
            $validated['parcelas'] = 1; // domínio: ganho e gasto sempre têm 1 parcela
        }

        return $this->movimentacaoService->storeMovimentacao($validated)->load(['categoria', 'parcelas']);
    }

    /**
     * Categoria por id, por nome (sem diferenciar maiúsculas nem acentos; a do usuário tem preferência sobre a global) ou,
     * se omitida, a "Outros" do tipo.
     *
     * @throws ValidationException Quando o nome não corresponde a nenhuma categoria do tipo (com as opções).
     */
    private function resolverCategoria(User $user, TipoMovimentacaoEnum $tipo, array $dados): Categoria
    {
        $disponiveis = $this->categoriasDisponiveis($user, $tipo);

        if (! empty($dados['categoria_id'])) {
            return $disponiveis->firstWhere('id', (int) $dados['categoria_id'])
                ?? throw ValidationException::withMessages(['categoria_id' => 'Categoria não encontrada para este tipo.']);
        }

        $nome = trim((string) ($dados['categoria'] ?? ''));
        // Sem diferenciar maiúsculas nem acentos ("alimentacao" = "Alimentação"), comum em transcrição de áudio.
        $normalizar = fn (string $texto) => Str::lower(Str::ascii(trim($texto)));
        $procurada = $normalizar($nome !== '' ? $nome : self::CATEGORIA_PADRAO);
        $encontrada = $disponiveis->first(fn (Categoria $categoria) => $normalizar($categoria->nome) === $procurada);

        if ($encontrada) {
            return $encontrada;
        }

        $opcoes = $disponiveis->pluck('nome')->unique()->values()->implode(', ');

        throw ValidationException::withMessages([
            'categoria' => $nome !== ''
                ? "Categoria \"{$nome}\" não encontrada. Opções: {$opcoes}."
                : "Informe a categoria. Opções: {$opcoes}.",
        ]);
    }
}
