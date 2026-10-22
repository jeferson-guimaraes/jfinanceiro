<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { Eye, EyeOff } from 'lucide-vue-next';
import { ref, type HTMLAttributes } from 'vue';

defineOptions({ inheritAttrs: false });

const props = defineProps<{
  class?: HTMLAttributes['class'];
}>();

const senha = defineModel<string>({ default: '' });

const visivel = ref(false);
</script>

<template>
  <div class="relative">
    <Input
      v-bind="$attrs"
      v-model="senha"
      :type="visivel ? 'text' : 'password'"
      :class="cn('pr-10', props.class)"
    />
    <button
      type="button"
      class="absolute inset-y-0 right-0 flex w-10 items-center justify-center rounded-r-md text-muted-foreground transition-colors hover:cursor-pointer hover:text-foreground focus-visible:text-foreground focus-visible:outline-none"
      :aria-label="visivel ? 'Ocultar senha' : 'Mostrar senha'"
      :aria-pressed="visivel"
      :title="visivel ? 'Ocultar senha' : 'Mostrar senha'"
      @click="visivel = !visivel"
    >
      <EyeOff v-if="visivel" class="h-4 w-4" aria-hidden="true" />
      <Eye v-else class="h-4 w-4" aria-hidden="true" />
    </button>
  </div>
</template>
