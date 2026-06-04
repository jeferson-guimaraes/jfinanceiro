<script setup lang="ts">
import { ref, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import ConfirmarExclusaoModal from './modals/ConfirmarExclusaoModal.vue'
import type { Categoria, Paginated, Filter } from '@/types/movimentacoes/categorias'
import { Pencil, Trash2 } from 'lucide-vue-next'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

const props = defineProps<{
  categorias: Paginated<Categoria>
  filters: Filter
}>()

const emit = defineEmits(['update:search', 'update:tipo', 'update:per_page'])

const selectedItems = ref<number[]>([])
const isModalConfirmarExclusaoAberto = ref(false)

const localPerPage = ref(String(props.filters.per_page || 10));

const deleteContext = ref<{ type: 'single' | 'multiple', count: number } | null>(null)

function toggleItem(id: number) {
  if (selectedItems.value.includes(id)) {
    selectedItems.value = selectedItems.value.filter(i => i !== id)
  } else {
    selectedItems.value.push(id)
  }
}

function handleSearch(event: Event) {
  emit('update:search', (event.target as HTMLInputElement).value)
}

function selectTab(tipo: string) {
  emit('update:tipo', tipo)
}

function handlePerPageChange(value: any) {
  const strValue = String(value);
  localPerPage.value = strValue;
  emit('update:per_page', Number(strValue));
}

function requestDeleteSingle(id: number) {
  selectedItems.value = [id]
  deleteContext.value = { type: 'single', count: 1 }
  isModalConfirmarExclusaoAberto.value = true
}

function requestDeleteMultiple() {
  deleteContext.value = { type: 'multiple', count: selectedItems.value.length }
  isModalConfirmarExclusaoAberto.value = true
}

function confirmDelete() {
  router.delete('/movimentacoes/categorias', {
    data: { ids: selectedItems.value },
    preserveScroll: true,
    onSuccess: () => {
      selectedItems.value = []
      deleteContext.value = null
      isModalConfirmarExclusaoAberto.value = false
    },
  })
}

const modalTitle = computed(() => deleteContext.value ? (deleteContext.value.type === 'single' ? 'Excluir categoria' : `Excluir ${deleteContext.value.count} categorias`) : '')
const modalMessage = computed(() => deleteContext.value ? (deleteContext.value.type === 'single' ? 'Essa categoria será removida permanentemente.' : 'As categorias selecionadas serão removidas permanentemente.') : '')

const tabs = [
  { title: 'Ganhos', tipo: 'ganho' },
  { title: 'Despesas', tipo: 'gasto' },
  { title: 'Futuras', tipo: 'gasto futuro' },
];

const paginasVisiveis = computed(() => {
  const total = props.categorias.last_page;
  const atual = props.categorias.current_page;
  const delta = 1;
  
  const range: (number | string)[] = [];
  
  for (let i = 1; i <= total; i++) {
    if (
      i === 1 || 
      i === total || 
      (i >= atual - delta && i <= atual + delta)
    ) {
      range.push(i);
    } else if (
      (i === atual - delta - 1) || 
      (i === atual + delta + 1)
    ) {
      range.push('...');
    }
  }
  
  return range.filter((item, pos) => range.indexOf(item) === pos);
});

const getPageUrl = (page: number | string) => {
  if (page === '...') return '#';
  const url = new URL(props.categorias.path, window.location.origin);
  const params = new URLSearchParams(window.location.search);
  params.set('page', String(page));
  // Mantém outros filtros se existirem
  if (props.filters.search) params.set('search', props.filters.search);
  if (props.filters.tipo) params.set('tipo', props.filters.tipo);
  if (props.filters.per_page) params.set('per_page', String(props.filters.per_page));
  return `${url.pathname}?${params.toString()}`;
};
</script>

<template>
  <div class="space-y-4">
    <!-- Tabs -->
    <nav class="-mb-px flex space-x-8 overflow-x-auto border-b border-gray-200 dark:border-gray-800">
      <button
        v-for="tab in tabs"
        :key="tab.tipo"
        @click="selectTab(tab.tipo)"
        class="whitespace-nowrap border-b-2 py-4 text-sm font-medium transition-all duration-200 hover:cursor-pointer"
        :class="[
          filters.tipo === tab.tipo
            ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
        ]"
      >
        {{ tab.title }}
      </button>
    </nav>

    <!-- Header de Ações e Busca -->
    <div class="relative flex items-center gap-2 px-1 mb-2 mt-4 min-h-[36px]">
      <div class="flex-1 w-full" :class="{ 'opacity-0 pointer-events-none': selectedItems.length > 0 }">
        <Input :model-value="filters.search" @input="handleSearch" placeholder="Buscar categoria..." class="h-9 text-xs w-full" />
      </div>

      <div v-if="selectedItems.length > 0" class="absolute inset-0 bg-blue-50 dark:bg-blue-900/20 p-1.5 rounded-lg border border-blue-100 dark:border-blue-800 flex justify-between items-center shadow-sm">
        <span class="text-[10px] font-bold text-blue-600 dark:text-blue-400 ml-2 uppercase">
          {{ selectedItems.length }} selecionados
        </span>
        <Button size="sm" variant="destructive" class="h-7 px-3 text-[10px] font-bold uppercase" @click="requestDeleteMultiple">
          <Trash2 class="h-3.5 w-3.5 mr-1" />
          Excluir
        </Button>
      </div>
    </div>

    <!-- Lista de Categorias -->
    <div class="divide-y divide-gray-100 dark:divide-gray-800 border-y border-gray-100 dark:border-gray-800">
      <div v-for="item in categorias.data" :key="item.id" 
        class="flex items-center gap-3 py-3 px-2 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
        
        <Checkbox :model-value="selectedItems.includes(item.id)" @update:modelValue="() => toggleItem(item.id)" />
        
        <div class="flex-1 font-medium text-sm text-gray-900 dark:text-gray-100">
          {{ item.nome }}
        </div>

        <div class="flex gap-1">
          <Link :href="`/movimentacoes/categorias/${item.id}/edit`">
            <Button size="icon" variant="ghost" class="h-8 w-8 text-gray-400 hover:text-blue-600">
              <Pencil class="h-4 w-4" />
            </Button>
          </Link>
          <Button size="icon" variant="ghost" class="h-8 w-8 text-gray-400 hover:text-red-600" @click="requestDeleteSingle(item.id)">
            <Trash2 class="h-4 w-4 text-red-500" />
          </Button>
        </div>
      </div>

      <div v-if="categorias.data.length === 0" class="py-8 text-center text-sm text-gray-500">
        Nenhuma categoria encontrada.
      </div>
    </div>

    <!-- Paginação Responsiva -->
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-6">
      <!-- Info e Itens por Página -->
      <div class="flex items-center justify-between w-full sm:w-auto gap-6">
        <div class="text-[10px] text-gray-500 uppercase font-bold tracking-wider">
          {{ categorias.from || 0 }} - {{ categorias.to || 0 }} de {{ categorias.total || 0 }} registros
        </div>
        
        <div class="flex items-center gap-2">
          <span class="text-[10px] text-gray-400 uppercase font-bold hidden sm:inline">Exibir:</span>
          <Select :model-value="localPerPage" @update:model-value="handlePerPageChange">
            <SelectTrigger class="h-8 text-[10px] w-16 bg-transparent border-gray-200 dark:border-gray-800">
              <SelectValue :placeholder="localPerPage" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="10">10</SelectItem>
              <SelectItem value="25">25</SelectItem>
              <SelectItem value="50">50</SelectItem>
              <SelectItem value="100">100</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <!-- Navegação Inteligente -->
      <div class="flex items-center justify-center w-full sm:w-auto gap-1">
        <!-- Anterior -->
        <Link 
          :href="categorias.links[0].url || '#'"
          preserve-scroll 
          preserve-state
          class="h-9 flex items-center justify-center px-2 text-[10px] font-bold uppercase border rounded-md transition-all"
          :class="categorias.links[0].url ? 'text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm hover:border-blue-300' : 'text-gray-300 dark:text-gray-700 border-gray-100 dark:border-gray-900 opacity-50 cursor-not-allowed'"
        >
          <span class="text-[7pt] sm:inline">« Anterior</span>
        </Link>

        <!-- Páginas -->
        <div class="flex items-center gap-1">
          <template v-for="(page, index) in paginasVisiveis" :key="index">
            <span v-if="page === '...'" class="px-1 text-gray-400 text-xs font-bold">...</span>
            <Link v-else
              :href="getPageUrl(page)"
              preserve-scroll 
              preserve-state 
              class="min-w-[32px] sm:min-w-[36px] h-9 flex items-center justify-center px-2 text-[10px] font-black border rounded-md transition-all duration-200" 
              :class="{
                'bg-blue-600 text-white border-blue-600 shadow-sm z-10': page === categorias.current_page,
                'text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 hover:border-blue-300': page !== categorias.current_page,
              }">
              {{ page }}
            </Link>
          </template>
        </div>

        <!-- Próximo -->
        <Link 
          :href="categorias.links[categorias.links.length - 1].url || '#'"
          preserve-scroll 
          preserve-state
          class="h-9 flex items-center justify-center px-2 text-[10px] font-bold uppercase border rounded-md transition-all"
          :class="categorias.links[categorias.links.length - 1].url ? 'text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm hover:border-blue-300' : 'text-gray-300 dark:text-gray-700 border-gray-100 dark:border-gray-900 opacity-50 cursor-not-allowed'"
        >
          <span class="text-[7pt] sm:inline">Próximo »</span>
        </Link>
      </div>
    </div>
  </div>

  <!-- Modal -->
  <ConfirmarExclusaoModal v-model:open="isModalConfirmarExclusaoAberto" :title="modalTitle" :message="modalMessage"
    description="Essa ação não pode ser desfeita. As movimentações desta categoria serão alteradas para a categoria 'Outros'."
    confirm-text="Excluir" @confirm="confirmDelete" @cancel="isModalConfirmarExclusaoAberto = false" />
</template>
