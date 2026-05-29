<script setup lang="ts">
import { type PropType, computed } from 'vue';
import type { Component } from 'vue';
import { formataDinheiroBRL } from '@/utils/formataDinheiro';

const props = defineProps({
  icon: {
    type: [Function, Object] as PropType<Component>,
    required: true,
  },
  iconClasses: {
    type: String,
    default: 'text-blue-900',
  },
  title: {
    type: String,
    required: true,
  },
  value: {
    type: Number,
    required: true,
  },
  valueClasses: {
    type: String,
    default: 'text-2xl font-semibold',
  },
  valueColorClass: {
    type: String,
    default: '',
  },
});

const formattedValue = computed(() => formataDinheiroBRL(props.value));
</script>

<template>
  <div class="rounded-xl border bg-white p-2 sm:p-3 shadow-sm dark:border-gray-800 dark:bg-gray-900 flex flex-col justify-between min-h-[70px] sm:min-h-[80px] w-full">		
    <div class="flex items-center justify-between">
      <h3 class="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ title }}</h3>
      <component :is="props.icon" :class="props.iconClasses" class="w-4 h-4 opacity-70" />
    </div>
    <div :class="`text-sm sm:text-base font-bold ${props.valueColorClass}`">
      {{ formattedValue }}
    </div>
  </div>
</template>