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

const props = defineProps<{
  movimentacoes: Movimentacao[];
  parcelas: ParcelaComMovimentacao[];
  activeTab: string;
}>();

const emit = defineEmits(['delete', 'update:selection', 'delete:selected']);

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

const statusBgColors: Record<string, string> = {
  paga: 'bg-green-500/5',
  vencida: 'bg-red-500/5',
  'vence-hoje': 'bg-yellow-500/5',
  pendente: 'bg-white',
};

const getStatusBorderClass = (parcela: ParcelaComMovimentacao) => {
  const status = getParcelaStatus(parcela);
  return statusBorderColors[status];
};

const getStatusBgClass = (parcela: ParcelaComMovimentacao) => {
  const status = getParcelaStatus(parcela);
  return statusBgColors[status];
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
    <div v-if="selectedMovimentacoes.length > 0" class="flex justify-end">
      <Button class="bg-red-500/10 text-red-500 font-semibold hover:bg-red-200" @click="requestDeleteMany">
        <Trash2 class="text-red-500 font-semibold h-4 w-4 mr-2" />
        Excluir selecionados ({{ selectedMovimentacoes.length }})
      </Button>
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
            <p class="text-sm text-blue-500 font-bold dark:text-gray-400 bg-blue-500/10 w-fit py-1 px-2 rounded-lg">{{
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
