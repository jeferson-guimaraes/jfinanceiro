<?php

namespace App\Http\Controllers\Api;

use App\Enums\TipoMovimentacaoEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ConsultaAssistenteRequest;
use App\Http\Requests\Api\LancarMovimentacaoRequest;
use App\Http\Requests\Api\PagarParcelasApiRequest;
use App\Http\Resources\CategoriaResource;
use App\Http\Resources\MovimentacaoResource;
use App\Http\Resources\ParcelaResource;
use App\Models\Movimentacao;
use App\Services\AssistenteFinanceiroService;
use App\Services\DashboardService;
use App\Services\MovimentacaoService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * API do assistente (Logan e afins): consulta categorias, parcelas pendentes e resumo do mês; lança movimentações
 * e paga parcelas. Tudo em nome do dono do token; a lógica fica nos Services.
 */
class AssistenteFinanceiroController extends Controller
{
    public function categorias(ConsultaAssistenteRequest $request, AssistenteFinanceiroService $service): AnonymousResourceCollection
    {
        $tipo = $request->validated('tipo') ? TipoMovimentacaoEnum::from($request->validated('tipo')) : null;

        return CategoriaResource::collection($service->categoriasDisponiveis($request->user(), $tipo));
    }

    public function parcelasPendentes(ConsultaAssistenteRequest $request, MovimentacaoService $service): AnonymousResourceCollection
    {
        $ate = Carbon::parse($request->validated('ate', now('America/Sao_Paulo')->toDateString()));

        return ParcelaResource::collection($service->parcelasPendentesAte($request->user()->id, $ate));
    }

    public function resumo(ConsultaAssistenteRequest $request, DashboardService $service): JsonResponse
    {
        $mes = Carbon::createFromFormat('Y-m-d', $request->validated('mes', now('America/Sao_Paulo')->format('Y-m')).'-01');

        return response()->json(['data' => ['mes' => $mes->format('Y-m')] + $service->getTotaisDoMes($request->user(), $mes)]);
    }

    public function lancar(LancarMovimentacaoRequest $request, AssistenteFinanceiroService $service): JsonResponse
    {
        // Envelope explícito: o recurso tem um campo "data" (a data do lançamento), e nesse caso o Laravel não
        // aplicaria o envelope padrão.
        $movimentacao = $service->lancar($request->user(), $request->validated());

        return response()->json(['data' => (new MovimentacaoResource($movimentacao))->resolve($request)], 201);
    }

    public function pagar(PagarParcelasApiRequest $request, Movimentacao $movimentacao, MovimentacaoService $service): JsonResponse
    {
        abort_unless($movimentacao->user_id === $request->user()->id, 404, 'Registro não encontrado.');

        $gasto = $service->pagarParcelas($movimentacao, $request->dadosDoPagamento());

        return response()->json(['data' => (new MovimentacaoResource($gasto->load('categoria')))->resolve($request)]);
    }
}
