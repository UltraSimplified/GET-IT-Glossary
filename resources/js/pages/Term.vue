<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, SquarePen, Volume2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import CommentForm from '@/components/glossary/CommentForm.vue';
import CommentList from '@/components/glossary/CommentList.vue';
import IdgiPrompt from '@/components/glossary/IdgiPrompt.vue';
import RichHtml from '@/components/glossary/RichHtml.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { TermComment, TermEntry, TermLink } from '@/types';

defineOptions({ layout: { width: 'wide' } });

const props = defineProps<{
    query: string;
    term: TermEntry | null;
    adjacent?: { previous: TermLink | null; next: TermLink | null };
    canEdit?: boolean;
    /** Null for visitors: comments are an editors' discussion. */
    comments?: TermComment[] | null;
}>();

const { t } = useTranslations();
const page = usePage();

const italicDefinition = computed(
    () => page.props.theme.definition_style === 'italic-dash',
);

// The term said aloud, recorded in the term editor.
const pronunciation = ref<HTMLAudioElement | null>(null);
const speaking = ref(false);
const cannotPlay = ref(false);

async function sayIt() {
    const audio = pronunciation.value;

    if (!audio) {
        return;
    }

    cannotPlay.value = false;
    audio.currentTime = 0;

    try {
        await audio.play();
    } catch {
        // Some browsers can't play the format the recording was made in.
        cannotPlay.value = true;
    }
}

// The page component is reused when moving between terms.
watch(
    () => props.term?.id,
    () => {
        speaking.value = false;
        cannotPlay.value = false;
    },
);
</script>

<template>
    <!-- No such term -->
    <template v-if="!term">
        <Head
            :title="
                query
                    ? t('no-definition-for', { query })
                    : t('no-term-selected')
            "
        />

        <div class="max-w-[44rem]">
            <h1 class="text-2xl font-bold text-ink">
                {{
                    query
                        ? t('no-definition-for', { query })
                        : t('no-term-selected')
                }}
            </h1>
            <p class="mt-4 text-lg">{{ t('no-definition-hint') }}</p>
            <p class="mt-6 flex flex-wrap gap-x-8 gap-y-2">
                <Link
                    v-if="query"
                    :href="`/search/${encodeURIComponent(query).replace(/%20/g, '+')}`"
                    class="font-semibold text-ink underline decoration-brand decoration-2 underline-offset-4 hover:bg-brand-soft"
                >
                    {{ t('searching-for', { query }) }}
                </Link>
                <Link
                    href="/get-in-touch/suggest-term"
                    class="font-semibold text-ink underline decoration-brand decoration-2 underline-offset-4 hover:bg-brand-soft"
                >
                    {{ t('suggest-a-term') }}
                </Link>
            </p>
        </div>
    </template>

    <template v-else>
        <Head :title="`“${term.term}”`" />

        <!-- The single column on narrow screens never grows past the screen,
             whatever the content needs. -->
        <article
            class="grid grid-cols-[minmax(0,1fr)] gap-x-16 gap-y-12 lg:grid-cols-[minmax(0,44rem)_minmax(0,1fr)]"
        >
            <div>
                <header>
                    <!-- When a word of the term doesn't fit beside the Edit link,
                         the link moves below it. A word too long for the screen
                         breaks rather than making the page scroll sideways. -->
                    <div class="flex flex-wrap items-start gap-x-4 gap-y-3">
                        <div
                            class="flex max-w-full flex-1 flex-wrap items-center gap-x-3 gap-y-2"
                        >
                            <h1
                                class="max-w-full text-3xl font-bold text-balance wrap-break-word text-ink md:text-4xl"
                            >
                                <a
                                    v-if="term.do_not_use"
                                    href="#warning"
                                    :title="t('not-recommended')"
                                    class="mr-1"
                                >
                                    <span
                                        class="warn-mark"
                                        role="img"
                                        :aria-label="t('not-recommended')"
                                    ></span>
                                </a>
                                {{ term.term }}
                            </h1>

                            <template v-if="term.pronunciation">
                                <button
                                    type="button"
                                    class="inline-flex size-10 shrink-0 items-center justify-center rounded-full border-2 border-primary text-ink transition-colors hover:bg-brand-soft"
                                    :class="{ 'bg-brand-soft': speaking }"
                                    :aria-label="
                                        t('hear-pronunciation', {
                                            term: term.term,
                                        })
                                    "
                                    :title="
                                        t('hear-pronunciation', {
                                            term: term.term,
                                        })
                                    "
                                    data-test="say-it"
                                    @click="sayIt"
                                >
                                    <Volume2
                                        class="size-5"
                                        aria-hidden="true"
                                    />
                                </button>
                                <audio
                                    ref="pronunciation"
                                    :src="term.pronunciation"
                                    preload="none"
                                    @play="speaking = true"
                                    @pause="speaking = false"
                                    @ended="speaking = false"
                                ></audio>
                                <p
                                    v-if="cannotPlay"
                                    role="status"
                                    class="w-full text-sm text-muted-foreground"
                                >
                                    {{ t('pronunciation-cannot-play') }}
                                </p>
                            </template>
                        </div>

                        <Link
                            v-if="canEdit"
                            :href="`/terms/${term.id}/edit`"
                            class="mt-2 ml-auto inline-flex shrink-0 items-center gap-1.5 rounded-md border bg-card px-3 py-1.5 text-sm font-semibold text-ink hover:bg-brand-soft"
                        >
                            <SquarePen class="size-4" aria-hidden="true" />
                            {{ t('edit') }}
                        </Link>
                    </div>

                    <!-- The theme sets the definition plain, or in italic after a dash.
                         The dash is decoration, so screen readers skip it. -->
                    <p
                        class="mt-5 text-xl text-foreground"
                        :class="{ italic: italicDefinition }"
                    >
                        <span v-if="italicDefinition" aria-hidden="true"
                            >—&nbsp;</span
                        ><RichHtml as="span" :html="term.definition_html" />
                    </p>
                </header>

                <section v-if="term.synonyms_html" class="mt-9">
                    <h2 class="text-lg font-bold text-ink">
                        {{ t('synonyms') }}
                    </h2>
                    <!-- eslint-disable-next-line vue/no-v-html -->
                    <p class="mt-1.5" v-html="term.synonyms_html"></p>
                </section>

                <section v-if="term.explanation_html" class="mt-9">
                    <h2 class="text-lg font-bold text-ink">
                        {{ t('full-explanation') }}
                    </h2>
                    <RichHtml class="mt-1.5" :html="term.explanation_html" />
                </section>

                <section v-if="term.cartoon_html" class="mt-9">
                    <h2 class="text-lg font-bold text-ink">
                        {{ t('cartoon') }}
                    </h2>
                    <RichHtml class="mt-3" :html="term.cartoon_html" />
                </section>

                <section v-if="term.video_html" class="mt-9">
                    <h2 class="text-lg font-bold text-ink">{{ t('video') }}</h2>
                    <RichHtml class="mt-3" :html="term.video_html" />
                </section>

                <section v-if="term.example_html" class="mt-9">
                    <h2 class="text-lg font-bold text-ink">
                        {{ t('example') }}
                    </h2>
                    <RichHtml
                        class="mt-1.5 border-l-4 border-brand pl-5"
                        :html="term.example_html"
                    />
                </section>

                <section v-if="term.links_html" class="mt-9">
                    <h2 class="text-lg font-bold text-ink">
                        {{ t('more-content') }}
                    </h2>
                    <RichHtml class="mt-1.5" :html="term.links_html" />
                </section>

                <section
                    v-if="term.warning_html || term.do_not_use"
                    id="warning"
                    class="mt-9 scroll-mt-8 rounded-lg bg-warning-soft p-5"
                >
                    <h2
                        class="flex items-center gap-1 text-lg font-bold text-warning"
                    >
                        <span class="warn-mark" aria-hidden="true"></span>
                        {{ t('not-recommended') }}
                    </h2>
                    <RichHtml
                        v-if="term.warning_html"
                        class="mt-1.5"
                        :html="term.warning_html"
                    />
                </section>
            </div>

            <aside class="space-y-9 lg:pt-3">
                <section v-if="term.see_also.length">
                    <h2 class="text-lg font-bold text-ink">
                        {{ t('see-also') }}
                    </h2>
                    <ul class="mt-2 space-y-1.5">
                        <li
                            v-for="related in term.see_also"
                            :key="related.path"
                        >
                            <Link
                                :href="related.path"
                                prefetch
                                class="text-ink underline decoration-brand decoration-2 underline-offset-4 hover:bg-brand-soft"
                            >
                                {{ related.term }}
                            </Link>
                        </li>
                    </ul>
                </section>

                <section v-if="term.tags.length">
                    <h2 class="text-lg font-bold text-ink">{{ t('tags') }}</h2>
                    <ul class="mt-2 flex flex-wrap gap-2">
                        <li
                            v-for="tag in term.tags"
                            :key="tag"
                            class="rounded-full bg-secondary px-3 py-0.5 text-sm text-secondary-foreground"
                        >
                            {{ tag }}
                        </li>
                    </ul>
                </section>

                <IdgiPrompt :key="term.id" :term-id="term.id" />
            </aside>
        </article>

        <nav
            v-if="adjacent && (adjacent.previous || adjacent.next)"
            class="mt-14 flex flex-wrap justify-between gap-4 border-t pt-6"
            aria-label="Neighbouring terms"
        >
            <Link
                v-if="adjacent.previous"
                :href="adjacent.previous.path"
                prefetch
                rel="prev"
                class="inline-flex items-center gap-2 font-semibold text-ink hover:underline"
            >
                <ArrowLeft class="size-4" aria-hidden="true" />
                <span class="sr-only">{{ t('previous-term') }}:</span>
                {{ adjacent.previous.term }}
            </Link>
            <Link
                v-if="adjacent.next"
                :href="adjacent.next.path"
                prefetch
                rel="next"
                class="ml-auto inline-flex items-center gap-2 font-semibold text-ink hover:underline"
            >
                <span class="sr-only">{{ t('next-term') }}:</span>
                {{ adjacent.next.term }}
                <ArrowRight class="size-4" aria-hidden="true" />
            </Link>
        </nav>

        <!-- Editors' discussion, signed-in users only -->
        <section
            v-if="comments"
            class="mt-14 max-w-[44rem] border-t border-dashed pt-8"
            aria-labelledby="comments-title"
        >
            <h2 id="comments-title" class="text-xl font-bold text-ink">
                {{ t('comments') }}
            </h2>
            <p class="mt-1 text-sm text-muted-foreground">
                {{ t('showing-comments') }}
                <Link
                    :href="`/comments/${term.path.replace('/term/', '')}`"
                    class="text-ink underline underline-offset-4"
                >
                    {{ t('view-comment-thread') }}
                </Link>
            </p>

            <p
                v-if="canEdit && term.version > 1"
                class="mt-1 text-sm text-muted-foreground"
            >
                {{ t('versions-of', { count: term.version }) }}
                <Link
                    :href="`/terms/${term.master_id}/version/${term.version - 1}`"
                    class="text-ink underline underline-offset-4"
                >
                    {{ t('compare-versions') }}
                </Link>
            </p>

            <CommentList
                v-if="comments.length"
                class="mt-6"
                :comments="comments"
            />
            <p v-else class="mt-6 text-muted-foreground">
                {{ t('no-comments') }}
            </p>

            <CommentForm class="mt-8" :term-id="term.id" />
        </section>
    </template>
</template>
