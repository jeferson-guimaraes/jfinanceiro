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
import { formatBRL, handleValorKeydown, unformatBRL, handleValorClick } from '@/utils/masks';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Info, Save, Wallet, Calendar, Tag, CreditCard, Edit, PlusCircle } from 'lucide-vue-next';
import { computed, ref, type PropType, watch } from 'vue';

const props = defineProps({
    movimentacao: Object as PropType<Movimentacao>,
    categoriasGanhos: Array as PropType<Categoria[]>,
    categoriasGastos: Array as PropType<Categoria[]>,
    categoriasGastosFuturos: Array as PropType<Categoria[]>,
    filters: Object as PropType<Record<string, any>>,
});

const isCategoriaModalOpen = ref(false);

function normalizeDate(value?: string): string {
    if (!value) {
        return '';
    }

    return value.slice(0, 10);
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Movimentações',
        href: movimentacoes.index({ query: props.filters }).url,
    },
    {
        title: 'Editar Movimentação',
        href: props.movimentacao ? movimentacoes.edit({ movimentacao: props.movimentacao.id }, { query: props.filters }).url : '#',
    },
];

const form = useForm({
    descricao: props.movimentacao?.descricao || '',
    valor: props.movimentacao?.valor || 0,
    tipo: props.movimentacao?.tipo || 'gasto',
    data_movimentacao: normalizeDate(props.movimentacao?.data),
    categoria_id: props.movimentacao?.categoria_id ? String(props.movimentacao.categoria_id) : '',
    parcelas: props.movimentacao?.lista_parcelas?.length || 1,
    data_vencimento: normalizeDate(props.movimentacao?.lista_parcelas?.[0]?.data_vencimento),
    parcelas_editadas: props.movimentacao?.lista_parcelas ? props.movimentacao.lista_parcelas.map((p: any) => ({
        id: p.id,
        valor: Number(p.valor),
        data_vencimento: normalizeDate(p.data_vencimento),
        numero: p.numero,
        pago: p.pago,
        is_temp: false
    })) : [] as Array<{ id: number | null, valor: number, data_vencimento: string, numero: number, pago: boolean, is_temp?: boolean }>,
});

watch(() => form.parcelas, (newQtd) => {
    if (form.tipo !== 'gasto futuro') return;
    const qtd = Number(newQtd) || 0;
    if (qtd < form.parcelas_editadas.length) {
        form.parcelas_editadas = form.parcelas_editadas.slice(0, qtd);
    } else if (qtd > form.parcelas_editadas.length) {
        const diff = qtd - form.parcelas_editadas.length;
        const lastNum = form.parcelas_editadas.length > 0 
            ? form.parcelas_editadas[form.parcelas_editadas.length - 1].numero 
            : 0;
        const lastDateStr = form.parcelas_editadas.length > 0
            ? form.parcelas_editadas[form.parcelas_editadas.length - 1].data_vencimento
            : form.data_vencimento;

        const lastDate = lastDateStr ? new Date(lastDateStr + 'T00:00:00') : new Date();
        const avgValor = form.parcelas_editadas.length > 0
            ? form.parcelas_editadas.reduce((acc, c) => acc + c.valor, 0) / form.parcelas_editadas.length
            : 0;

        for (let i = 1; i <= diff; i++) {
            if (lastNum === 0 && i === 1) {
                // Herda o vencimento inicial sem adicionar mês
            } else {
                lastDate.setMonth(lastDate.getMonth() + 1);
            }
            const yyyy = lastDate.getFullYear();
            const mm = String(lastDate.getMonth() + 1).padStart(2, '0');
            const dd = String(lastDate.getDate()).padStart(2, '0');
            const nextDateStr = `${yyyy}-${mm}-${dd}`;

            form.parcelas_editadas.push({
                id: null,
                valor: avgValor,
                data_vencimento: nextDateStr,
                numero: lastNum + i,
                pago: false,
                is_temp: true
            });
        }
    }
});

watch(() => form.data_vencimento, (newVal) => {
    if (form.tipo !== 'gasto futuro' || !newVal) return;
    
    const currentDate = new Date(newVal + 'T00:00:00');
    form.parcelas_editadas.forEach((parcela, index) => {
        if (index > 0) {
            currentDate.setMonth(currentDate.getMonth() + 1);
        }
        const yyyy = currentDate.getFullYear();
        const mm = String(currentDate.getMonth() + 1).padStart(2, '0');
        const dd = String(currentDate.getDate()).padStart(2, '0');
        parcela.data_vencimento = `${yyyy}-${mm}-${dd}`;
    });
});

watch(() => form.parcelas_editadas, (newVal) => {
    if (form.tipo === 'gasto futuro' && newVal) {
        const sum = newVal.reduce((acc, curr) => acc + (Number(curr.valor) || 0), 0);
        form.valor = Number(sum.toFixed(2));
        valor.value = form.valor;
    }
}, { deep: true });

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

watch(() => form.tipo, (newTipo, oldTipo) => {
    if (oldTipo === undefined || newTipo === oldTipo) {
        return;
    }

    const primeiraCategoria = categoriasDisponiveis.value?.[0];
    form.categoria_id = primeiraCategoria ? String(primeiraCategoria.id) : '';
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

function scrollToFormTop(): void {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function submit(): void {
    if (!props.movimentacao) {
        return;
    }

    const patchOptions = {
        onSuccess: () => {
            router.get(movimentacoes.index({ query: props.filters }).url);
        },
        onError: () => {
            scrollToFormTop();
        },
    };

    form
        .transform((data) => {
            const payload = {
                ...data,
                categoria_id: data.categoria_id ? Number(data.categoria_id) : null,
            };

            if (data.tipo === 'gasto futuro') {
                return {
                    ...payload,
                    parcelas_editadas: data.parcelas_editadas.filter(
                        (p) => p.id !== null && !p.is_temp,
                    ),
                };
            }

            const { parcelas, data_vencimento, parcelas_editadas, ...rest } = payload;

            return rest;
        })
        .patch(movimentacoes.update({ movimentacao: props.movimentacao.id }).url, patchOptions);
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
                    <div
                        v-if="form.hasErrors"
                        class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800/30 dark:bg-red-950/30 dark:text-red-400"
                    >
                        Verifique os campos destacados abaixo.
                    </div>

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
                                        :value="String(categoria.id)">
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
                            <Input id="valor" v-model="valorFormatado" name="valor" type="text"
                                @keydown="handleValorKeydown" @click="handleValorClick" @focus="handleValorClick" class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 font-mono text-lg" />
                            <InputError :message="form.errors.valor" />
                        </div>

                        <!-- Campos adicionais de Gasto Futuro -->
                        <template v-if="form.tipo === 'gasto futuro'">
                            <div class="sm:col-span-3 space-y-2">
                                <Label for="parcelas" class="text-sm font-semibold flex items-center gap-2">
                                    <CreditCard class="h-4 w-4 text-muted-foreground" />
                                    Quantidade de Parcelas
                                </Label>
                                <Input id="parcelas" v-model.number="form.parcelas" name="parcelas" type="number"
                                    min="1" class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700" />
                                <InputError :message="form.errors.parcelas" />
                            </div>

                            <div class="sm:col-span-3 space-y-2">
                                <Label for="data_vencimento" class="text-sm font-semibold flex items-center gap-2">
                                    <Calendar class="h-4 w-4 text-muted-foreground" />
                                    Vencimento (1ª Parcela)
                                </Label>
                                <Input id="data_vencimento" v-model="form.data_vencimento" name="data_vencimento" type="date"
                                    class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700" />
                                <InputError :message="form.errors.data_vencimento" />
                            </div>
                        </template>
                    </div>

                    <!-- Detalhamento de Parcelas se for Gasto Futuro -->
                    <div v-if="form.tipo === 'gasto futuro'" class="mt-8 border-t border-gray-100 dark:border-gray-800 pt-8 space-y-4">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                <CreditCard class="h-5 w-5 text-gray-500" />
                                Detalhamento de Parcelas
                            </h3>
                            <p class="text-sm text-gray-500">
                                Edite os valores e datas de vencimento de cada parcela individualmente.
                            </p>
                        </div>

                        <div class="border rounded-xl overflow-hidden bg-white dark:bg-gray-900 shadow-sm border-gray-200 dark:border-gray-800">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-800 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            <th class="px-6 py-4 w-24">Nº Parcela</th>
                                            <th class="px-6 py-4">Valor</th>
                                            <th class="px-6 py-4">Vencimento</th>
                                            <th class="px-6 py-4 w-32">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                                        <tr v-for="(parcela, index) in form.parcelas_editadas" :key="index" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                                            <td class="px-6 py-4 font-mono font-medium text-gray-600 dark:text-gray-400">
                                                {{ parcela.numero }} / {{ form.parcelas }}
                                            </td>
                                            <td class="px-6 py-4">
                                                <Input 
                                                    type="text"
                                                    :model-value="formatBRL(parcela.valor)"
                                                    @update:model-value="(val: string | number) => parcela.valor = unformatBRL(String(val))"
                                                    @keydown="handleValorKeydown"
                                                    @click="handleValorClick"
                                                    @focus="handleValorClick"
                                                    class="h-10 w-44 bg-gray-50 dark:bg-gray-800 font-mono text-base border-gray-200 dark:border-gray-700"
                                                    :disabled="parcela.pago"
                                                />
                                            </td>
                                            <td class="px-6 py-4">
                                                <Input 
                                                    type="date"
                                                    v-model="parcela.data_vencimento"
                                                    class="h-10 w-48 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                                                    :disabled="parcela.pago"
                                                />
                                            </td>
                                            <td class="px-6 py-4">
                                                <span v-if="parcela.pago" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 dark:bg-green-950/30 dark:text-green-400 border border-green-200/50 dark:border-green-800/20">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                                    Paga
                                                </span>
                                                <span v-else class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400 border border-amber-200/50 dark:border-amber-800/20">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                    Pendente
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
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