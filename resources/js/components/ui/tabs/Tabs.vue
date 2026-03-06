<script setup lang="ts">
import { cn } from '@/lib/utils';

interface Tab {
    id: string;
    label: string;
    value: string;
}

defineOptions({
    inheritAttrs: false,
});

const props = defineProps<{
    tabs: Tab[];
    modelValue: string;
    class?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();
</script>

<template>
    <div :class="cn('flex space-x-1 rounded-lg bg-gray-100 p-1 dark:bg-gray-800', props.class)">
        <button
            v-for="tab in tabs"
            :key="tab.id"
            type="button"
            :class="cn(
                'flex-1 rounded-md px-3 py-2 text-sm font-medium transition-all focus-visible:outline-2 focus-visible:outline-offset-2',
                modelValue === tab.value
                    ? 'bg-white text-gray-950 shadow dark:bg-gray-50 dark:text-gray-900'
                    : 'text-gray-600 hover:bg-white/50 dark:text-gray-400 dark:hover:bg-gray-700/50'
            )"
            @click="emit('update:modelValue', tab.value)"
        >
            {{ tab.label }}
        </button>
    </div>
</template>
