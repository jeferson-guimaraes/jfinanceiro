<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Menu, X } from 'lucide-vue-next'
import { useSidebar } from './utils'
const props = defineProps<{
  class?: HTMLAttributes['class']
}>()

const { toggleSidebar, open, isMobile } = useSidebar()
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
    <component :is="open || isMobile ? Menu : X" />
    <span class="sr-only">Toggle Sidebar</span>
  </Button>
</template>