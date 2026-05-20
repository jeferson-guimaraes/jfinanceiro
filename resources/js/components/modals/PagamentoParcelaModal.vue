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
import { computed, watch, ref } from 'vue';
import { type Movimentacao } from '@/types';
import { formataDinheiroBRL } from '@/utils/formataDinheiro';
import { formatBRL, handleValorKeydown } from '@/utils/masks';
import { Info, Calendar, DollarSign, Tag, CreditCard, Layers } from 'lucide-vue-next';
import movimentacoes from '@/routes/movimentacoes';

const props = defineProps<{
  open: boolean;
  movimentacao: Movimentacao | null;
}>();

const emit = defineEmits(['update:open', 'success']);

const totalParcelas = computed(() => {
  const p = props.movimentacao?.parcelas;
  return p ? Number(p) : 0;
});
const parcelasPagas = computed(() => {
  const pp = props.movimentacao?.parcelas_pagas;
  return pp ? Number(pp) : 0;
});
const parcelasDisponiveis = computed(() => totalParcelas.value - parcelasPagas.value);

const valorParcela = computed(() => {
  const mov = props.movimentacao;
  if (!mov || totalParcelas.value === 0) return 0;
  return Number(mov.valor) / totalParcelas.value;
});

const form = useForm({
  quantidade_parcelas: '1',
  data_pagamento: new Date().toISOString().split('T')[0],
  valor_total_pago: 0,
});

const valorInterno = ref(0);

const valorFormatado = computed({
  get() {
    return formatBRL(valorInterno.value);
  },
  set(value: string) {
    const digits = Number(value.replace(/[^\d]/g, ''));
    valorInterno.value = digits;
    form.valor_total_pago = digits / 100;
  }
});

const opcoesQuantidade = computed(() => {
  const options = [];
  for (let i = 1; i <= parcelasDisponiveis.value; i++) {
    options.push({ value: String(i), label: String(i) });
  }
  return options;
});

// Atualiza o valor total pago quando a quantidade muda
watch(() => form.quantidade_parcelas, (newQty) => {
  const novoValor = Number((Number(newQty) * valorParcela.value).toFixed(2));
  form.valor_total_pago = novoValor;
  valorInterno.value = Math.round(novoValor * 100);
});

// Inicializa o valor total quando o modal abre ou a movimentação muda
const inicializarForm = (mov: Movimentacao | null) => {
  if (mov) {
    const total = Number(mov.parcelas || 0);
    const vParcela = total > 0 ? Number(mov.valor) / total : 0;
    
    const valorInicial = Number((1 * vParcela).toFixed(2));
    form.quantidade_parcelas = '1';
    form.valor_total_pago = valorInicial;
    valorInterno.value = Math.round(valorInicial * 100);
    form.data_pagamento = new Date().toISOString().split('T')[0];
  }
};

watch(() => props.open, (isOpen) => {
  if (isOpen && props.movimentacao) {
    inicializarForm(props.movimentacao);
  }
});

watch(() => props.movimentacao, (newMov) => {
  if (props.open && newMov) {
    inicializarForm(newMov);
  }
}, { deep: true });

const submit = () => {
  if (!props.movimentacao) return;

  form.post(movimentacoes.pagar({ movimentacao: props.movimentacao.id }).url, {
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
    <DialogContent class="sm:max-w-[480px] p-0 overflow-hidden border-none shadow-2xl">
      <div class="bg-blue-600 p-6 text-white relative">
        <div class="absolute top-4 right-4 opacity-10">
          <CreditCard class="h-24 w-24" />
        </div>
        <DialogHeader>
          <DialogTitle class="text-2xl font-bold text-white">Pagar Parcelas</DialogTitle>
          <DialogDescription class="text-blue-100 opacity-90">
            Confirme o pagamento das parcelas pendentes desta movimentação.
          </DialogDescription>
        </DialogHeader>
      </div>

      <form @submit.prevent="submit" class="p-6 space-y-6 bg-white dark:bg-sidebar" v-if="props.movimentacao">
        <!-- Card de Resumo -->
        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4 border border-gray-100 dark:border-gray-700/50 space-y-3">
          <div class="flex items-center gap-3">
            <div class="bg-blue-100 dark:bg-blue-900/30 p-2 rounded-lg">
              <Tag class="h-5 w-5 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wider">Descrição</p>
              <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ props.movimentacao.descricao }}</p>
            </div>
          </div>
          
          <div class="grid grid-cols-2 gap-4 pt-2 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
              <div class="bg-gray-100 dark:bg-gray-700/50 p-2 rounded-lg">
                <Layers class="h-4 w-4 text-gray-600 dark:text-gray-400" />
              </div>
              <div>
                <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase">Categoria</p>
                <p class="text-xs font-medium">{{ props.movimentacao.categoria?.nome || 'Outros' }}</p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <div class="bg-gray-100 dark:bg-gray-700/50 p-2 rounded-lg">
                <DollarSign class="h-4 w-4 text-gray-600 dark:text-gray-400" />
              </div>
              <div>
                <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase">Valor Total</p>
                <p class="text-xs font-medium">{{ formataDinheiroBRL(props.movimentacao.valor) }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-6">
          <div class="space-y-2">
            <Label for="quantidade" class="text-sm font-medium">
              Qtd. Parcelas
              <TooltipProvider>
                <Tooltip>
                  <TooltipTrigger as-child>
                    <Info class="h-3.5 w-3.5 text-gray-400 cursor-help" />
                  </TooltipTrigger>
                  <TooltipContent>
                    <p>Quantas parcelas deseja quitar agora?</p>
                  </TooltipContent>
                </Tooltip>
              </TooltipProvider>
            </Label>
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
            <Label class="text-sm font-medium mb-1 inline-block">Status Atual</Label>
            <div class="h-11 flex items-center px-3 rounded-md bg-gray-100 dark:bg-gray-800 border border-transparent text-sm font-medium text-gray-600 dark:text-gray-400">
              {{ parcelasPagas }} de {{ totalParcelas }} pagas
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-6">
          <div class="space-y-2">
            <Label for="data_pagamento" class="text-sm font-medium flex items-center gap-2">
              <Calendar class="h-4 w-4 text-gray-400" />
              Data Pagamento
            </Label>
            <Input id="data_pagamento" v-model="form.data_pagamento" type="date" required class="h-11 bg-white dark:bg-gray-800" />
          </div>
          <div class="space-y-2">
            <Label for="valor_total_pago" class="text-sm font-medium flex items-center gap-2">
              <DollarSign class="h-4 w-4 text-gray-400" />
              Valor a Pagar
            </Label>
            <Input 
              id="valor_total_pago" 
              v-model="valorFormatado" 
              type="tel" 
              required 
              @keydown="handleValorKeydown"
              class="h-11 font-bold text-blue-600 dark:text-blue-400 bg-white dark:bg-gray-800" 
            />
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
