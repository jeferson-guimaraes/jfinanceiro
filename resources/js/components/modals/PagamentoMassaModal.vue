<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { type Movimentacao } from '@/types';
import { Info, Calendar, CreditCard, Tag } from 'lucide-vue-next';
import movimentacoesRoutes from '@/routes/movimentacoes';

const props = defineProps<{
  open: boolean;
  movimentacoes: Movimentacao[];
}>();

const emit = defineEmits(['update:open', 'success']);

const parcelasDisponiveis = computed(() => {
  if (props.movimentacoes.length === 0) return 0;
  
  // Encontra a movimentação com o menor número de parcelas pendentes
  return Math.min(...props.movimentacoes.map(m => {
    const total = Number(m.parcelas || 0);
    const pagas = Number(m.parcelas_pagas || 0);
    return total - pagas;
  }));
});

const form = useForm({
  movimentacao_ids: [] as number[],
  quantidade_parcelas: '1',
  data_pagamento: new Date().toISOString().split('T')[0],
});

const opcoesQuantidade = computed(() => {
  const options = [];
  for (let i = 1; i <= parcelasDisponiveis.value; i++) {
    options.push({ value: String(i), label: String(i) });
  }
  return options;
});

watch(() => props.open, (isOpen) => {
  if (isOpen) {
    form.movimentacao_ids = props.movimentacoes.map(m => m.id);
    form.quantidade_parcelas = '1';
    form.data_pagamento = new Date().toISOString().split('T')[0];
  }
});

// Adiciona um watcher para garantir que a lista de movimentações e opções sejam reavaliadas caso mudem com o modal aberto
watch(() => props.movimentacoes, () => {
  if (props.open) {
    form.movimentacao_ids = props.movimentacoes.map(m => m.id);
  }
}, { deep: true });

const submit = () => {
  form.post(movimentacoesRoutes.pagarMassa().url, {
    onSuccess: () => {
      emit('update:open', false);
      emit('success');
    },
  });
};

const handleOpenChange = (value: boolean) => {
  emit('update:open', value);
};
</script>

<template>
  <Dialog :open="open" @update:open="handleOpenChange">
    <DialogContent class="sm:max-w-[550px] p-0 overflow-hidden border-none shadow-2xl">
      <div class="bg-blue-600 p-6 text-white relative">
        <div class="absolute top-4 right-4 opacity-10">
          <CreditCard class="h-24 w-24" />
        </div>
        <DialogHeader>
          <DialogTitle class="text-2xl font-bold text-white">Pagar Parcelas em Massa</DialogTitle>
          <DialogDescription class="text-blue-100 opacity-90">
            Confirmar pagamento para {{ movimentacoes.length }} movimentações.
          </DialogDescription>
        </DialogHeader>
      </div>

      <form @submit.prevent="submit" class="p-6 space-y-6 bg-white dark:bg-sidebar">
        <!-- Lista de Movimentações -->
        <div class="space-y-3">
          <Label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Movimentações Selecionadas:</Label>
          <div class="max-h-40 overflow-y-auto pr-2 space-y-2 border border-gray-100 dark:border-gray-700 rounded-lg p-2 bg-gray-50 dark:bg-gray-800">
            <div v-for="mov in movimentacoes" :key="mov.id" class="flex items-center justify-between text-xs bg-white dark:bg-gray-700 p-2 rounded shadow-sm border border-gray-100 dark:border-gray-600">
              <div class="flex items-center gap-2">
                <Tag class="h-3 w-3 text-blue-500" />
                <span class="font-medium text-gray-800 dark:text-gray-200">{{ mov.descricao }}</span>
              </div>
              <div class="text-gray-500 font-mono">
                {{ mov.parcelas_pagas }} / {{ mov.parcelas }}
              </div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-6">
          <div class="space-y-2">
            <div class="flex items-center gap-1.5 mb-1">
              <Label for="quantidade" class="text-sm font-medium">Qtd. Parcelas</Label>
              <TooltipProvider>
                <Tooltip>
                  <TooltipTrigger as-child>
                    <Info class="h-3.5 w-3.5 text-gray-400 cursor-help" />
                  </TooltipTrigger>
                  <TooltipContent>
                    <p>Quantas parcelas deseja quitar em cada movimentação?</p>
                  </TooltipContent>
                </Tooltip>
              </TooltipProvider>
            </div>
            <Select v-model="form.quantidade_parcelas">
              <SelectTrigger id="quantidade" class="h-11 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                <SelectValue placeholder="Selecione" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="opcao in opcoesQuantidade" :key="opcao.value" :value="opcao.value">
                  {{ opcao.label }} {{ Number(opcao.value) === 1 ? 'parcela' : 'parcelas' }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="space-y-2">
            <Label for="data_pagamento" class="text-sm font-medium flex items-center gap-2">
              <Calendar class="h-4 w-4 text-gray-400" />
              Data Pagamento
            </Label>
            <Input id="data_pagamento" v-model="form.data_pagamento" type="date" required class="h-11 bg-white dark:bg-gray-800" />
          </div>
        </div>

        <DialogFooter class="pt-4 flex flex-col-reverse sm:flex-row gap-3">
          <Button type="button" variant="ghost" @click="handleOpenChange(false)" class="flex-1 h-12">
            Cancelar
          </Button>
          <Button type="submit" :disabled="form.processing" class="flex-1 h-12 bg-blue-600 hover:bg-blue-700 text-white shadow-lg shadow-blue-500/20">
            Confirmar Pagamento
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
