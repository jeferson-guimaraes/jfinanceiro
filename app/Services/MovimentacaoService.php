<?php

namespace App\Services;

use App\Enums\TipoMovimentacaoEnum;
use App\Models\Categoria;
use App\Models\Movimentacao;
use App\Models\Parcela;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection; // Para tipagem de retorno de coleções
use Illuminate\Support\Facades\Log; // Para logs

class MovimentacaoService
{
	/**
	 * Obtém os dados necessários para a criação de uma movimentação.
	 * Inclui categorias de ganhos, gastos e gastos futuros.
	 *
	 * @return array Retorna um array com as categorias separadas por tipo.
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
			'categoriasGanhos'        => $categorias->where('tipo', TipoMovimentacaoEnum::GANHO->value)->values(),
			'categoriasGastos'        => $categorias->where('tipo', TipoMovimentacaoEnum::GASTO->value)->values(),
			'categoriasGastosFuturos' => $categorias->where('tipo', TipoMovimentacaoEnum::GASTO_FUTURO->value)->values(),
		];
	}

	/**
	 * Busca movimentações (ganhos e gastos) e parcelas futuras com base em parâmetros da requisição.
	 *
	 * @return array Retorna um array contendo as movimentações e as parcelas futuras.
	 */
	public function getMovimentacoes(): array
	{
		$userId = Auth::id();
		$tipo = request('tipo') ?? 'todos';

		$ganhosGastos = collect();
		$parcelasFuturas = collect();

		switch ($tipo) {
			case 'ganho':
			case 'gasto':
				$ganhosGastos = $this->getGanhosGastosMovimentacoes($userId, $tipo);
				break;
			case 'gasto futuro':
				$parcelasFuturas = $this->getParcelasFuturasMovimentacoes($userId);
				break;
			case 'todos':
			default:
				// Busca ganhos e gastos normais
				$ganhosGastos = $this->getGanhosGastosMovimentacoes($userId);
				break;
		}

		return [
			'movimentacoes' => $ganhosGastos,
			'parcelasFuturas' => $parcelasFuturas,
		];
	}

	/**
	 * Obtém as movimentações de ganhos e gastos filtradas por data.
	 *
	 * @param int $userId O ID do usuário.
	 * @return Collection Retorna uma coleção de movimentações.
	 */
	private function getGanhosGastosMovimentacoes(int $userId, ?string $tipo = null): Collection
	{
		$dataInicioReq = request('data_inicio');
		$dataFimReq = request('data_fim');

		// Se não houver filtros de data, usa o mês atual por padrão para performance
		if (!$dataInicioReq && !$dataFimReq) {
			$dataInicio = Carbon::now()->startOfMonth();
			$dataFim = Carbon::now()->endOfMonth();
		} else {
			$dataInicio = $dataInicioReq ? Carbon::parse($dataInicioReq) : null;
			$dataFim = $dataFimReq ? Carbon::parse($dataFimReq) : null;
		}

		$busca = request('busca');

		$query = Movimentacao::query()
			->where('user_id', $userId);

		if ($tipo && $tipo !== 'todos') {
			$query->where('tipo', $tipo);
		} else {
			$query->whereIn('tipo', [
				TipoMovimentacaoEnum::GANHO->value,
				TipoMovimentacaoEnum::GASTO->value
			]);
		}

		// Filtro por período de datas
		if ($dataInicio) {
			$query->whereDate('data', '>=', $dataInicio);
		}
		if ($dataFim) {
			$query->whereDate('data', '<=', $dataFim);
		}

		// Filtro por busca de texto
		if ($busca) {
			$query->where('descricao', 'like', '%' . $busca . '%');
		}

		return $query->with('categoria')
			->withCount(['parcelas as parcelas_pagas' => fn($q) => $q->where('pago', true)])
			->orderByDesc('data')
			->get();
	}

	/**
	 * Obtém as parcelas futuras para um mês e ano específicos.
	 *
	 * @param int $userId O ID do usuário.
	 * @return Collection Retorna uma coleção de parcelas futuras.
	 */
	private function getParcelasFuturasMovimentacoes(int $userId): Collection
	{
		$mes = request('mes') ?: Carbon::now()->month;
		$ano = request('ano') ?: Carbon::now()->year;
		$busca = request('busca');

		if ($mes && $ano) {
			$inicioMes = Carbon::create($ano, $mes)->startOfMonth();
			$fimMes = $inicioMes->copy()->endOfMonth();

			$query = Parcela::query()
				->whereBetween('data_vencimento', [$inicioMes, $fimMes])
				->whereHas('movimentacao', fn($q) => $q->where('user_id', $userId));

			if ($busca) {
				$query->whereHas('movimentacao', fn($q) => $q->where('descricao', 'like', '%' . $busca . '%'));
			}

			$query->with([
				'movimentacao' => function ($q) {
					$q->with('categoria')
						->withCount(['parcelas as parcelas_pagas' => fn($q) => $q->where('pago', true)]);
				}
			])
				->orderBy('data_vencimento');

			return $query->get();
		}

		return collect();
	}


	/**
	 * Armazena uma nova movimentação, incluindo a criação de parcelas se for gasto futuro.
	 *
	 * @param array $validated Dados validados da requisição.
	 * @throws \Exception Em caso de erro na criação.
	 */
	public function storeMovimentacao(array $validated)
	{
		$userId = Auth::id();

		$movimentacaoData = $this->prepareMovimentacaoDataForStore($validated);

		$movimentacao = Movimentacao::create(array_merge($movimentacaoData, ['user_id' => $userId]));

		if ($movimentacao->tipo->value === TipoMovimentacaoEnum::GASTO_FUTURO->value) {
			$this->createParcelasForMovimentacao($movimentacao, $validated);
		}
	}

	/**
	 * Prepara os dados para a criação de uma movimentação.
	 * Mapeia 'data_movimentacao' para 'data' e remove campos extras.
	 *
	 * @param array $validated Dados validados.
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
	 * @param Movimentacao $movimentacao A movimentação para a qual criar as parcelas.
	 * @param array $validated Dados validados contendo informações das parcelas.
	 * @throws \Exception Em caso de erro na criação das parcelas.
	 */
	private function createParcelasForMovimentacao(Movimentacao $movimentacao, array $validated): void
	{
		// Garante que os dados necessários para parcelas estejam presentes
		$numParcelas = $validated['parcelas'] ?? 0;
		$valorParcela = $validated['valor_parcelas'] ?? 0;
		$dataVencimentoInicial = $validated['data_vencimento'] ?? null;

		if ($numParcelas <= 0 || $valorParcela <= 0 || !$dataVencimentoInicial) {
			// Logar ou lançar um erro se os dados das parcelas forem inválidos
			Log::warning('Dados insuficientes para criar parcelas para a movimentação ID: ' . $movimentacao->id);
			// Poderia lançar uma exceção aqui se for um erro crítico
			return;
		}

		$dataVencimento = Carbon::parse($dataVencimentoInicial);
		Log::info([
			'numParcelas' => $numParcelas,
			'valorParcela' => $valorParcela,
			'dataVencimentoInicial' => $dataVencimentoInicial,
			'dataVencimento' => $dataVencimento->format('Y-m-d')
		]);
		for ($parcelaNum = 1; $parcelaNum <= $numParcelas; $parcelaNum++) {
			Parcela::create([
				'movimentacao_id' => $movimentacao->id,
				'numero' => $parcelaNum,
				'valor' => $valorParcela,
				'data_vencimento' => $dataVencimento->format('Y-m-d'), // Formato YYYY-MM-DD
				// Outros campos da parcela, se houver
			]);

			// Calcula a data de vencimento da próxima parcela (assumindo mensalmente)
			$dataVencimento->addMonth();
		}
	}

	/**
	 * Atualiza uma movimentação existente.
	 *
	 * @param Movimentacao $movimentacao A movimentação a ser atualizada.
	 * @param array $validated Dados validados.
	 */
	public function updateMovimentacao(Movimentacao $movimentacao, array $validated): void
	{
		$movimentacaoData = $this->prepareMovimentacaoDataForUpdate($validated);
		$movimentacao->update($movimentacaoData);
	}

	/**
	 * Prepara os dados para a atualização de uma movimentação.
	 * Mapeia 'data_movimentacao' para 'data'.
	 *
	 * @param array $validated Dados validados.
	 * @return array Dados preparados para atualização.
	 */
	private function prepareMovimentacaoDataForUpdate(array $validated): array
	{
		$validated['data'] = $validated['data_movimentacao'];
		unset($validated['data_movimentacao']);
		// Retorna apenas os campos que podem ser atualizados, para evitar mass assignment issues
		$movimentacaoFields = ['categoria_id', 'descricao', 'valor', 'data', 'tipo', 'conta_id']; // Ajuste conforme os campos do seu Model Movimentacao
		return array_intersect_key($validated, array_flip($movimentacaoFields));
	}

	/**
	 * Deleta uma movimentação.
	 *
	 * @param Movimentacao $movimentacao A movimentação a ser deletada.
	 */
	public function destroyMovimentacao(Movimentacao $movimentacao): void
	{
		$movimentacao->delete();
	}

	/**
	 * Deleta várias movimentações.
	 *
	 * @param array $movimentacoesIds Array com os IDs das movimentações a serem deletadas.
	 */
	public function destroyManyMovimentacoes(array $movimentacoesIds): void
	{
		Movimentacao::whereIn('id', $movimentacoesIds)
			->where('user_id', Auth::id())
			->delete();
	}

	public function pagarParcelas(Movimentacao $movimentacao, array $data): void
	{
		\Illuminate\Support\Facades\DB::transaction(function () use ($movimentacao, $data) {
			$quantidade = $data['quantidade_parcelas'];
			$dataPagamento = $data['data_pagamento'];
			$valorTotalPago = (float) $data['valor_total_pago'];

			// 1. Marca as próximas parcelas pendentes como pagas
			$parcelasAPagar = $movimentacao->parcelas()
				->where('pago', false)
				->orderBy('numero')
				->take($quantidade)
				->get();

			foreach ($parcelasAPagar as $parcela) {
				$parcela->update([
					'pago' => true,
					'data_pagamento' => $dataPagamento,
				]);
			}

			// 2. Cria uma nova movimentação de GASTO para representar a saída de caixa
			// Não criamos uma entrada na tabela 'parcelas' aqui, pois para GASTO/GANHO
			// a movimentação já é a entidade principal e o sistema não exige entrada na tabela parcelas.
			Movimentacao::create([
				'user_id'      => $movimentacao->user_id,
				'categoria_id' => $movimentacao->categoria_id,
				'data'         => $dataPagamento,
				'descricao'    => "Pagamento de {$quantidade} parcela(s) de: " . $movimentacao->descricao,
				'valor'        => $valorTotalPago,
				'tipo'         => TipoMovimentacaoEnum::GASTO->value,
				'parcelas'     => 1,
			]);
		});
	}

	/**
	 * Processa o pagamento de parcelas para múltiplas movimentações.
	 *
	 * @param array $movimentacaoIds IDs das movimentações a serem pagas.
	 * @param array $data Dados do pagamento (quantidade, data).
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
					Movimentacao::create([
						'user_id'      => $movimentacao->user_id,
						'categoria_id' => $movimentacao->categoria_id,
						'data'         => $dataPagamento,
						'descricao'    => "Pagamento de {$quantidade} parcela(s) de: " . $movimentacao->descricao,
						'valor'        => $valorPagoNestaMovimentacao,
						'tipo'         => TipoMovimentacaoEnum::GASTO->value,
						'parcelas'     => 1,
					]);
				}
			}
		});
	}
}
