<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <div
        class="flex aspect-square size-9 shrink-0 items-center justify-center rounded-xl bg-white from-primary  p-1 shadow-md ring-1 ring-white/15"
    >
        <AppLogoIcon class="size-5" />
    </div>
    <div
        class="ml-2 grid min-w-0 flex-1 text-left leading-tight group-data-[collapsible=icon]:hidden"
    >
        <span class="truncate text-sm font-bold tracking-tight text-sidebar-foreground">
            JFinanceiro
        </span>
        <span class="truncate text-[11px] text-sidebar-foreground/55">
            Gestão Financeira
        </span>
    </div>
</template>
