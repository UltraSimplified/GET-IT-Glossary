<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import RichHtml from '@/components/glossary/RichHtml.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { RankedTerm } from '@/types';

defineProps<{
    terms: RankedTerm[];
}>();

const { t } = useTranslations();
</script>

<template>
    <Head :title="t('top-ten')" />

    <h1 class="mb-8 text-2xl font-bold text-ink">{{ t('top-ten') }}</h1>

    <p v-if="terms.length === 0" class="text-lg">{{ t('top-ten-empty') }}</p>

    <!-- A ranking, so the position numbers carry meaning. -->
    <ol v-else class="divide-y">
        <li
            v-for="(term, index) in terms"
            :key="term.id"
            class="grid grid-cols-[3.25rem_1fr] gap-x-3 py-5 first:pt-0"
        >
            <span
                class="letter-token grid w-[3.25rem] place-items-center bg-primary text-xl font-bold text-primary-foreground"
                aria-hidden="true"
            >
                {{ index + 1 }}
            </span>
            <div>
                <p
                    class="flex flex-wrap items-baseline gap-x-3 text-xl leading-snug"
                >
                    <span>
                        <span
                            v-if="term.do_not_use"
                            class="warn-mark"
                            role="img"
                            :aria-label="t('not-recommended')"
                        ></span>
                        <Link
                            :href="term.path"
                            prefetch
                            class="font-bold text-ink underline decoration-brand decoration-2 underline-offset-4 hover:bg-brand-soft"
                        >
                            {{ term.term }}
                        </Link>
                    </span>
                    <span class="text-sm text-muted-foreground tabular-nums">
                        {{ term.hits.toLocaleString() }}
                        {{ term.hits === 1 ? t('view-singular') : t('views') }}
                    </span>
                </p>
                <RichHtml as="p" class="mt-1.5" :html="term.definition_html" />
            </div>
        </li>
    </ol>
</template>
