<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import Icon from '@/components/Icon.vue';
import { formataDinheiroBRL } from '@/utils/formataDinheiro';
import { Wallet, TrendingUp, TrendingDown, Calendar, CreditCard, DollarSign, type Component } from 'lucide-vue-next';

export interface DashboardStat {
    title: string;
    value: number;
    icon: string;
    color: string;
    description: string;
}

defineProps<{
    stats: DashboardStat[];
}>();

const iconMap: Record<string, typeof Component> = {
    wallet: Wallet,
    trendingUp: TrendingUp,
    trendingDown: TrendingDown,
    calendar: Calendar,
    creditCard: CreditCard,
    dollarSign: DollarSign,
};
</script>

<template>
    <div class="grid gap-4 md:grid-cols-2" :class="stats.length > 4 ? 'lg:grid-cols-3 xl:grid-cols-5' : 'lg:grid-cols-4'">
        <Card v-for="stat in stats" :key="stat.title" class="overflow-hidden">
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">{{ stat.title }}</CardTitle>
                <Icon :icon="iconMap[stat.icon]" :class="stat.color" size="20" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold" :class="stat.color">
                    {{ formataDinheiroBRL(stat.value) }}
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ stat.description }}
                </p>
            </CardContent>
        </Card>
    </div>
</template>
