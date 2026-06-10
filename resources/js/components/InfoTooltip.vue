<script setup lang="ts">
import { Info } from 'lucide-vue-next';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { useMediaQuery } from '@vueuse/core';

defineProps<{
    message: string;
}>();

const isDesktop = useMediaQuery('(min-width: 768px)');
</script>

<template>
    <div class="inline-flex">
        <template v-if="isDesktop">
            <TooltipProvider>
                <Tooltip>
                    <TooltipTrigger as-child>
                        <button 
                            type="button" 
                            class="inline-flex items-center justify-center rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors focus:outline-none"
                        >
                            <Info class="h-4 w-4 text-gray-400 cursor-help" />
                            <span class="sr-only">Informação</span>
                        </button>
                    </TooltipTrigger>
                    <TooltipContent>
                        <p class="max-w-xs text-xs font-medium leading-relaxed">
                            {{ message }}
                        </p>
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>
        </template>
        <template v-else>
            <Popover>
                <PopoverTrigger as-child>
                    <button 
                        type="button" 
                        class="inline-flex items-center justify-center rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors focus:outline-none"
                    >
                        <Info class="h-4 w-4 text-gray-400 cursor-help" />
                        <span class="sr-only">Informação</span>
                    </button>
                </PopoverTrigger>
                <PopoverContent class="w-64 z-[100]">
                    <p class="text-xs font-medium leading-relaxed">
                        {{ message }}
                    </p>
                </PopoverContent>
            </Popover>
        </template>
    </div>
</template>
