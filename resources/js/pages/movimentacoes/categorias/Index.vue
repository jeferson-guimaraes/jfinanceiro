<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import TabsListCategories from '@/components/TabsListCategories.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { create } from '@/routes/movimentacoes/categorias';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Categoria, Paginated, Filter } from '@/types/movimentacoes/categorias';

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
		<div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
			<div class="space-y-2 xl:px-8">
				<div class="flex gap-4 justify-between">
					<div>
						<h2 class="text-base font-semibold leading-7 text-gray-900 dark:text-gray-100">Categoria</h2>
						<p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-400">
							Consulte todas as suas categorias para suas movimentações.
						</p>
					</div>

					<div>
						<Link :href="create({ query: { tipo: localTipo } }).url">
						<Button class="w-30">
							Nova Categoria
						</Button>
						</Link>
					</div>

				</div>

				<div class="grid grid-cols-1 gap-x-6 gap-y-4">

					<TabsListCategories :categorias="listaCategorias" :filters="filters"
						@update:search="localSearch = $event" @update:tipo="localTipo = $event"
						@update:per_page="localPerPage = $event" />

				</div>
			</div>
		</div>
	</AppLayout>
</template>