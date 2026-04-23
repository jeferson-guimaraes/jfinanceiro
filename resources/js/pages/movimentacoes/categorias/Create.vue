<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';

defineProps<{
	tipos_movimentacao: { name: string; value: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
	{
		title: 'Categorias',
		href: '/movimentacoes/categorias',
	},
	{
		title: 'Nova Categoria',
		href: '#',
	},
];

const form = useForm({
	nome: '',
	tipo: 'ganho',
});

function submit() {
	form.post('/movimentacoes/categorias', {
		onSuccess: () => {
			form.reset('nome', 'tipo');
		},
	});
}
</script>

<template>

	<Head title="Nova Categoria" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
			<form @submit.prevent="submit" class="md:max-w-5xl">
				<div class="space-y-12">
					<div class="border-b border-gray-900/10 dark:border-gray-700 pb-12">
						<h2 class="text-base font-semibold leading-7 text-gray-900 dark:text-gray-100">Nova Categoria</h2>
						<p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-400">Crie uma nova categoria para suas
							movimentações.</p>

						<div class="mt-10 grid grid-cols-1 gap-6">
							
							<div>
								<Label for="nome">Nome</Label>
								<Input id="nome" v-model="form.nome" name="nome" type="text" autocomplete="off" />
							</div>
							<div>
								<Label for="tipo">
									Tipo
								</Label>
								
								<div class="mt-2">
									<Select v-model="form.tipo">
										<SelectTrigger>
											<SelectValue placeholder="Selecione o tipo" />
										</SelectTrigger>
										<SelectContent>
											<SelectItem v-for="t in tipos_movimentacao" :key="t.value" :value="t.value"> {{ t.name }} </SelectItem>
										</SelectContent>
									</Select>
								</div>
							</div>

						</div>
					</div>
				</div>

				<div class="mt-6 flex flex-col-reverse gap-y-4 sm:flex-row sm:justify-end sm:gap-x-6">
					<Button type="submit" :disabled="form.processing" class="btn-primary w-30">
						Salvar
					</Button>
				</div>
			</form>
		</div>
	</AppLayout>
</template>