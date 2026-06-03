<script setup lang="ts">
import FormLayout from '@/components/FormLayout.vue';
import InputError from '@/components/InputError.vue';
import CategoriaModal from '@/components/modals/CategoriaModal.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import movimentacoes from '@/routes/movimentacoes';
import {
    type BreadcrumbItem,
    type Categoria,
    type Movimentacao,
} from '@/types';
import { formatBRL, handleValorKeydown } from '@/utils/masks';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Info, Save, Wallet, Calendar, Tag, CreditCard, Edit, PlusCircle } from 'lucide-vue-next';
import { computed, ref, type PropType } from 'vue';

const props = defineProps({
    movimentacao: Object as PropType<Movimentacao>,
    categoriasGanhos: Array as PropType<Categoria[]>,
    categoriasGastos: Array as PropType<Categoria[]>,
    categoriasGastosFuturos: Array as PropType<Categoria[]>,
});

const isCategoriaModalOpen = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Movimentações',
        href: movimentacoes.index().url,
    },
    {
        title: 'Editar Movimentação',
        href: props.movimentacao ? movimentacoes.edit({ movimentacao: props.movimentacao.id }).url : '#',
    },
];

const form = useForm({
    descricao: props.movimentacao?.descricao || '',
    valor: props.movimentacao?.valor || 0,
    tipo: props.movimentacao?.tipo || 'gasto',
    data_movimentacao: props.movimentacao?.data || '',
    categoria_id: props.movimentacao?.categoria_id || (null as number | null),
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

const categoriasDisponiveis = computed(() => {
    switch (form.tipo) {
        case 'ganho':
            return props.categoriasGanhos;
        case 'gasto':
            return props.categoriasGastos;
        case 'gasto futuro':
            return props.categoriasGastosFuturos;
        default:
            return [];
    }
});

const valor = ref(props.movimentacao?.valor || 0);

const valorFormatado = computed({
    get() {
        return formatBRL(valor.value);
    },
    set(value: string) {
        const digits = Number(value.replace(/[^\d]/g, ''));
        valor.value = digits / 100;
        form.valor = valor.value;
    },
});

function submit() {
    if (!props.movimentacao) return;

    form.patch(movimentacoes.update({ movimentacao: props.movimentacao.id }).url, {
        onSuccess: () => {
            router.get(movimentacoes.index().url);
        },
    });
}

function refreshCategories() {
    router.get(
        window.location.href,
        {},
        {
            preserveState: true,
            preserveScroll: true,
            only: [
                'categoriasGanhos',
                'categoriasGastos',
                'categoriasGastosFuturos',
            ],
        },
    );
}
</script>

<template>
    <Head title="Editar Movimentação" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <form @submit.prevent="submit">
                <FormLayout 
                    title="Editar Movimentação" 
                    description="Atualize as informações da sua movimentação financeira."
                    :variant="formVariant"
                    :icon="formIcon"
                >
                    <div class="grid grid-cols-1 gap-8 sm:grid-cols-6">
                        <div class="sm:col-span-3 space-y-2">
                            <Label for="tipo" class="text-sm font-semibold flex items-center gap-2">
                                <Info class="h-4 w-4 text-muted-foreground" />
                                Tipo de Movimentação
                            </Label>
                            <Select v-model="form.tipo">
                                <SelectTrigger class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                                    <SelectValue placeholder="Selecione o tipo" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="ganho"> Ganho </SelectItem>
                                    <SelectItem value="gasto"> Despesa </SelectItem>
                                    <SelectItem value="gasto futuro"> Despesa Futura </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.tipo" />
                        </div>

                        <div class="sm:col-span-3 space-y-2">
                            <Label for="data" class="text-sm font-semibold flex items-center gap-2">
                                <Calendar class="h-4 w-4 text-muted-foreground" />
                                Data da Movimentação
                            </Label>
                            <Input id="data" v-model="form.data_movimentacao" name="data" type="date" class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700" />
                            <InputError :message="form.errors.data_movimentacao" />
                        </div>

                        <div class="sm:col-span-full space-y-2">
                            <Label for="descricao" class="text-sm font-semibold">Descrição</Label>
                            <Input id="descricao" v-model="form.descricao" name="descricao" type="text"
                                placeholder="Ex: Salário, Aluguel, Supermercado..."
                                autocomplete="off" class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700" />
                            <InputError :message="form.errors.descricao" />
                        </div>

                        <div class="sm:col-span-3 space-y-2">
                            <Label for="categoria_id" class="text-sm font-semibold flex items-center gap-2">
                                <Tag class="h-4 w-4 text-muted-foreground" />
                                Categoria
                            </Label>
                            <Select v-model="form.categoria_id">
                                <SelectTrigger class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                                    <SelectValue placeholder="Selecione a categoria" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="categoria in categoriasDisponiveis" :key="categoria.id"
                                        :value="categoria.id">
                                        {{ categoria.nome }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.categoria_id" />
                            <div class="mt-1">
                                <button type="button" @click="isCategoriaModalOpen = true"
                                    class="text-xs text-blue-500 hover:text-blue-600 font-medium flex items-center gap-1">
                                    <PlusCircle class="h-3 w-3" />
                                    Cadastrar nova categoria
                                </button>
                            </div>
                        </div>

                        <div class="sm:col-span-3 space-y-2">
                            <Label for="valor" class="text-sm font-semibold flex items-center gap-2">
                                <Wallet class="h-4 w-4 text-muted-foreground" />
                                Valor Total
                            </Label>
                            <Input id="valor" v-model="valorFormatado" name="valor" type="tel"
                                @keydown="handleValorKeydown" class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 font-mono text-lg" />
                            <InputError :message="form.errors.valor" />
                        </div>
                    </div>

                    <template #footer>
                        <Button type="submit" class="h-12 px-8 bg-blue-600 hover:bg-blue-700 text-white shadow-lg shadow-blue-500/20" :disabled="form.processing">
                            <Save class="h-4 w-4 mr-2" />
                            Salvar Alterações
                        </Button>
                    </template>
                </FormLayout>
            </form>
        </div>
        <CategoriaModal
            :open="isCategoriaModalOpen"
            @close="isCategoriaModalOpen = false"
            @category-created="refreshCategories"
        />
    </AppLayout>
</template>
