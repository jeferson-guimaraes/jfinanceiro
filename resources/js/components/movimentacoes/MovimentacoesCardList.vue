<script setup lang="ts">
import { ref } from 'vue';
import type { Movimentacao, ParcelaComMovimentacao } from '@/types';
import { formataDinheiroBRL } from '@/utils/formataDinheiro';
import { formatDate } from '@/utils/formatDate';
import { Button } from '../ui/button';
import { Pencil, Trash2 } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';
import movimentacoesRoute from '@/routes/movimentacoes';
import Checkbox from '../ui/checkbox/Checkbox.vue';

defineProps<{
  movimentacoes: Movimentacao[];
  parcelas: ParcelaComMovimentacao[];
  activeTab: string;
}>();

const emit = defineEmits(['delete', 'update:selection', 'delete:selected', 'pay']);

const selectedMovimentacoes = ref<number[]>([]);

function requestDelete(movimentacao: Movimentacao) {
  emit('delete', movimentacao);
}

const handleSelection = (movimentacaoId: number, checked: boolean) => {
  if (checked) {
    if (!selectedMovimentacoes.value.includes(movimentacaoId)) {
      selectedMovimentacoes.value.push(movimentacaoId);
    }
  } else {
    selectedMovimentacoes.value = selectedMovimentacoes.value.filter(
      id => id !== movimentacaoId
    );
  }

  emit('update:selection', selectedMovimentacoes.value);
};

const requestDeleteMany = () => {
  emit('delete:selected', selectedMovimentacoes.value);
}

const getParcelaStatus = (parcela: ParcelaComMovimentacao): 'paga' | 'vencida' | 'vence-hoje' | 'pendente' => {
  if (parcela.pago) {
    return 'paga';
  }

  const hoje = new Date();
  hoje.setHours(0, 0, 0, 0);

  const dataVencimento = new Date(parcela.data_vencimento + 'T00:00:00');

  if (dataVencimento < hoje) {

    return 'vencida';
  }
  if (dataVencimento.getTime() === hoje.getTime()) {
    return 'vence-hoje';
  }

  return 'pendente';
}

const statusBorderColors: Record<string, string> = {
  paga: 'border-green-500',
  vencida: 'border-red-500',
  'vence-hoje': 'border-yellow-500',
  pendente: 'border-gray-200 dark:border-gray-700',
};

const getStatusBorderClass = (parcela: ParcelaComMovimentacao) => {
  const status = getParcelaStatus(parcela);
  return statusBorderColors[status];
};

const getStatusBgClass = (parcela: ParcelaComMovimentacao) => {
  const status = getParcelaStatus(parcela);
  if (selectedMovimentacoes.value.includes(parcela.movimentacao.id)) return 'bg-blue-100 dark:bg-blue-900/40';
  
  switch (status) {
    case 'paga':
      return 'bg-green-50/50 dark:bg-green-900/10';
    case 'vencida':
      return 'bg-red-50/50 dark:bg-red-900/10';
    case 'vence-hoje':
      return 'bg-yellow-50/50 dark:bg-yellow-900/10';
    default:
      return 'bg-white dark:bg-gray-800';
  }
};

const getTipoLabel = (tipo: string) => {
  switch (tipo) {
    case 'ganho':
      return 'Ganho';
    case 'gasto':
      return 'Despesa';
    case 'gasto futuro':
      return 'Despesa Futura';
    default:
      return tipo;
  }
};
</script>

<template>
  <div class="space-y-4">
    <div class="flex flex-col gap-4">
      <!-- Legenda -->
      <div v-if="activeTab === 'gasto futuro'" class="flex flex-wrap gap-4 text-[10px] uppercase tracking-wider font-semibold text-gray-500 dark:text-gray-400">
        <div class="flex items-center gap-1.5">
          <div class="w-3 h-3 rounded-sm bg-green-100 dark:bg-green-900/30 border border-green-200 dark:border-green-800"></div>
          <span>Paga</span>
        </div>
        <div class="flex items-center gap-1.5">
          <div class="w-3 h-3 rounded-sm bg-yellow-100 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800"></div>
          <span>Vence Hoje</span>
        </div>
        <div class="flex items-center gap-1.5">
          <div class="w-3 h-3 rounded-sm bg-red-100 dark:bg-red-900/30 border border-red-200 dark:border-red-800"></div>
          <span>Vencida</span>
        </div>
      </div>

      <div v-if="selectedMovimentacoes.length > 0" class="flex justify-end gap-2">
        <Button class="bg-green-500/10 text-green-600 font-semibold hover:bg-green-200 h-8" @click="emit('pay:selected', selectedMovimentacoes)">
          Pagar ({{ selectedMovimentacoes.length }})
        </Button>
        <Button class="bg-red-500/10 text-red-500 font-semibold hover:bg-red-200 h-8" @click="requestDeleteMany">
          <Trash2 class="text-red-500 font-semibold h-3.5 w-3.5 mr-2" />
          Excluir ({{ selectedMovimentacoes.length }})
        </Button>
      </div>
    </div>
    <div v-if="activeTab === 'gasto futuro'">
      <div v-for="parcela in parcelas" :key="parcela.id"
              class="rounded-lg border p-4 shadow-sm dark:bg-gray-800 mb-5"
              :class="[
                getStatusBorderClass(parcela),
                selectedMovimentacoes.includes(parcela.movimentacao.id)
                  ? 'bg-blue-200/30'
                  : getStatusBgClass(parcela)
              ]">
              <div class="flex justify-end mb-2">
                <Checkbox class="bg-gray-50" :id="`movimentacao-${parcela.movimentacao.id}`" :checked="selectedMovimentacoes.includes(parcela.movimentacao.id)"
                  @update:modelValue="(checked) => handleSelection(parcela.movimentacao.id, Boolean(checked))" />
              </div>
              <div class="flex justify-between items-start">
                <div class="flex-1">
                  <p class="font-semibold text-lg mb-2 leading-tight">{{ parcela.movimentacao.descricao }}</p>
                  <p class="text-sm text-blue-500 font-bold dark:text-gray-400 bg-blue-500/10 w-fit py-1 px-2 rounded-lg">{{
                    parcela.movimentacao.categoria?.nome }}</p>
                </div>
                <div class="text-right">
                  <p class="font-bold text-red-600 dark:text-red-400">
                    {{ formataDinheiroBRL(parcela.valor) }}
                  </p>
                  <p class="text-xs font-semibold text-gray-400">
                    Total: {{ formataDinheiroBRL(parcela.movimentacao.valor) }}
                  </p>
                </div>
              </div>
      
              <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                <div>
                  <p class="text-gray-400 font-semibold">Parcela</p>
                  <p class="font-semibold">{{ parcela.numero }} / {{ parcela.movimentacao.parcelas }}</p>
                </div>
                <div class="text-right">
                  <p class="text-gray-400 font-semibold">Vencimento</p>
                  <p class="font-semibold">{{ formatDate(parcela.data_vencimento) }}</p>
                </div>
                <div>
                  <p class="text-gray-400 font-semibold">Status</p>
                  <p class="font-semibold" :class="{
                    'text-green-600': getParcelaStatus(parcela) === 'paga',
                    'text-red-600': getParcelaStatus(parcela) === 'vencida',
                    'text-yellow-600': getParcelaStatus(parcela) === 'vence-hoje',
                    'text-gray-500': getParcelaStatus(parcela) === 'pendente',
                  }">
                    {{ getParcelaStatus(parcela) === 'paga' ? 'Pago' :
                      getParcelaStatus(parcela) === 'vencida' ? 'Vencida' :
                        getParcelaStatus(parcela) === 'vence-hoje' ? 'Vence Hoje' : 'Pendente'
                    }}
                  </p>
                </div>
                <div v-if="parcela.movimentacao.parcelas_pagas" class="text-right">
                  <p class="text-gray-400 font-semibold">Parcelas Pagas</p>
                  <p class="font-semibold">{{ parcela.movimentacao.parcelas_pagas }} de {{ parcela.movimentacao.parcelas }}</p>
                </div>
              </div>
              <div class="mt-6 grid grid-cols-2 gap-3">
                <Button v-if="parcela.movimentacao.tipo === 'gasto futuro' && (parcela.movimentacao.parcelas_pagas || 0) < parcela.movimentacao.parcelas"
                  @click="emit('pay', parcela.movimentacao)"
                  class="col-span-2 bg-green-600/10 text-green-600 font-semibold hover:bg-green-600/20">
                  Pagar Parcelas
                </Button>
                <Link :href="movimentacoesRoute.edit({
                  movimentacao:
                    parcela.movimentacao.id,
                }).url
                  ">
                <Button class="bg-gray-500/10 text-gray-400 w-full font-semibold hover:bg-gray-200">
                  <Pencil class="text-gray-400 font-semibold h-4 w-4 mr-2" />
                  Editar
                </Button>
                </Link>
                <Button @click="requestDelete(parcela.movimentacao)"
                  class="bg-red-500/10 text-red-500 w-full font-semibold hover:bg-red-200">
                  <Trash2 class="text-red-500 font-semibold h-4 w-4 mr-2" />
                  Excluir
                </Button>
              </div>
            </div>
    </div>
    <div v-else>
      <div v-for="movimentacao in movimentacoes" :key="movimentacao.id"
        class="rounded-lg border p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 mb-5"
        :class="selectedMovimentacoes.includes(movimentacao.id) ? 'bg-blue-200/30' : 'bg-white'">
        <div class="flex justify-end mb-2">
          <Checkbox :id="`movimentacao-${movimentacao.id}`" :checked="selectedMovimentacoes.includes(movimentacao.id)"
            @update:modelValue="(checked) => handleSelection(movimentacao.id, Boolean(checked))" />
        </div>
        <div class="flex justify-between">
          <div>
            <p class="text-gray-400 text-sm font-bold mb-0">{{ formatDate(movimentacao.data) }}</p>
            <p class="font-semibold text-lg">{{ movimentacao.descricao }}</p>
            <p class="text-sm text-blue-500 font-bold dark:text-gray-400 bg-blue-500/10 w-fit py-1 px-2 rounded-lg mt-2">{{
              movimentacao.categoria?.nome }}</p>
          </div>
          <div class="text-right">
            <p class="font-bold text-xs"
              :class="movimentacao.tipo === 'ganho' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
              {{ getTipoLabel(movimentacao.tipo).toUpperCase() }}
            </p>
            <p class="font-bold"
              :class="movimentacao.tipo === 'ganho' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
              {{ formataDinheiroBRL(movimentacao.valor) }}
            </p>
          </div>
        </div>
        <div class="mt-8 grid grid-cols-2 gap-3">
          <Button v-if="movimentacao.tipo === 'gasto futuro' && (movimentacao.parcelas_pagas || 0) < movimentacao.parcelas"
            @click="emit('pay', movimentacao)"
            class="col-span-2 bg-green-600/10 text-green-600 font-semibold hover:bg-green-600/20">
            Pagar Parcelas
          </Button>
          <Link :href="movimentacoesRoute.edit({
            movimentacao:
              movimentacao.id,
          }).url
            ">
            <Button class="bg-gray-500/10 text-gray-500 w-full font-semibold hover:bg-gray-200">
              <Pencil class="text-gray-500 font-semibold" />
              Editar
            </Button>
          </Link>
          <Button @click="requestDelete(movimentacao)"
            class="bg-red-500/10 text-red-500 w-full font-semibold hover:bg-red-200">
            <Trash2 class="text-red-500 font-semibold" />
            Deletar
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>
