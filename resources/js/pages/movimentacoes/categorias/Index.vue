<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import debounce from 'lodash-es/debounce';
import ConfirmarExclusaoModal from '@/components/modals/ConfirmarExclusaoModal.vue';
import TabsListCategories from '@/components/TabsListCategories.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import categoriasRoutes, { create } from '@/routes/movimentacoes/categorias';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Categoria, Paginated, Filter } from '@/types/movimentacoes/categorias';
import { Plus } from 'lucide-vue-next';

const props = defineProps<{
	listaCategorias: Paginated<Categoria>;
	filters: Filter;
}>();

const breadcrumbs: BreadcrumbItem[] = [
	{
		title: 'Categorias',
		href: categoriasRoutes.index().url,
	},
];

const localSearch = ref(props.filters.search);
const localTipo = ref(props.filters.tipo);
const localPerPage = ref(props.filters.per_page || 10);

const isModalExclusaoAberto = ref(false);
const categoriaParaExcluir = ref<Categoria | null>(null);
const selectedCategorias = ref<number[]>([]);
const isModalExclusaoMassaAberto = ref(false);

function requestDelete(categoria: Categoria) {
	categoriaParaExcluir.value = categoria;
	isModalExclusaoAberto.value = true;
}

function confirmDelete() {
	if (categoriaParaExcluir.value) {
		router.delete(categoriasRoutes.destroy({ categoria: categoriaParaExcluir.value.id }).url, {
			preserveScroll: true,
			preserveState: true,
			onSuccess: () => {
				isModalExclusaoAberto.value = false;
				categoriaParaExcluir.value = null;
			},
		});
	}
}

function requestDeleteMany(categoriasIds: number[]) {
	selectedCategorias.value = categoriasIds;
	isModalExclusaoMassaAberto.value = true;
}

function confirmDeleteMany() {
	if (selectedCategorias.value.length > 0) {
		router.delete(categoriasRoutes.destroyMany().url, {
			data: {
				categorias_ids: selectedCategorias.value,
			},
			preserveScroll: true,
			preserveState: true,
			onSuccess: () => {
				isModalExclusaoMassaAberto.value = false;
				selectedCategorias.value = [];
			},
		});
	}
}

const searchData = () => {
	router.get(categoriasRoutes.index().url, {
		search: localSearch.value,
		tipo: localTipo.value,
		per_page: localPerPage.value,
	}, {
		preserveState: true,
		replace: true,
		preserveScroll: true,
	});
};

const debouncedSearch = debounce(searchData, 500);

watch(localSearch, () => {
	debouncedSearch();
});

watch(localTipo, (newTipo) => {
	selectedCategorias.value = [];
	router.get(categoriasRoutes.index().url, {
		search: localSearch.value,
		tipo: newTipo,
		per_page: localPerPage.value,
	}, {
		preserveState: true,
		replace: true,
		preserveScroll: true,
	});
});

watch(localPerPage, () => {
	searchData();
});
</script>

<template>
	<Head title="Minhas Categorias" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="mx-auto w-full max-w-7xl px-4 py-8 pb-32 sm:px-6 lg:px-8 sm:pb-8">
			<div class="flex flex-col gap-8">
				<!-- Header Minimalista -->
				<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
					<div class="space-y-1">
						<h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100 sm:text-3xl">
							Categorias
						</h1>
						<p class="text-sm text-gray-500 dark:text-gray-400">
							Consulte e gerencie todas as suas categorias para organizar suas movimentações.
						</p>
					</div>

					<div class="hidden sm:block">
						<Link :href="create({ query: { tipo: localTipo } }).url">
							<Button class="bg-blue-600 hover:bg-blue-700 text-white shadow-sm transition-all px-6">
								<Plus class="mr-2 h-4 w-4" />
								Nova Categoria
							</Button>
						</Link>
					</div>
				</div>

				<!-- Conteúdo principal -->
				<div class="space-y-6">
					<TabsListCategories
						:categorias="listaCategorias"
						:filters="filters"
						v-model:selectedCategorias="selectedCategorias"
						@update:search="localSearch = $event"
						@update:tipo="localTipo = $event"
						@update:per_page="localPerPage = $event"
						@delete="requestDelete"
						@delete:selected="requestDeleteMany"
					/>
				</div>
			</div>
		</div>

		<ConfirmarExclusaoModal
			v-model:open="isModalExclusaoAberto"
			title="Excluir categoria"
			message="Essa categoria será removida permanentemente."
			description="Essa ação não pode ser desfeita. As movimentações desta categoria serão alteradas para a categoria 'Outros'."
			confirm-text="Excluir"
			@confirm="confirmDelete"
			@cancel="isModalExclusaoAberto = false"
		/>

		<ConfirmarExclusaoModal
			v-model:open="isModalExclusaoMassaAberto"
			title="Excluir categorias"
			message="As categorias selecionadas serão removidas permanentemente."
			description="Essa ação não pode ser desfeita. As movimentações desta categoria serão alteradas para a categoria 'Outros'."
			confirm-text="Excluir"
			@confirm="confirmDeleteMany"
			@cancel="isModalExclusaoMassaAberto = false"
		/>
	</AppLayout>
</template>
