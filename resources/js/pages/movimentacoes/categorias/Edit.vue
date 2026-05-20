<script setup lang="ts">
import FormLayout from '@/components/FormLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Categoria } from '@/types/movimentacoes/categorias';
import { Head, useForm } from '@inertiajs/vue3';
import { Tag, Wallet, CreditCard, Calendar, Save, Edit } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
	tipos_movimentacao: { name: string; value: string }[];
	categoria: Categoria;
}>();

const breadcrumbs: BreadcrumbItem[] = [
	{
		title: 'Categorias',
		href: '/movimentacoes/categorias',
	},
	{
		title: 'Editar Categoria',
		href: '#',
	},
];

const form = useForm({
	nome: props.categoria.nome,
	tipo: props.categoria.tipo,
});

const formVariant = computed(() => {
    switch (form.tipo) {
        case 'ganho': return 'success';
        case 'gasto': return 'danger';
        case 'gasto futuro': return 'warning';
        default: return 'primary';
    }
});

const formIcon = computed(() => {
    switch (form.tipo) {
        case 'ganho': return Wallet;
        case 'gasto': return CreditCard;
        case 'gasto futuro': return Calendar;
        default: return Edit;
    }
});

function submit() {
	form.patch(`/movimentacoes/categorias/${props.categoria.id}`, {
		onSuccess: () => {
			form.reset('nome', 'tipo');
		},
	});
}
</script>

<template>

	<Head title="Editar Categoria" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="flex h-full flex-1 flex-col gap-4 p-4">
			<form @submit.prevent="submit">
                <FormLayout
                    title="Editar Categoria"
                    description="Altere os dados da sua categoria para melhor organização."
                    :variant="formVariant"
                    :icon="formIcon"
                >
                    <div class="grid grid-cols-1 gap-8 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="nome" class="text-sm font-semibold flex items-center gap-2">
                                <Tag class="h-4 w-4 text-muted-foreground" />
                                Nome da Categoria
                            </Label>
                            <Input id="nome" v-model="form.nome" name="nome" type="text" autocomplete="off" class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700" />
                        </div>
                        
                        <div class="space-y-2">
                            <Label for="tipo" class="text-sm font-semibold flex items-center gap-2">
                                <Edit class="h-4 w-4 text-muted-foreground" />
                                Tipo
                            </Label>
                            <Select v-model="form.tipo">
                                <SelectTrigger class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                                    <SelectValue placeholder="Selecione o tipo" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="t in tipos_movimentacao" :key="t.value" :value="t.value"> {{ t.name }} </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <template #footer>
                        <Button type="submit" :disabled="form.processing" class="h-12 px-8 bg-blue-600 hover:bg-blue-700 text-white shadow-lg shadow-blue-500/20">
                            <Save class="h-4 w-4 mr-2" />
                            Salvar Alterações
                        </Button>
                    </template>
                </FormLayout>
			</form>
		</div>
	</AppLayout>
</template>

