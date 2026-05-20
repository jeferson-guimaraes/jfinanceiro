<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import movimentacoesRoute from '@/routes/movimentacoes';
import { type Movimentacao, type ParcelaComMovimentacao } from '@/types';
import { formatDate } from '@/utils/formatDate';
import { formatBRL } from '@/utils/masks';
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Checkbox from '../ui/checkbox/Checkbox.vue';
import { Trash2 } from 'lucide-vue-next';

interface Props {
  movimentacoes?: Movimentacao[];
  parcelas?: ParcelaComMovimentacao[];
  activeTab: string;
}

const props = defineProps<Props>();

const emit = defineEmits(['delete', 'delete:selected', 'pay']);

const selectedMovimentacoes = ref<number[]>([]);

const toggleSelection = (id: number, checked: boolean) => {
  if (checked) {
    if (!selectedMovimentacoes.value.includes(id)) {
      selectedMovimentacoes.value.push(id);
    }
  } else {
    selectedMovimentacoes.value = selectedMovimentacoes.value.filter(item => item !== id);
  }
};

const requestDeleteMany = () => {
  emit('delete:selected', selectedMovimentacoes.value);
  selectedMovimentacoes.value = [];
};

const sortKey = ref<string>('data');
const sortOrder = ref<'asc' | 'desc'>('desc');

const sortedMovimentacoes = computed(() => {
  if (!props.movimentacoes) return [];
  return [...props.movimentacoes].sort((a, b) => {
    let key = sortKey.value;
    if (key === 'movimentacao.valor') key = 'valor';
    if (key === 'movimentacao.parcelas_pagas') key = 'parcelas_pagas';
    if (key === 'categoria') key = 'categoria.nome';

    const aVal = normalizeValue(getValueByPath(a, key));
    const bVal = normalizeValue(getValueByPath(b, key));

    if (aVal < bVal) return sortOrder.value === 'asc' ? -1 : 1;
    if (aVal > bVal) return sortOrder.value === 'asc' ? 1 : -1;
    return 0;
  });
});

const sortedParcelas = computed(() => {
  if (!props.parcelas) return [];

  return [...props.parcelas].sort((a, b) => {
    let key = sortKey.value;
    if (key === 'data') key = 'movimentacao.data';
    if (key === 'descricao') key = 'movimentacao.descricao';
    if (key === 'categoria') key = 'movimentacao.categoria.nome';

    const aVal = normalizeValue(getValueByPath(a, key));
    const bVal = normalizeValue(getValueByPath(b, key));

    if (aVal < bVal) return sortOrder.value === 'asc' ? -1 : 1;
    if (aVal > bVal) return sortOrder.value === 'asc' ? 1 : -1;
    return 0;
  });
});

function normalizeValue(value: unknown): number | string {
  if (value === null || value === undefined) return 0;

  if (typeof value === 'number') return value;

  if (value instanceof Date) return value.getTime();

  if (typeof value === 'string') {
    // Se for um número válido no formato padrão (ex: "100.05"), retorna o número
    const asNumber = Number(value);
    if (!isNaN(asNumber) && value.trim() !== '') {
      return asNumber;
    }

    // Se parecer uma data, tenta converter para timestamp
    if (value.includes('-') && !isNaN(Date.parse(value))) {
      return new Date(value).getTime();
    }

    // Tenta converter formato brasileiro (ex: "1.234,56")
    const numeric = value
      .replace(/\./g, '')
      .replace(',', '.');

    if (!isNaN(Number(numeric)) && numeric.trim() !== '') {
      return Number(numeric);
    }

    return value.toLowerCase();
  }

  return String(value);
}

function getValueByPath(obj: any, path: string) {
  return path.split('.').reduce((acc, key) => acc?.[key], obj);
}

function sort(key: string) {
  if (sortKey.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortKey.value = key;
    sortOrder.value = 'asc';
  }
}

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

const getTipoClass = (tipo: string) => {
  switch (tipo) {
    case 'ganho':
      return 'text-green-600 dark:text-green-400';
    case 'gasto':
      return 'text-red-600 dark:text-red-400';
    case 'gasto futuro':
      return 'text-sky-600 dark:text-orange-400';
    default:
      return '';
  }
};

const getValorPrefix = (tipo: string) => {
  return tipo === 'gasto' ? '-' : '+';
};

const getParcelaStatus = (parcela: ParcelaComMovimentacao): 'paga' | 'vencida' | 'vence-hoje' | 'pendente' => {
  if (parcela.pago) return 'paga';

  const hoje = new Date();
  hoje.setHours(0, 0, 0, 0);

  const dataVencimento = new Date(parcela.data_vencimento + 'T00:00:00');

  if (dataVencimento < hoje) return 'vencida';
  if (dataVencimento.getTime() === hoje.getTime()) return 'vence-hoje';

  return 'pendente';
};

const getStatusRowClass = (parcela: ParcelaComMovimentacao) => {
  if (selectedMovimentacoes.value.includes(parcela.movimentacao.id)) return 'bg-blue-100 dark:bg-blue-900/40';

  const status = getParcelaStatus(parcela);
  switch (status) {
    case 'paga':
      return 'bg-green-100/50 dark:bg-green-900/20 hover:bg-green-100/90 dark:hover:bg-green-900/30';
    case 'vencida':
      return 'bg-red-100/70 dark:bg-red-900/20 hover:bg-red-100/90 dark:hover:bg-red-900/30';
    case 'vence-hoje':
      return 'bg-yellow-100/50 dark:bg-yellow-900/20 hover:bg-yellow-100/90 dark:hover:bg-yellow-900/30';
    default:
      return '';
  }
};

function requestDelete(movimentacao: Movimentacao | ParcelaComMovimentacao) {
  emit('delete', movimentacao);
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
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
      <div v-else></div>

      <div v-if="selectedMovimentacoes.length > 0" class="flex gap-2">
        <Button class="bg-green-500/10 text-green-600 font-semibold hover:bg-green-200 h-8" @click="emit('pay:selected', selectedMovimentacoes)">
          Pagar ({{ selectedMovimentacoes.length }})
        </Button>
        <Button class="bg-red-500/10 text-red-500 font-semibold hover:bg-red-200 h-8" @click="requestDeleteMany">
          <Trash2 class="text-red-500 font-semibold h-3.5 w-3.5 mr-2" />
          Excluir ({{ selectedMovimentacoes.length }})
        </Button>
      </div>
    </div>
    <Table>
      <TableHeader class="bg-[#5B92EA] dark:bg-blue-950">
          <TableHead class="w-10"></TableHead>
          <TableHead class="text-center cursor-pointer text-gray-100 hover:opacity-80" @click="sort('data')">
            Data
            <span v-if="activeTab === 'gasto futuro'">
              da Compra
            </span>
            <span v-if="sortKey === 'data'">{{
              sortOrder === 'asc' ? '↑' : '↓'
            }}</span>
          </TableHead>
          <TableHead class="text-center cursor-pointer text-gray-100 hover:opacity-80" @click="sort('descricao')">
            Descrição
            <span v-if="sortKey === 'descricao'">{{
              sortOrder === 'asc' ? '↑' : '↓'
            }}</span>
          </TableHead>
          <TableHead class="text-center cursor-pointer text-gray-100 hover:opacity-80" @click="sort('categoria')">
            Categoria
            <span v-if="sortKey === 'categoria'">{{
              sortOrder === 'asc' ? '↑' : '↓'
            }}</span>
          </TableHead>
          <TableHead class="text-center cursor-pointer text-gray-100 hover:opacity-80" @click="sort('tipo')" v-if="activeTab != 'gasto futuro'">
            Tipo
            <span v-if="sortKey === 'tipo'">{{
              sortOrder === 'asc' ? '↑' : '↓'
            }}</span>
          </TableHead>
          <TableHead class="text-center cursor-pointer text-gray-100 hover:opacity-80" @click="sort('movimentacao.valor')">
            Valor
            <span v-if="sortKey === 'movimentacao.valor'">{{
              sortOrder === 'asc' ? '↑' : '↓'
            }}</span>
          </TableHead>
          <template v-if="activeTab === 'gasto futuro'">
            <TableHead class="text-center cursor-pointer text-gray-100 hover:opacity-80" @click="sort('numero')">
              Parcela
              <span v-if="sortKey === 'numero'">{{ 
                sortOrder === 'asc' ? '↑' : '↓'
              }}</span>
            </TableHead>
            <TableHead class="text-center cursor-pointer text-gray-100" @click="sort('valor')">
              Valor das Parcelas
              <span v-if="sortKey === 'valor'">{{ 
                sortOrder === 'asc' ? '↑' : '↓'
              }}</span>
            </TableHead>
            <TableHead class="text-center cursor-pointer text-gray-100 hover:opacity-80" @click="sort('data_vencimento')">
              Data de Vencimento
              <span v-if="sortKey === 'data_vencimento'">{{ 
                sortOrder === 'asc' ? '↑' : '↓'
              }}</span>
            </TableHead>
            <TableHead class="text-center cursor-pointer text-gray-100 hover:opacity-80" @click="sort('movimentacao.parcelas_pagas')">
              Parcelas Pagas
              <span v-if="sortKey === 'movimentacao.parcelas_pagas'">{{ 
                sortOrder === 'asc' ? '↑' : '↓'
              }}</span>
            </TableHead>
          </template>
          <TableHead class="text-center text-gray-100">Ações</TableHead>
      </TableHeader>

      <TableBody class="text-center">
        <template v-if="activeTab === 'gasto futuro'">
          <TableRow v-for="parcela in sortedParcelas" :key="parcela.id" :class="getStatusRowClass(parcela)">
            <TableCell>
              <Checkbox :checked="selectedMovimentacoes.includes(parcela.movimentacao.id)" @update:modelValue="(val) => toggleSelection(parcela.movimentacao.id, Boolean(val))" />
            </TableCell>
            <TableCell class="whitespace-nowrap">
              {{ formatDate(parcela.movimentacao.data) }}
            </TableCell>
            <TableCell>
              {{ parcela.movimentacao.descricao }}
            </TableCell>
            <TableCell>
              {{ parcela.movimentacao.categoria?.nome || '-' }}
            </TableCell>
            <TableCell class="whitespace-nowrap">
              {{ formatBRL(parcela.movimentacao.valor) }}
            </TableCell>
            <TableCell class="whitespace-nowrap">
              {{ parcela.numero }}/{{ parcela.movimentacao.parcelas }}
            </TableCell>
            <TableCell class="whitespace-nowrap">
              {{ formatBRL(parcela.valor) }}
            </TableCell>
            <TableCell class="whitespace-nowrap">
              {{ formatDate(parcela.data_vencimento) }}
            </TableCell>
            <TableCell class="whitespace-nowrap">
              {{ parcela.movimentacao.parcelas_pagas }}
            </TableCell>
            <TableCell>
              <div class="flex items-center justify-end gap-2">
                <Button v-if="(parcela.movimentacao.parcelas_pagas || 0) < parcela.movimentacao.parcelas"
                  size="sm" variant="outline" @click="emit('pay', parcela.movimentacao)"
                  class="border-green-600 text-green-600 hover:bg-green-600/10 hover:text-green-600 dark:border-green-400 dark:text-green-400">
                  Pagar
                </Button>
                <Link :href="movimentacoesRoute.edit({
                  movimentacao:
                    parcela.movimentacao_id,
                }).url
                  ">
                  <Button size="sm" variant="outline"
                    class="border-[#5B92EA] text-[#5B92EA] hover:bg-[#5B92EA]/10 hover:text-[#5B92EA] dark:border-[#5894f3] dark:hover:bg-[#5B92EA]/30">
                    Editar
                  </Button>
                </Link>
                <Button size="sm" variant="destructive" @click="requestDelete(parcela)">
                  Excluir
                </Button>
              </div>
            </TableCell>
          </TableRow>
        </template>
        <template v-else>
          <TableRow v-for="movimentacao in sortedMovimentacoes" :key="movimentacao.id" :class="selectedMovimentacoes.includes(movimentacao.id) ? 'bg-blue-100 dark:bg-blue-900/40' : ''">
            <TableCell>
              <Checkbox :checked="selectedMovimentacoes.includes(movimentacao.id)" @update:modelValue="(val) => toggleSelection(movimentacao.id, Boolean(val))" />
            </TableCell>
            <TableCell class="whitespace-nowrap">
              {{ formatDate(movimentacao.data) }}
            </TableCell>
            <TableCell>
              {{ movimentacao.descricao }}
            </TableCell>
            <TableCell>
              {{ movimentacao.categoria?.nome || '-' }}
            </TableCell>
            <TableCell class="whitespace-nowrap">
              <span :class="getTipoClass(movimentacao.tipo)">
                {{ getTipoLabel(movimentacao.tipo) }}
              </span>
            </TableCell>
            <TableCell class="whitespace-nowrap">
              <span :class="getTipoClass(movimentacao.tipo)">
                {{ getValorPrefix(movimentacao.tipo)
                }}{{ formatBRL(movimentacao.valor) }}
              </span>
            </TableCell>
            <TableCell>
              <div class="flex items-center justify-end gap-2">
                <Button v-if="movimentacao.tipo === 'gasto futuro' && (movimentacao.parcelas_pagas || 0) < movimentacao.parcelas"
                  size="sm" variant="outline" @click="emit('pay', movimentacao)"
                  class="border-green-600 text-green-600 hover:bg-green-600/10 hover:text-green-600 dark:border-green-400 dark:text-green-400">
                  Pagar
                </Button>
                <Link :href="movimentacoesRoute.edit({
                  movimentacao: movimentacao.id,
                }).url
                  ">
                  <Button size="sm" variant="outline"
                    class="border-[#5B92EA] text-[#5B92EA] hover:bg-[#5B92EA]/10 hover:text-[#5B92EA] dark:border-[#5894f3] dark:hover:bg-[#5B92EA]/30">
                    Editar
                  </Button>
                </Link>
                <Button size="sm" variant="destructive" @click="requestDelete(movimentacao)">
                  Excluir
                </Button>
              </div>
            </TableCell>
          </TableRow>
        </template>

        <TableRow v-if="
          (activeTab === 'gasto futuro'
            ? sortedParcelas.length
            : sortedMovimentacoes.length) === 0
        ">
          <TableCell :colspan="activeTab === 'gasto futuro' ? 10 : 7" class="h-24 text-center">
            Nenhuma movimentação encontrada.
          </TableCell>
        </TableRow>
      </TableBody>
    </Table>
  </div>
</template>