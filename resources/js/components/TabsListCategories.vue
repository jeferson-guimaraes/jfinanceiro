<script setup lang="ts">
import { ref, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

import ConfirmarExclusaoModal from './modals/ConfirmarExclusaoModal.vue'
import type { Categoria, Paginated, Filter } from '@/types/movimentacoes/categorias'
import { AcceptableValue } from 'reka-ui'

/* -------------------------------------------------------------------------- */
/* Props / Emits                                                               */
/* -------------------------------------------------------------------------- */

const props = defineProps<{
  categorias: Paginated<Categoria>
  filters: Filter
}>()

const emit = defineEmits(['update:search', 'update:tipo', 'update:per_page'])

/* -------------------------------------------------------------------------- */
/* State                                                                      */
/* -------------------------------------------------------------------------- */

const selectedItems = ref<number[]>([])
const isModalConfirmarExclusaoAberto = ref(false)

type DeleteContext = {
  type: 'single' | 'multiple'
  count: number
}

const deleteContext = ref<DeleteContext | null>(null)

/* -------------------------------------------------------------------------- */
/* Tabs                                                                       */
/* -------------------------------------------------------------------------- */

const tabs = [
  { title: 'Ganhos', tipo: 'ganho' },
  { title: 'Despesas', tipo: 'gasto' },
  { title: 'Futuras', tipo: 'gasto futuro' },
  ];

/* -------------------------------------------------------------------------- */
/* Selection logic                                                            */
/* -------------------------------------------------------------------------- */

const allIds = computed(() =>
  props.categorias.data.map(item => item.id)
)

const isAllSelected = computed(() =>
  allIds.value.length > 0 &&
  allIds.value.every(id => selectedItems.value.includes(id))
)

const isIndeterminate = computed(() =>
  selectedItems.value.length > 0 && !isAllSelected.value
)

const allSelectedProxy = computed({
  get: () => isAllSelected.value,
  set: (value: boolean) => {
    selectedItems.value = value ? [...allIds.value] : []
  },
})

function toggleItem(id: number) {
  if (selectedItems.value.includes(id)) {
    selectedItems.value = selectedItems.value.filter(i => i !== id)
  } else {
    selectedItems.value.push(id)
  }
}

/* -------------------------------------------------------------------------- */
/* Filters                                                                    */
/* -------------------------------------------------------------------------- */

function handleSearch(event: Event) {
  emit('update:search', (event.target as HTMLInputElement).value)
}

function selectTab(tipo: string) {
  emit('update:tipo', tipo)
}

function handlePerPageChange(value: AcceptableValue) {
  if (value) {
    emit('update:per_page', value)
  }
}

/* -------------------------------------------------------------------------- */
/* Delete flow                                                                */
/* -------------------------------------------------------------------------- */

function requestDeleteSingle(id: number) {
  selectedItems.value = [id]
  deleteContext.value = { type: 'single', count: 1 }
  isModalConfirmarExclusaoAberto.value = true
}

function requestDeleteMultiple() {
  deleteContext.value = {
    type: 'multiple',
    count: selectedItems.value.length,
  }
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

/* -------------------------------------------------------------------------- */
/* Modal texts                                                                */
/* -------------------------------------------------------------------------- */

const modalTitle = computed(() => {
  if (!deleteContext.value) return ''

  return deleteContext.value.type === 'single'
    ? 'Excluir categoria'
    : `Excluir ${deleteContext.value.count} categorias`
})

const modalMessage = computed(() => {
  if (!deleteContext.value) return ''

  return deleteContext.value.type === 'single'
    ? 'Essa categoria será removida permanentemente.'
    : 'As categorias selecionadas serão removidas permanentemente.'
})
</script>

<template>
  <div>
    <!-- Tabs -->
    <div class="border-b border-gray-200 dark:border-gray-700">
      <ul class="flex -mb-px text-sm font-medium text-gray-500 dark:text-gray-400">
        <li v-for="tab in tabs" :key="tab.tipo" class="mr-2">
          <button class="hover:cursor-pointer" @click="selectTab(tab.tipo)" :class="[
            'p-4 border-b-2',
            filters.tipo === tab.tipo
              ? 'text-blue-600 border-blue-600'
              : 'border-transparent hover:text-gray-600 hover:border-gray-300',
          ]">
            {{ tab.title }}
          </button>
        </li>
      </ul>
    </div>

    <!-- Content -->
    <div class="p-4 bg-gray-50 dark:bg-sidebar rounded-b-lg">
      <div class="mb-4 flex justify-between gap-2">
        <Input :model-value="filters.search" @input="handleSearch" placeholder="Buscar categoria..." class="max-w-sm" />

        <Button v-if="selectedItems.length > 0" variant="destructive" size="sm" @click="requestDeleteMultiple">
          Excluir selecionados
        </Button>
      </div>

      <div class="border rounded-md">
        <Table>
          <TableHeader class="bg-[#5B92EA] dark:bg-blue-950">
            <TableHead class="pr-0 w-1">
              <Checkbox v-model="allSelectedProxy" :indeterminate="isIndeterminate" />
            </TableHead>
            <TableHead class="text-gray-100">Nome</TableHead>
            <TableHead class="text-right text-gray-100 pr-[15%] md:pr-[10%] xl:pr-[4%]">Ações</TableHead>
          </TableHeader>

          <TableBody>
            <TableRow v-for="item in categorias.data" :key="item.id">
              <TableCell class="pr-0">
                <Checkbox :model-value="selectedItems.includes(item.id)"
                  @update:modelValue="() => toggleItem(item.id)" />
              </TableCell>

              <TableCell>
                {{ item.nome }}
              </TableCell>

              <TableCell class="text-right">
                <Link :href="`/movimentacoes/categorias/${item.id}/edit`">
                  <Button size="sm" variant="outline" class="mr-2 border-[#5B92EA] text-[#5B92EA] hover:bg-[#5B92EA]/10 hover:text-[#5B92EA] dark:border-[#5894f3] dark:hover:bg-[#5B92EA]/30">
                    Editar
                  </Button>
                </Link>
                <Button size="sm" variant="destructive" @click="requestDeleteSingle(item.id)">
                  Excluir
                </Button>
              </TableCell>
            </TableRow>

            <TableRow v-if="categorias.data.length === 0">
              <TableCell colspan="3" class="h-24 text-center">
                Nenhum resultado.
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>

      <!-- Pagination -->
      <div class="lg:flex justify-between items-center py-4">
        <div class="text-sm text-center text-gray-700 dark:text-gray-400">
          Mostrando {{ categorias.from }} a {{ categorias.to }} de {{ categorias.total }} resultados
        </div>

        <div class="lg:flex items-center gap-4">
          <div class="flex items-center justify-center gap-2 my-4">
            <Select :model-value="String(categorias.per_page)" @update:model-value="handlePerPageChange">
              <SelectTrigger class="w-auto text-gray-600">
                <SelectValue placeholder="Itens por página" />
              </SelectTrigger>
              <SelectContent>
                <SelectGroup>
                  <SelectItem value="10" class="text-gray-600">
                    10 por página
                  </SelectItem>
                  <SelectItem value="25" class="text-gray-600">
                    25 por página
                  </SelectItem>
                  <SelectItem value="50" class="text-gray-600">
                    50 por página
                  </SelectItem>
                  <SelectItem value="50" class="text-gray-600">
                    100 por página
                  </SelectItem>
                </SelectGroup>
              </SelectContent>
            </Select>
          </div>

          <div class="flex items-center justify-center">
            <Link v-for="link in categorias.links" :key="link.label" :href="link.url || '#'"
              preserve-scroll class="px-3 py-2 text-sm" :class="{
                'bg-blue-500 text-white rounded-md': link.active,
                'text-gray-500': !link.url,
              }">
              <span v-html="link.label" />
            </Link>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal -->
  <ConfirmarExclusaoModal v-model:open="isModalConfirmarExclusaoAberto" :title="modalTitle" :message="modalMessage"
    description="Essa ação não pode ser desfeita. As movimentações desta categoria serão alteradas para a categoria 'Outros'."
    confirm-text="Excluir" @confirm="confirmDelete" @cancel="isModalConfirmarExclusaoAberto = false" />
</template>
