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
import { Check, Hourglass, Wallet, X, Plus } from 'lucide-vue-next';
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
              <div class="mb-4 flex flex-col space-y-4"
                :class="[
                  abaAtiva === 'gasto futuro'
                    ? 'lg:grid lg:grid-cols-2 lg:gap-6'
                    : 'md:grid md:grid-cols-2 md:gap-6',
                ]"
              >
                <!-- Filtros e Busca -->
                <div class="space-y-4 order-2 lg:order-1 flex-1"
                  :class="[abaAtiva === 'gastos futuros' && 'lg:col-span-2 xl:col-span-1']"
                >
                  <!-- Filtro de data (Invisível apenas na aba 'gasto futuro') -->
                  <div v-if="abaAtiva === 'todos' || abaAtiva === 'ganho' || abaAtiva === 'gasto'"
                    class="flex flex-col gap-2 lg:flex-row lg:items-center">
                    <div class="grid grid-cols-2 items-center gap-2 w-full max-w-lg">
                      <div class="md:flex w-full gap-2">
                        <Label for="dataInicio" class="my-auto mb-2 md:mb-auto">De:</Label>
                        <Input v-model="dataInicio" type="date" class="w-full" id="dataInicio" />
                      </div>
                      <span class="text-gray-500 hidden">-</span>
                      <div class="md:flex w-full gap-2">
                        <Label for="dataFim" class="my-auto mb-2 md:mb-auto">Até:</Label>
                        <Input v-model="dataFim" type="date" class="w-full" id="dataFim" />
                      </div>
                    </div>
                  </div>

                  <!-- Filtro mês/ano para gastos futuros -->
                  <div v-if="abaAtiva === 'gasto futuro'" class="flex flex-col gap-2 lg:items-center max-w-lg">
                    <div class="flex w-full items-center gap-2">
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
                      <Input type="text" v-model="anoSelecionado" placeholder="Ano" class="md:w-[180px]" />
                    </div>
                  </div>

                  <!-- Filtro de Busca por Texto -->
                  <div class="flex items-center gap-2 max-w-lg">
                    <!-- Busca -->
                    <Input v-model="buscaTexto" placeholder="Buscar por descrição..." class="lg:col-span-2" />

                    <!-- Limpar Filtros -->
                    <div class="flex items-center">
                      <button type="button" @click="limparFiltros"
                        class="w-fit text-xs md:text-[11pt] border border-gray-200 text-gray-500 py-2.5 md:py-2 px-2 md:px-4 rounded-md flex gap-2 items-center justify-center hover:bg-gray-100 hover:cursor-pointer ease-in-out duration-300 whitespace-nowrap">
                        <X class="w-4 h-4" /> Limpar Filtros
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Totais -->
                <div class="order-1 max-w-md lg:max-w-none mb-5 lg:mb-0 flex flex-wrap gap-2 lg:gap-6 xl:col-span-1">
                  <div v-if="abaAtiva === 'gasto futuro'" class="flex flex-wrap sm:flex-nowrap gap-2 xl:ml-auto">
                    <!-- Card Total -->
                    <TotalCard :icon="Wallet" :iconClasses="'text-[#6F4E37] w-6 h-6 xl:w-8 xl:h-8'" title="Total Contas"
                      :value="total" :valueClasses="'text-xl xl:text-2xl font-semibold'" />
                    <!-- Card Total Pago -->
                    <TotalCard :icon="Check"
                      :iconClasses="'text-white bg-green-400 rounded-full p-1 w-6 h-6 xl:w-8 xl:h-8'" title="Total Pago"
                      :value="totalPago" :valueClasses="'text-xl xl:text-2xl font-semibold'" />
                    <!-- Card Total Pendente -->
                    <TotalCard :icon="Hourglass" :iconClasses="'text-red-700 w-6 h-6 xl:w-8 xl:h-8'"
                      title="Total Pendente" :value="totalPendente" :valueClasses="'text-xl xl:text-2xl font-semibold'"
                      :valueColorClass="'text-red-700 dark:text-red-400'" />
                  </div>
                  <div v-else class="w-full md:w-fit lg:ml-auto">
                    <!-- Card Total -->
                    <TotalCard :icon="Wallet" iconClasses="text-[#6F4E37] w-8 h-8" title="Total" :value="total"
                      valueClasses="text-2xl font-semibold flex"
                      :valueColorClass="total >= 0 && abaAtiva !== 'gasto' ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400'" />
                  </div>
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
