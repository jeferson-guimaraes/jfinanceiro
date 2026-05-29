<script setup lang="ts">
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { type Movimentacao, type ParcelaComMovimentacao } from '@/types';
import { formataDinheiroBRL } from '@/utils/formataDinheiro';
import { formatDate } from '@/utils/formatDate';
import { 
  Calendar, 
  DollarSign, 
  Tag, 
  Info, 
  Layers,
  CheckCircle2, 
  Clock,
  ArrowUpCircle,
  ArrowDownCircle,
  CalendarDays
} from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
  open: boolean;
  item: Movimentacao | ParcelaComMovimentacao | null;
}>();

const emit = defineEmits(['update:open']);

const isParcela = computed(() => props.item && 'movimentacao' in props.item);

const movimentacao = computed(() => {
  if (!props.item) return null;
  return isParcela.value 
    ? (props.item as ParcelaComMovimentacao).movimentacao 
    : (props.item as Movimentacao);
});

const parcela = computed(() => {
  return isParcela.value ? (props.item as ParcelaComMovimentacao) : null;
});

const headerConfig = computed(() => {
  if (!movimentacao.value) return { color: 'bg-gray-600', icon: Info, label: 'Detalhes' };
  
  const tipo = movimentacao.value.tipo;
  if (tipo === 'ganho') return { color: 'bg-green-600', icon: ArrowUpCircle, label: 'Entrada de Recurso' };
  if (tipo === 'gasto') return { color: 'bg-red-600', icon: ArrowDownCircle, label: 'Saída de Recurso' };
  return { color: 'bg-blue-600', icon: CalendarDays, label: 'Gasto Agendado' };
});

const handleOpenChange = (value: boolean) => {
  emit('update:open', value);
};
</script>

<template>
  <Dialog :open="open" @update:open="handleOpenChange">
    <DialogContent class="sm:max-w-[500px] p-0 overflow-hidden border-none shadow-2xl">
      <div :class="[headerConfig.color, 'p-6 text-white relative transition-colors duration-300']">
        <div class="absolute top-4 right-4 opacity-10">
          <component :is="headerConfig.icon" class="h-24 w-24" />
        </div>
        <DialogHeader>
          <div class="flex items-center gap-2 mb-1">
             <component :is="headerConfig.icon" class="h-5 w-5 text-white/80" />
             <span class="text-xs font-bold uppercase tracking-widest text-white/80">{{ headerConfig.label }}</span>
          </div>
          <DialogTitle class="text-2xl font-bold text-white truncate pr-8">
            {{ movimentacao?.descricao }}
          </DialogTitle>
        </DialogHeader>
      </div>

      <div class="p-6 bg-white dark:bg-sidebar space-y-6" v-if="movimentacao">
        <!-- Grid de Informações Principais -->
        <div class="grid grid-cols-2 gap-4">
          <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4 border border-gray-100 dark:border-gray-700/50 space-y-1">
            <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400 mb-1">
              <Tag class="h-3.5 w-3.5" />
              <span class="text-[10px] font-bold uppercase tracking-wider">Categoria</span>
            </div>
            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
              {{ movimentacao.categoria?.nome || 'Sem Categoria' }}
            </p>
          </div>

          <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4 border border-gray-100 dark:border-gray-700/50 space-y-1">
            <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400 mb-1">
              <DollarSign class="h-3.5 w-3.5" />
              <span class="text-[10px] font-bold uppercase tracking-wider">Valor {{ isParcela ? 'da Parcela' : 'Total' }}</span>
            </div>
            <p class="text-lg font-bold text-gray-900 dark:text-gray-100" :class="movimentacao.tipo === 'ganho' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
              {{ formataDinheiroBRL(isParcela ? parcela!.valor : movimentacao.valor) }}
            </p>
          </div>
        </div>

        <!-- Seção de Datas e Status -->
        <div class="space-y-4">
          <h4 class="text-[10px] font-bold uppercase tracking-widest text-gray-400 px-1">Cronograma e Status</h4>
          <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 divide-y divide-gray-100 dark:divide-gray-800">
            <!-- Data da Compra/Movimentação -->
            <div class="p-4 flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="bg-blue-50 dark:bg-blue-900/20 p-2 rounded-lg text-blue-600 dark:text-blue-400">
                  <Calendar class="h-4 w-4" />
                </div>
                <div>
                  <p class="text-[10px] text-gray-500 uppercase font-medium">Data da {{ movimentacao.tipo === 'gasto futuro' ? 'Compra' : 'Movimentação' }}</p>
                  <p class="text-sm font-medium">{{ formatDate(movimentacao.data) }}</p>
                </div>
              </div>
            </div>

            <!-- Informações Específicas de Parcela -->
            <template v-if="isParcela && parcela">
              <div class="p-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="bg-purple-50 dark:bg-purple-900/20 p-2 rounded-lg text-purple-600 dark:text-purple-400">
                    <Layers class="h-4 w-4" />
                  </div>
                  <div>
                    <p class="text-[10px] text-gray-500 uppercase font-medium">Parcela</p>
                    <p class="text-sm font-medium">{{ parcela.numero }} de {{ movimentacao.parcelas }}</p>
                  </div>
                </div>
                <div v-if="parcela.pago" class="flex items-center gap-1 text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 px-2 py-1 rounded-full text-[10px] font-bold uppercase">
                  <CheckCircle2 class="h-3 w-3" />
                  Paga
                </div>
                <div v-else class="flex items-center gap-1 text-yellow-600 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-900/20 px-2 py-1 rounded-full text-[10px] font-bold uppercase">
                  <Clock class="h-3 w-3" />
                  Pendente
                </div>
              </div>

              <div class="p-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="bg-orange-50 dark:bg-orange-900/20 p-2 rounded-lg text-orange-600 dark:text-orange-400">
                    <CalendarDays class="h-4 w-4" />
                  </div>
                  <div>
                    <p class="text-[10px] text-gray-500 uppercase font-medium">Vencimento</p>
                    <p class="text-sm font-medium">{{ formatDate(parcela.data_vencimento) }}</p>
                  </div>
                </div>
                <div v-if="parcela.data_pagamento" class="text-right">
                   <p class="text-[10px] text-gray-500 uppercase font-medium">Pagamento em</p>
                   <p class="text-sm font-medium text-green-600">{{ formatDate(parcela.data_pagamento) }}</p>
                </div>
              </div>
            </template>

            <!-- Informações Gerais de Gasto Futuro (quando não é uma parcela específica mas a movimentação pai) -->
            <template v-else-if="movimentacao.tipo === 'gasto futuro'">
              <div class="p-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="bg-purple-50 dark:bg-purple-900/20 p-2 rounded-lg text-purple-600 dark:text-purple-400">
                    <Layers class="h-4 w-4" />
                  </div>
                  <div>
                    <p class="text-[10px] text-gray-500 uppercase font-medium">Total de Parcelas</p>
                    <p class="text-sm font-medium">{{ movimentacao.parcelas }} parcelas</p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="text-[10px] text-gray-500 uppercase font-medium">Progresso</p>
                  <p class="text-sm font-bold text-blue-600">{{ movimentacao.parcelas_pagas || 0 }} de {{ movimentacao.parcelas }} pagas</p>
                </div>
              </div>
            </template>
          </div>
        </div>

        <!-- Seção de Metadados -->
        <div class="pt-2 flex justify-between items-center text-[10px] text-gray-400 dark:text-gray-500 uppercase font-medium">
          <div class="flex items-center gap-1">
            <Clock class="h-3 w-3" />
            Criado em {{ formatDate(movimentacao.created_at) }}
          </div>
          <div>ID #{{ movimentacao.id }}{{ isParcela ? ` (P:${parcela!.id})` : '' }}</div>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>
