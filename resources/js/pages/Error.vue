<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTranslations } from '@/composables/useTranslations';

const { status } = defineProps<{
    status: number;
}>();

const { t } = useTranslations();

const message = computed(() => {
    switch (status) {
        case 403:
            return {
                title: 'You don’t have access to this page',
                hint: 'Sign in with an account that has the right permissions, or ask an administrator.',
            };
        case 404:
            return {
                title: t('page-not-found'),
                hint: t('page-not-found-hint'),
            };
        case 503:
            return {
                title: 'The glossary is being updated',
                hint: 'It will be back in a few minutes.',
            };
        default:
            return {
                title: 'Something went wrong on our side',
                hint: 'Try again in a moment. If it keeps happening, let us know through the feedback form.',
            };
    }
});
</script>

<template>
    <Head :title="message.title" />

    <h1 class="text-2xl font-bold text-ink">{{ message.title }}</h1>
    <p class="mt-4 text-lg">{{ message.hint }}</p>
    <p class="mt-8">
        <Link
            href="/"
            class="font-semibold text-ink underline decoration-brand decoration-2 underline-offset-4 hover:bg-brand-soft"
        >
            {{ t('back-to-glossary') }}
        </Link>
    </p>
</template>
