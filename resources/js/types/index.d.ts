import { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
}

export interface Paginated<T> {
    data: T[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
    current_page: number;
    from: number;
    last_page: number;
    path: string;
    per_page: number;
    to: number;
    total: number;
}

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
    flash: {
        success?: string;
        error?: string;
    };
};

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface Categoria {
    id: number;
    nome: string;
    tipo: 'ganho' | 'gasto' | 'gasto futuro';
    user_id: number;
    created_at: string;
    updated_at: string;
}

export interface Movimentacao {
    id: number;
    categoria_id: number;
    user_id: number;
    data: string;
    descricao: string;
    valor: number;
    tipo: 'ganho' | 'gasto' | 'gasto futuro';
    parcelas: number;
    parcelas_pagas?: number;
    created_at: string;
    updated_at: string;
    categoria?: Categoria;
    lista_parcelas?: Parcela[];
}

export interface Parcela {
    id: number;
    movimentacao_id: number;
    numero: number;
    valor: number;
    data_vencimento: string;
    data_pagamento: string | null;
    pago: boolean;
    created_at: string;
    updated_at: string;
}

export interface ParcelaComMovimentacao extends Parcela {
    movimentacao: Movimentacao;
}
