<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import CategoriaModal from '@/components/modals/CategoriaModal.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import Tooltip from '@/components/ui/tooltip/Tooltip.vue';
import TooltipContent from '@/components/ui/tooltip/TooltipContent.vue';
import TooltipProvider from '@/components/ui/tooltip/TooltipProvider.vue';
import TooltipTrigger from '@/components/ui/tooltip/TooltipTrigger.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import movimentacoes from '@/routes/movimentacoes';
import { type BreadcrumbItem, type Categoria } from '@/types';
import { formatBRL, handleValorKeydown } from '@/utils/masks';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Info } from 'lucide-vue-next';
import { computed, ref, type PropType, watch } from 'vue';

const props = defineProps({
    categoriasGanhos: Array as PropType<Categoria[]>,
    categoriasGastos: Array as PropType<Categoria[]>,
    categoriasGastosFuturos: Array as PropType<Categoria[]>,
    tipo: String,
});

const isCategoriaModalOpen = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Movimentações',
        href: '/movimentacoes/index',
    },
    {
        title: 'Nova Movimentação',
        href: movimentacoes.create().url,
    },
];

const form = useForm({
    descricao: '',
    valor: 0,
    tipo: props.tipo && ['ganho', 'gasto', 'gasto futuro'].includes(props.tipo) ? props.tipo : 'ganho',
    data_movimentacao: '',
    categoria_id: null as number | null,
    parcelas: 1,
    data_vencimento: '',
    valor_parcelas: '',
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

watch(() => form.tipo, (newTipo) => {
    switch (newTipo) {
        case 'ganho':
            form.categoria_id = 2;
            break;
        case 'gasto':
            form.categoria_id = 1;
            break;
        case 'gasto futuro':
            form.categoria_id = 3;
            break;
    }
}, { immediate: true });

const valor = ref(0);

const valorFormatado = computed({
    get() {
        return formatBRL(valor.value);
    },
    set(value: string) {
        const digits = Number(value.replace(/[^\d]/g, ''));
        valor.value = digits;
        form.valor = digits / 100;
    }
})

const valorParcelas = ref(0);

const valorParcelasFormatado = computed({
    get() {
        return formatBRL(valorParcelas.value);
    },
    set(value: string) {
        const digits = Number(value.replace(/[^\d]/g, ''));
        valorParcelas.value = digits;
        form.valor_parcelas = (digits / 100).toFixed(2);
    }
})

watch([valor, () => form.parcelas], () => {
    if (form.parcelas > 0 && valor.value > 0) {
        const calculatedValue = valor.value / form.parcelas;
        const roundedValue = Math.round(calculatedValue);
        valorParcelas.value = roundedValue;
        form.valor_parcelas = (roundedValue / 100).toFixed(2);
    }
});


function submit() {
    if (form.tipo === 'gasto futuro' && form.data_vencimento && form.data_movimentacao) {
        if (form.data_vencimento < form.data_movimentacao) {
            form.setError('data_vencimento', 'A data de vencimento da primeira parcela não pode ser anterior à data da compra.');
            return;
        }
    }

    form.post(movimentacoes.store().url, {
        onSuccess: () => {
            form.reset('descricao', 'valor', 'data_movimentacao', 'parcelas', 'valor_parcelas', 'data_vencimento');
            valor.value = 0;
            valorParcelas.value = 0;
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
            only: ['categoriasGanhos', 'categoriasGastos', 'categoriasGastosFuturos'],
        },
    );
}
</script>

<template>

    <Head title="Nova Movimentação" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <form @submit.prevent="submit" class="md:max-w-5xl">
                <div class="space-y-12">
                    <div class="border-b border-gray-900/10 dark:border-gray-700 pb-12">
                        <h2 class="text-base font-semibold leading-7 text-gray-900 dark:text-gray-100">
                            Nova Movimentação
                        </h2>
                        <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-400">
                            Crie uma nova movimentação financeira.
                        </p>

                        <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-6">
                            <div class="sm:col-span-3">
                                <Label for="tipo">Tipo</Label>
                                <Select v-model="form.tipo">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecione o tipo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="ganho"> Ganho </SelectItem>
                                        <SelectItem value="gasto"> Despesa </SelectItem>
                                        <SelectItem value="gasto futuro"> Despesa Futura </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError class="mt-2" :message="form.errors.tipo" />
                            </div>

                            <div class="sm:col-span-3">
                                <Label for="data">Data</Label>
                                <Input id="data" v-model="form.data_movimentacao" name="data" type="date" />
                                <InputError class="mt-2" :message="form.errors.data_movimentacao" />
                            </div>

                            <div class="sm:col-span-full">
                                <Label for="descricao">Descrição</Label>
                                <Input id="descricao" v-model="form.descricao" name="descricao" type="text"
                                    autocomplete="off" />
                                <InputError class="mt-2" :message="form.errors.descricao" />
                            </div>

                            <div class="sm:col-span-3">
                                <Label for="categoria_id">Categoria</Label>
                                <Select v-model="form.categoria_id">
                                    <SelectTrigger class="focus:ring-indigo-500 focus:border-indigo-500">
                                        <SelectValue placeholder="Selecione a categoria" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="categoria in categoriasDisponiveis" :key="categoria.id"
                                            :value="categoria.id">
                                            {{ categoria.nome }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError class="mt-2" :message="form.errors.categoria_id" />
                                <div class="mt-1">
                                    <button type="button" @click="isCategoriaModalOpen = true"
                                        class="text-sm text-blue-400 underline hover:text-indigo-500 hover:cursor-pointer">
                                        Cadastrar nova categoria
                                    </button>
                                </div>
                            </div>

                            <div class="sm:col-span-3">
                                <Label for="valor">Valor da Compra</Label>
                                <Input id="valor" v-model="valorFormatado" name="valor" type="tel"
                                    @keydown="handleValorKeydown" />
                                <InputError class="mt-2" :message="form.errors.valor" />
                            </div>

                            <template v-if="form.tipo === 'gasto futuro'">
                                <div class="sm:col-span-2">
                                    <Label for="parcelas">Parcelas</Label>
                                    <Input id="parcelas" v-model="form.parcelas" name="parcelas" type="number"
                                        min="1" />
                                    <InputError class="mt-2" :message="form.errors.parcelas" />
                                </div>

                                <div class="sm:col-span-2">
                                    <Label for="valor_parcelas">Valor das Parcelas</Label>
                                    <Input id="valor_parcelas" v-model="valorParcelasFormatado" name="valor_parcelas" type="tel" @keydown="handleValorKeydown" />
                                    <InputError class="mt-2" :message="form.errors.valor_parcelas" />
                                </div>

                                <div class="sm:col-span-2">
                                    <div class="flex items-center gap-1.5">
                                        <Label for="quantidade" class="text-sm font-medium">Data de Vencimento</Label>
                                        <TooltipProvider>
                                            <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Info class="h-3.5 w-3.5 text-gray-400 cursor-help mb-2" />
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>Data de vencimento da primeira parcela</p>
                                            </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                        </div>
                                    <Input id="data_vencimento" v-model="form.data_vencimento" name="data_vencimento" type="date" />
                                    <InputError class="mt-2" :message="form.errors.data_vencimento" />
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-x-6">
                    <Button type="submit" class="w-30 btn-primary" :disabled="form.processing">
                        Salvar
                    </button>
                </div>
            </form>
        </div>
        <CategoriaModal :open="isCategoriaModalOpen" @close="isCategoriaModalOpen = false"
            @category-created="refreshCategories" />
    </AppLayout>
</template>
