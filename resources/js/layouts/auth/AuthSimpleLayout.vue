<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue'
import { home } from '@/routes'
import { Link } from '@inertiajs/vue3'
import { onMounted, onUnmounted, ref } from 'vue'

defineProps<{
    title?: string
    description?: string
}>()

const testimonials = [
    {
        quote: "Eu cansei de usar planilhas complicadas e decidi criar o JF para ser simples de verdade.",
        author: "Jeferson Guimarães"
    },
    {
        quote: "O sistema me ajudou muito a organizar as contas. Agora não perco mais nenhum prazo.",
        author: "Gabriele Ribeiro"
    },
    {
        quote: "Agora eu sei exatamente para onde foi meu dinheiro no final do mês. Sem mistérios.",
        author: "Nycolas Gazola"
    }
]

const currentIndex = ref(0)
let interval: any = null

onMounted(() => {
    interval = setInterval(() => {
        currentIndex.value = (currentIndex.value + 1) % testimonials.length
    }, 5000)
})

onUnmounted(() => {
    if (interval) clearInterval(interval)
})
</script>

<template>
    <div class="flex min-h-screen">
        <div class="hidden md:block w-2/3 relative bg-cover bg-center p-10"
            style="background-image: url('https://images.unsplash.com/photo-1554224155-6726b3ff858f?q=80&w=2511&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D');">
            <div class="absolute inset-0 bg-black/30"></div>

            <div className="relative z-20 flex items-center text-lg font-medium w-9 h-9 bg-gray-100 rounded">
                <AppLogoIcon class="size-9 fill-current text-[var(--foreground)] dark:text-white" />
            </div>

            <div className="h-full z-20 text-white">
                <transition name="fade" mode="out-in">
                    <blockquote :key="currentIndex" className="absolute bottom-10 space-y-2">
                        <p className="text-lg italic">
                            &ldquo;{{ testimonials[currentIndex].quote }}&rdquo;
                        </p>
                        <footer className="text-sm">{{ testimonials[currentIndex].author }}</footer>
                    </blockquote>
                </transition>
            </div>

        </div>
...
        <div class="flex w-full md:w-1/3 flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
            <div class="w-full max-w-sm">
                <div class="flex flex-col gap-8">
                    <div class="flex flex-col items-center gap-4">
                        <Link :href="home()" class="flex flex-col items-center gap-2 font-medium">
                        <div class="mb-1 flex w-[75px] items-center justify-center rounded-md dark:bg-gray-100 p-0.5">
                            <AppLogoIcon class="size-9 fill-current text-[var(--foreground)] dark:text-white" />
                        </div>
                        <span class="sr-only">{{ title }}</span>
                        </Link>

                        <div class="space-y-2 text-center">
                            <h1 class="text-xl font-medium">{{ title }}</h1>
                            <p class="text-center text-sm text-muted-foreground">
                                {{ description }}
                            </p>
                        </div>
                    </div>
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.5s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>