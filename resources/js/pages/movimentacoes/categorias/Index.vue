<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { create } from '@/routes/movimentacoes/categorias';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
	tipos_movimentacao: { name: string; value: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
	{
		title: 'Categorias',
		href: '/movimentacoes/categorias',
	},
];

</script>

<template>

	<Head title="Minhas Categorias" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
			<div class="space-y-12 px-8">
				<div class="pb-12">
					<div class="flex gap-4 justify-between">
						<div>
							<h2 class="text-base font-semibold leading-7 text-gray-900 dark:text-gray-100">Categoria</h2>
							<p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-400">
								Consulte todas as suas categorias para suas movimentações.
							</p>
						</div>

						<div>
							<Link :href="create()">
								<Button class="w-30">
										Nova Categoria
								</Button>
							</Link>
						</div>

					</div>

					<div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-4">
						
					</div>
				</div>
			</div>
		</div>
	</AppLayout>
</template>