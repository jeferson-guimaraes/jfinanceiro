<script setup lang="ts">
import FormLayout from '@/components/FormLayout.vue';
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
import { Info, PlusCircle, Wallet, Calendar, Tag, CreditCard } from 'lucide-vue-next';
import { computed, ref, type PropType, watch } from 'vue';

const props = defineProps({
    categoriasGanhos: Array as PropType<Categoria[]>,
    categoriasGastos: Array as PropType<Categoria[]>,
    categoriasGastosFuturos: Array as PropType<Categoria[]>,
    tipo: String,
    filters: Object as PropType<Record<string, any>>,
});

const isCategoriaModalOpen = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Movimentações',
        href: movimentacoes.index({ query: props.filters }).url,
    },
    {
        title: 'Nova Movimentação',
        href: movimentacoes.create({ query: props.filters }).url,
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
        default: return PlusCircle;
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
        valor.value = digits / 100;
        form.valor = valor.value;
    }
})

const valorParcelas = ref(0);

const valorParcelasFormatado = computed({
    get() {
        return formatBRL(valorParcelas.value);
    },
    set(value: string) {
        const digits = Number(value.replace(/[^\d]/g, ''));
        valorParcelas.value = digits / 100;
        form.valor_parcelas = valorParcelas.value.toFixed(2);
    }
})

watch([valor, () => form.parcelas], () => {
    if (form.parcelas > 0 && valor.value > 0) {
        const calculatedValue = valor.value / form.parcelas;
        valorParcelas.value = Number(calculatedValue.toFixed(2));
        form.valor_parcelas = valorParcelas.value.toFixed(2);
    }
});


function submit() {
    if (form.tipo === 'gasto futuro' && form.data_vencimento && form.data_movimentacao) {
        if (form.data_vencimento < form.data_movimentacao) {
            form.setError('data_vencimento', 'A data de vencimento da primeira parcela não pode ser anterior à data da compra.');
            return;
        }
    }

    form.post(movimentacoes.store({ query: props.filters }).url, {
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
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <form @submit.prevent="submit">
                <FormLayout 
                    title="Nova Movimentação" 
                    description="Crie uma nova movimentação financeira para manter seu controle em dia."
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

                        <template v-if="form.tipo === 'gasto futuro'">
                            <div class="sm:col-span-2 space-y-2">
                                <Label for="parcelas" class="text-sm font-semibold">Parcelas</Label>
                                <Input id="parcelas" v-model="form.parcelas" name="parcelas" type="number"
                                    min="1" class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700" />
                                <InputError :message="form.errors.parcelas" />
                            </div>

                            <div class="sm:col-span-2 space-y-2">
                                <Label for="valor_parcelas" class="text-sm font-semibold">Valor das Parcelas</Label>
                                <Input id="valor_parcelas" v-model="valorParcelasFormatado" name="valor_parcelas" type="tel" @keydown="handleValorKeydown" class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 font-mono" />
                                <InputError :message="form.errors.valor_parcelas" />
                            </div>

                            <div class="sm:col-span-2 space-y-2">
                                <Label for="data_vencimento" class="text-sm font-semibold flex items-center gap-1.5">
                                    Vencimento
                                    <TooltipProvider>
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Info class="h-3.5 w-3.5 text-gray-400 cursor-help" />
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>Data de vencimento da primeira parcela</p>
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                </Label>
                                <Input id="data_vencimento" v-model="form.data_vencimento" name="data_vencimento" type="date" class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700" />
                                <InputError :message="form.errors.data_vencimento" />
                            </div>
                        </template>
                    </div>

                    <template #footer>
                        <Button type="submit" class="h-12 px-8 bg-blue-600 hover:bg-blue-700 text-white shadow-lg shadow-blue-500/20" :disabled="form.processing">
                            Salvar Movimentação
                        </Button>
                    </template>
                </FormLayout>
            </form>
        </div>
        <CategoriaModal :open="isCategoriaModalOpen" @close="isCategoriaModalOpen = false"
            @category-created="refreshCategories" />
    </AppLayout>
</template>
