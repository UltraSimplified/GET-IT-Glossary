<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import TermList from '@/components/glossary/TermList.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { ListedTerm } from '@/types';

defineProps<{
    letter: string;
    terms: ListedTerm[];
}>();

const { t } = useTranslations();
</script>

<template>
    <Head :title="t('index-of', { letter })" />

    <h1 class="mb-8 text-2xl font-bold text-ink">
        {{ t('index-of', { letter }) }}
    </h1>

    <p v-if="terms.length === 0" class="text-lg">
        {{ t('no-terms-for-letter', { letter }) }}
    </p>
    <TermList v-else :terms="terms" />
</template>
