<script setup lang="ts">
import { Link2 } from '@lucide/vue';
import { computed, nextTick, ref, useId, useTemplateRef } from 'vue';
import { filterTermNames, loadTermNames } from '@/lib/termNames';

/**
 * "Link a term" button. Opens a searchable list of glossary terms and
 * emits the one chosen; the field it sits on inserts the [term] link.
 */
const emit = defineEmits<{ pick: [term: string]; open: [] }>();

const listId = useId();
const open = ref(false);
const query = ref('');
const names = ref<string[]>([]);
const active = ref(0);
const failed = ref(false);
const input = useTemplateRef<HTMLInputElement>('input');

const matches = computed(() => filterTermNames(names.value, query.value));

async function toggle() {
    if (open.value) {
        close();

        return;
    }

    // Let the host remember the cursor position before focus moves here.
    emit('open');
    open.value = true;
    query.value = '';
    active.value = 0;
    await nextTick();
    input.value?.focus();

    try {
        names.value = await loadTermNames();
        failed.value = false;
    } catch {
        failed.value = true;
    }
}

function close() {
    open.value = false;
}

function choose(name: string | undefined) {
    if (!name) {
        return;
    }

    close();
    emit('pick', name);
}

function move(step: number) {
    const count = matches.value.length;

    if (count) {
        active.value = (active.value + step + count) % count;
        document
            .getElementById(`${listId}-${active.value}`)
            ?.scrollIntoView({ block: 'nearest' });
    }
}

function onFocusOut(event: FocusEvent) {
    const container = event.currentTarget as HTMLElement;

    if (!container.contains(event.relatedTarget as Node | null)) {
        close();
    }
}
</script>

<template>
    <div class="relative" @focusout="onFocusOut" @keydown.esc.stop="close">
        <button
            type="button"
            class="inline-flex h-8 items-center gap-1.5 rounded-md px-2.5 text-sm font-semibold text-ink hover:bg-brand-soft"
            :aria-expanded="open"
            @mousedown.prevent
            @click="toggle"
        >
            <Link2 class="size-4" aria-hidden="true" />
            Link a term
        </button>

        <div
            v-if="open"
            class="absolute top-full left-0 z-30 mt-1 w-72 rounded-lg border bg-popover p-2 text-popover-foreground shadow-lg shadow-primary/10"
        >
            <input
                ref="input"
                v-model="query"
                type="search"
                role="combobox"
                :aria-controls="listId"
                :aria-activedescendant="
                    matches.length ? `${listId}-${active}` : undefined
                "
                aria-expanded="true"
                aria-label="Find a term to link"
                placeholder="Find a term"
                autocomplete="off"
                class="h-9 w-full rounded-md border border-input bg-card px-2.5 text-sm"
                @input="active = 0"
                @keydown.down.prevent="move(1)"
                @keydown.up.prevent="move(-1)"
                @keydown.enter.prevent="choose(matches[active])"
            />
            <p v-if="failed" class="px-1 pt-2 text-sm text-destructive">
                The term list could not be loaded.
            </p>
            <ul
                v-else
                :id="listId"
                role="listbox"
                class="mt-1.5 max-h-60 overflow-auto text-sm"
            >
                <li
                    v-for="(name, index) in matches"
                    :id="`${listId}-${index}`"
                    :key="name"
                    role="option"
                    :aria-selected="index === active"
                    class="cursor-pointer rounded px-2 py-1.5"
                    :class="
                        index === active
                            ? 'bg-primary text-primary-foreground'
                            : 'hover:bg-accent'
                    "
                    @mousedown.prevent="choose(name)"
                    @mousemove="active = index"
                >
                    {{ name }}
                </li>
                <li
                    v-if="names.length && !matches.length"
                    class="px-2 py-1.5 text-muted-foreground"
                >
                    No term matches “{{ query }}”.
                </li>
            </ul>
        </div>
    </div>
</template>
