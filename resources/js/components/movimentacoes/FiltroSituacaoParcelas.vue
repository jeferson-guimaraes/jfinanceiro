<script setup lang="ts">
export type SituacaoParcelas = 'todas' | 'pagas' | 'nao_pagas';

const situacao = defineModel<SituacaoParcelas>({ default: 'todas' });

const opcoes: { valor: SituacaoParcelas; rotulo: string }[] = [
  { valor: 'todas', rotulo: 'Todas' },
  { valor: 'pagas', rotulo: 'Pagas' },
  { valor: 'nao_pagas', rotulo: 'Não pagas' },
];
</script>

<template>
  <div
    role="radiogroup"
    aria-label="Situação das parcelas"
    class="grid h-11 w-full grid-cols-3 gap-1 rounded-md border border-input bg-gray-50 p-1 sm:h-9 sm:w-auto sm:self-start dark:bg-gray-900/50"
  >
    <label v-for="opcao in opcoes" :key="opcao.valor" class="relative min-w-0">
      <input
        v-model="situacao"
        type="radio"
        name="situacao-parcelas"
        :value="opcao.valor"
        class="peer sr-only"
      />
      <span
        class="flex h-full cursor-pointer items-center justify-center truncate rounded px-3 text-xs font-medium whitespace-nowrap text-gray-500 transition-colors select-none hover:text-gray-800 peer-checked:bg-white peer-checked:text-blue-600 peer-checked:shadow-sm peer-focus-visible:ring-2 peer-focus-visible:ring-primary/50 dark:text-gray-400 dark:hover:text-gray-200 dark:peer-checked:bg-gray-800 dark:peer-checked:text-blue-400"
      >
        {{ opcao.rotulo }}
      </span>
    </label>
  </div>
</template>
