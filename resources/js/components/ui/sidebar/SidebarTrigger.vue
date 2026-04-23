<script setup lang="ts">
import { computed, type HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Menu, X } from 'lucide-vue-next'
import { useSidebar } from './utils'
const props = defineProps<{
  class?: HTMLAttributes['class']
}>()

const { toggleSidebar, open, isMobile } = useSidebar()

const currentIcon = computed(() => {
  if (isMobile.value) return Menu;
  return open.value ? X : Menu;
});
</script>

<template>
  <Button
    data-sidebar="trigger"
    data-slot="sidebar-trigger"
    variant="ghost"
    size="icon"
    :class="cn('h-7 w-7', props.class)"
    @click="toggleSidebar"
  >
    <component :is="currentIcon" />
    <span class="sr-only">Toggle Sidebar</span>
  </Button>
</template>