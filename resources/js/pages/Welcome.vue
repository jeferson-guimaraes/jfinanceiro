<script setup lang="ts">
import { dashboard, login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import { 
    CheckCircle, 
    ShieldCheck, 
    Zap, 
    ArrowRight, 
    LayoutDashboard,
    Lock,
    Gift,
    Ban
} from 'lucide-vue-next';

defineProps<{
    canRegister?: boolean;
}>();
</script>

<template>
    <Head title="JFinanceiro - Sua liberdade financeira começa aqui" />

    <div class="min-h-screen bg-background text-foreground font-sans selection:bg-primary/20">
        <!-- Header -->
        <header class="sticky top-0 z-50 w-full border-b border-border bg-background/80 backdrop-blur-md">
            <div class="container mx-auto flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-2">
                    <img src="/images/logo_jfinanceiro.png" alt="Logo JFinanceiro" class="h-8 w-8" />
                    <span class="text-xl font-bold tracking-tight text-foreground">JFinanceiro</span>
                </div>
                <nav class="flex items-center gap-4">
                    <template v-if="$page.props.auth.user">
                        <Link :href="dashboard()" class="text-sm font-medium text-muted-foreground transition-colors hover:text-primary flex items-center gap-2">
                            <LayoutDashboard class="h-4 w-4" />
                            <span class="hidden sm:inline">Dashboard</span>
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="login()" class="text-sm font-medium text-muted-foreground transition-colors hover:text-primary">
                            Entrar
                        </Link>
                        <Link v-if="canRegister" :href="register()" class="inline-flex h-9 items-center justify-center rounded-full bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                            Começar
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <main>
            <!-- Hero Section -->
            <section class="relative overflow-hidden pt-16 pb-20 sm:pt-24 sm:pb-32">
                <div class="container relative mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <div class="mx-auto flex max-w-fit items-center justify-center space-x-2 rounded-full border border-primary/20 bg-primary/5 px-4 py-1.5 mb-8 animate-in fade-in slide-in-from-top-4 duration-1000">
                        <Gift class="h-4 w-4 text-primary" />
                        <span class="text-xs font-semibold uppercase tracking-wider text-primary">100% Gratuito</span>
                        <span class="h-1 w-1 rounded-full bg-primary/30 mx-2"></span>
                        <Ban class="h-4 w-4 text-primary" />
                        <span class="text-xs font-semibold uppercase tracking-wider text-primary">Sem Anúncios</span>
                    </div>
                    
                    <h1 class="mx-auto max-w-4xl text-4xl font-extrabold tracking-tight text-foreground sm:text-6xl lg:text-7xl leading-tight">
                        Controle seu dinheiro com <span class="text-primary italic">leveza</span> e clareza.
                    </h1>
                    
                    <p class="mx-auto mt-6 max-w-2xl text-lg text-muted-foreground sm:text-xl">
                        A ferramenta minimalista que você precisava para organizar suas finanças, planejar parcelas e alcançar a tranquilidade financeira.
                    </p>
                    
                    <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                        <Link v-if="canRegister" :href="register()" class="inline-flex h-12 w-full sm:w-auto items-center justify-center rounded-full bg-primary px-8 text-base font-bold text-primary-foreground shadow-lg shadow-primary/20 transition-all hover:scale-105 active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                            Criar conta agora
                            <ArrowRight class="ml-2 h-5 w-5" />
                        </Link>
                    </div>

                    <!-- Mockup Preview -->
                    <div class="mt-16 relative mx-auto max-w-5xl px-4 animate-in fade-in zoom-in duration-1000 delay-300">
                        <div class="rounded-2xl border border-border bg-card/50 p-2 backdrop-blur-sm">
                            <div class="rounded-xl border border-border bg-background shadow-2xl overflow-hidden shadow-primary/10">
                                <img 
                                    src="/images/jfinanceiro-dashboard.png" 
                                    alt="Interface do JFinanceiro" 
                                    class="w-full h-auto object-cover"
                                />
                            </div>
                        </div>
                        <!-- Decorative Orbs -->
                        <div class="absolute -top-10 -left-10 h-64 w-64 rounded-full bg-primary/10 blur-3xl -z-10"></div>
                        <div class="absolute -bottom-10 -right-10 h-64 w-64 rounded-full bg-primary/10 blur-3xl -z-10"></div>
                    </div>
                </div>
            </section>

            <!-- Benefits Grid -->
            <section class="py-20 bg-muted/30">
                <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-16">
                        <h2 class="text-3xl font-bold tracking-tight text-foreground sm:text-4xl">Tudo o que você precisa</h2>
                        <p class="mt-4 text-muted-foreground">Focado no essencial, sem distrações ou complexidade desnecessária.</p>
                    </div>

                    <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                        <!-- Benefit 1 -->
                        <div class="group relative overflow-hidden rounded-3xl border border-border bg-card p-8 transition-all hover:shadow-md">
                            <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary transition-colors group-hover:bg-primary group-hover:text-primary-foreground">
                                <Zap class="h-6 w-6" />
                            </div>
                            <h3 class="text-xl font-bold mb-3">Rápido como um clique</h3>
                            <p class="text-muted-foreground">Registre gastos e ganhos em segundos. Otimizado para o uso diário em qualquer dispositivo.</p>
                        </div>

                        <!-- Benefit 2 -->
                        <div class="group relative overflow-hidden rounded-3xl border border-border bg-card p-8 transition-all hover:shadow-md">
                            <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary transition-colors group-hover:bg-primary group-hover:text-primary-foreground">
                                <CheckCircle class="h-6 w-6" />
                            </div>
                            <h3 class="text-xl font-bold mb-3">Controle de Parcelas</h3>
                            <p class="text-muted-foreground">Gerencie compras parceladas sem dor de cabeça. Saiba exatamente quanto terá que pagar nos próximos meses.</p>
                        </div>

                        <!-- Benefit 3 -->
                        <div class="group relative overflow-hidden rounded-3xl border border-border bg-card p-8 transition-all hover:shadow-md">
                            <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary transition-colors group-hover:bg-primary group-hover:text-primary-foreground">
                                <ShieldCheck class="h-6 w-6" />
                            </div>
                            <h3 class="text-xl font-bold mb-3">Sua privacidade importa</h3>
                            <p class="text-muted-foreground">Não vendemos seus dados. Seus dados financeiros são protegidos e acessíveis apenas por você.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Call to Action -->
            <section class="py-24 overflow-hidden relative">
                <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="relative rounded-3xl bg-foreground p-8 sm:p-16 text-center overflow-hidden">
                        <!-- Decorative background -->
                        <div class="absolute inset-0 opacity-10">
                            <div class="absolute top-0 left-0 h-40 w-40 rounded-full bg-primary blur-3xl"></div>
                            <div class="absolute bottom-0 right-0 h-40 w-40 rounded-full bg-primary blur-3xl"></div>
                        </div>
                        
                        <div class="relative z-10">
                            <h2 class="text-3xl font-bold tracking-tight text-background sm:text-5xl">Pronto para assumir o controle?</h2>
                            <p class="mt-6 text-lg text-background/70 max-w-xl mx-auto italic font-medium">
                                "A simplicidade é o último grau de sofisticação."
                            </p>
                            <p class="mt-4 text-background/60">
                                Junte-se a quem já descobriu a liberdade de uma vida financeira organizada.
                            </p>
                            <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                                <Link v-if="canRegister" :href="register()" class="inline-flex h-12 w-full sm:w-auto items-center justify-center rounded-full bg-background px-8 text-base font-bold text-foreground transition-all hover:bg-background/90 active:scale-95 shadow-xl">
                                    Começar Gratuitamente
                                </Link>
                                <div class="flex items-center gap-2 text-background/50 text-sm">
                                    <Lock class="h-4 w-4" />
                                    <span>Seguro e sem cartões</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="border-t border-border py-12">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-6 text-center sm:text-left">
                <div class="flex items-center gap-2">
                    <img src="/images/logo_jfinanceiro.png" alt="Logo JFinanceiro" class="h-6 w-6 grayscale opacity-50" />
                    <span class="text-sm font-semibold text-muted-foreground uppercase tracking-widest">JFinanceiro</span>
                </div>
                <div class="text-sm text-muted-foreground">
                    &copy; 2026 JFinanceiro. Feito para você dominar seu dinheiro.
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
/* Adicionando algumas animações sutis via CSS puro se necessário, 
   embora o Tailwind v4 e animate-in resolvam a maioria */
.selection\:bg-primary\/20 ::selection {
    background-color: rgba(var(--primary), 0.2);
}
</style>
