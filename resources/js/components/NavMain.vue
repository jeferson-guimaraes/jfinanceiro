<script setup lang="ts">
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuBadge,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { urlIsActive } from '@/lib/utils';
import { type NavGroup } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';

defineProps<{
    groups: NavGroup[];
}>();

const page = usePage();
</script>

<template>
    <SidebarGroup
        v-for="group in groups"
        :key="group.label"
        class="px-2 py-0"
    >
        <SidebarGroupLabel
            class="px-2 text-[10px] font-semibold tracking-wider text-sidebar-foreground/45 uppercase"
        >
            {{ group.label }}
        </SidebarGroupLabel>
        <SidebarMenu class="gap-1">
            <SidebarMenuItem v-for="item in group.items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="urlIsActive(item.href, page.url)"
                    :tooltip="item.title"
                    class="group/nav-item h-auto min-h-10 py-2 transition-all duration-200 data-[active=true]:bg-primary/15 data-[active=true]:font-medium data-[active=true]:shadow-[inset_3px_0_0_0_var(--color-primary)]"
                >
                    <Link
                        :href="item.href"
                        class="flex min-w-0 w-full items-center gap-2.5 overflow-hidden"
                    >
                        <span
                            :class="[
                                'flex size-8 shrink-0 items-center justify-center rounded-lg transition-colors duration-200',
                                item.iconClass ??
                                    'bg-sidebar-accent text-sidebar-accent-foreground',
                            ]"
                        >
                            <component :is="item.icon" class="size-4" />
                        </span>
                        <span
                            class="flex min-w-0 flex-1 flex-col gap-0.5 group-data-[collapsible=icon]:hidden"
                        >
                            <span class="truncate leading-none font-medium">{{
                                item.title
                            }}</span>
                            <span
                                v-if="item.description"
                                class="truncate text-[11px] leading-tight text-sidebar-foreground/55"
                            >
                                {{ item.description }}
                            </span>
                        </span>
                    </Link>
                </SidebarMenuButton>
                <SidebarMenuBadge
                    v-if="item.badge"
                    class="bg-primary/20 text-primary-foreground"
                >
                    {{ item.badge }}
                </SidebarMenuBadge>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
