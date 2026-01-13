<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import ToastNotification from '@/components/ToastNotification.vue';
import { useToast } from '@/composables/useToast';
import type { BreadcrumbItem } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItem[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const { success, error } = useToast();

watch(() => page.props.flash?.success, (message) => {
    if (message) {
        success(String(message));
        page.props.flash.success = undefined;
    }
}, { immediate: true });

watch(() => page.props.flash?.error, (message) => {
    if (message) {
        error(String(message));
        page.props.flash.error = undefined;
    }
}, { immediate: true });
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
    </AppShell>
    <ToastNotification />
</template>
