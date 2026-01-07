<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import movimentacoes from '@/routes/movimentacoes';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Movimentações',
        href: '#',
    },
    {
        title: 'Nova Movimentação',
        href: movimentacoes.create().url,
    },
];

const form = useForm({
    descricao: '',
    valor: 0,
    tipo: 'gasto',
    data: new Date().toISOString().split('T')[0],
    categoria_id: null,
    parcelas: 1,
    data_vencimento: null,
    valor_parcelas: null,
});

function submit() {
    form.post(movimentacoes.store().url);
}
</script>

<template>

    <Head title="Nova Movimentação" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <form @submit.prevent="submit">
                <div class="space-y-12">
                    <div class="border-b border-gray-900/10 pb-12">
                        <h2 class="text-base font-semibold leading-7 text-gray-900">Nova Movimentação</h2>
                        <p class="mt-1 text-sm leading-6 text-gray-600">Crie uma nova movimentação financeira.</p>

                        <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                            <div class="sm:col-span-4">
                                <label for="tipo" class="block text-sm font-medium leading-6 text-gray-900">Tipo</label>
                                <div class="mt-2">
                                    <Select v-model="form.tipo">
                                        <SelectTrigger class="focus:ring-indigo-500 focus:border-indigo-500">
                                            <SelectValue placeholder="Selecione o tipo" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="ganho"> Ganho </SelectItem>
                                            <SelectItem value="gasto"> Gasto </SelectItem>
                                            <SelectItem value="gasto futuro"> Gasto Futuro </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div class="sm:col-span-4">
                                <label for="data" class="block text-sm font-medium leading-6 text-gray-900">Data</label>
                                <div class="mt-2">
                                    <Input
                                        id="data"
                                        v-model="form.data"
                                        name="data"
                                        type="date"
                                    />
                                </div>
                            </div>

                            <div class="sm:col-span-4">
                                <label for="descricao"
                                    class="block text-sm font-medium leading-6 text-gray-900">Descrição</label>
                                <div class="mt-2">
                                    <Input id="descricao" v-model="form.descricao" name="descricao" type="text"
                                        autocomplete="off" />
                                </div>
                            </div>

                            <div class="sm:col-span-4">
                                <label for="categoria_id" class="block text-sm font-medium leading-6 text-gray-900">Categoria</label>
                                <div class="mt-2">
                                    <Select v-model="form.categoria_id">
                                        <SelectTrigger class="focus:ring-indigo-500 focus:border-indigo-500">
                                            <SelectValue placeholder="Selecione a categoria" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="1"> Categoria 1 </SelectItem>
                                            <SelectItem value="2"> Categoria 2 </SelectItem>
                                            <SelectItem value="3"> Categoria 3 </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="valor"
                                    class="block text-sm font-medium leading-6 text-gray-900">Valor</label>
                                <div class="mt-2">
                                    <Input id="valor" v-model="form.valor" name="valor" type="number" step="0.01" />
                                </div>
                            </div>

                            <template v-if="form.tipo === 'gasto futuro'">
                                <div class="sm:col-span-2">
                                    <label for="parcelas" class="block text-sm font-medium leading-6 text-gray-900">Parcelas</label>
                                    <div class="mt-2">
                                        <Input
                                            id="parcelas"
                                            v-model="form.parcelas"
                                            name="parcelas"
                                            type="number"
                                            min="1"
                                        />
                                    </div>
                                </div>

                                <div class="sm:col-span-2">
                                    <label for="data_vencimento" class="block text-sm font-medium leading-6 text-gray-900">Data de Vencimento</label>
                                    <div class="mt-2">
                                        <Input
                                            id="data_vencimento"
                                            v-model="form.data_vencimento"
                                            name="data_vencimento"
                                            type="date"
                                        />
                                    </div>
                                </div>

                                <div class="sm:col-span-2">
                                    <label for="valor_parcelas" class="block text-sm font-medium leading-6 text-gray-900">Valor das Parcelas</label>
                                    <div class="mt-2">
                                        <Input
                                            id="valor_parcelas"
                                            v-model="form.valor_parcelas"
                                            name="valor_parcelas"
                                            type="number"
                                            step="0.01"
                                        />
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-x-6">
                    <Button type="submit"
                        class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                        :disabled="form.processing">
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
