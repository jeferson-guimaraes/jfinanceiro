<template>
  <div
    v-for="toast in toasts"
    :key="toast.id"
    :class="['fixed right-4 top-4 p-4 rounded-md shadow-lg text-white z-50 transition-all duration-300 transform translate-x-0', {
      'bg-green-500': toast.type === 'success',
      'bg-red-500': toast.type === 'error',
      'bg-blue-500': toast.type === 'info',
    }]"
  >
    {{ toast.message }}
  </div>
</template>

<script setup lang="ts">
import { useToast } from '@/composables/useToast';

const { toasts } = useToast();
</script>

<style scoped>
/* Optional: Add more specific transitions or animations here if needed */
</style>
