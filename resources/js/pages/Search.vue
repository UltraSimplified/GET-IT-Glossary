<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import TermList from '@/components/glossary/TermList.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { SearchResult } from '@/types';

defineProps<{
    query: string;
    terms: SearchResult[];
}>();

const { t } = useTranslations();
</script>

<template>
    <Head :title="t('searching-for', { query })" />

    <h1 class="text-2xl font-bold text-ink">
        {{ t('searching-for', { query }) }}
    </h1>

    <template v-if="terms.length === 0">
        <p class="mt-6 text-lg">{{ t('no-search-results', { query }) }}</p>
        <p class="mt-2 text-muted-foreground">
            {{ t('no-search-results-hint') }}
        </p>
    </template>

    <template v-else>
        <p class="mt-2 mb-8 text-muted-foreground" aria-live="polite">
            {{
                terms.length === 1
                    ? t('match-found')
                    : t('matches-found', { count: terms.length })
            }}
        </p>
        <TermList :terms="terms" />
    </template>
</template>
