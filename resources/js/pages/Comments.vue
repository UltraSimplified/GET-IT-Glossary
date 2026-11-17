<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import CommentForm from '@/components/glossary/CommentForm.vue';
import CommentList from '@/components/glossary/CommentList.vue';
import RichHtml from '@/components/glossary/RichHtml.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { TermComment } from '@/types';

type VersionThread = {
    id: number;
    version: number;
    definition_html: string;
    updated_by: string | null;
    date: string | null;
    comments: TermComment[];
};

defineProps<{
    query: string;
    term: {
        id: number;
        term: string;
        path: string;
        definition_html: string;
    } | null;
    versions: VersionThread[];
}>();

const { t } = useTranslations();
</script>

<template>
    <template v-if="!term">
        <Head :title="t('no-definition-for', { query })" />
        <h1 class="text-2xl font-bold text-ink">
            {{ t('no-definition-for', { query }) }}
        </h1>
    </template>

    <template v-else>
        <Head :title="t('comment-history', { term: term.term })" />

        <p>
            <Link
                :href="term.path"
                class="inline-flex items-center gap-2 text-sm font-semibold text-ink hover:underline"
            >
                <ArrowLeft class="size-4" aria-hidden="true" />
                {{ t('back-to-term', { term: term.term }) }}
            </Link>
        </p>

        <h1 class="mt-4 text-2xl font-bold text-ink">
            {{ t('comment-history', { term: term.term }) }}
        </h1>
        <RichHtml as="p" class="mt-3 text-lg" :html="term.definition_html" />

        <p v-if="versions.length === 0" class="mt-10 text-muted-foreground">
            {{ t('no-comments') }}
        </p>

        <section
            v-for="version in versions"
            :key="version.id"
            class="mt-10 border-t border-dashed pt-6"
        >
            <h2 class="text-sm font-bold text-muted-foreground">
                {{ t('version') }} {{ version.version }}.
                <template v-if="version.updated_by">
                    {{ t('updated-by') }} {{ version.updated_by }} {{ t('on') }}
                    {{ version.date }}
                </template>
                <template v-else
                    >{{ t('imported-on') }} {{ version.date }}</template
                >
            </h2>
            <RichHtml
                as="p"
                class="mt-2 border-l-4 border-brand pl-4 text-lg"
                :html="version.definition_html"
            />
            <CommentList class="mt-6" :comments="version.comments" />
        </section>

        <CommentForm class="mt-12 border-t pt-8" :term-id="term.id" />
    </template>
</template>
