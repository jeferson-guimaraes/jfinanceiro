<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Link } from '@inertiajs/vue3';
import { Categoria, Paginated, Filter } from '@/types/movimentacoes/categorias';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';

defineProps<{
  categorias: Paginated<Categoria>;
  filters: Filter;
}>();

const emit = defineEmits(['update:search', 'update:tipo']);

const tabs = [
  { title: 'Ganhos', tipo: 'ganho' },
  { title: 'Despesas', tipo: 'gasto' },
  { title: 'Futuros', tipo: 'gasto futuro' },
]

function handleSearch(event: Event) {
  emit('update:search', (event.target as HTMLInputElement).value);
}

function selectTab(tipo: string) {
  emit('update:tipo', tipo);
}
</script>

<template>
  <div>
    <div class="border-b border-gray-200 dark:border-gray-700">
      <ul class="flex flex-wrap -mb-px text-sm font-medium text-center text-gray-500 dark:text-gray-400">
        <li v-for="tab in tabs" :key="tab.tipo" class="mr-2">
          <button @click="selectTab(tab.tipo)" :class="[
            'inline-block p-4 border-b-2 rounded-t-lg cursor-pointer',
            {
              'text-blue-600 border-blue-600 active dark:text-blue-500 dark:border-blue-500': filters.tipo === tab.tipo,
              'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300': filters.tipo !== tab.tipo,
            },
          ]">
            {{ tab.title }}
          </button>
        </li>
      </ul>
    </div>

    <div class="p-4 bg-gray-50 dark:bg-sidebar rounded-b-lg">
      <div class="mb-4">
        <Input :model-value="filters.search" @input="handleSearch" placeholder="Buscar categoria..." class="max-w-sm" />
      </div>
      <div class="border rounded-md relative h-[600px] overflow-y-auto">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead class="text-gray-800 dark:text-gray-100">Nome</TableHead>
              <TableHead class="text-gray-800 text-right dark:text-gray-100">Ações</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <template v-if="categorias.data.length > 0">
              <TableRow v-for="item in categorias.data" :key="item.id">
                <TableCell class="text-gray-600 dark:text-gray-300">{{ item.nome }}</TableCell>
                <TableCell class="text-right">
                  <!-- Action buttons will go here -->
                </TableCell>
              </TableRow>
            </template>
            <template v-else>
              <TableRow>
                <TableCell colspan="2" class="h-24 text-center">
                  Nenhum resultado.
                </TableCell>
              </TableRow>
            </template>
          </TableBody>
        </Table>
      </div>


      <div class="flex items-center justify-end space-x-2 py-4">
        <Link v-for="link in categorias.links" :key="link.label" :href="link.url || '#'" class="px-3 py-2 text-sm"
          :class="{ 'bg-blue-500 text-white rounded-md': link.active, 'text-gray-500': !link.url }"
          v-html="link.label" preserve-scroll />
      </div>
    </div>
  </div>
</template>
