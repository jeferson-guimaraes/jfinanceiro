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
    default: 'text-[#6F4E37] w-8 h-8',
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
  <div class="rounded-lg border bg-white p-4 space-y-2 shadow-sm dark:border-gray-700 dark:bg-gray-800 flex items-center gap-3 w-full">
    <div class="flex justify-center items-center">
      <component :is="props.icon" :class="props.iconClasses" />
    </div>
		
    <div>
			<h3 class="md:block text-sm font-medium text-gray-500 dark:text-gray-400">{{ title }}</h3>
      <div :class="`${props.valueClasses} ${props.valueColorClass}`">
        {{ formattedValue }}
      </div>
    </div>
  </div>
</template>