<script setup lang="ts">
import { cn } from '@/lib/utils';
import { Star } from 'lucide-vue-next';
import { computed, ref, type HTMLAttributes } from 'vue';

const props = withDefaults(
  defineProps<{
    readonly?: boolean;
    tamanho?: 'sm' | 'lg';
    invalido?: boolean;
    class?: HTMLAttributes['class'];
  }>(),
  {
    readonly: false,
    tamanho: 'lg',
    invalido: false,
  },
);

const nota = defineModel<number>({ default: 0 });

const TOTAL = 5;
const rotulos = ['Muito ruim', 'Ruim', 'Regular', 'Bom', 'Excelente'];

const notaEmDestaque = ref(0);
const estrelas: (HTMLButtonElement | null)[] = [];

const notaExibida = computed(() => notaEmDestaque.value || nota.value);

const classeIcone = computed(() => (props.tamanho === 'lg' ? 'h-8 w-8' : 'h-4 w-4'));

/**
 * No grupo de radio, só a estrela marcada (ou a primeira, sem nota) entra na ordem de Tab.
 */
const indiceFocavel = computed(() => Math.max(nota.value, 1) - 1);

function escolher(valor: number) {
  if (props.readonly) {
    return;
  }

  nota.value = valor;
}

function aoTeclar(evento: KeyboardEvent, valor: number) {
  const proxima: Record<string, number> = {
    ArrowRight: Math.min(valor + 1, TOTAL),
    ArrowUp: Math.min(valor + 1, TOTAL),
    ArrowLeft: Math.max(valor - 1, 1),
    ArrowDown: Math.max(valor - 1, 1),
    Home: 1,
    End: TOTAL,
  };

  if (evento.key in proxima) {
    evento.preventDefault();
    escolher(proxima[evento.key]);
    estrelas[proxima[evento.key] - 1]?.focus();
  }
}
</script>

<template>
  <div
    v-if="readonly"
    role="img"
    :aria-label="`${nota} de ${TOTAL} estrelas`"
    :class="cn('flex items-center gap-0.5', props.class)"
  >
    <Star
      v-for="valor in TOTAL"
      :key="valor"
      :class="[classeIcone, valor <= nota ? 'fill-yellow-400 text-yellow-400' : 'fill-transparent text-muted-foreground/30']"
      aria-hidden="true"
    />
  </div>

  <div v-else :class="cn('flex flex-col gap-1', props.class)">
    <div
      role="radiogroup"
      aria-label="Nota de 1 a 5 estrelas"
      :aria-invalid="invalido || undefined"
      class="flex items-center gap-1"
      @mouseleave="notaEmDestaque = 0"
    >
      <button
        v-for="valor in TOTAL"
        :key="valor"
        :ref="(el) => (estrelas[valor - 1] = el as HTMLButtonElement | null)"
        type="button"
        role="radio"
        :aria-checked="nota === valor"
        :aria-label="`${valor} ${valor === 1 ? 'estrela' : 'estrelas'}: ${rotulos[valor - 1]}`"
        :tabindex="valor - 1 === indiceFocavel ? 0 : -1"
        class="rounded-md p-0.5 transition-transform hover:scale-110 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
        @click="escolher(valor)"
        @mouseenter="notaEmDestaque = valor"
        @keydown="aoTeclar($event, valor)"
      >
        <Star
          :class="[
            classeIcone,
            'transition-colors',
            valor <= notaExibida ? 'fill-yellow-400 text-yellow-400' : 'fill-transparent text-muted-foreground/40',
          ]"
          aria-hidden="true"
        />
      </button>
    </div>
    <p class="h-5 text-sm text-muted-foreground" aria-live="polite">
      {{ notaExibida ? rotulos[notaExibida - 1] : 'Toque nas estrelas para dar sua nota' }}
    </p>
  </div>
</template>
