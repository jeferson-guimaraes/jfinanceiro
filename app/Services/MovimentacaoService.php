<?php

namespace App\Services;

use App\Enums\SituacaoParcelaEnum;
use App\Enums\TipoMovimentacaoEnum;
use App\Exceptions\MovimentacaoInvalidaException;
use App\Models\Categoria;
use App\Models\Movimentacao;
use App\Models\Parcela;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MovimentacaoService
{
    /**
     * Quantidade máxima de palavras consideradas na busca. As demais são ignoradas para
     * evitar consultas enormes quando um texto longo é colado no campo.
     */
    private const LIMITE_PALAVRAS_BUSCA = 10;

    /**
     * Maior valor que cabe nas colunas DECIMAL(10, 2) de movimentações e parcelas.
     */
    private const VALOR_MAXIMO = 99999999.99;

    /**
     * Obtém os dados necessários para a criação de uma movimentação.
     * Inclui categorias de ganhos, gastos e gastos futuros e a categoria padrão ("Outros") de cada tipo.
     *
     * @return array{categoriasGanhos: \Illuminate\Support\Collection, categoriasGastos: \Illuminate\Support\Collection, categoriasGastosFuturos: \Illuminate\Support\Collection, categoriasPadrao: array<string, int|null>}
     */
    public function getCreateMovimentacaoData(): array
    {
        $user = Auth::user();

        $categorias = Categoria::where(function ($query) use ($user) {
            $query->where('user_id', $user->id)
                ->orWhereNull('user_id');
        })
            ->orderBy('nome')
            ->get();

        return [
            'categoriasGanhos' => $categorias->where('tipo', TipoMovimentacaoEnum::GANHO->value)->values(),
            'categoriasGastos' => $categorias->where('tipo', TipoMovimentacaoEnum::GASTO->value)->values(),
            'categoriasGastosFuturos' => $categorias->where('tipo', TipoMovimentacaoEnum::GASTO_FUTURO->value)->values(),
            'categoriasPadrao' => collect(TipoMovimentacaoEnum::cases())
                ->mapWithKeys(fn (TipoMovimentacaoEnum $tipo) => [$tipo->value => $this->categoriaPadraoId($categorias, $tipo)])
                ->all(),
        ];
    }

    /**
     * Id da categoria "Outros" do tipo (a global tem preferência sobre a do usuário), ou null se não houver.
     *
     * @param  Collection<int, Categoria>  $categorias  Categorias disponíveis ao usuário.
     */
    private function categoriaPadraoId(Collection $categorias, TipoMovimentacaoEnum $tipo): ?int
    {
        return $categorias
            ->where('tipo', $tipo->value)
            ->filter(fn (Categoria $categoria) => Str::lower(Str::ascii(trim($categoria->nome))) === 'outros')
            ->sortBy(fn (Categoria $categoria) => $categoria->user_id === null ? 0 : 1)
            ->first()?->id;
    }

    /**
     * Busca movimentações (ganhos e gastos) e parcelas futuras com base em parâmetros da requisição.
     *
     * @return array{movimentacoes: LengthAwarePaginator|null, parcelasFuturas: LengthAwarePaginator|null, totais: array, filters: array}
     */
    public function getMovimentacoes(): array
    {
        $userId = Auth::id();
        $tipo = request('tipo') ?? 'todos';
        $perPage = (int) request('per_page', 50);

        $ganhosGastos = null;
        $parcelasFuturas = null;
        $totais = [
            'total' => 0,
            'ganhos' => 0,
            'despesas' => 0,
            'pago' => 0,
            'pendente' => 0,
        ];

        switch ($tipo) {
            case 'ganho':
            case 'gasto':
                $ganhosGastos = $this->getGanhosGastosMovimentacoes($userId, $tipo);
                $parcelasFuturas = Parcela::whereRaw('1 = 0')->paginate($perPage);
                $totais['total'] = (float) $this->getGanhosGastosQuery($userId, $tipo)->sum('valor');
                break;
            case 'gasto futuro':
                $parcelasFuturas = $this->getParcelasFuturasMovimentacoes($userId);
                $ganhosGastos = Movimentacao::whereRaw('1 = 0')->paginate($perPage);
                $queryParcelas = $this->getParcelasFuturasQuery($userId, aplicarSituacao: false);
                $totais['total'] = (float) (clone $queryParcelas)->sum('valor');
                $totais['pago'] = (float) (clone $queryParcelas)->where('pago', true)->sum('valor');
                $totais['pendente'] = $totais['total'] - $totais['pago'];
                break;
            case 'todos':
            default:
                $ganhosGastos = $this->getGanhosGastosMovimentacoes($userId);
                $parcelasFuturas = Parcela::whereRaw('1 = 0')->paginate($perPage);
                $queryGanhosGastos = $this->getGanhosGastosQuery($userId);
                $totais['ganhos'] = (float) (clone $queryGanhosGastos)->where('tipo', TipoMovimentacaoEnum::GANHO->value)->sum('valor');
                $totais['despesas'] = (float) (clone $queryGanhosGastos)->where('tipo', TipoMovimentacaoEnum::GASTO->value)->sum('valor');
                $totais['total'] = $totais['ganhos'] - $totais['despesas'];
                break;
        }

        return [
            'movimentacoes' => $ganhosGastos,
            'parcelasFuturas' => $parcelasFuturas,
            'totais' => $totais,
            'filters' => [
                'busca' => request('busca'),
                'data_inicio' => request('data_inicio'),
                'data_fim' => request('data_fim'),
                'mes' => request('mes'),
                'ano' => request('ano'),
                'per_page' => (int) request('per_page', 50),
                'tipo' => $tipo,
                'situacao' => SituacaoParcelaEnum::daRequisicao(request('situacao'))->value,
            ],
        ];
    }

    /**
     * Retorna a query base para ganhos e gastos.
     *
     * @param  int  $userId  ID do usuário.
     * @param  string|null  $tipo  Tipo de movimentação (ganho, gasto ou null para ambos).
     * @return Builder|Movimentacao
     */
    private function getGanhosGastosQuery(int $userId, ?string $tipo = null)
    {
        $dataInicioReq = request('data_inicio');
        $dataFimReq = request('data_fim');
        $mes = request('mes');
        $ano = request('ano');

        $query = Movimentacao::doUsuario($userId);

        if ($tipo === TipoMovimentacaoEnum::GANHO->value) {
            $query->ganhos();
        } elseif ($tipo === TipoMovimentacaoEnum::GASTO->value) {
            $query->gastos();
        } else {
            $query->whereIn('tipo', [
                TipoMovimentacaoEnum::GANHO,
                TipoMovimentacaoEnum::GASTO,
            ]);
        }

        if ($dataInicioReq || $dataFimReq) {
            $dataInicio = $dataInicioReq ? Carbon::parse($dataInicioReq) : null;
            $dataFim = $dataFimReq ? Carbon::parse($dataFimReq) : null;
            if ($dataInicio) {
                $query->whereDate('data', '>=', $dataInicio);
            }
            if ($dataFim) {
                $query->whereDate('data', '<=', $dataFim);
            }
        } else {
            $mes = $mes ?: Carbon::now()->month;
            $ano = $ano ?: Carbon::now()->year;
            $query->doMes($mes, $ano);
        }

        foreach ($this->palavrasDaBusca(request('busca')) as $palavra) {
            $query->where(fn (Builder $q) => $this->filtraMovimentacaoPorPalavra($q, $palavra));
        }

        return $query;
    }

    /**
     * Retorna a query base para parcelas futuras.
     *
     * O filtro de situação (pagas ou não pagas) vale para a listagem, mas não para os totais:
     * os cards já separam o mês em pago e pendente, e filtrar por situação zeraria um deles.
     * A busca, por outro lado, vale para os dois: os cards resumem o que foi buscado no mês.
     *
     * @param  int  $userId  ID do usuário.
     * @param  bool  $aplicarSituacao  Se deve aplicar o filtro de situação da requisição.
     * @return Builder|Parcela
     */
    private function getParcelasFuturasQuery(int $userId, bool $aplicarSituacao = true)
    {
        $mes = request('mes') ?: Carbon::now()->month;
        $ano = request('ano') ?: Carbon::now()->year;
        $busca = request('busca');

        $inicioMes = Carbon::create($ano, $mes, 1)->startOfMonth();
        $fimMes = $inicioMes->copy()->endOfMonth();

        $query = Parcela::query()
            ->whereBetween('data_vencimento', [$inicioMes, $fimMes])
            ->whereHas('movimentacao', fn ($q) => $q->doUsuario($userId));

        if ($aplicarSituacao) {
            match (SituacaoParcelaEnum::daRequisicao(request('situacao'))) {
                SituacaoParcelaEnum::PAGAS => $query->where('pago', true),
                SituacaoParcelaEnum::NAO_PAGAS => $query->where('pago', false),
                SituacaoParcelaEnum::TODAS => null,
            };
        }

        foreach ($this->palavrasDaBusca($busca) as $palavra) {
            $valor = $this->valorDaPalavra($palavra);

            $query->where(function (Builder $q) use ($palavra, $valor) {
                $q->whereHas('movimentacao', fn (Builder $m) => $this->filtraMovimentacaoPorPalavra($m, $palavra));

                if ($valor !== null) {
                    $q->orWhere('valor', $valor);
                }
            });
        }

        return $query;
    }

    /**
     * Separa o texto da busca em palavras. Cada palavra precisa corresponder a algum campo
     * da movimentação para que ela apareça no resultado. O símbolo "R$" é ignorado e só as
     * primeiras palavras, até o limite, são consideradas.
     *
     * @return list<string>
     */
    private function palavrasDaBusca(?string $busca): array
    {
        $palavras = preg_split('/\s+/', trim((string) $busca), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $palavras = array_values(array_filter(
            $palavras,
            fn (string $palavra) => strcasecmp($palavra, 'R$') !== 0,
        ));

        return array_slice($palavras, 0, self::LIMITE_PALAVRAS_BUSCA);
    }

    /**
     * Interpreta uma palavra da busca como valor em reais, aceitando os formatos
     * "45", "45,9", "45,90", "45.90", "1.234", "1.234,56" e "R$45,90".
     * Retorna null quando a palavra não é um valor ou passa do maior valor armazenável.
     */
    private function valorDaPalavra(string $palavra): ?float
    {
        $texto = preg_replace('/^R\$/i', '', $palavra);
        $valor = null;

        if (preg_match('/^\d{1,3}(\.\d{3})*(,\d{1,2})?$/', $texto) || preg_match('/^\d+(,\d{1,2})?$/', $texto)) {
            $valor = (float) str_replace(['.', ','], ['', '.'], $texto);
        } elseif (preg_match('/^\d+\.\d{1,2}$/', $texto)) {
            $valor = (float) $texto;
        }

        if ($valor === null || ! is_finite($valor) || $valor > self::VALOR_MAXIMO) {
            return null;
        }

        return $valor;
    }

    /**
     * Restringe a query de movimentações às que contêm a palavra na descrição ou no nome da
     * categoria, ou cujo valor é igual à palavra interpretada como valor.
     */
    private function filtraMovimentacaoPorPalavra(Builder $query, string $palavra): void
    {
        $valor = $this->valorDaPalavra($palavra);

        $query->where(function (Builder $q) use ($palavra, $valor) {
            $q->where('descricao', 'like', '%'.$palavra.'%')
                ->orWhereHas('categoria', fn (Builder $c) => $c->where('nome', 'like', '%'.$palavra.'%'));

            if ($valor !== null) {
                $q->orWhere('valor', $valor);
            }
        });
    }

    /**
     * Obtém as movimentações de ganhos e gastos filtradas por data.
     *
     * @param  int  $userId  O ID do usuário.
     * @param  string|null  $tipo  O tipo de movimentação.
     * @return LengthAwarePaginator Retorna um paginador de movimentações.
     */
    private function getGanhosGastosMovimentacoes(int $userId, ?string $tipo = null): LengthAwarePaginator
    {
        $perPage = request('per_page', 50);

        return $this->getGanhosGastosQuery($userId, $tipo)
            ->select(['id', 'categoria_id', 'data', 'descricao', 'valor', 'tipo', 'parcelas', 'user_id'])
            ->with('categoria:id,nome')
            ->orderByDesc('data')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Obtém as parcelas futuras para um mês e ano específicos.
     *
     * @param  int  $userId  O ID do usuário.
     * @return LengthAwarePaginator Retorna um paginador de parcelas futuras.
     */
    private function getParcelasFuturasMovimentacoes(int $userId): LengthAwarePaginator
    {
        $perPage = request('per_page', 50);

        return $this->getParcelasFuturasQuery($userId)
            ->select(['id', 'movimentacao_id', 'numero', 'valor', 'data_vencimento', 'pago'])
            ->with([
                'movimentacao' => function ($q) {
                    $q->select(['id', 'categoria_id', 'data', 'descricao', 'valor', 'tipo', 'parcelas', 'user_id'])
                        ->with('categoria:id,nome')
                        ->withCount(['parcelas as parcelas_pagas' => fn ($q) => $q->where('pago', true)]);
                },
            ])
            ->orderBy('data_vencimento')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Armazena uma nova movimentação, incluindo a criação de parcelas se for gasto futuro.
     *
     * Tudo numa transação: se as parcelas não puderem ser criadas, nada é gravado.
     *
     * @param  array  $validated  Dados validados da requisição. Para gasto futuro: `parcelas`, `data_vencimento` e
     *                            `valor_parcelas` (valor fixo) ou `valores_parcelas` (lista com o valor de cada uma).
     * @return Movimentacao A movimentação criada.
     *
     * @throws MovimentacaoInvalidaException Quando faltam dados para gerar as parcelas do gasto futuro.
     */
    public function storeMovimentacao(array $validated): Movimentacao
    {
        return DB::transaction(function () use ($validated) {
            $movimentacaoData = $this->prepareMovimentacaoDataForStore($validated);

            $movimentacao = Movimentacao::create(array_merge($movimentacaoData, ['user_id' => Auth::id()]));

            if ($movimentacao->tipo->value === TipoMovimentacaoEnum::GASTO_FUTURO->value) {
                $this->createParcelasForMovimentacao($movimentacao, $validated);
            }

            return $movimentacao;
        });
    }

    /**
     * Vencimentos mensais a partir do 1º, sem pular mês: em meses mais curtos cai no último dia
     * (31/01 → 28/02 → 31/03). Usado no caminho da API do assistente.
     *
     * @return list<string> Datas AAAA-MM-DD.
     */
    public function montarVencimentosParcelas(Carbon $primeiroVencimento, int $quantidade): array
    {
        return array_map(
            fn (int $indice) => $primeiroVencimento->copy()->addMonthsNoOverflow($indice)->format('Y-m-d'),
            range(0, $quantidade - 1),
        );
    }

    /**
     * Divide um valor total em parcelas fixas, em centavos; a diferença de arredondamento vai para a última.
     * Ex.: 1000 em 3x → [333.33, 333.33, 333.34] (soma sempre igual ao total).
     *
     * @return list<float>
     */
    public function montarValoresParcelas(float $valorTotal, int $quantidade): array
    {
        $totalCentavos = (int) round($valorTotal * 100);
        $parcelaCentavos = intdiv($totalCentavos, $quantidade);
        $valores = array_fill(0, $quantidade, $parcelaCentavos);
        $valores[$quantidade - 1] += $totalCentavos - $parcelaCentavos * $quantidade;

        return array_map(fn (int $centavos) => $centavos / 100, $valores);
    }

    /**
     * Prepara os dados para a criação de uma movimentação.
     * Mapeia 'data_movimentacao' para 'data' e remove campos extras.
     *
     * @param  array  $validated  Dados validados.
     * @return array Dados preparados para a criação da movimentação.
     */
    private function prepareMovimentacaoDataForStore(array $validated): array
    {
        $validated['data'] = $validated['data_movimentacao'];
        unset($validated['data_movimentacao']);
        $movimentacaoFields = ['categoria_id', 'data', 'descricao', 'valor', 'tipo', 'parcelas'];

        return array_intersect_key($validated, array_flip($movimentacaoFields));
    }

    /**
     * Cria as parcelas para uma movimentação de gasto futuro.
     *
     * @param  Movimentacao  $movimentacao  A movimentação para a qual criar as parcelas.
     * @param  array  $validated  Dados validados contendo informações das parcelas.
     */
    private function createParcelasForMovimentacao(Movimentacao $movimentacao, array $validated): void
    {
        $numParcelas = (int) ($validated['parcelas'] ?? 0);
        $valores = $validated['valores_parcelas'] ?? array_fill(0, max($numParcelas, 0), (float) ($validated['valor_parcelas'] ?? 0));
        $dataVencimentoInicial = $validated['data_vencimento'] ?? null;

        if ($numParcelas <= 0 || count($valores) !== $numParcelas || min($valores) <= 0 || ! $dataVencimentoInicial) {
            throw new MovimentacaoInvalidaException('Informe a quantidade, o valor e o vencimento da primeira parcela.');
        }

        // Vencimentos: os informados (API) ou mensais encadeados a partir do 1º (fluxo web, comportamento original).
        $vencimentos = $validated['vencimentos_parcelas'] ?? [];
        $dataVencimento = Carbon::parse($dataVencimentoInicial);
        foreach (array_values($valores) as $indice => $valorParcela) {
            Parcela::create([
                'movimentacao_id' => $movimentacao->id,
                'numero' => $indice + 1,
                'valor' => $valorParcela,
                'data_vencimento' => $vencimentos[$indice] ?? $dataVencimento->format('Y-m-d'),
            ]);

            $dataVencimento->addMonth();
        }
    }

    /**
     * Atualiza uma movimentação existente.
     *
     * @param  Movimentacao  $movimentacao  A movimentação a ser atualizada.
     * @param  array  $validated  Dados validados.
     */
    public function updateMovimentacao(Movimentacao $movimentacao, array $validated): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($movimentacao, $validated) {
            $movimentacaoData = $this->prepareMovimentacaoDataForUpdate($validated);
            $movimentacao->update($movimentacaoData);

            if ($movimentacao->tipo->value === TipoMovimentacaoEnum::GASTO_FUTURO->value) {
                // 1. Ajuste de Vencimento Inicial
                $primeiraParcela = $movimentacao->parcelas()->orderBy('numero')->first();
                if (! empty($validated['data_vencimento']) && $primeiraParcela && $validated['data_vencimento'] !== $primeiraParcela->data_vencimento->format('Y-m-d')) {
                    $novaData = Carbon::parse($validated['data_vencimento']);
                    $parcelasExistentes = $movimentacao->parcelas()->orderBy('numero')->get();
                    foreach ($parcelasExistentes as $parcela) {
                        $parcela->update([
                            'data_vencimento' => $novaData->format('Y-m-d'),
                        ]);
                        $novaData->addMonth();
                    }
                }

                // 2. Ajuste de Quantidade de Parcelas
                if (isset($validated['parcelas'])) {
                    $novaQtd = (int) $validated['parcelas'];
                    $qtdAtual = $movimentacao->parcelas()->count();

                    if ($novaQtd > $qtdAtual) {
                        // Criar novas parcelas
                        $valorMedio = $qtdAtual > 0
                            ? ($movimentacao->parcelas()->avg('valor') ?: 0)
                            : (($validated['valor'] ?? $movimentacao->valor) / $novaQtd);

                        $ultimaParcela = $movimentacao->parcelas()->orderByDesc('numero')->first();
                        $dataVencimento = $ultimaParcela
                            ? Carbon::parse($ultimaParcela->data_vencimento)->addMonth()
                            : (! empty($validated['data_vencimento']) ? Carbon::parse($validated['data_vencimento']) : Carbon::now());

                        for ($num = $qtdAtual + 1; $num <= $novaQtd; $num++) {
                            Parcela::create([
                                'movimentacao_id' => $movimentacao->id,
                                'numero' => $num,
                                'valor' => $valorMedio,
                                'data_vencimento' => $dataVencimento->format('Y-m-d'),
                            ]);
                            $dataVencimento->addMonth();
                        }
                    } elseif ($novaQtd < $qtdAtual) {
                        // Deletar as parcelas com número maior do que novaQtd
                        $movimentacao->parcelas()->where('numero', '>', $novaQtd)->delete();
                    }
                }

                // 3. Edição Individual das parcelas enviadas
                if (! empty($validated['parcelas_editadas'])) {
                    foreach ($validated['parcelas_editadas'] as $parcelaEditada) {
                        $parcela = Parcela::find($parcelaEditada['id']);
                        if ($parcela && $parcela->movimentacao_id === $movimentacao->id) {
                            $parcela->update([
                                'valor' => $parcelaEditada['valor'],
                                'data_vencimento' => Carbon::parse($parcelaEditada['data_vencimento'])->format('Y-m-d'),
                            ]);
                        }
                    }
                }

                // 4. Recalcular Valor Total da movimentação e o número de parcelas
                $somaValores = $movimentacao->parcelas()->sum('valor');
                $totalParcelas = $movimentacao->parcelas()->count();
                $movimentacao->update([
                    'valor' => $somaValores,
                    'parcelas' => $totalParcelas,
                ]);
            } else {
                // Se mudou de gasto futuro para outro tipo, deleta todas as parcelas
                $movimentacao->parcelas()->delete();
                $movimentacao->update([
                    'parcelas' => 1,
                ]);
            }
        });
    }

    /**
     * Prepara os dados para a atualização de uma movimentação.
     * Mapeia 'data_movimentacao' para 'data'.
     *
     * @param  array  $validated  Dados validados.
     * @return array Dados preparados para atualização.
     */
    private function prepareMovimentacaoDataForUpdate(array $validated): array
    {
        $validated['data'] = $validated['data_movimentacao'];
        unset($validated['data_movimentacao']);
        // Retorna apenas os campos que podem ser atualizados, para evitar mass assignment issues
        $movimentacaoFields = ['categoria_id', 'descricao', 'valor', 'data', 'tipo', 'conta_id', 'parcelas']; // Ajuste conforme os campos do seu Model Movimentacao

        return array_intersect_key($validated, array_flip($movimentacaoFields));
    }

    /**
     * Deleta uma movimentação.
     *
     * @param  Movimentacao  $movimentacao  A movimentação a ser deletada.
     */
    public function destroyMovimentacao(Movimentacao $movimentacao): void
    {
        $movimentacao->delete();
    }

    /**
     * Deleta várias movimentações.
     *
     * @param  array  $movimentacoesIds  Array com os IDs das movimentações a serem deletadas.
     */
    public function destroyManyMovimentacoes(array $movimentacoesIds): void
    {
        Movimentacao::doUsuario(Auth::id())
            ->whereIn('id', $movimentacoesIds)
            ->delete();
    }

    /**
     * Paga as próximas parcelas pendentes de uma movimentação de gasto futuro e lança o GASTO correspondente.
     *
     * As parcelas são travadas na transação (evita pagamento em dobro por envios simultâneos). Sem
     * `valor_total_pago`, o gasto lançado é a soma das parcelas efetivamente pagas.
     *
     * @param  Movimentacao  $movimentacao  A movimentação de gasto futuro.
     * @param  array  $data  `quantidade_parcelas`, `data_pagamento`, `valor_total_pago` (opcional) e `descricao` (opcional).
     * @return Movimentacao O gasto lançado pelo pagamento.
     *
     * @throws MovimentacaoInvalidaException Se não for gasto futuro ou não houver parcelas pendentes suficientes.
     */
    public function pagarParcelas(Movimentacao $movimentacao, array $data): Movimentacao
    {
        if ($movimentacao->tipo !== TipoMovimentacaoEnum::GASTO_FUTURO) {
            throw new MovimentacaoInvalidaException('Só é possível pagar parcelas de um gasto futuro (compra parcelada).');
        }

        return DB::transaction(function () use ($movimentacao, $data) {
            $quantidade = (int) $data['quantidade_parcelas'];
            $dataPagamento = $data['data_pagamento'];

            // 1. Trava e marca as próximas parcelas pendentes como pagas
            $parcelasAPagar = $movimentacao->parcelas()
                ->where('pago', false)
                ->orderBy('numero')
                ->lockForUpdate()
                ->take($quantidade)
                ->get();

            if ($parcelasAPagar->isEmpty()) {
                throw new MovimentacaoInvalidaException('Esta compra não tem parcelas pendentes.');
            }

            if ($parcelasAPagar->count() < $quantidade) {
                throw new MovimentacaoInvalidaException("Só há {$parcelasAPagar->count()} parcela(s) pendente(s) nesta compra.");
            }

            foreach ($parcelasAPagar as $parcela) {
                $parcela->update([
                    'pago' => true,
                    'data_pagamento' => $dataPagamento,
                ]);
            }

            $valorTotalPago = isset($data['valor_total_pago'])
                ? (float) $data['valor_total_pago']
                : (float) $parcelasAPagar->sum('valor');

            $descricao = ! empty($data['descricao'])
                ? "{$data['descricao']}: {$movimentacao->descricao}"
                : $movimentacao->descricao;

            // 2. Lança o GASTO que representa a saída de caixa
            return Movimentacao::create([
                'user_id' => $movimentacao->user_id,
                'categoria_id' => $movimentacao->categoria_id,
                'data' => $dataPagamento,
                'descricao' => $descricao,
                'valor' => $valorTotalPago,
                'tipo' => TipoMovimentacaoEnum::GASTO->value,
                'parcelas' => 1,
            ]);
        });
    }

    /**
     * Parcelas pendentes de gasto futuro do usuário que vencem até a data (inclui as atrasadas).
     *
     * @return Collection<int, Parcela>
     */
    public function parcelasPendentesAte(int $userId, Carbon $ate): Collection
    {
        return Parcela::query()
            ->whereHas('movimentacao', fn ($query) => $query->doUsuario($userId)->gastosFuturos())
            ->where('pago', false)
            ->whereNotNull('data_vencimento')
            ->whereDate('data_vencimento', '<=', $ate->toDateString())
            ->with('movimentacao.categoria')
            ->orderBy('data_vencimento')
            ->orderBy('id')
            ->get();
    }

    /**
     * Processa o pagamento de parcelas para múltiplas movimentações.
     *
     * @param  array  $movimentacaoIds  IDs das movimentações a serem pagas.
     * @param  array  $data  Dados do pagamento (quantidade, data).
     */
    public function pagarParcelasMassa(array $movimentacaoIds, array $data): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($movimentacaoIds, $data) {
            $quantidade = $data['quantidade_parcelas'];
            $dataPagamento = $data['data_pagamento'];

            $movimentacoes = Movimentacao::whereIn('id', $movimentacaoIds)
                ->where('user_id', Auth::id())
                ->get();

            foreach ($movimentacoes as $movimentacao) {
                // 1. Marca as próximas parcelas pendentes como pagas
                $parcelasAPagar = $movimentacao->parcelas()
                    ->where('pago', false)
                    ->orderBy('numero')
                    ->lockForUpdate()
                    ->take($quantidade)
                    ->get();

                $valorPagoNestaMovimentacao = 0;
                foreach ($parcelasAPagar as $parcela) {
                    $parcela->update([
                        'pago' => true,
                        'data_pagamento' => $dataPagamento,
                    ]);
                    $valorPagoNestaMovimentacao += $parcela->valor;
                }

                if ($valorPagoNestaMovimentacao > 0) {
                    // 2. Cria uma nova movimentação de GASTO para representar a saída de caixa
                    $descricao = ! empty($data['descricao'])
                        ? "{$data['descricao']} - {$movimentacao->descricao}"
                        : $movimentacao->descricao;

                    Movimentacao::create([
                        'user_id' => $movimentacao->user_id,
                        'categoria_id' => $movimentacao->categoria_id,
                        'data' => $dataPagamento,
                        'descricao' => $descricao,
                        'valor' => $valorPagoNestaMovimentacao,
                        'tipo' => TipoMovimentacaoEnum::GASTO->value,
                        'parcelas' => 1,
                    ]);
                }
            }
        });
    }
}
