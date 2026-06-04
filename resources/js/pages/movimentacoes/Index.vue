<script setup lang="ts">
import ConfirmarExclusaoModal from '@/components/modals/ConfirmarExclusaoModal.vue';
import PagamentoParcelaModal from '@/components/modals/PagamentoParcelaModal.vue';
import PagamentoMassaModal from '@/components/modals/PagamentoMassaModal.vue';
import DetalhesMovimentacaoModal from '@/components/modals/DetalhesMovimentacaoModal.vue';
import TabelaMovimentacoes from '@/components/movimentacoes/TabelaMovimentacoes.vue';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import TotalCard from '@/components/movimentacoes/ValorCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import movimentacoesRoutes from '@/routes/movimentacoes';
import type { BreadcrumbItem, Movimentacao, ParcelaComMovimentacao, Paginated } from '@/types';
import { Head, router, Link } from '@inertiajs/vue3';
import { Check, Hourglass, Wallet, X, Plus, ArrowUp, ArrowDown, CircleDollarSign } from 'lucide-vue-next';
import MovimentacoesMobileList from '@/components/movimentacoes/MovimentacoesMobileList.vue';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import isValidDate from '@/utils/validaData';
import { Button } from '@/components/ui/button';

const isMediumScreen = ref(false);

const checkScreenSize = () => {
  isMediumScreen.value = window.innerWidth < 1024;
};

onMounted(() => {
  checkScreenSize();
  window.addEventListener('resize', checkScreenSize);
});

onUnmounted(() => {
  window.removeEventListener('resize', checkScreenSize);
});

const props = defineProps<{
  movimentacoes: Paginated<Movimentacao>;
  parcelasFuturas: Paginated<ParcelaComMovimentacao>;
  totais: {
    total: number;
    ganhos: number;
    despesas: number;
    pago: number;
    pendente: number;
  };
  filters: {
    busca: string;
    data_inicio: string;
    data_fim: string;
    mes: string;
    ano: string;
    per_page: number;
    tipo: string;
  };
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Movimentações', href: movimentacoesRoutes.index().url },
];

const tabs = [
  { title: 'Todas', tipo: 'todos' },
  { title: 'Ganhos', tipo: 'ganho' },
  { title: 'Despesas', tipo: 'gasto' },
  { title: 'Futuras', tipo: 'gasto futuro' },
];

const abaAtiva = ref(props.filters.tipo || 'todos');

const dataInicio = ref(props.filters.data_inicio || '');
const dataFim = ref(props.filters.data_fim || '');
const buscaTexto = ref(props.filters.busca || '');
const mesSelecionado = ref(props.filters.mes || '');
const anoSelecionado = ref(props.filters.ano || '');
const perPage = ref(props.filters.per_page || 50);

const currentFilters = computed(() => {
  const params: Record<string, any> = {
    tipo: abaAtiva.value,
    per_page: perPage.value,
  };

  if (abaAtiva.value === 'todos' || abaAtiva.value === 'ganho' || abaAtiva.value === 'gasto') {
    if (dataInicio.value) params.data_inicio = dataInicio.value;
    if (dataFim.value) params.data_fim = dataFim.value;
  } else if (abaAtiva.value === 'gasto futuro') {
    if (mesSelecionado.value) params.mes = mesSelecionado.value;
    if (anoSelecionado.value) params.ano = anoSelecionado.value;
  }

  if (buscaTexto.value) params.busca = buscaTexto.value;

  return params;
});

const meses = [
  { value: '1', label: 'Janeiro' }, { value: '2', label: 'Fevereiro' }, { value: '3', label: 'Março' },
  { value: '4', label: 'Abril' }, { value: '5', label: 'Maio' }, { value: '6', label: 'Junho' },
  { value: '7', label: 'Julho' }, { value: '8', label: 'Agosto' }, { value: '9', label: 'Setembro' },
  { value: '10', label: 'Outubro' }, { value: '11', label: 'Novembro' }, { value: '12', label: 'Dezembro' },
];

const currentDate = new Date();
// Inicializa com mês/ano atual se não vier da prop
onMounted(() => {
  if (abaAtiva.value === 'gasto futuro' || (!mesSelecionado.value && !anoSelecionado.value)) {
    if (!mesSelecionado.value) mesSelecionado.value = String(currentDate.getMonth() + 1);
    if (!anoSelecionado.value) anoSelecionado.value = String(currentDate.getFullYear());
  }

  if ((abaAtiva.value === 'todos' || abaAtiva.value === 'ganho' || abaAtiva.value === 'gasto') && !dataInicio.value && !dataFim.value) {
    const now = new Date();
    const primeiroDia = new Date(now.getFullYear(), now.getMonth(), 1);
    const ultimoDia = new Date(now.getFullYear(), now.getMonth() + 1, 0);

    dataInicio.value = primeiroDia.toISOString().split('T')[0];
    dataFim.value = ultimoDia.toISOString().split('T')[0];
    triggerSearch();
  }
});

const total = computed(() => Number(props.totais.total));
const totalGanhos = computed(() => Number(props.totais.ganhos));
const totalDespesas = computed(() => Number(props.totais.despesas));
const totalDisponivel = computed(() => Number(props.totais.ganhos) - Number(props.totais.despesas));
const totalPago = computed(() => Number(props.totais.pago));
const totalPendente = computed(() => Number(props.totais.pendente));

const isModalExclusaoAberto = ref(false);
const movimentacaoParaExcluir = ref<Movimentacao | null>(null);

const isModalPagamentoAberto = ref(false);
const movimentacaoParaPagar = ref<Movimentacao | null>(null);

const isModalPagamentoMassaAberto = ref(false);
const movimentacoesParaPagarMassa = ref<Movimentacao[]>([]);

const isModalDetalhesAberto = ref(false);
const itemParaDetalhes = ref<Movimentacao | ParcelaComMovimentacao | null>(null);

function handleShowDetails(item: Movimentacao | ParcelaComMovimentacao) {
  itemParaDetalhes.value = item;
  isModalDetalhesAberto.value = true;
}

function handlePay(movimentacao: Movimentacao) {
  movimentacaoParaPagar.value = movimentacao;
  isModalPagamentoAberto.value = true;
}

function handlePayMany(ids: number[]) {
  // Se estivermos na aba 'gasto futuro', as movimentações estão dentro do objeto parcela
  if (abaAtiva.value === 'gasto futuro') {
    const uniqueMovs = new Map<number, Movimentacao>();
    props.parcelasFuturas.data
      .filter(p => ids.includes(p.movimentacao.id))
      .forEach(p => {
        if (!uniqueMovs.has(p.movimentacao.id)) {
          uniqueMovs.set(p.movimentacao.id, p.movimentacao);
        }
      });
    movimentacoesParaPagarMassa.value = Array.from(uniqueMovs.values());
  } else {
    // Caso contrário, busca no array de movimentações normal
    movimentacoesParaPagarMassa.value = props.movimentacoes.data.filter(m => ids.includes(m.id));
  }
  isModalPagamentoMassaAberto.value = true;
}

function requestDelete(movimentacao: Movimentacao) {
  movimentacaoParaExcluir.value = movimentacao;
  isModalExclusaoAberto.value = true;
}

function confirmDelete() {
  if (movimentacaoParaExcluir.value) {
    router.delete(movimentacoesRoutes.destroy({ movimentacao: movimentacaoParaExcluir.value.id }).url, {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        isModalExclusaoAberto.value = false;
        movimentacaoParaExcluir.value = null;
      },
    });
  }
}

const selectedMovimentacoes = ref<number[]>([]);
const isModalExclusaoMassaAberto = ref(false);

const requestDeleteMany = (movimentacoesIds: number[]) => {
  selectedMovimentacoes.value = movimentacoesIds;
  isModalExclusaoMassaAberto.value = true;
};

const confirmDeleteMany = () => {
  if (selectedMovimentacoes.value.length > 0) {
    router.delete(movimentacoesRoutes.destroyMany().url, {
      data: {
        movimentacoes_ids: selectedMovimentacoes.value,
      },
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        isModalExclusaoMassaAberto.value = false;
        selectedMovimentacoes.value = [];
      },
    });
  }
};

const debounceTimer = ref<any>(null);

const triggerSearch = () => {
  const params: Record<string, string | number | null> = {};

  if (abaAtiva.value === 'todos' || abaAtiva.value === 'ganho' || abaAtiva.value === 'gasto') {
    if (dataInicio.value) params.data_inicio = dataInicio.value;
    if (dataFim.value) params.data_fim = dataFim.value;
  } else if (abaAtiva.value === 'gasto futuro') {
    if (mesSelecionado.value) params.mes = mesSelecionado.value;
    if (anoSelecionado.value) params.ano = anoSelecionado.value;
  }

  if (buscaTexto.value) {
    params.busca = buscaTexto.value;
  }
  params.tipo = abaAtiva.value;
  params.per_page = perPage.value;

  router.get(movimentacoesRoutes.index().url, params, {
    preserveState: true,
    replace: true,
  });
};

function changeTab(newAba: string) {
  if (abaAtiva.value === newAba) return;

  abaAtiva.value = newAba;
  selectedMovimentacoes.value = []; // Limpa seleção ao trocar de aba

  if (newAba === 'gasto futuro') {
    if (!mesSelecionado.value || !anoSelecionado.value) {
      const now = new Date();
      mesSelecionado.value = String(now.getMonth() + 1);
      anoSelecionado.value = String(now.getFullYear());
    }
  } else {
    if (!dataInicio.value || !dataFim.value) {
      const now = new Date();
      const primeiroDia = new Date(now.getFullYear(), now.getMonth(), 1);
      const ultimoDia = new Date(now.getFullYear(), now.getMonth() + 1, 0);

      dataInicio.value = primeiroDia.toISOString().split('T')[0];
      dataFim.value = ultimoDia.toISOString().split('T')[0];
    }
  }

  triggerSearch();
}

// Watchers para disparar a busca quando os filtros mudam
watch([dataInicio, dataFim], () => {
  if (abaAtiva.value === 'todos' || abaAtiva.value === 'ganho' || abaAtiva.value === 'gasto') {
    // Só dispara se ambas estiverem vazias (limpeza) ou se ambas forem válidas e a ordem estiver correta
    const inicioValido = isValidDate(dataInicio.value);
    const fimValido = isValidDate(dataFim.value);

    if (!dataInicio.value && !dataFim.value) {
      triggerSearch();
      return;
    }

    if (inicioValido && fimValido) {
      if (new Date(dataInicio.value) <= new Date(dataFim.value)) {
        triggerSearch();
      }
    }
  }
});

watch([mesSelecionado, anoSelecionado, perPage], () => {
  if (abaAtiva.value === 'gasto futuro') {
    const ano = parseInt(anoSelecionado.value);
    const anoValido = !isNaN(ano) && ano >= 2000 && ano <= new Date().getFullYear() + 10;
    
    if (mesSelecionado.value && anoValido) {
      triggerSearch();
    }
  } else {
    triggerSearch();
  }
});

watch(buscaTexto, () => {
  clearTimeout(debounceTimer.value);
  debounceTimer.value = setTimeout(() => {
    triggerSearch();
  }, 500);
});

const limparFiltros = () => {
  const now = new Date();

  if (abaAtiva.value === 'gasto futuro'){
    mesSelecionado.value = String(currentDate.getMonth() + 1);
    anoSelecionado.value = String(currentDate.getFullYear());
  }else{
    const primeiroDia = new Date(now.getFullYear(), now.getMonth(), 1);
    const ultimoDia = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    dataInicio.value = primeiroDia.toISOString().split('T')[0];
    dataFim.value = ultimoDia.toISOString().split('T')[0];
  }

  buscaTexto.value = '';
  triggerSearch();
};

const paginacao = computed(() => {
  return abaAtiva.value === 'gasto futuro' ? props.parcelasFuturas : props.movimentacoes;
});

const paginasVisiveis = computed(() => {
  const total = paginacao.value.last_page;
  const atual = paginacao.value.current_page;
  const delta = 1; // Páginas ao redor da atual
  
  const range: (number | string)[] = [];
  
  for (let i = 1; i <= total; i++) {
    if (
      i === 1 || 
      i === total || 
      (i >= atual - delta && i <= atual + delta)
    ) {
      range.push(i);
    } else if (
      (i === atual - delta - 1) || 
      (i === atual + delta + 1)
    ) {
      range.push('...');
    }
  }
  
  // Remove elipses duplicadas
  return range.filter((item, pos) => range.indexOf(item) === pos);
});

const getPageUrl = (page: number | string) => {
  if (page === '...') return '#';
  const url = new URL(paginacao.value.path, window.location.origin);
  const params = new URLSearchParams(window.location.search);
  params.set('page', String(page));
  return `${url.pathname}?${params.toString()}`;
};
</script>

<template>

  <Head title="Movimentações" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto w-full max-w-7xl px-4 py-8 pb-32 sm:px-6 lg:px-8 sm:pb-8">
      <div class="flex flex-col gap-8">
        <!-- Header Minimalista -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
          <div class="space-y-1">
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100 sm:text-3xl">
              Movimentações
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              Gerencie seu fluxo financeiro e acompanhe seus gastos futuros de forma simples.
            </p>
          </div>

          <div class="hidden sm:block">
            <Link :href="movimentacoesRoutes.create({ query: currentFilters }).url">
              <Button class="bg-blue-600 hover:bg-blue-700 text-white shadow-sm transition-all px-6">
                <Plus class="mr-2 h-4 w-4" />
                Nova Movimentação
              </Button>
            </Link>
          </div>
        </div>

        <div class="space-y-6">
          <!-- Tabs Elegantes -->
          <div class="border-b border-gray-200 dark:border-gray-800">
            <nav class="-mb-px flex space-x-8 overflow-x-auto pb-px">
              <button
                v-for="tab in tabs"
                :key="tab.tipo"
                @click="changeTab(tab.tipo)"
                class="whitespace-nowrap border-b-2 py-4 text-sm font-medium transition-all duration-200 hover:cursor-pointer"
                :class="[
                  abaAtiva === tab.tipo
                    ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                ]"
              >
                {{ tab.title }}
              </button>
            </nav>
          </div>

          <div class="space-y-6">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                <!-- Filtros e Busca -->
                <div class="flex flex-1 flex-col gap-4 max-w-2xl">
                  <div class="flex flex-col sm:flex-row gap-3">
                    <!-- Filtro de data (Invisível apenas na aba 'gasto futuro') -->
                    <div v-if="abaAtiva === 'todos' || abaAtiva === 'ganho' || abaAtiva === 'gasto'"
                      class="space-y-2 sm:space-y-0 sm:flex sm:flex-1 sm:gap-2 relative">
                      <div class="flex">
                        <Input v-model="dataInicio" type="date" id="dataInicio" class="h-9 text-xs" 
                          :class="{'border-red-500 focus-visible:ring-red-500': dataInicio && !isValidDate(dataInicio)}" />
                      </div>
                      <div class="flex">
                        <Input v-model="dataFim" type="date" id="dataFim" class="h-9 text-xs" 
                          :class="{'border-red-500 focus-visible:ring-red-500': (dataFim && !isValidDate(dataFim)) || (dataInicio && dataFim && new Date(dataInicio) > new Date(dataFim))}" />
                      </div>
                    </div>

                    <!-- Filtro mês/ano para gastos futuros -->
                    <div v-if="abaAtiva === 'gasto futuro'" class="flex flex-1 gap-2">
                      <Select v-model="mesSelecionado">
                        <SelectTrigger class="h-9 text-xs">
                          <SelectValue placeholder="Mês" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem v-for="mes in meses" :key="mes.value" :value="mes.value">
                            {{ mes.label }}
                          </SelectItem>
                        </SelectContent>
                      </Select>
                      <Input type="text" v-model="anoSelecionado" placeholder="Ano" class="h-9 text-xs w-20" 
                        :class="{'border-red-500 focus-visible:ring-red-500': anoSelecionado && (isNaN(parseInt(anoSelecionado)) || parseInt(anoSelecionado) < 2000 || parseInt(anoSelecionado) > new Date().getFullYear() + 10)}" />
                    </div>

                    <!-- Filtro de Busca por Texto -->
                    <div class="flex flex-[1.5] items-center gap-2">
                      <div class="relative flex-1">
                        <Input v-model="buscaTexto" placeholder="Buscar descrição..." class="h-9 pl-3 text-xs" />
                      </div>
                      <Button variant="outline" size="sm" @click="limparFiltros" class="h-9 px-2 text-xs">
                        <X class="mr-1 h-3 w-3" />
                        Limpar
                      </Button>
                    </div>
                  </div>
                </div>

                <!-- Totais -->
                <div class="grid grid-cols-3 gap-2 sm:gap-3 lg:w-1/3 min-w-[320px]">
                  <!-- Caso 'gasto futuro' -->
                  <template v-if="abaAtiva === 'gasto futuro'">
                    <TotalCard :icon="CircleDollarSign" :icon-classes="'text-red-600'" title="Total" :value="total" />
                    <TotalCard :icon="Check" :icon-classes="'text-green-600'" title="Pago" :value="totalPago" />
                    <TotalCard :icon="Hourglass" :icon-classes="'text-yellow-600'" title="Pendente" :value="totalPendente" />
                  </template>
                  
                  <!-- Caso 'todos' -->
                  <template v-else-if="abaAtiva === 'todos'">
                    <TotalCard :icon="ArrowUp" title="Ganhos" :value="totalGanhos" icon-classes="text-green-600" />
                    <TotalCard :icon="ArrowDown" title="Despesas" :value="totalDespesas" icon-classes="text-red-600" />
                    <TotalCard :icon="Wallet" title="Saldo" :value="totalDisponivel" 
                      :valueColorClass="totalDisponivel >= 0 ? 'text-green-600' : 'text-red-600'" />
                  </template>

                  <!-- Caso simples (ganho/gasto) -->
                  <template v-else>
                    <div class="col-span-2 hidden lg:block"></div>
                    <TotalCard 
                      v-if="abaAtiva === 'ganho'" 
                      :icon="ArrowUp" 
                      title="Total Ganhos" 
                      :value="total" 
                      icon-classes="text-green-600" 
                    />
                    <TotalCard 
                      v-else-if="abaAtiva === 'gasto'" 
                      :icon="ArrowDown" 
                      title="Total Despesas" 
                      :value="total" 
                      icon-classes="text-red-600" 
                    />
                  </template>
                </div>
            </div>

            <!-- Tabelas de Movimentações/Parcelas -->
            <TabelaMovimentacoes v-if="!isMediumScreen"
              :key="'desktop-' + abaAtiva"
              :movimentacoes="abaAtiva === 'gasto futuro' ? [] : props.movimentacoes.data"
              :parcelas="abaAtiva === 'gasto futuro' ? props.parcelasFuturas.data : []" :active-tab="abaAtiva"
              :filters="currentFilters"
              @delete="requestDelete" @delete:selected="requestDeleteMany" @pay="handlePay" @pay:selected="handlePayMany"
              @show-details="handleShowDetails" v-model:selectedMovimentacoes="selectedMovimentacoes" />
            <MovimentacoesMobileList v-else-if="isMediumScreen"
              :key="'mobile-' + abaAtiva"
              :movimentacoes="abaAtiva === 'gasto futuro' ? [] : props.movimentacoes.data"
              :parcelas="abaAtiva === 'gasto futuro' ? props.parcelasFuturas.data : []" :active-tab="abaAtiva"
              :filters="currentFilters"
              @delete="requestDelete" @delete:selected="requestDeleteMany" @pay="handlePay" @pay:selected="handlePayMany"
              @show-details="handleShowDetails" />

            <!-- Paginação Responsiva -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-6 border-t border-gray-100 dark:border-gray-800">
              <!-- Info e Itens por Página -->
              <div class="flex items-center justify-between w-full sm:w-auto gap-6">
                <div class="text-[10px] text-gray-500 uppercase font-bold tracking-wider">
                  {{ paginacao.from || 0 }} - {{ paginacao.to || 0 }} de {{ paginacao.total || 0 }} registros
                </div>
                
                <div class="flex items-center gap-2">
                  <span class="text-[10px] text-gray-400 uppercase font-bold hidden sm:inline">Exibir:</span>
                  <Select v-model="perPage">
                    <SelectTrigger class="h-8 text-[10px] w-16 bg-transparent border-gray-200 dark:border-gray-800">
                      <SelectValue :placeholder="String(perPage)" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="10">10</SelectItem>
                      <SelectItem value="25">25</SelectItem>
                      <SelectItem value="50">50</SelectItem>
                      <SelectItem value="100">100</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <!-- Navegação Inteligente -->
              <div class="flex items-center justify-center w-full sm:w-auto gap-1">
                <!-- Anterior -->
                <Link 
                  :href="paginacao.links[0].url || '#'"
                  preserve-scroll 
                  preserve-state
                  class="h-9 flex items-center justify-center px-2 text-[10px] font-bold uppercase border rounded-md transition-all"
                  :class="paginacao.links[0].url ? 'text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm hover:border-blue-300' : 'text-gray-300 dark:text-gray-700 border-gray-100 dark:border-gray-900 opacity-50 cursor-not-allowed'"
                >
                  <span class="text-[7pt] sm:inline">« Anterior</span>
                </Link>

                <!-- Páginas -->
                <div class="flex items-center gap-1">
                  <template v-for="(page, index) in paginasVisiveis" :key="index">
                    <span v-if="page === '...'" class="px-1 text-gray-400 text-xs font-bold">...</span>
                    <Link v-else
                      :href="getPageUrl(page)"
                      preserve-scroll 
                      preserve-state 
                      class="min-w-[32px] sm:min-w-[36px] h-9 flex items-center justify-center px-2 text-[10px] font-black border rounded-md transition-all duration-200" 
                      :class="{
                        'bg-blue-600 text-white border-blue-600 shadow-sm z-10': page === paginacao.current_page,
                        'text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 hover:border-blue-300': page !== paginacao.current_page,
                      }">
                      {{ page }}
                    </Link>
                  </template>
                </div>

                <!-- Próximo -->
                <Link 
                  :href="paginacao.links[paginacao.links.length - 1].url || '#'"
                  preserve-scroll 
                  preserve-state
                  class="h-9 flex items-center justify-center px-2 text-[10px] font-bold uppercase border rounded-md transition-all"
                  :class="paginacao.links[paginacao.links.length - 1].url ? 'text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm hover:border-blue-300' : 'text-gray-300 dark:text-gray-700 border-gray-100 dark:border-gray-900 opacity-50 cursor-not-allowed'"
                >
                  <span class="text-[7pt] sm:inline">Próximo »</span>
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <DetalhesMovimentacaoModal v-model:open="isModalDetalhesAberto" :item="itemParaDetalhes" />

    <ConfirmarExclusaoModal v-model:open="isModalExclusaoAberto" title="Excluir movimentação"
      message="Esta movimentação será removida permanentemente." description="Essa ação não pode ser desfeita."
      confirm-text="Excluir" @confirm="confirmDelete" @cancel="isModalExclusaoAberto = false" />

    <ConfirmarExclusaoModal v-model:open="isModalExclusaoMassaAberto" title="Excluir movimentações"
      message="As movimentações selecionadas serão removidas permanentemente."
      description="Essa ação não pode ser desfeita." confirm-text="Excluir" @confirm="confirmDeleteMany"
      @cancel="isModalExclusaoMassaAberto = false" />

    <PagamentoParcelaModal v-model:open="isModalPagamentoAberto" :movimentacao="movimentacaoParaPagar" />
    <PagamentoMassaModal v-model:open="isModalPagamentoMassaAberto" :movimentacoes="movimentacoesParaPagarMassa" />

    <!-- Floating Action Button para Telas Pequenas -->
    <div v-if="isMediumScreen" class="fixed bottom-4 right-4 z-50">
      <Link :href="movimentacoesRoutes.create({ query: currentFilters }).url"
        class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-600 text-white shadow-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
        <Plus class="h-6 w-6" />
      </Link>
    </div>
  </AppLayout>
</template>
