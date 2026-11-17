<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { computed, ref, useId, watch } from 'vue';
import { useTranslations } from '@/composables/useTranslations';
import { getJson, slugify } from '@/lib/http';

/**
 * Search field with suggestions. Choosing a suggestion opens that term;
 * pressing Enter on what was typed runs a full search.
 */
const { size = 'compact', autofocus = false } = defineProps<{
    size?: 'compact' | 'large';
    autofocus?: boolean;
}>();

type Suggestion = { id: number; name: string; path: string };

const { t } = useTranslations();
const listId = useId();

const query = ref('');
const suggestions = ref<Suggestion[]>([]);
const active = ref(-1);
const open = ref(false);

let timer: ReturnType<typeof setTimeout> | undefined;
let controller: AbortController | undefined;

const expanded = computed(() => open.value && suggestions.value.length > 0);

watch(query, (value) => {
    clearTimeout(timer);
    controller?.abort();
    active.value = -1;

    const text = value.trim();

    if (text.length < 2) {
        suggestions.value = [];

        return;
    }

    timer = setTimeout(async () => {
        controller = new AbortController();

        try {
            suggestions.value = await getJson<Suggestion[]>(
                `/ajax/terms?query=${encodeURIComponent(text)}`,
                controller.signal,
            );
            open.value = true;
        } catch {
            // Suggestions are a convenience; searching still works without them.
        }
    }, 140);
});

function close() {
    open.value = false;
    active.value = -1;
}

function move(step: number) {
    if (!suggestions.value.length) {
        return;
    }

    open.value = true;
    const count = suggestions.value.length;
    // -1 is "nothing chosen": the text field itself.
    active.value = ((active.value + 1 + step + (count + 1)) % (count + 1)) - 1;
}

function go(suggestion: Suggestion) {
    close();
    query.value = '';
    router.visit(suggestion.path);
}

function submit() {
    const chosen = suggestions.value[active.value];

    if (expanded.value && chosen) {
        go(chosen);

        return;
    }

    const text = query.value.trim();

    if (text) {
        close();
        router.visit(`/search/${slugify(text)}`);
    }
}
</script>

<template>
    <form
        role="search"
        class="relative"
        action="/search"
        method="post"
        @submit.prevent="submit"
        @focusout="close"
    >
        <label :for="`${listId}-input`" class="sr-only">{{
            t('search')
        }}</label>
        <div class="relative">
            <input
                :id="`${listId}-input`"
                v-model="query"
                v-focus="autofocus"
                type="search"
                name="query"
                role="combobox"
                autocomplete="off"
                autocapitalize="none"
                spellcheck="false"
                :aria-expanded="expanded"
                :aria-controls="listId"
                :aria-activedescendant="
                    active >= 0 ? `${listId}-${active}` : undefined
                "
                aria-autocomplete="list"
                :placeholder="t('search')"
                class="w-full rounded-full border-2 border-input bg-card text-foreground placeholder:text-muted-foreground focus-visible:border-primary focus-visible:ring-4 focus-visible:ring-ring/35 focus-visible:outline-none [&::-webkit-search-cancel-button]:hidden"
                :class="
                    size === 'large'
                        ? 'h-16 pr-16 pl-6 text-lg'
                        : 'h-11 pr-12 pl-4 text-base'
                "
                @focus="open = true"
                @keydown.down.prevent="move(1)"
                @keydown.up.prevent="move(-1)"
                @keydown.esc="close"
            />
            <button
                type="submit"
                class="absolute top-1/2 grid -translate-y-1/2 place-items-center rounded-full bg-primary text-primary-foreground hover:bg-primary/90"
                :class="
                    size === 'large' ? 'right-2 size-12' : 'right-1.5 size-8'
                "
                :aria-label="t('search')"
            >
                <Search
                    :class="size === 'large' ? 'size-5' : 'size-4'"
                    aria-hidden="true"
                />
            </button>
        </div>

        <ul
            v-show="expanded"
            :id="listId"
            role="listbox"
            class="absolute inset-x-0 top-full z-40 mt-2 max-h-80 overflow-auto rounded-lg border bg-popover py-1 text-popover-foreground shadow-lg shadow-primary/10"
            :class="size === 'large' ? 'text-lg' : 'text-base'"
        >
            <li
                v-for="(suggestion, index) in suggestions"
                :id="`${listId}-${index}`"
                :key="suggestion.id"
                role="option"
                :aria-selected="index === active"
                class="cursor-pointer px-4 py-2"
                :class="
                    index === active
                        ? 'bg-primary text-primary-foreground'
                        : 'hover:bg-accent'
                "
                @mousedown.prevent="go(suggestion)"
                @mousemove="active = index"
            >
                {{ suggestion.name }}
            </li>
        </ul>
    </form>
</template>
