<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import type { Movimentacao, ParcelaComMovimentacao } from '@/types';
import { formataDinheiroBRL } from '@/utils/formataDinheiro';
import { formatDate } from '@/utils/formatDate';
import { Button } from '../ui/button';
import { Pencil, Trash2, CheckCircle2, Calendar, SortAsc, SortDesc } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';
import movimentacoesRoute from '@/routes/movimentacoes';
import Checkbox from '../ui/checkbox/Checkbox.vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../ui/select';
import { canPayMovimentacoesInBulk } from '@/utils/movimentacoes';

const props = defineProps<{
  movimentacoes: Movimentacao[];
  parcelas: ParcelaComMovimentacao[];
  activeTab: string;
  filters?: Record<string, any>;
}>();

const emit = defineEmits(['delete', 'update:selection', 'delete:selected', 'pay', 'pay:selected', 'show-details']);

const selectedMovimentacoes = ref<number[]>([]);
const sortKey = ref(props.activeTab === 'gasto futuro' ? 'data_vencimento' : 'data');
const sortOrder = ref<'asc' | 'desc'>('desc');

watch(() => props.activeTab, (newTab) => {
  if (newTab === 'gasto futuro') {
    sortKey.value = 'data_vencimento';
  } else {
    sortKey.value = 'data';
  }
});

function getValueByPath(obj: any, path: string) {
  return path.split('.').reduce((acc, key) => acc?.[key], obj);
}

function normalizeValue(value: unknown): number | string {
  if (value === null || value === undefined) return 0;
  if (typeof value === 'number') return value;
  if (value instanceof Date) return value.getTime();
  if (typeof value === 'string') {
    const asNumber = Number(value);
    if (!isNaN(asNumber) && value.trim() !== '') return asNumber;
    if (value.includes('-') && !isNaN(Date.parse(value))) return new Date(value).getTime();
    const numeric = value.replace(/\./g, '').replace(',', '.');
    if (!isNaN(Number(numeric)) && numeric.trim() !== '') return Number(numeric);
    return value.toLowerCase();
  }
  return String(value);
}

const sortedMovimentacoes = computed(() => {
  return [...props.movimentacoes].sort((a, b) => {
    const aVal = normalizeValue(getValueByPath(a, sortKey.value));
    const bVal = normalizeValue(getValueByPath(b, sortKey.value));
    if (aVal < bVal) return sortOrder.value === 'asc' ? -1 : 1;
    if (aVal > bVal) return sortOrder.value === 'asc' ? 1 : -1;
    return 0;
  });
});

const sortedParcelas = computed(() => {
  return [...props.parcelas].sort((a, b) => {
    const aVal = normalizeValue(getValueByPath(a, sortKey.value));
    const bVal = normalizeValue(getValueByPath(b, sortKey.value));
    if (aVal < bVal) return sortOrder.value === 'asc' ? -1 : 1;
    if (aVal > bVal) return sortOrder.value === 'asc' ? 1 : -1;
    return 0;
  });
});

const toggleSortOrder = () => {
  sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
};

function requestDelete(movimentacao: Movimentacao | ParcelaComMovimentacao) {
  const mov = 'movimentacao' in movimentacao ? movimentacao.movimentacao : movimentacao;
  emit('delete', mov);
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

const getParcelaStatus = (parcela: ParcelaComMovimentacao): 'paga' | 'vencida' | 'vence-hoje' | 'pendente' => {
  if (parcela.pago) return 'paga';
  const hoje = new Date();
  hoje.setHours(0, 0, 0, 0);
  const dataVencimento = new Date(parcela.data_vencimento + 'T00:00:00');
  if (dataVencimento < hoje) return 'vencida';
  if (dataVencimento.getTime() === hoje.getTime()) return 'vence-hoje';
  return 'pendente';
}

const getStatusColorClass = (parcela: ParcelaComMovimentacao) => {
  const status = getParcelaStatus(parcela);
  switch (status) {
    case 'paga': return 'bg-green-500';
    case 'vencida': return 'bg-red-500';
    case 'vence-hoje': return 'bg-yellow-500';
    default: return 'bg-gray-300 dark:bg-gray-600';
  }
};

const getTipoColorClass = (tipo: string) => {
  switch (tipo) {
    case 'ganho': return 'bg-green-500';
    case 'gasto': return 'bg-red-500';
    default: return 'bg-blue-500';
  }
};

const totalSelecionado = computed(() => {
  if (selectedMovimentacoes.value.length === 0) return 0;
  
  if (props.activeTab === 'gasto futuro') {
    return props.parcelas
      .filter(p => selectedMovimentacoes.value.includes(p.movimentacao.id))
      .reduce((acc, p) => acc + Number(p.valor), 0);
  }
  
  return props.movimentacoes
    .filter(m => selectedMovimentacoes.value.includes(m.id))
    .reduce((acc, m) => acc + Number(m.valor), 0);
});

const canPaySelected = computed(() => {
  if (selectedMovimentacoes.value.length === 0) return false;

  const selectedIds = new Set(selectedMovimentacoes.value);
  let selectedMovs: Movimentacao[] = [];

  if (props.activeTab === 'gasto futuro') {
    const movMap = new Map<number, Movimentacao>();
    props.parcelas.forEach(parcela => {
      if (selectedIds.has(parcela.movimentacao.id)) {
        movMap.set(parcela.movimentacao.id, parcela.movimentacao);
      }
    });
    selectedMovs = Array.from(movMap.values());
  } else {
    selectedMovs = props.movimentacoes.filter(movimentacao => selectedIds.has(movimentacao.id));
  }

  return canPayMovimentacoesInBulk(selectedMovs);
});
</script>

<template>
  <div class="space-y-2">
    <!-- Header de Ações em Massa e Ordenação -->
    <div class="flex items-center justify-between gap-2 px-1 mb-2">
      <div v-if="selectedMovimentacoes.length === 0" class="flex-1 flex items-center gap-2">
        <div class="flex-1 max-w-[200px]">
          <Select v-model="sortKey">
            <SelectTrigger class="h-8 text-[10px] font-bold uppercase bg-white dark:bg-gray-900 border-gray-100 dark:border-gray-800">
              <SelectValue placeholder="Ordenar por" />
            </SelectTrigger>
            <SelectContent>
              <template v-if="activeTab === 'gasto futuro'">
                <SelectItem value="movimentacao.data">Data Movimentação</SelectItem>
                <SelectItem value="data_vencimento">Data Vencimento</SelectItem>
                <SelectItem value="valor">Valor Parcela</SelectItem>
                <SelectItem value="movimentacao.parcelas">Qtd Parcelas</SelectItem>
                <SelectItem value="movimentacao.parcelas_pagas">Parcelas Pagas</SelectItem>
                <SelectItem value="data_pagamento">Data Pagamento</SelectItem>
              </template>
              <template v-else>
                <SelectItem value="data">Data</SelectItem>
                <SelectItem value="valor">Valor</SelectItem>
                <SelectItem value="descricao">Descrição</SelectItem>
              </template>
            </SelectContent>
          </Select>
        </div>
        <Button variant="ghost" size="icon" class="h-8 w-8 text-gray-500" @click="toggleSortOrder">
          <SortAsc v-if="sortOrder === 'asc'" class="h-4 w-4" />
          <SortDesc v-else class="h-4 w-4" />
        </Button>
      </div>

      <div v-if="selectedMovimentacoes.length > 0" class="flex-1 bg-blue-50 dark:bg-blue-900/20 p-2 rounded-lg border border-blue-100 dark:border-blue-800 flex justify-between items-center shadow-sm animate-in fade-in zoom-in-95">
        <div class="flex flex-col ml-1">
          <span class="text-[9px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-tight">
            {{ selectedMovimentacoes.length }} selecionados
          </span>
          <span class="text-xs font-black text-blue-900 dark:text-blue-100">
            {{ formataDinheiroBRL(totalSelecionado) }}
          </span>
        </div>
        <div class="flex gap-1.5">
          <Button v-if="canPaySelected" size="sm" class="h-8 px-3 bg-green-600 hover:bg-green-700 text-[10px] font-bold uppercase shadow-sm" @click="emit('pay:selected', selectedMovimentacoes)">
            Pagar
          </Button>
          <Button size="sm" variant="ghost" class="h-8 px-2 text-red-600 hover:bg-red-50" @click="emit('delete:selected', selectedMovimentacoes)">
            <Trash2 class="h-3.5 w-3.5" />
          </Button>
        </div>
      </div>
    </div>

    <!-- Lista de Gastos Futuros (Parcelas) -->
    <div v-if="activeTab === 'gasto futuro'" key="lista-parcelas" class="divide-y divide-gray-100 dark:divide-gray-800 border-y border-gray-100 dark:border-gray-800">
      <div v-for="parcela in sortedParcelas" :key="'mobile-parcela-' + parcela.id" 
        class="relative flex items-center gap-3 py-2 px-2 bg-white dark:bg-gray-900 transition-all cursor-pointer"
        :class="selectedMovimentacoes.includes(parcela.movimentacao.id) ? 'bg-blue-50/50 dark:bg-blue-900/10' : ''"
        @click="emit('show-details', parcela)">

        <!-- Faixa lateral de status -->
        <div class="absolute left-0 top-1 bottom-1 w-1 rounded-r-full" :class="getStatusColorClass(parcela)"></div>

        <div @click.stop>
          <Checkbox :id="`p-${parcela.id}`" :checked="selectedMovimentacoes.includes(parcela.movimentacao.id)"
            @update:modelValue="(checked) => handleSelection(parcela.movimentacao.id, Boolean(checked))" />
        </div>
        
        <div class="flex-1 min-w-0 flex flex-col">
          <div class="flex justify-between items-start gap-1">
            <h3 class="font-medium text-gray-900 dark:text-gray-100 truncate text-xs sm:text-sm">
              {{ parcela.movimentacao.descricao }}
            </h3>
            <span class="font-bold text-xs sm:text-sm text-red-600 dark:text-red-400 whitespace-nowrap">
              {{ formataDinheiroBRL(parcela.valor) }}
            </span>
          </div>
          
          <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 mt-0.5 text-[10px] text-gray-500 dark:text-gray-400">
            <span class="truncate max-w-[80px] text-blue-600 dark:text-blue-400 font-medium">
              {{ parcela.movimentacao.categoria?.nome }}
            </span>
            <span class="flex items-center gap-0.5" title="Data da Compra">
              <Calendar class="w-2.5 h-2.5 opacity-70" />
              {{ formatDate(parcela.movimentacao.data) }}
            </span>
            <span class="flex items-center gap-0.5 text-orange-600 dark:text-orange-400 font-medium" title="Data de Vencimento">
              <span class="w-1 h-1 rounded-full bg-current opacity-40"></span>
              {{ formatDate(parcela.data_vencimento) }}
            </span>
            <span class="font-bold bg-gray-100 dark:bg-gray-800 px-1 rounded">
              {{ parcela.numero }}/{{ parcela.movimentacao.parcelas }}
            </span>
            <span v-if="parcela.pago" class="text-green-600 font-bold ml-auto uppercase text-[9px]">Paga</span>
            <span v-else-if="getParcelaStatus(parcela) === 'vencida'" class="text-red-600 font-bold ml-auto uppercase text-[9px]">Vencida</span>
            <span v-else-if="getParcelaStatus(parcela) === 'vence-hoje'" class="text-yellow-600 font-bold ml-auto uppercase text-[9px]">Hoje</span>
          </div>
        </div>

        <!-- Ações (sempre visíveis mas muito pequenas) -->
        <div class="flex gap-0 ml-auto" @click.stop>
          <Button v-if="!parcela.pago" size="icon" variant="ghost" class="h-7 w-7 text-green-600" @click="emit('pay', parcela.movimentacao)">
            <CheckCircle2 class="h-4 w-4" />
          </Button>
          <Link :href="movimentacoesRoute.edit({ movimentacao: parcela.movimentacao.id }, { query: props.filters }).url">
            <Button size="icon" variant="ghost" class="h-7 w-7 text-gray-400">
              <Pencil class="h-4 w-4" />
            </Button>
          </Link>
          <Button size="icon" variant="ghost" class="h-7 w-7 text-red-400" @click="requestDelete(parcela)">
            <Trash2 class="h-4 w-4" />
          </Button>
        </div>
      </div>
    </div>

    <!-- Lista de Movimentações Normais (Ganhos/Gastos) -->
    <div v-else key="lista-movimentacoes" class="divide-y divide-gray-100 dark:divide-gray-800 border-y border-gray-100 dark:border-gray-800">
      <div v-for="mov in sortedMovimentacoes" :key="'mobile-movimentacao-' + mov.id" 
        class="relative flex items-center gap-3 py-2 px-2 bg-white dark:bg-gray-900 transition-all cursor-pointer"
        :class="selectedMovimentacoes.includes(mov.id) ? 'bg-blue-50/50 dark:bg-blue-900/10' : ''"
        @click="emit('show-details', mov)">
        
        <div class="absolute left-0 top-1 bottom-1 w-1 rounded-r-full" :class="getTipoColorClass(mov.tipo)"></div>
        
        <div @click.stop>
          <Checkbox :id="`m-${mov.id}`" :checked="selectedMovimentacoes.includes(mov.id)"
            @update:modelValue="(checked) => handleSelection(mov.id, Boolean(checked))" />
        </div>
        
        <div class="flex-1 min-w-0 flex flex-col">
          <div class="flex justify-between items-start gap-1">
            <h3 class="font-medium text-gray-900 dark:text-gray-100 truncate text-xs sm:text-sm">
              {{ mov.descricao }}
            </h3>
            <span class="font-bold text-xs sm:text-sm whitespace-nowrap" :class="mov.tipo === 'ganho' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
              {{ mov.tipo === 'ganho' ? '+' : '-' }}{{ formataDinheiroBRL(mov.valor) }}
            </span>
          </div>
          
          <div class="flex items-center gap-2 mt-0.5 text-[10px] text-gray-500 dark:text-gray-400">
            <span class="truncate max-w-[100px] text-blue-600 dark:text-blue-400">
              {{ mov.categoria?.nome }}
            </span>
            <span class="flex items-center gap-0.5">
              <Calendar class="w-2.5 h-2.5" />
              {{ formatDate(mov.data) }}
            </span>
          </div>
        </div>

        <div class="flex gap-0 ml-auto" @click.stop>
          <Button v-if="mov.tipo === 'gasto futuro' && (mov.parcelas_pagas || 0) < mov.parcelas"
            size="icon" variant="ghost" class="h-7 w-7 text-green-600" @click="emit('pay', mov)">
            <CheckCircle2 class="h-4 w-4" />
          </Button>
          <Link :href="movimentacoesRoute.edit({ movimentacao: mov.id }, { query: props.filters }).url">
            <Button size="icon" variant="ghost" class="h-7 w-7 text-gray-400">
              <Pencil class="h-4 w-4" />
            </Button>
          </Link>
          <Button size="icon" variant="ghost" class="h-7 w-7 text-red-400" @click="requestDelete(mov)">
            <Trash2 class="h-4 w-4" />
          </Button>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="(activeTab === 'gasto futuro' ? parcelas.length : movimentacoes.length) === 0" 
      class="flex flex-col items-center justify-center py-12 text-gray-500 dark:text-gray-400">
      <div class="bg-gray-100 dark:bg-gray-800 p-4 rounded-full mb-4">
        <Calendar class="w-8 h-8 opacity-20" />
      </div>
      <p class="text-sm">Nenhuma movimentação encontrada.</p>
    </div>
  </div>
</template>
