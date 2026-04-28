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

interface Props {
  movimentacoes?: Movimentacao[];
  parcelas?: ParcelaComMovimentacao[];
  activeTab: string;
}

const props = defineProps<Props>();

const emit = defineEmits(['delete']);

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

function requestDelete(movimentacao: Movimentacao | ParcelaComMovimentacao) {
  emit('delete', movimentacao);
}
</script>

<template>
    <Table>
      <TableHeader class="bg-[#5B92EA] dark:bg-blue-950">
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
          <TableRow v-for="parcela in sortedParcelas" :key="parcela.id">
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
          <TableRow v-for="movimentacao in sortedMovimentacoes" :key="movimentacao.id">
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
          <TableCell :colspan="activeTab === 'gasto futuro' ? 9 : 6" class="h-24 text-center">
            Nenhuma movimentação encontrada.
          </TableCell>
        </TableRow>
      </TableBody>
    </Table>
</template>
