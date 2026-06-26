<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarRail,
    SidebarSeparator,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import movimentacoes from '@/routes/movimentacoes';
import { index as indexCategorias } from '@/routes/movimentacoes/categorias';
import { edit as editProfile } from '@/routes/profile';
import { type NavGroup, type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
    ArrowUpDown,
    LayoutDashboard,
    LayoutGrid,
    Plus,
    Settings,
    Sparkles,
} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const currentFilters = computed(() => page.props.filters || {});

const navGroups = computed<NavGroup[]>(() => [
    {
        label: 'Visão Geral',
        items: [
            {
                title: 'Dashboard',
                href: dashboard(),
                icon: LayoutDashboard,
                description: 'Resumo financeiro',
                iconClass: 'bg-blue-500/20 text-blue-300',
            },
        ],
    },
    {
        label: 'Financeiro',
        items: [
            {
                title: 'Movimentações',
                href: movimentacoes.index({ query: currentFilters.value as any }).url,
                icon: ArrowUpDown,
                description: 'Receitas e despesas',
                iconClass: 'bg-emerald-500/20 text-emerald-300',
            },
            {
                title: 'Categorias',
                href: indexCategorias(),
                icon: LayoutGrid,
                description: 'Organize seus lançamentos',
                iconClass: 'bg-violet-500/20 text-violet-300',
            },
        ],
    },
]);

const footerNavItems: NavItem[] = [
    {
        title: 'Configurações',
        href: editProfile(),
        icon: Settings,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset" class="overflow-hidden">
        <SidebarHeader
            class="overflow-x-hidden border-b border-sidebar-border/40 pb-3"
        >
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        size="lg"
                        as-child
                        class="h-auto overflow-hidden py-3 hover:bg-transparent active:bg-transparent"
                    >
                        <Link
                            :href="dashboard()"
                            class="flex min-w-0 w-full items-center overflow-hidden"
                        >
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>

            <div
                class="mx-2 mt-1 flex min-w-0 items-center gap-2 overflow-hidden rounded-lg bg-sidebar-accent/50 px-3 py-2 group-data-[collapsible=icon]:hidden"
            >
                <Sparkles class="size-3.5 shrink-0 text-primary" />
                <p class="min-w-0 truncate text-xs text-sidebar-foreground/70">
                    Olá,
                    <span class="font-medium text-sidebar-foreground">{{
                        user.name.split(' ')[0]
                    }}</span>
                </p>
            </div>
        </SidebarHeader>

        <SidebarContent class="gap-2 overflow-x-hidden py-3">
            <SidebarGroup class="px-2 py-0">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            as-child
                            tooltip="Nova movimentação"
                            class="h-10 overflow-hidden bg-primary font-medium text-primary-foreground shadow-md transition-all hover:bg-primary/90 hover:text-primary-foreground hover:shadow-lg active:bg-primary/90 active:text-primary-foreground"
                        >
                            <Link
                                :href="
                                    movimentacoes.create({
                                        query: currentFilters,
                                    }).url
                                "
                                class="min-w-0 overflow-hidden"
                            >
                                <Plus class="size-4" />
                                <span>Nova movimentação</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>

            <SidebarSeparator class="bg-sidebar-border/50" />

            <NavMain :groups="navGroups" />
        </SidebarContent>

        <SidebarFooter
            class="gap-2 overflow-x-hidden border-t border-sidebar-border/40 pt-3"
        >
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>

        <SidebarRail />
    </Sidebar>
    <slot />
</template>
