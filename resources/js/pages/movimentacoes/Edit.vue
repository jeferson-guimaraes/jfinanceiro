<script setup lang="ts">
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
        href: movimentacoes.edit({ movimentacao: props.movimentacao?.id }).url,
    },
];

const form = useForm({
    descricao: props.movimentacao?.descricao || '',
    valor: props.movimentacao?.valor || 0,
    tipo: props.movimentacao?.tipo || 'gasto',
    data_movimentacao: props.movimentacao?.data || '',
    categoria_id: props.movimentacao?.categoria_id || (null as number | null),
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

const valor = ref((props.movimentacao?.valor || 0) * 100);

const valorFormatado = computed({
    get() {
        return formatBRL(valor.value);
    },
    set(value: string) {
        const digits = Number(value.replace(/[^\d]/g, ''));
        valor.value = digits;
        form.valor = digits / 100;
    },
});

function submit() {
    form.put(movimentacoes.edit({ movimentacao: props.movimentacao?.id }).url, {
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
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <form @submit.prevent="submit" class="md:max-w-5xl">
                <div class="space-y-12">
                    <div
                        class="border-b border-gray-900/10 pb-12 dark:border-gray-700"
                    >
                        <h2
                            class="text-base leading-7 font-semibold text-gray-900 dark:text-gray-100"
                        >
                            Editar Movimentação
                        </h2>
                        <p
                            class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-400"
                        >
                            Edite uma movimentação financeira.
                        </p>

                        <div
                            class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-6"
                        >
                            <div class="sm:col-span-3">
                                <Label for="tipo">Tipo</Label>
                                <Select v-model="form.tipo">
                                    <SelectTrigger>
                                        <SelectValue
                                            placeholder="Selecione o tipo"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="ganho">
                                            Ganho
                                        </SelectItem>
                                        <SelectItem value="gasto">
                                            Gasto
                                        </SelectItem>
                                        <SelectItem value="gasto futuro">
                                            Gasto Futuro
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.tipo"
                                />
                            </div>

                            <div class="sm:col-span-3">
                                <Label for="data">Data</Label>
                                <Input
                                    id="data"
                                    v-model="form.data_movimentacao"
                                    name="data"
                                    type="date"
                                />
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.data_movimentacao"
                                />
                            </div>

                            <div class="sm:col-span-full">
                                <Label for="descricao">Descrição</Label>
                                <Input
                                    id="descricao"
                                    v-model="form.descricao"
                                    name="descricao"
                                    type="text"
                                    autocomplete="off"
                                />
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.descricao"
                                />
                            </div>

                            <div class="sm:col-span-3">
                                <Label for="categoria_id">Categoria</Label>
                                <Select v-model="form.categoria_id">
                                    <SelectTrigger
                                        class="focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <SelectValue
                                            placeholder="Selecione a categoria"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="categoria in categoriasDisponiveis"
                                            :key="categoria.id"
                                            :value="categoria.id"
                                        >
                                            {{ categoria.nome }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.categoria_id"
                                />
                                <div class="mt-1">
                                    <button
                                        type="button"
                                        @click="isCategoriaModalOpen = true"
                                        class="text-sm text-blue-400 underline hover:cursor-pointer hover:text-indigo-500"
                                    >
                                        Cadastrar nova categoria
                                    </button>
                                </div>
                            </div>

                            <div class="sm:col-span-3">
                                <Label for="valor">Valor</Label>
                                <Input
                                    id="valor"
                                    v-model="valorFormatado"
                                    name="valor"
                                    type="tel"
                                    @keydown="handleValorKeydown"
                                />
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.valor"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-x-6">
                    <Button
                        type="submit"
                        class="btn-primary w-30"
                        :disabled="form.processing"
                    >
                        Salvar
                    </Button>
                </div>
            </form>
        </div>
        <CategoriaModal
            :open="isCategoriaModalOpen"
            @close="isCategoriaModalOpen = false"
            @category-created="refreshCategories"
        />
    </AppLayout>
</template>
