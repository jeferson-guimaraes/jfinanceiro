<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import debounce from 'lodash-es/debounce';
import TabsListCategories from '@/components/TabsListCategories.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { create } from '@/routes/movimentacoes/categorias';
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
		href: '/movimentacoes/categorias',
	},
];

const localSearch = ref(props.filters.search);
const localTipo = ref(props.filters.tipo);
const localPerPage = ref(props.filters.per_page || 10);

const searchData = () => {
	router.get('/movimentacoes/categorias', {
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
	router.get('/movimentacoes/categorias', {
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
						@update:search="localSearch = $event" 
						@update:tipo="localTipo = $event"
						@update:per_page="localPerPage = $event" 
					/>
				</div>
			</div>
		</div>
	</AppLayout>
</template>