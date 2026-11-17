<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import AlphabetNav from '@/components/glossary/AlphabetNav.vue';
import RichHtml from '@/components/glossary/RichHtml.vue';
import { useTranslations } from '@/composables/useTranslations';
import { postJson } from '@/lib/http';
import type {
    TermSummary,
    ThemeSettings,
    ThemeShared,
    ThemeVariables,
} from '@/types';

/**
 * Glossary pages drawn with a theme that has not been saved: the header
 * and A–Z strip, the home page alphabet, a term and a list of terms.
 *
 * The server turns the draft settings into CSS variables with the same
 * code that draws the site, and they are applied to this wrapper only, so
 * the rest of the page (and the live site) keep their own theme.
 */
const props = defineProps<{
    themeKey: string;
    settings: ThemeSettings;
    logo: string | null;
    sample: TermSummary[];
}>();

const page = usePage();
const { t } = useTranslations();

const variables = ref<ThemeVariables | null>(null);
const error = ref<string | null>(null);
const mode = ref<'light' | 'dark'>('light');

let timer: ReturnType<typeof setTimeout> | undefined;
let latest = 0;

async function refresh() {
    const request = ++latest;

    try {
        const result = await postJson<ThemeVariables>('/themes/preview', {
            theme: props.themeKey,
            settings: props.settings,
        });

        // A slower, older answer must not replace a newer one.
        if (request === latest) {
            variables.value = result;
            error.value = null;
        }
    } catch (failure) {
        if (request === latest) {
            error.value =
                failure instanceof Error ? failure.message : String(failure);
        }
    }
}

watch(
    () => props.settings,
    () => {
        clearTimeout(timer);
        timer = setTimeout(refresh, 150);
    },
    { deep: true, immediate: true },
);

onBeforeUnmount(() => clearTimeout(timer));

watch(
    () => props.settings.colours.dark,
    (dark) => {
        if (dark === null) {
            mode.value = 'light';
        }
    },
);

const style = computed(() => {
    if (!variables.value) {
        return {};
    }

    const dark = mode.value === 'dark' ? (variables.value.dark ?? {}) : {};

    return {
        ...variables.value.light,
        ...dark,
        // rem sizes follow the page root, so the text size is shown by zooming.
        zoom: String(props.settings.base_size / 16),
    };
});

const theme = computed<ThemeShared>(() => ({
    key: props.themeKey,
    name: '',
    letter_shape: props.settings.letter_shape,
    home_alphabet: props.settings.home_alphabet,
    definition_style: props.settings.definition_style,
    letter_colours: props.settings.letter_colours,
    accents: props.settings.accents,
    has_dark: props.settings.colours.dark !== null,
}));

const term = computed(() => props.sample[0] ?? null);
const logo = computed(() => props.logo ?? page.props.glossary.logo);
</script>

<template>
    <section :aria-label="t('theme-preview')" class="grid gap-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="text-lg font-bold text-ink">
                    {{ t('theme-preview') }}
                </h2>
                <p class="text-sm text-muted-foreground">
                    {{ t('theme-preview-hint') }}
                </p>
            </div>
            <div
                v-if="settings.colours.dark"
                class="inline-flex rounded-md border bg-card p-0.5 text-sm"
                role="group"
                :aria-label="t('theme-preview')"
            >
                <button
                    v-for="option in ['light', 'dark'] as const"
                    :key="option"
                    type="button"
                    class="rounded px-3 py-1 font-semibold"
                    :class="
                        mode === option
                            ? 'bg-primary text-primary-foreground'
                            : 'text-muted-foreground'
                    "
                    :aria-pressed="mode === option"
                    @click="mode = option"
                >
                    {{ t(`theme-preview-${option}`) }}
                </button>
            </div>
        </div>

        <p
            v-if="error"
            role="status"
            class="rounded-md border-l-4 border-warning bg-warning-soft px-3 py-2 text-sm"
        >
            {{ t('theme-preview-error', { message: error }) }}
        </p>

        <div class="overflow-hidden rounded-lg border">
            <div
                data-theme-scope
                data-test="theme-preview"
                class="bg-background text-foreground"
                :style="style"
                inert
            >
                <!-- Header and A–Z strip, as on every page -->
                <div class="border-b bg-card px-4 pt-3 pb-2">
                    <div class="flex items-center gap-2.5">
                        <img
                            v-if="logo"
                            :src="logo"
                            alt=""
                            class="h-8 w-auto"
                        />
                        <span class="font-bold text-ink">{{
                            page.props.glossary.title
                        }}</span>
                        <span
                            class="ml-auto rounded-full border bg-background px-3 py-1 text-xs text-muted-foreground"
                            >{{ t('search') }}</span
                        >
                    </div>
                    <AlphabetNav
                        class="mt-3"
                        :theme="theme"
                        :letters="page.props.glossary.letters"
                        current="B"
                        :interactive="false"
                        compact
                    />
                </div>

                <div class="space-y-8 px-4 py-5">
                    <!-- Home page alphabet -->
                    <div>
                        <h3 class="mb-3 text-base font-bold text-ink">
                            {{ t('browse-glossary') }}
                        </h3>
                        <AlphabetNav
                            variant="home"
                            :theme="theme"
                            :letters="page.props.glossary.letters"
                            :interactive="false"
                            compact
                        />
                    </div>

                    <!-- A term page -->
                    <article v-if="term">
                        <p
                            class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            {{ t('theme-preview-sample') }}
                        </p>
                        <h3 class="mt-1 text-2xl font-bold text-ink">
                            {{ term.term }}
                        </h3>
                        <p
                            class="mt-2 text-lg"
                            :class="{
                                italic:
                                    settings.definition_style === 'italic-dash',
                            }"
                        >
                            <span
                                v-if="
                                    settings.definition_style === 'italic-dash'
                                "
                                aria-hidden="true"
                                >—&nbsp;</span
                            ><RichHtml as="span" :html="term.definition_html" />
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span
                                class="rounded-full bg-secondary px-2.5 py-0.5 text-xs font-semibold text-secondary-foreground"
                                >diagnosis</span
                            >
                            <span
                                class="rounded-[var(--radius)] bg-primary px-3 py-1 text-sm font-semibold text-primary-foreground"
                                >{{ t('edit') }}</span
                            >
                        </div>
                    </article>

                    <!-- A list of terms, with a search match -->
                    <ul v-if="sample.length" class="divide-y">
                        <li
                            v-for="(entry, index) in sample"
                            :key="entry.id"
                            class="py-3 first:pt-0"
                        >
                            <p class="text-lg leading-snug">
                                <span
                                    class="font-bold text-ink underline decoration-brand decoration-2 underline-offset-4"
                                >
                                    <mark v-if="index === 0">{{
                                        entry.term
                                    }}</mark>
                                    <template v-else>{{ entry.term }}</template>
                                </span>
                            </p>
                            <RichHtml
                                as="p"
                                class="mt-1 text-sm"
                                :html="entry.definition_html"
                            />
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
</template>
