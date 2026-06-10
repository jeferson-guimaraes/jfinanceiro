<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import Icon from '@/components/Icon.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { formataDinheiroBRL } from '@/utils/formataDinheiro';
import { Head } from '@inertiajs/vue3';
import { Wallet, TrendingUp, TrendingDown, Calendar, CreditCard, DollarSign, type Component } from 'lucide-vue-next';

interface Props {
    stats: Array<{
        title: string;
        value: number;
        icon: string;
        color: string;
        description: string;
    }>;
    recentTransactions: Array<{
        id: number;
        description: string;
        category: string;
        value: number;
        type: 'GANHO' | 'GASTO';
        date: string;
        status: string;
    }>;
    categoriesSummary: Array<{
        name: string;
        percentage: number;
        color: string;
        value: number;
    }>;
}

defineProps<Props>();

const iconMap: Record<string, typeof Component> = {
    wallet: Wallet,
    trendingUp: TrendingUp,
    trendingDown: TrendingDown,
    calendar: Calendar,
    creditCard: CreditCard,
    dollarSign: DollarSign,
};

const getStatValueColor = (stat: Props['stats'][0]) => {
    if (stat.title === 'Saldo Atual') {
        return stat.value >= 0 ? 'text-emerald-600' : 'text-rose-600';
    }
    if (stat.title.includes('Ganhos')) {
        return 'text-emerald-600';
    }
    if (stat.title.includes('Gastos') || stat.title.includes('A Pagar')) {
        return 'text-rose-600';
    }
    return '';
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-8">
            <!-- Header section -->
            <div class="flex flex-col gap-2">
                <h2 class="text-3xl font-bold tracking-tight">Olá, Bem-vindo de volta! 👋</h2>
                <p class="text-muted-foreground">Aqui está o resumo da sua vida financeira hoje.</p>
            </div>

            <!-- Stats Grid -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                <Card v-for="stat in stats" :key="stat.title" class="overflow-hidden">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">{{ stat.title }}</CardTitle>
                        <Icon :icon="iconMap[stat.icon]" :class="stat.color" size="20" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold" :class="getStatValueColor(stat)">
                            {{ formataDinheiroBRL(stat.value) }}
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">
                            {{ stat.description }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Main Content Grid -->
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-7">
                <!-- Recent Transactions (Desktop Table / Mobile Cards) -->
                <Card class="lg:col-span-4">
                    <CardHeader>
                        <CardTitle>Transações Recentes</CardTitle>
                        <p class="text-sm text-muted-foreground">Suas últimas movimentações financeiras.</p>
                    </CardHeader>
                    <CardContent>
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
                                    <span class="font-medium text-sm">{{ transaction.description }}</span>
                                    <span class="text-xs text-muted-foreground">{{ transaction.category }} • {{ transaction.date }}</span>
                                </div>
                                <div class="flex flex-col items-end gap-1">
                                    <span class="font-bold text-sm" :class="transaction.type === 'GANHO' ? 'text-emerald-600' : 'text-rose-600'">
                                        {{ transaction.type === 'GANHO' ? '+' : '-' }} {{ formataDinheiroBRL(transaction.value) }}
                                    </span>
                                    <Badge :variant="transaction.type === 'GANHO' ? 'default' : 'secondary'" class="text-[10px] px-1 py-0">
                                        {{ transaction.status }}
                                    </Badge>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Expenses by Category -->
                <Card class="lg:col-span-3">
                    <CardHeader>
                        <CardTitle>Gastos por Categoria</CardTitle>
                        <p class="text-sm text-muted-foreground">Distribuição das suas despesas este mês.</p>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-6">
                            <div v-for="cat in categoriesSummary" :key="cat.name" class="space-y-2">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="font-medium">{{ cat.name }}</span>
                                    <span class="text-muted-foreground">{{ cat.percentage }}% ({{ formataDinheiroBRL(cat.value) }})</span>
                                </div>
                                <div class="h-2 w-full overflow-hidden rounded-full bg-secondary">
                                    <div class="h-full rounded-full transition-all" :class="cat.color" :style="{ width: `${cat.percentage}%` }" />
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
