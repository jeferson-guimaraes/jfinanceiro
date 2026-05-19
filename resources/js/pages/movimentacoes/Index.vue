<script setup lang="ts">
import ConfirmarExclusaoModal from '@/components/modals/ConfirmarExclusaoModal.vue';
import TabelaMovimentacoes from '@/components/movimentacoes/TabelaMovimentacoes.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import TotalCard from '@/components/movimentacoes/ValorCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import movimentacoesRoutes from '@/routes/movimentacoes';
import type { BreadcrumbItem, Movimentacao, ParcelaComMovimentacao } from '@/types';
import { Head, router, Link } from '@inertiajs/vue3';
import { Check, Hourglass, Wallet, X, Plus, ArrowUp, ArrowDown, CircleDollarSign } from 'lucide-vue-next';
import MovimentacoesCardList from '@/components/movimentacoes/MovimentacoesCardList.vue';
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
  movimentacoes: Movimentacao[];
  parcelasFuturas: ParcelaComMovimentacao[];
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

const abaAtiva = ref('todos');

const dataInicio = ref('');
const dataFim = ref('');
const buscaTexto = ref('');
const mesSelecionado = ref('');
const anoSelecionado = ref('');

const meses = [
  { value: '1', label: 'Janeiro' }, { value: '2', label: 'Fevereiro' }, { value: '3', label: 'Março' },
  { value: '4', label: 'Abril' }, { value: '5', label: 'Maio' }, { value: '6', label: 'Junho' },
  { value: '7', label: 'Julho' }, { value: '8', label: 'Agosto' }, { value: '9', label: 'Setembro' },
  { value: '10', label: 'Outubro' }, { value: '11', label: 'Novembro' }, { value: '12', label: 'Dezembro' },
];

const currentDate = new Date();
// Inicializa com mês/ano atual
onMounted(() => {
  const now = new Date();
  const primeiroDia = new Date(now.getFullYear(), now.getMonth(), 1);
  const ultimoDia = new Date(now.getFullYear(), now.getMonth() + 1, 0);

  dataInicio.value = primeiroDia.toISOString().split('T')[0];
  dataFim.value = ultimoDia.toISOString().split('T')[0];

  if (abaAtiva.value === 'gasto futuro' || (!mesSelecionado.value && !anoSelecionado.value)) {
    mesSelecionado.value = String(currentDate.getMonth() + 1);
    anoSelecionado.value = String(currentDate.getFullYear());
  }
  triggerSearch();
});

const total = computed(() => {
  if (abaAtiva.value === 'gasto futuro') {
    return props.parcelasFuturas.reduce((acc, parcela) => acc + Number(parcela.valor), 0);
  }
  return props.movimentacoes.reduce((acc, movimentacao) => acc + Number(movimentacao.valor), 0);
});

const totalGanhos = computed(() => {
  if (abaAtiva.value !== 'todos') return 0;
  return props.movimentacoes
    .filter(m => m.tipo === 'ganho')
    .reduce((acc, m) => acc + Number(m.valor), 0);
});

const totalDespesas = computed(() => {
  if (abaAtiva.value !== 'todos') return 0;
  return props.movimentacoes
    .filter(m => m.tipo === 'gasto')
    .reduce((acc, m) => acc + Number(m.valor), 0);
});

const totalDisponivel = computed(() => {
  if (abaAtiva.value !== 'todos') return 0;
  return totalGanhos.value - totalDespesas.value;
});

const totalPago = computed(() => {
  if (abaAtiva.value !== 'gasto futuro') return 0;
  return props.parcelasFuturas.reduce((total, parcela) => {
    if (!parcela.pago) return total;
    const valor = Number(parcela.valor);
    return total + (isNaN(valor) ? 0 : valor);
  }, 0);
});

const totalPendente = computed(() => {
  if (abaAtiva.value !== 'gasto futuro') return 0;
  return total.value - totalPago.value;
});

const isModalExclusaoAberto = ref(false);
const movimentacaoParaExcluir = ref<Movimentacao | null>(null);

function requestDelete(movimentacao: Movimentacao) {
  movimentacaoParaExcluir.value = movimentacao;
  isModalExclusaoAberto.value = true;
}

function confirmDelete() {
  if (movimentacaoParaExcluir.value) {
    router.delete(movimentacoesRoutes.destroy({ movimentacao: movimentacaoParaExcluir.value.id }).url, {
      preserveScroll: true,
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

  router.get(movimentacoesRoutes.index().url, params, {
    preserveState: true,
    replace: true,
  });
};

function changeTab(newAba: string) {
  if (abaAtiva.value === newAba) return;

  abaAtiva.value = newAba;

  if (newAba === 'gasto futuro') {
    dataInicio.value = '';
    dataFim.value = '';
    if (!mesSelecionado.value || !anoSelecionado.value) {
      const now = new Date();
      mesSelecionado.value = String(now.getMonth() + 1);
      anoSelecionado.value = String(now.getFullYear());
    }
  } else {
    mesSelecionado.value = '';
    anoSelecionado.value = '';
  }

  triggerSearch();
}

// Watchers para disparar a busca quando os filtros mudam
watch([dataInicio, dataFim], () => {
  if (abaAtiva.value === 'todos' || abaAtiva.value === 'ganho' || abaAtiva.value === 'gasto') {
    if ((isValidDate(dataInicio.value) && isValidDate(dataFim.value)) || (!dataInicio.value && !dataFim.value)) {
      triggerSearch();
    }
  }
});

watch([mesSelecionado, anoSelecionado], () => {
  if (abaAtiva.value === 'gasto futuro') {
    if (anoSelecionado.value.length === 4 || (!mesSelecionado.value && !anoSelecionado.value)) {
      triggerSearch();
    }
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
</script>

<template>

  <Head title="Movimentações" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <div class="space-y-2 xl:px-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <h2 class="text-base leading-7 font-semibold text-gray-900 dark:text-gray-100">
              Movimentações
            </h2>
            <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-400">
              Visualize todas as suas movimentações financeiras.
            </p>
          </div>

          <div class="hidden md:block">
            <Link :href="movimentacoesRoutes.create({ query: { tipo: abaAtiva !== 'todos' ? abaAtiva : 'ganho' } }).url">
              <Button class="w-full md:w-auto">
                Nova Movimentação
              </Button>
            </Link>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-x-6 gap-y-4">
          <div>
            <div class="border-b border-gray-200 dark:border-gray-700">
              <ul class="-mb-px flex text-sm font-medium text-gray-500 dark:text-gray-400">
                <li v-for="tab in tabs" :key="tab.tipo">
                  <button class="border-b-2 p-4 hover:cursor-pointer" :class="[
                    abaAtiva === tab.tipo
                      ? 'border-blue-600 text-blue-600'
                      : 'border-transparent hover:border-gray-300 hover:text-gray-600']" @click="changeTab(tab.tipo)">
                    {{ tab.title }}
                  </button>
                </li>
              </ul>
            </div>

            <div class="rounded-b-lg bg-gray-50 p-4 dark:bg-sidebar">
              <div class="mb-4 grid grid-cols-1 xl:grid-cols-2 gap-6">
                <!-- Filtros e Busca -->
                <div class="flex flex-col gap-4">
                  <!-- Filtro de data (Invisível apenas na aba 'gasto futuro') -->
                  <div v-if="abaAtiva === 'todos' || abaAtiva === 'ganho' || abaAtiva === 'gasto'"
                    class="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div class="flex flex-col gap-1">
                        <Label for="dataInicio">De:</Label>
                        <Input v-model="dataInicio" type="date" id="dataInicio" />
                      </div>
                      <div class="flex flex-col gap-1">
                        <Label for="dataFim">Até:</Label>
                        <Input v-model="dataFim" type="date" id="dataFim" />
                      </div>
                  </div>

                  <!-- Filtro mês/ano para gastos futuros -->
                  <div v-if="abaAtiva === 'gasto futuro'" class="flex flex-col gap-2">
                    <Label>Período:</Label>
                    <div class="flex gap-2">
                      <Select v-model="mesSelecionado">
                        <SelectTrigger>
                          <SelectValue placeholder="Mês" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem v-for="mes in meses" :key="mes.value" :value="mes.value">
                            {{ mes.label }}
                          </SelectItem>
                        </SelectContent>
                      </Select>
                      <Input type="text" v-model="anoSelecionado" placeholder="Ano" />
                    </div>
                  </div>

                  <!-- Filtro de Busca por Texto -->
                  <div class="flex items-end gap-2">
                    <Input v-model="buscaTexto" placeholder="Buscar por descrição..." class="flex-1" />
                    <button type="button" @click="limparFiltros"
                      class="border border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 flex items-center gap-2">
                      <X class="w-5 h-5" />
                      <span class="text-sm">Limpar</span>
                    </button>
                  </div>
                </div>

                <!-- Totais -->
                <div class="grid grid-cols-2 lg:grid-cols-3 gap-2">
                  <!-- Caso 'gasto futuro' -->
                  <template v-if="abaAtiva === 'gasto futuro'">
                    <TotalCard :icon="CircleDollarSign" :icon-classes="'text-red-700'" title="Total" :value="total" />
                    <TotalCard :icon="Check" :icon-classes="'text-green-500'" title="Pago" :value="totalPago" />
                    <TotalCard :icon="Hourglass" :icon-classes="'text-yellow-500'" title="Pendente" :value="totalPendente" />
                  </template>
                  
                  <!-- Caso 'todos' -->
                  <template v-else-if="abaAtiva === 'todos'">
                    <TotalCard :icon="ArrowUp" title="Ganhos" :value="totalGanhos" icon-classes="text-green-600" />
                    <TotalCard :icon="ArrowDown" title="Despesas" :value="totalDespesas" icon-classes="text-red-600" />
                    <TotalCard :icon="Wallet" title="Disponível" :value="totalDisponivel" 
                      :valueColorClass="totalDisponivel >= 0 ? 'text-green-600' : 'text-red-600'" />
                  </template>

                  <!-- Caso simples (ganho/gasto) -->
                  <template v-else>
                    <TotalCard 
                      v-if="abaAtiva === 'ganho'" 
                      :icon="ArrowUp" 
                      title="Total" 
                      :value="total" 
                      icon-classes="text-green-600" 
                      class="col-start-2 lg:col-start-3"
                    />
                    <TotalCard 
                      v-else-if="abaAtiva === 'gasto'" 
                      :icon="ArrowDown" 
                      title="Total" 
                      :value="total" 
                      icon-classes="text-red-600" 
                      class="col-start-2 lg:col-start-3"
                    />
                  </template>
                </div>
              </div>

              <!-- Tabelas de Movimentações/Parcelas -->
              <TabelaMovimentacoes v-if="!isMediumScreen"
                :movimentacoes="abaAtiva === 'gasto futuro' ? [] : props.movimentacoes"
                :parcelas="abaAtiva === 'gasto futuro' ? props.parcelasFuturas : []" :active-tab="abaAtiva"
                @delete="requestDelete" @delete:selected="requestDeleteMany" />
              <MovimentacoesCardList v-else-if="isMediumScreen"
                :movimentacoes="abaAtiva === 'gasto futuro' ? [] : props.movimentacoes"
                :parcelas="abaAtiva === 'gasto futuro' ? props.parcelasFuturas : []" :active-tab="abaAtiva"
                @delete="requestDelete" @delete:selected="requestDeleteMany" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <ConfirmarExclusaoModal v-model:open="isModalExclusaoAberto" title="Excluir movimentação"
      message="Esta movimentação será removida permanentemente." description="Essa ação não pode ser desfeita."
      confirm-text="Excluir" @confirm="confirmDelete" @cancel="isModalExclusaoAberto = false" />

    <ConfirmarExclusaoModal v-model:open="isModalExclusaoMassaAberto" title="Excluir movimentações"
      message="As movimentações selecionadas serão removidas permanentemente."
      description="Essa ação não pode ser desfeita." confirm-text="Excluir" @confirm="confirmDeleteMany"
      @cancel="isModalExclusaoMassaAberto = false" />

    <!-- Floating Action Button para Telas Pequenas -->
    <div v-if="isMediumScreen" class="fixed bottom-4 right-4 z-50">
      <Link :href="movimentacoesRoutes.create({ query: { tipo: abaAtiva !== 'todos' ? abaAtiva : 'gasto' } }).url"
        class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-600 text-white shadow-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
        <Plus class="h-6 w-6" />
      </Link>
    </div>
  </AppLayout>
</template>
