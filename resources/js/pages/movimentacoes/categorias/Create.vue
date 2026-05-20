<script setup lang="ts">
import FormLayout from '@/components/FormLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Tag, Wallet, CreditCard, Calendar, PlusCircle } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
	tipos_movimentacao: { name: string; value: string }[];
	tipo?: string;
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
	tipo: props.tipo && ['ganho', 'gasto', 'gasto futuro'].includes(props.tipo) ? props.tipo : 'ganho',
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
        default: return Tag;
    }
});

function submit() {
	form.post('/movimentacoes/categorias', {
		onSuccess: () => {
			form.reset('nome');
		},
	});
}
</script>

<template>

	<Head title="Nova Categoria" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="flex h-full flex-1 flex-col gap-4 p-4">
			<form @submit.prevent="submit">
                <FormLayout
                    title="Nova Categoria"
                    description="Organize suas finanças criando categorias personalizadas para seus ganhos e despesas."
                    :variant="formVariant"
                    :icon="formIcon"
                >
                    <div class="grid grid-cols-1 gap-8 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="nome" class="text-sm font-semibold flex items-center gap-2">
                                <Tag class="h-4 w-4 text-muted-foreground" />
                                Nome da Categoria
                            </Label>
                            <Input id="nome" v-model="form.nome" name="nome" type="text" autocomplete="off" placeholder="Ex: Alimentação, Transporte, Salário..." class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700" />
                        </div>
                        
                        <div class="space-y-2">
                            <Label for="tipo" class="text-sm font-semibold flex items-center gap-2">
                                <PlusCircle class="h-4 w-4 text-muted-foreground" />
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
                            Salvar Categoria
                        </Button>
                    </template>
                </FormLayout>
			</form>
		</div>
	</AppLayout>
</template>