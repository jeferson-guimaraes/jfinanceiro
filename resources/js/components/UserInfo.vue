<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import type { User } from '@/types';
import { computed } from 'vue';

interface Props {
    user: User;
    showEmail?: boolean;
    variant?: 'default' | 'sidebar';
}

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
    variant: 'default',
});

const { getInitials } = useInitials();

// Compute whether we should show the avatar image
const showAvatar = computed(
    () => props.user.avatar && props.user.avatar !== '',
);
</script>

<template>
    <Avatar
        :class="[
            'h-8 w-8 overflow-hidden rounded-lg',
            variant === 'sidebar' ? 'ring-1 ring-sidebar-border/40' : '',
        ]"
    >
        <AvatarImage v-if="showAvatar" :src="user.avatar!" :alt="user.name" />
        <AvatarFallback
            :class="[
                'rounded-lg text-xs font-semibold',
                variant === 'sidebar'
                    ? 'bg-primary/20 text-primary-foreground'
                    : 'text-black dark:text-white',
            ]"
        >
            {{ getInitials(user.name) }}
        </AvatarFallback>
    </Avatar>

    <div class="grid min-w-0 flex-1 text-left text-sm leading-tight">
        <span class="truncate font-medium">{{ user.name }}</span>
        <span
            v-if="showEmail"
            :class="[
                'truncate text-xs',
                variant === 'sidebar'
                    ? 'text-sidebar-foreground/55'
                    : 'text-muted-foreground',
            ]"
            >{{ user.email }}</span
        >
    </div>
</template>
