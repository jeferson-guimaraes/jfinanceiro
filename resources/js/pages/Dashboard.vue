<script setup lang="ts">
import GastosPorCategoria, { type CategoriaResumo } from '@/components/dashboard/GastosPorCategoria.vue';
import StatCards, { type DashboardStat } from '@/components/dashboard/StatCards.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { formataDinheiroBRL } from '@/utils/formataDinheiro';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface Props {
    periodo: {
        data_inicio: string;
        data_fim: string;
    };
    stats: DashboardStat[];
    recentTransactions: Array<{
        id: number;
        description: string;
        category: string;
        value: number;
        type: 'GANHO' | 'GASTO';
        date: string;
        status: string;
    }>;
    categoriesSummary: CategoriaResumo[];
    geral: {
        desde: string;
        stats: DashboardStat[];
        categoriesSummary: CategoriaResumo[];
    };
}

type PresetPeriodo = 'mes_atual' | 'mes_anterior' | 'ultimos_3_meses' | 'ano_atual' | 'personalizado';

const props = defineProps<Props>();

const page = usePage();
const errors = computed(() => page.props.errors as Record<string, string>);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const paraIsoLocal = (data: Date): string => {
    const ano = data.getFullYear();
    const mes = String(data.getMonth() + 1).padStart(2, '0');
    const dia = String(data.getDate()).padStart(2, '0');

    return `${ano}-${mes}-${dia}`;
};

const paraDataBR = (iso: string): string => iso.split('-').reverse().join('/');

const intervaloDoPreset = (preset: Exclude<PresetPeriodo, 'personalizado'>): { data_inicio: string; data_fim: string } => {
    const hoje = new Date();
    const ano = hoje.getFullYear();
    const mes = hoje.getMonth();

    const intervalos = {
        mes_atual: [new Date(ano, mes, 1), new Date(ano, mes + 1, 0)],
        mes_anterior: [new Date(ano, mes - 1, 1), new Date(ano, mes, 0)],
        ultimos_3_meses: [new Date(ano, mes - 2, 1), new Date(ano, mes + 1, 0)],
        ano_atual: [new Date(ano, 0, 1), new Date(ano, 11, 31)],
    } as const;

    const [inicio, fim] = intervalos[preset];

    return { data_inicio: paraIsoLocal(inicio), data_fim: paraIsoLocal(fim) };
};

const presets: Array<{ value: PresetPeriodo; label: string }> = [
    { value: 'mes_atual', label: 'Mês atual' },
    { value: 'mes_anterior', label: 'Mês anterior' },
    { value: 'ultimos_3_meses', label: 'Últimos 3 meses' },
    { value: 'ano_atual', label: 'Ano atual' },
    { value: 'personalizado', label: 'Personalizado' },
];

const presetDoPeriodo = (periodo: Props['periodo']): PresetPeriodo => {
    const encontrado = presets.find(({ value }) => {
        if (value === 'personalizado') {
            return false;
        }
        const intervalo = intervaloDoPreset(value);

        return intervalo.data_inicio === periodo.data_inicio && intervalo.data_fim === periodo.data_fim;
    });

    return encontrado?.value ?? 'personalizado';
};

const presetSelecionado = ref<PresetPeriodo>(presetDoPeriodo(props.periodo));
const dataInicio = ref(props.periodo.data_inicio);
const dataFim = ref(props.periodo.data_fim);
const carregando = ref(false);

watch(
    () => props.periodo,
    (periodo) => {
        dataInicio.value = periodo.data_inicio;
        dataFim.value = periodo.data_fim;
    },
);

const periodoInvalido = computed(() => !dataInicio.value || !dataFim.value || dataInicio.value > dataFim.value);

const descricaoPeriodo = computed(() => `${paraDataBR(props.periodo.data_inicio)} a ${paraDataBR(props.periodo.data_fim)}`);

const carregarPeriodo = (dados: Record<string, string>): void => {
    router.get(dashboard().url, dados, {
        only: ['periodo', 'stats', 'recentTransactions', 'categoriesSummary', 'errors'],
        preserveState: true,
        preserveScroll: true,
        onStart: () => (carregando.value = true),
        onFinish: () => (carregando.value = false),
    });
};

const aplicarPreset = (preset: PresetPeriodo): void => {
    presetSelecionado.value = preset;

    if (preset === 'personalizado') {
        return;
    }

    carregarPeriodo(preset === 'mes_atual' ? {} : intervaloDoPreset(preset));
};

const aplicarPeriodoPersonalizado = (): void => {
    if (periodoInvalido.value) {
        return;
    }

    carregarPeriodo({ data_inicio: dataInicio.value, data_fim: dataFim.value });
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-8">
            <!-- Header section -->
            <div class="flex flex-col gap-2">
                <h2 class="text-3xl font-bold tracking-tight">Olá, Bem-vindo de volta! 👋</h2>
                <p class="text-muted-foreground">Aqui está o resumo da sua vida financeira.</p>
            </div>

            <!-- Resumo do período -->
            <section class="flex flex-col gap-6" :class="{ 'opacity-60 transition-opacity': carregando }">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div class="flex flex-col gap-1">
                        <h3 class="text-xl font-semibold tracking-tight">Resumo do Período</h3>
                        <p class="text-sm text-muted-foreground">{{ descricaoPeriodo }}</p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                        <div class="flex flex-col gap-1.5">
                            <Label for="periodo">Período</Label>
                            <Select :model-value="presetSelecionado" @update:model-value="(valor) => aplicarPreset(valor as PresetPeriodo)">
                                <SelectTrigger id="periodo" class="w-full sm:w-44">
                                    <SelectValue placeholder="Selecione o período" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="preset in presets" :key="preset.value" :value="preset.value">
                                        {{ preset.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <template v-if="presetSelecionado === 'personalizado'">
                            <div class="flex flex-col gap-1.5">
                                <Label for="data_inicio">De</Label>
                                <Input id="data_inicio" v-model="dataInicio" type="date" class="sm:w-40" />
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <Label for="data_fim">Até</Label>
                                <Input id="data_fim" v-model="dataFim" type="date" class="sm:w-40" :class="{ 'border-red-500': dataInicio && dataFim && dataInicio > dataFim }" />
                            </div>
                            <Button :disabled="periodoInvalido || carregando" @click="aplicarPeriodoPersonalizado">Aplicar</Button>
                        </template>
                    </div>
                </div>

                <div v-if="errors.data_inicio || errors.data_fim" class="flex flex-col gap-1">
                    <InputError :message="errors.data_inicio" />
                    <InputError :message="errors.data_fim" />
                </div>

                <StatCards :stats="stats" />

                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-7">
                    <!-- Recent Transactions (Desktop Table / Mobile Cards) -->
                    <Card class="lg:col-span-4">
                        <CardHeader>
                            <CardTitle>Transações Recentes</CardTitle>
                            <p class="text-sm text-muted-foreground">Suas últimas movimentações no período.</p>
                        </CardHeader>
                        <CardContent>
                            <p v-if="!recentTransactions.length" class="text-sm text-muted-foreground">Nenhuma movimentação no período.</p>

                            <template v-else>
                                <!-- Desktop Table -->
                                <div class="hidden md:block">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Descrição</TableHead>
                                                <TableHead>Categoria</TableHead>
                                                <TableHead>Data</TableHead>
                                                <TableHead class="text-right">Valor</TableHead>
                                                <TableHead class="text-right">Status</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-for="transaction in recentTransactions" :key="transaction.id">
                                                <TableCell class="font-medium">{{ transaction.description }}</TableCell>
                                                <TableCell>{{ transaction.category }}</TableCell>
                                                <TableCell>{{ transaction.date }}</TableCell>
                                                <TableCell class="text-right" :class="transaction.type === 'GANHO' ? 'text-emerald-600' : 'text-rose-600'">
                                                    {{ transaction.type === 'GANHO' ? '+' : '-' }} {{ formataDinheiroBRL(transaction.value) }}
                                                </TableCell>
                                                <TableCell class="text-right">
                                                    <Badge :variant="transaction.type === 'GANHO' ? 'default' : 'secondary'">
                                                        {{ transaction.status }}
                                                    </Badge>
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </div>

                                <!-- Mobile List -->
                                <div class="flex flex-col gap-4 md:hidden">
                                    <div v-for="transaction in recentTransactions" :key="transaction.id" class="flex items-center justify-between border-b pb-4 last:border-0 last:pb-0">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-medium">{{ transaction.description }}</span>
                                            <span class="text-xs text-muted-foreground">{{ transaction.category }} • {{ transaction.date }}</span>
                                        </div>
                                        <div class="flex flex-col items-end gap-1">
                                            <span class="text-sm font-bold" :class="transaction.type === 'GANHO' ? 'text-emerald-600' : 'text-rose-600'">
                                                {{ transaction.type === 'GANHO' ? '+' : '-' }} {{ formataDinheiroBRL(transaction.value) }}
                                            </span>
                                            <Badge :variant="transaction.type === 'GANHO' ? 'default' : 'secondary'" class="px-1 py-0 text-[10px]">
                                                {{ transaction.status }}
                                            </Badge>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </CardContent>
                    </Card>

                    <GastosPorCategoria class="lg:col-span-3" :categories="categoriesSummary" description="Distribuição das suas despesas no período." />
                </div>
            </section>

            <Separator />

            <!-- Visão geral desde o cadastro -->
            <section class="flex flex-col gap-6">
                <div class="flex flex-col gap-1">
                    <h3 class="text-xl font-semibold tracking-tight">Visão Geral</h3>
                    <p class="text-sm text-muted-foreground">Todo o seu histórico, desde {{ geral.desde }}.</p>
                </div>

                <StatCards :stats="geral.stats" />

                <GastosPorCategoria :categories="geral.categoriesSummary" description="Distribuição de todas as suas despesas." />
            </section>
        </div>
    </AppLayout>
</template>
