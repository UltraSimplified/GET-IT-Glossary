<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTranslations } from '@/composables/useTranslations';
import { readableTextOn } from '@/lib/contrast';
import type { ThemeShared } from '@/types/glossary';

/**
 * The A–Z index. Letters with no published terms are muted and are not
 * links; the letter the current page belongs to is marked in the theme's
 * letter shape.
 *
 * `strip` sits under the site header. `home` is the large alphabet on the
 * home page, laid out as the theme says: one row, a grid or a honeycomb.
 * The theme editor's preview passes its own theme and letters, turns the
 * links off so a click cannot leave the form, and asks for the `compact`
 * size, which fits a narrow column whatever the screen width.
 */
const {
    variant = 'strip',
    theme: themeOverride,
    letters: lettersOverride,
    current: currentOverride,
    interactive = true,
    compact = false,
} = defineProps<{
    variant?: 'strip' | 'home';
    theme?: ThemeShared;
    letters?: string[];
    current?: string | null;
    interactive?: boolean;
    compact?: boolean;
}>();

const page = usePage();
const { t } = useTranslations();

const theme = computed(() => themeOverride ?? page.props.theme);
const lettersInUse = computed(
    () => lettersOverride ?? page.props.glossary.letters,
);

const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('');

const current = computed(() =>
    (currentOverride ?? page.props.letter ?? '').toUpperCase(),
);

const layout = computed(() =>
    variant === 'strip' ? 'strip' : theme.value.home_alphabet,
);

type Letter = {
    letter: string;
    href: string;
    available: boolean;
    isCurrent: boolean;
    /** Fill and text colour when letters take the accents in turn. */
    style: Record<string, string> | undefined;
};

const letters = computed<Letter[]>(() => {
    const inUse = new Set(lettersInUse.value);
    // Editions in other languages have letters beyond A–Z (Æ, Ø, Å…).
    const extra = lettersInUse.value.filter(
        (letter) => !alphabet.includes(letter) && /\p{L}/u.test(letter),
    );
    const accents = theme.value.accents;
    const cycle = theme.value.letter_colours === 'cycle' && accents.length > 1;

    return [...alphabet, ...extra].map((letter, index) => {
        const available = inUse.has(letter);
        const isCurrent = current.value === letter;
        const accent = accents[index % accents.length];

        return {
            letter,
            href: `/listing/${encodeURIComponent(letter.toLowerCase())}`,
            available,
            isCurrent,
            style:
                cycle && available && !isCurrent
                    ? { backgroundColor: accent, color: readableTextOn(accent) }
                    : undefined,
        };
    });
});

const cycling = computed(
    () =>
        theme.value.letter_colours === 'cycle' &&
        theme.value.accents.length > 1,
);

/** Classes for a letter that has terms, by layout. */
const availableClass = computed(() => {
    if (cycling.value) {
        return 'transition-[filter] hover:brightness-110 focus-visible:brightness-110';
    }

    switch (layout.value) {
        case 'honeycomb':
            return 'bg-primary text-primary-foreground transition-colors hover:bg-brand focus-visible:bg-brand';
        case 'grid':
            return 'bg-secondary text-ink transition-colors hover:bg-brand-soft';
        default:
            return 'text-ink transition-colors hover:bg-brand-soft';
    }
});

const unavailableClass = computed(() =>
    layout.value === 'honeycomb' || layout.value === 'grid'
        ? 'bg-muted text-muted-foreground/70'
        : 'text-muted-foreground/60',
);

function letterClass(item: Letter) {
    if (!item.available) {
        return unavailableClass.value;
    }

    return item.isCurrent ? 'letter-token-current' : availableClass.value;
}

function linkProps(item: Letter) {
    return interactive && item.available
        ? { href: item.href, prefetch: true }
        : {};
}

function animationDelay(index: number) {
    return { animationDelay: `${index * 22}ms` };
}

// Honeycomb rows: 13 + 13 on wide screens, 7 + 6 + 7 + 6 on phones.
function rows(pattern: number[]) {
    const result: Letter[][] = [];
    let index = 0;
    let step = 0;

    while (index < letters.value.length) {
        const size = pattern[step % pattern.length];
        result.push(letters.value.slice(index, index + size));
        index += size;
        step++;
    }

    return result;
}

// Phones get the narrow honeycomb, wider screens the wide one; compact has only the narrow.
const honeycombLayouts = computed(() =>
    compact
        ? [{ rows: rows([7, 6]), class: '' }]
        : [
              { rows: rows([7, 6]), class: 'md:hidden' },
              { rows: rows([13]), class: 'hidden md:block' },
          ],
);

/** Sizes for each layout, normal or compact. */
const sizes = computed(() =>
    compact
        ? {
              strip: 'grid grid-cols-9 gap-y-1',
              stripLetter: 'w-8 text-sm',
              rowLetter: 'w-9 text-lg',
              grid: 'grid grid-cols-7 gap-1.5',
              gridLetter: 'text-lg',
              honeycomb: { '--cell': '2.4rem' },
          }
        : {
              strip: 'grid grid-cols-9 gap-y-1 sm:grid-cols-13 lg:flex lg:justify-between',
              stripLetter: 'w-9 text-base',
              rowLetter: 'w-11 text-xl md:w-12 md:text-2xl',
              grid: 'grid max-w-3xl grid-cols-7 gap-2 sm:grid-cols-9 lg:grid-cols-13',
              gridLetter: 'text-xl md:text-2xl',
              honeycomb: undefined,
          },
);
</script>

<template>
    <nav :aria-label="t('browse-glossary')">
        <!-- Compact strip under the site header -->
        <ul v-if="layout === 'strip'" :class="sizes.strip">
            <li
                v-for="item in letters"
                :key="item.letter"
                class="flex justify-center"
            >
                <component
                    :is="item.available && interactive ? Link : 'span'"
                    v-bind="linkProps(item)"
                    :aria-current="item.isCurrent ? 'page' : undefined"
                    :aria-disabled="item.available ? undefined : 'true'"
                    class="letter-token grid place-items-center"
                    :class="[
                        sizes.stripLetter,
                        letterClass(item),
                        { 'font-bold': item.available },
                    ]"
                    :style="item.style"
                >
                    {{ item.letter }}
                </component>
            </li>
        </ul>

        <!-- Home page: the letters in one row that wraps on narrow screens -->
        <ul
            v-else-if="layout === 'row'"
            class="flex flex-wrap gap-x-1.5 gap-y-2"
        >
            <li v-for="(item, index) in letters" :key="item.letter">
                <component
                    :is="item.available && interactive ? Link : 'span'"
                    v-bind="linkProps(item)"
                    :aria-disabled="item.available ? undefined : 'true'"
                    class="letter-token grid animate-letter-in place-items-center"
                    :class="[
                        sizes.rowLetter,
                        letterClass(item),
                        { 'font-bold': item.available },
                    ]"
                    :style="[item.style, animationDelay(index)]"
                >
                    {{ item.letter }}
                </component>
            </li>
        </ul>

        <!-- Home page: the letters as tiles in a grid -->
        <ul v-else-if="layout === 'grid'" :class="sizes.grid">
            <li v-for="(item, index) in letters" :key="item.letter">
                <component
                    :is="item.available && interactive ? Link : 'span'"
                    v-bind="linkProps(item)"
                    :aria-disabled="item.available ? undefined : 'true'"
                    class="letter-token grid w-full animate-letter-in place-items-center"
                    :class="[
                        sizes.gridLetter,
                        letterClass(item),
                        { 'font-bold': item.available },
                    ]"
                    :style="[item.style, animationDelay(index)]"
                >
                    {{ item.letter }}
                </component>
            </li>
        </ul>

        <!-- Home page: the alphabet as a honeycomb -->
        <template v-else>
            <div
                v-for="(honeycomb, layoutIndex) in honeycombLayouts"
                :key="layoutIndex"
                :class="honeycomb.class"
                :style="sizes.honeycomb"
                class="honeycomb"
            >
                <ul
                    v-for="(row, rowIndex) in honeycomb.rows"
                    :key="rowIndex"
                    class="honeycomb-row flex"
                    :class="{ 'honeycomb-row-offset': rowIndex % 2 === 1 }"
                >
                    <li v-for="(item, index) in row" :key="item.letter">
                        <component
                            :is="item.available && interactive ? Link : 'span'"
                            v-bind="linkProps(item)"
                            :aria-disabled="item.available ? undefined : 'true'"
                            class="letter-token honeycomb-cell animate-letter-in"
                            :class="[
                                letterClass(item),
                                { 'font-bold': item.available },
                            ]"
                            :style="[
                                item.style,
                                animationDelay(rowIndex * 4 + index),
                            ]"
                        >
                            {{ item.letter }}
                        </component>
                    </li>
                </ul>
            </div>
        </template>
    </nav>
</template>

<style scoped>
/*
 * Pointy-top hexagons (and circles) interlock when each row is shifted
 * half a cell sideways and rows sit 0.866 of a cell's width apart.
 */
.honeycomb {
    --cell: clamp(2.6rem, 12.4vw, 4.25rem);
    --gap: 0.3rem;
}

.honeycomb-row {
    gap: var(--gap);
}

.honeycomb-row + .honeycomb-row {
    margin-top: calc(
        var(--cell) * (0.866 - var(--letter-ratio, 1.1547)) + var(--gap)
    );
}

.honeycomb-row-offset {
    margin-left: calc((var(--cell) + var(--gap)) / 2);
}

.honeycomb-cell {
    display: grid;
    place-items: center;
    width: var(--cell);
    height: calc(var(--cell) * var(--letter-ratio, 1.1547));
    font-size: calc(var(--cell) * 0.42);
    line-height: 1;
}

@media (min-width: 48rem) {
    .honeycomb {
        --cell: clamp(3rem, 6.1vw, 4.5rem);
    }
}
</style>
