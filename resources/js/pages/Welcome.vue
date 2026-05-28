<script setup lang="ts">
import { dashboard, login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    canRegister?: boolean;
}>();
</script>

<template>
    <Head title="JFinanceiro - Sua liberdade financeira começa aqui" />

    <div class="min-h-screen bg-white text-gray-900">
        <!-- Header -->
        <header class="container mx-auto flex items-center justify-between p-6">
            <div class="flex items-center gap-2">
                <img src="/images/logo_jfinanceiro.png" alt="Logo JFinanceiro" class="h-10 w-10" />
                <span class="text-2xl font-bold text-gray-900">JFinanceiro</span>
            </div>
            <nav class="flex items-center gap-4">
                <template v-if="$page.props.auth.user">
                    <Link :href="dashboard()" class="text-gray-600 hover:text-emerald-600 font-medium">Dashboard</Link>
                </template>
                <template v-else>
                    <Link :href="login()" class="text-gray-600 hover:text-emerald-600 font-medium">Entrar</Link>
                    <Link v-if="canRegister" :href="register()" class="rounded-full bg-gray-900 px-5 py-2 text-white hover:bg-gray-800 transition">
                        Começar Agora
                    </Link>
                </template>
            </nav>
        </header>

        <!-- Hero Section -->
        <main class="container mx-auto mt-16 px-6 text-center">
            <div class="inline-block rounded-full bg-emerald-100 text-emerald-800 px-4 py-1.5 text-sm font-semibold mb-6">
                100% Gratuito e Sem Anúncios
            </div>
            <h1 class="text-6xl font-extrabold tracking-tighter text-gray-900 leading-tight">
                Domine suas finanças<br />com <span class="text-emerald-600">simplicidade</span>.
            </h1>
            <p class="mt-8 text-xl text-gray-600 max-w-2xl mx-auto">
                O JFinanceiro transforma a complexidade do seu dinheiro em clareza. Registre ganhos, controle gastos e planeje parcelamentos de forma inteligente.
            </p>
            <div class="mt-12 flex justify-center gap-4">
                <Link v-if="canRegister" :href="register()" class="rounded-full bg-emerald-600 px-10 py-4 text-lg font-bold text-white hover:bg-emerald-700 transition shadow-lg shadow-emerald-200">
                    Criar minha conta gratuita
                </Link>
            </div>
        </main>

        <!-- Benefits -->
        <section class="container mx-auto mt-24 px-6 mb-24">
            <h2 class="text-3xl font-bold text-center mb-16">Por que escolher o JFinanceiro?</h2>
            <div class="grid gap-10 md:grid-cols-3">
                <div class="bg-gray-50 p-8 rounded-3xl">
                    <div class="mb-4 text-3xl">🚀</div>
                    <h3 class="text-xl font-bold">Rápido e Simples</h3>
                    <p class="mt-2 text-gray-600">Registre suas movimentações em segundos, direto do seu celular.</p>
                </div>
                <div class="bg-gray-50 p-8 rounded-3xl">
                    <div class="mb-4 text-3xl">🎯</div>
                    <h3 class="text-xl font-bold">Planejamento Inteligente</h3>
                    <p class="mt-2 text-gray-600">Tenha visão clara de todos os seus gastos futuros e parcelas.</p>
                </div>
                <div class="bg-gray-50 p-8 rounded-3xl">
                    <div class="mb-4 text-3xl">🛡️</div>
                    <h3 class="text-xl font-bold">Privacidade Total</h3>
                    <p class="mt-2 text-gray-600">Seus dados financeiros são seus e seguros. Sem vender informações.</p>
                </div>
            </div>
        </section>
    </div>
</template>
