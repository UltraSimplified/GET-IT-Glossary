<script setup lang="ts">
import { computed } from 'vue';
import { useTranslations } from '@/composables/useTranslations';
import type { IdgiScore } from '@/types';

/**
 * How often readers pressed "I don't get it". Above 30% the definition
 * needs a look; above 60% it is not working.
 */
const {
    score,
    percent,
    compact = false,
} = defineProps<{
    score?: IdgiScore | null;
    percent?: number | null;
    compact?: boolean;
}>();

const { t } = useTranslations();

const value = computed(() => score?.percent ?? percent ?? null);

const tone = computed(() => {
    if (value.value === null) {
        return '';
    }

    if (value.value > 60) {
        return 'bg-danger-soft text-destructive';
    }

    return value.value > 30
        ? 'bg-warning-soft text-warning'
        : 'bg-success-soft text-success';
});
</script>

<template>
    <template v-if="value !== null">
        <span
            v-if="compact"
            class="inline-block rounded-full px-2.5 py-0.5 text-xs font-bold tabular-nums"
            :class="tone"
            :title="`${value}% of readers didn't get it`"
        >
            {{ value }}%
        </span>
        <p
            v-else
            class="rounded-lg px-4 py-3 text-sm"
            :class="tone"
            role="status"
        >
            {{ t('idgi-score') }}
            <strong class="tabular-nums">{{ value }}%</strong>
            <span v-if="score" class="tabular-nums">
                ({{ score.count }} {{ t('idgi-out-of') }}
                {{ score.hits.toLocaleString() }} {{ t('hits') }})
            </span>
        </p>
    </template>
</template>
