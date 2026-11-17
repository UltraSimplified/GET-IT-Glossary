<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import RichHtml from '@/components/glossary/RichHtml.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { ListedTerm, SearchResult, TermSummary } from '@/types';

/**
 * A list of glossary entries: the term, any synonyms and its one-line
 * definition. Used by the A–Z listing and by search results.
 */
defineProps<{
    terms: (TermSummary & Partial<ListedTerm> & Partial<SearchResult>)[];
}>();

const { t } = useTranslations();
</script>

<template>
    <ul class="divide-y">
        <li v-for="term in terms" :key="term.id" class="py-5 first:pt-0">
            <p class="text-xl leading-snug">
                <span
                    v-if="term.do_not_use"
                    class="warn-mark"
                    role="img"
                    :aria-label="t('not-recommended')"
                    :title="t('not-recommended')"
                ></span>

                <!-- Filed under another name: "risk reduction, absolute — see absolute risk reduction" -->
                <template v-if="term.listed_as">
                    <span class="font-bold text-foreground">{{
                        term.listed_as
                    }}</span>
                    <span class="mx-2 text-base text-muted-foreground italic">{{
                        t('see')
                    }}</span>
                </template>

                <Link
                    :href="term.path"
                    prefetch
                    class="font-bold text-ink underline decoration-brand decoration-2 underline-offset-4 hover:bg-brand-soft"
                >
                    <!-- eslint-disable-next-line vue/no-v-html -->
                    <span v-if="term.term_html" v-html="term.term_html"></span>
                    <template v-else>{{ term.term }}</template>
                </Link>

                <!-- eslint-disable vue/no-v-html -->
                <span
                    v-if="term.synonyms_html"
                    class="ml-2 text-base font-normal text-muted-foreground"
                    v-html="`(${term.synonyms_html})`"
                ></span>
                <!-- eslint-enable vue/no-v-html -->
            </p>

            <RichHtml
                v-if="term.definition_html"
                as="p"
                class="mt-1.5"
                :html="term.definition_html"
            />
        </li>
    </ul>
</template>
