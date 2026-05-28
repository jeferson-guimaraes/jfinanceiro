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

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

// Dados fictícios para demonstração
const stats = [
    {
        title: 'Saldo Atual',
        value: 2450.00,
        icon: 'wallet',
        color: 'text-emerald-600',
        description: 'Disponível em conta'
    },
    {
        title: 'Ganhos (Maio)',
        value: 5000.00,
        icon: 'trendingUp',
        color: 'text-blue-600',
        description: 'Total recebido este mês'
    },
    {
        title: 'Gastos (Maio)',
        value: 2550.00,
        icon: 'trendingDown',
        color: 'text-rose-600',
        description: 'Total de despesas pagas'
    },
    {
        title: 'A Pagar (Futuro)',
        value: 1200.00,
        icon: 'calendar',
        color: 'text-amber-600',
        description: 'Parcelas pendentes'
    }
];

const recentTransactions = [
    { id: 1, description: 'Supermercado Mensal', category: 'Alimentação', value: 450.00, type: 'GASTO', date: '28/05/2026', status: 'Pago' },
    { id: 2, description: 'Salário Mensal', category: 'Trabalho', value: 5000.00, type: 'GANHO', date: '05/05/2026', status: 'Recebido' },
    { id: 3, description: 'Aluguel Apartamento', category: 'Moradia', value: 1200.00, type: 'GASTO', date: '10/05/2026', status: 'Pago' },
    { id: 4, description: 'Assinatura Netflix', category: 'Lazer', value: 55.90, type: 'GASTO', date: '15/05/2026', status: 'Pago' },
    { id: 5, description: 'Jantar Restaurante', category: 'Alimentação', value: 120.00, type: 'GASTO', date: '20/05/2026', status: 'Pago' },
];

const categoriesSummary = [
    { name: 'Alimentação', percentage: 45, color: 'bg-rose-500', value: 1147.50 },
    { name: 'Moradia', percentage: 30, color: 'bg-blue-500', value: 765.00 },
    { name: 'Lazer', percentage: 15, color: 'bg-amber-500', value: 382.50 },
    { name: 'Outros', percentage: 10, color: 'bg-slate-500', value: 255.00 },
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
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card v-for="stat in stats" :key="stat.title" class="overflow-hidden">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">{{ stat.title }}</CardTitle>
                        <Icon :name="stat.icon" :class="stat.color" size="20" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold" :class="stat.title === 'Saldo Atual' ? 'text-emerald-600' : ''">
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

                        <div class="mt-8 rounded-lg bg-muted p-4">
                            <div class="flex items-center gap-3">
                                <div class="rounded-full bg-primary/10 p-2">
                                    <Icon name="lightbulb" class="text-primary" size="20" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium">Dica de hoje</p>
                                    <p class="text-xs text-muted-foreground">Seu gasto com <strong>Alimentação</strong> está 10% maior que no mês passado. Que tal cozinhar mais em casa esta semana?</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
