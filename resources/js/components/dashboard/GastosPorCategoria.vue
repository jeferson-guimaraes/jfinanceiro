<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formataDinheiroBRL } from '@/utils/formataDinheiro';

export interface CategoriaResumo {
    name: string;
    percentage: number;
    color: string;
    value: number;
}

defineProps<{
    categories: CategoriaResumo[];
    description: string;
}>();
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Gastos por Categoria</CardTitle>
            <p class="text-sm text-muted-foreground">{{ description }}</p>
        </CardHeader>
        <CardContent>
            <div v-if="categories.length" class="flex flex-col gap-6">
                <div v-for="cat in categories" :key="cat.name" class="flex flex-col gap-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium">{{ cat.name }}</span>
                        <span class="text-muted-foreground">{{ cat.percentage }}% ({{ formataDinheiroBRL(cat.value) }})</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-secondary">
                        <div class="h-full rounded-full transition-all" :class="cat.color" :style="{ width: `${cat.percentage}%` }" />
                    </div>
                </div>
            </div>
            <p v-else class="text-sm text-muted-foreground">Nenhum gasto registrado.</p>
        </CardContent>
    </Card>
</template>
