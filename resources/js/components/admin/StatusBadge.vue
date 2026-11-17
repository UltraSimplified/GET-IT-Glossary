<script setup lang="ts">
import { Archive, CircleCheck, History, PencilLine } from '@lucide/vue';
import { computed } from 'vue';
import type { TermStatus } from '@/types';

const { status } = defineProps<{ status: TermStatus | string }>();

const look = computed(() => {
    switch (status) {
        case 'LIVE':
            return {
                label: 'Live',
                icon: CircleCheck,
                class: 'bg-success-soft text-success',
            };
        case 'DRAFT':
            return {
                label: 'Draft',
                icon: PencilLine,
                class: 'bg-muted text-muted-foreground',
            };
        case 'ARCHIVED':
            return {
                label: 'Archived',
                icon: Archive,
                class: 'bg-warning-soft text-warning',
            };
        default:
            return {
                label: 'Earlier version',
                icon: History,
                class: 'bg-muted text-muted-foreground',
            };
    }
});
</script>

<template>
    <span
        class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-bold whitespace-nowrap"
        :class="look.class"
    >
        <component :is="look.icon" class="size-3.5" aria-hidden="true" />
        {{ look.label }}
    </span>
</template>
