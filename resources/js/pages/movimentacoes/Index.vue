<script setup lang="ts">
import ConfirmarExclusaoModal from '@/components/modals/ConfirmarExclusaoModal.vue';
import TabelaMovimentacoes from '@/components/movimentacoes/TabelaMovimentacoes.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import movimentacoesRoutes from '@/routes/movimentacoes';
import { type BreadcrumbItem, type Movimentacao, type ParcelaComMovimentacao } from '@/types';
import { formataDinheiroBRL } from '@/utils/formataDinheiro';
import { Head, router } from '@inertiajs/vue3';
import { Check, Hourglass, Wallet } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
  movimentacoes: Movimentacao[];
  parcelasFuturas: ParcelaComMovimentacao[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Movimentações',
    href: movimentacoesRoutes.index().url,
  },
];

const tabs = [
  { title: 'Todas', tipo: 'todos' },
  { title: 'Ganhos', tipo: 'ganho' },
  { title: 'Despesas', tipo: 'gasto' },
  { title: 'Futuros', tipo: 'gasto futuro' },
];

const abaAtiva = ref('todos');

const dataInicio = ref('');
const dataFim = ref('');
const buscaTexto = ref('');

const meses = [
  { value: '1', label: 'Janeiro' },
  { value: '2', label: 'Fevereiro' },
  { value: '3', label: 'Março' },
  { value: '4', label: 'Abril' },
  { value: '5', label: 'Maio' },
  { value: '6', label: 'Junho' },
  { value: '7', label: 'Julho' },
  { value: '8', label: 'Agosto' },
  { value: '9', label: 'Setembro' },
  { value: '10', label: 'Outubro' },
  { value: '11', label: 'Novembro' },
  { value: '12', label: 'Dezembro' },
];

const currentDate = new Date();
const mesSelecionado = ref(String(currentDate.getMonth() + 1));
const anoSelecionado = ref(String(currentDate.getFullYear()));

const movimentacoesFiltradas = computed(() => {
  let result = props.movimentacoes;

  if (abaAtiva.value === 'todos') {
    result = result.filter((m) => m.tipo === 'ganho' || m.tipo === 'gasto');
  } else {
    result = result.filter((m) => m.tipo === abaAtiva.value);
  }

  if (dataInicio.value) {
    const dataInicioObj = new Date(dataInicio.value);
    result = result.filter((m) => new Date(m.data) >= dataInicioObj);
  }

  if (dataFim.value) {
    const dataFimObj = new Date(dataFim.value);
    result = result.filter((m) => new Date(m.data) <= dataFimObj);
  }

  if (buscaTexto.value) {
    result = result.filter((m) =>
      m.descricao.toLowerCase().includes(buscaTexto.value.toLowerCase()),
    );
  }

  return result;
});

const parcelasFiltradas = computed(() => {
  let result = props.parcelasFuturas.map((parcela) => ({
    ...parcela,
    descricao: parcela.movimentacao.descricao,
    categoria: parcela.movimentacao.categoria,
    totalParcelas: parcela.movimentacao.parcelas,
    tipo: 'gasto futuro' as const,
    data: parcela.data_vencimento,
  }));

  if (buscaTexto.value) {
    result = result.filter((p) =>
      p.descricao.toLowerCase().includes(buscaTexto.value.toLowerCase()),
    );
  }

  return result;
});

const saldo = computed(() => {
  return props.movimentacoes.reduce((acc, m) => {
    if (m.tipo === 'ganho') {
      return acc + Number(m.valor);
    }
    if (m.tipo === 'gasto') {
      return acc - Number(m.valor);
    }
    return acc;
  }, 0);
});

const total = computed(() => {
  if (abaAtiva.value === 'gasto futuro') {
    return props.parcelasFuturas.reduce(
      (acc, parcela) => acc + Number(parcela.valor),
      0,
    );
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
    router.delete(
      movimentacoesRoutes.destroy({
        movimentacao: movimentacaoParaExcluir.value.id,
      }).url,
      {
        preserveScroll: true,
        onSuccess: () => {
          isModalExclusaoAberto.value = false;
          movimentacaoParaExcluir.value = null;
        },
      },
    );
  }
}

watch([dataInicio, dataFim], () => {
  router.get(
    movimentacoesRoutes.index().url,
    {
      data_inicio: dataInicio.value,
      data_fim: dataFim.value,
      mes: mesSelecionado.value,
      ano: anoSelecionado.value,
    },
    {
      preserveState: true,
      replace: true,
    },
  );
});

watch([mesSelecionado, anoSelecionado], () => {
  if (anoSelecionado.value.length == 4) {
    router.get(
      movimentacoesRoutes.index().url,
      {
        data_inicio: dataInicio.value,
        data_fim: dataFim.value,
        mes: mesSelecionado.value,
        ano: anoSelecionado.value,
      },
      {
        preserveState: true,
        replace: true,
      },
    );
  }
});
</script>

<template>

  <Head title="Movimentações" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <div class="space-y-2 xl:px-8">
        <div>
          <h2 class="text-base leading-7 font-semibold text-gray-900 dark:text-gray-100">
            Movimentações
          </h2>
          <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-400">
            Visualize todas as suas movimentações financeiras.
          </p>
        </div>

        <div class="grid grid-cols-1 gap-x-6 gap-y-4">
          <div>
            <!-- Tabs -->
            <div class="border-b border-gray-200 dark:border-gray-700">
              <ul class="-mb-px flex text-sm font-medium text-gray-500 dark:text-gray-400">
                <li v-for="tab in tabs" :key="tab.tipo">
                  <button class="border-b-2 p-4 hover:cursor-pointer" :class="[
                    abaAtiva === tab.tipo
                      ? 'border-blue-600 text-blue-600'
                      : 'border-transparent hover:border-gray-300 hover:text-gray-600',
                  ]" @click="abaAtiva = tab.tipo">
                    {{ tab.title }}
                  </button>
                </li>
              </ul>
            </div>

            <!-- Content -->
            <div class="rounded-b-lg bg-gray-50 p-4 dark:bg-sidebar">
              <div class="mb-4 flex flex-col xl:flex xl:flex-row lg:justify-between md:gap-6 space-y-4">
                <!-- Coluna da Esquerda: Filtros e Busca -->
                <div class="space-y-4 order-2 lg:order-1">
                  <!-- Filtro de data -->
                  <div class="flex flex-col gap-2 lg:flex-row lg:items-center" v-if="abaAtiva === 'todos'">
                    <div class="grid grid-cols-2 items-center gap-2">
                      <div class="md:flex w-full gap-2">
                        <Label for="dataInicio" class="my-auto mb-2 md:mb-auto">De:</Label>
                        <Input v-model="dataInicio" type="date" class="w-full md:w-auto" id="dataInicio"/>
                      </div>
                      <span class="text-gray-500 hidden">-</span>
                      <div class="md:flex w-full gap-2">
                        <Label for="dataFim" class="my-auto mb-2 md:mb-auto">Até:</Label>
                        <Input v-model="dataFim" type="date" class="w-full md:w-auto" id="dataFim"/>
                      </div>
                    </div>
                  </div>

                  <!-- Filtro mês/ano para gastos futuros -->
                  <div class="flex flex-col gap-2 lg:flex-row lg:items-center" v-if="abaAtiva === 'gasto futuro'">
                    <div class="flex items-center gap-2">
                      <Select v-model="mesSelecionado">
                        <SelectTrigger>
                          <SelectValue placeholder="Mes" />
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

                  <!-- Busca -->
                  <div class="flex-1">
                    <Input v-model="buscaTexto" placeholder="Buscar por descrição..." />
                  </div>
                </div>

                <!-- Coluna da Direita: Saldo/Total -->
                <div class="order-1 lg:order-2 mb-5 lg:mb-0">
                  <div v-if="abaAtiva == 'gasto futuro'" class="grid grid-cols-2 lg:flex gap-2 lg:gap-6">
                    <div
                      class="text-center lg:flex md:text-left w-full gap-6 rounded-lg border bg-white p-4 space-y-2 lg:pr-20 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                      <div class="flex justify-center items-center">
                        <Wallet class="text-[#6F4E37] lg:w-8 lg:h-8" />
                      </div>

                      <div>
                        <h3 class="md:block text-sm font-medium text-gray-500 dark:text-gray-400">
                          Total do Mês
                        </h3>
                        <div class="mt-2 text-md md:text-2xl font-semibold">
                          {{ formataDinheiroBRL(total) }}
                        </div>
                      </div>
                    </div>
                    <div
                      class="text-center lg:flex md:text-left w-full gap-6 rounded-lg border bg-white p-4 space-y-2 lg:pr-20 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                      <div class="flex justify-center items-center">
                        <Check class="text-white bg-green-400 rounded-full p-1 lg:w-8 lg:h-8" />
                      </div>

                      <div>
                        <h3 class="md:block text-sm font-medium text-gray-500 dark:text-gray-400">
                          Total Pago
                        </h3>
                        <div class="mt-2 text-md md:text-2xl font-semibold">
                          {{ formataDinheiroBRL(totalPago) }}
                        </div>
                      </div>
                    </div>
                    <div
                      class="text-center lg:flex md:text-left w-full gap-6 rounded-lg border bg-white p-4 space-y-2 lg:pr-20 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                      <div class="flex justify-center items-center">
                        <Hourglass class="text-red-700 lg:w-8 lg:h-8" />
                      </div>

                      <div>
                        <h3 class="md:block text-sm font-medium text-gray-500 dark:text-gray-400">
                          Total Pendente
                        </h3>
                        <div class="mt-2 text-lg md:text-2xl font-semibold">
                          {{ formataDinheiroBRL(totalPendente) }}
                        </div>
                      </div>
                    </div>
                  </div>
                  <div v-else>
                    <div
                      class="text-center lg:flex md:text-left w-full gap-6 rounded-lg border bg-white p-4 space-y-2 lg:pr-20 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                      <div class="flex justify-center items-center">
                        <Wallet class="text-[#6F4E37] lg:w-8 lg:h-8" />
                      </div>

                      <div>
                        <h3 class="md:block text-sm font-medium text-gray-500 dark:text-gray-400">
                          Total do Mês
                        </h3>
                        <div class="mt-2 text-md md:text-2xl font-semibold">
                          <span
                            :class="saldo >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                            {{ formataDinheiroBRL(saldo) }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <TabelaMovimentacoes :movimentacoes="abaAtiva === 'gasto futuro' ? [] : movimentacoesFiltradas"
                :parcelas="abaAtiva === 'gasto futuro' ? parcelasFiltradas : []" :active-tab="abaAtiva"
                @delete="requestDelete" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <ConfirmarExclusaoModal v-model:open="isModalExclusaoAberto" title="Excluir movimentação"
      message="Esta movimentação será removida permanentemente." description="Essa ação não pode ser desfeita."
      confirm-text="Excluir" @confirm="confirmDelete" @cancel="isModalExclusaoAberto = false" />
  </AppLayout>
</template>