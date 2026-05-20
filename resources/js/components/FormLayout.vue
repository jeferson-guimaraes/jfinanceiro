<script setup lang="ts">
import { type LucideIcon } from 'lucide-vue-next';

interface Props {
    title: string;
    description?: string;
    icon?: LucideIcon;
    variant?: 'primary' | 'success' | 'danger' | 'warning' | 'info';
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'primary',
});

const variantClasses = {
    primary: 'bg-blue-600 text-blue-100',
    success: 'bg-emerald-600 text-emerald-100',
    danger: 'bg-red-600 text-red-100',
    warning: 'bg-amber-600 text-amber-100',
    info: 'bg-sky-600 text-sky-100',
};
</script>

<template>
    <div class="mx-auto max-w-5xl overflow-hidden rounded-xl border-none shadow-2xl bg-white dark:bg-sidebar">
        <!-- Header -->
        <div :class="[variantClasses[props.variant].split(' ')[0], 'p-8 text-white relative']">
            <div v-if="icon" class="absolute top-4 right-4 opacity-10">
                <component :is="icon" class="h-28 w-28" />
            </div>
            <div class="relative z-10">
                <h2 class="text-3xl font-bold text-white leading-tight tracking-tight">{{ title }}</h2>
                <p v-if="description" :class="[variantClasses[props.variant].split(' ')[1], 'opacity-90 mt-2 text-lg max-w-2xl']">
                    {{ description }}
                </p>
            </div>
        </div>

        <!-- Form Content -->
        <div class="p-8 space-y-6">
            <slot />
        </div>

        <!-- Footer -->
        <div v-if="$slots.footer"
            class="flex flex-col-reverse sm:flex-row justify-end gap-3 p-8 pt-0 bg-white dark:bg-sidebar">
            <slot name="footer" />
        </div>
    </div>
</template>
