<script setup lang="ts">
import { nextTick, useTemplateRef } from 'vue';
import TermPicker from '@/components/admin/TermPicker.vue';

/**
 * A plain text field that understands the glossary's [term] links.
 * "Link a term" inserts [term] at the cursor, or [selected words|term]
 * when words are selected.
 */
const model = defineModel<string | null>({ required: true });

const { id, rows = 3 } = defineProps<{
    id: string;
    rows?: number;
    invalid?: boolean;
    describedby?: string;
}>();

const textarea = useTemplateRef<HTMLTextAreaElement>('textarea');
let selection = { start: 0, end: 0 };

function remember() {
    const el = textarea.value;

    if (el) {
        selection = { start: el.selectionStart, end: el.selectionEnd };
    }
}

async function insert(term: string) {
    const text = model.value ?? '';
    const { start, end } = selection;
    const selected = text.slice(start, end).trim();
    const link =
        selected && selected.toLowerCase() !== term.toLowerCase()
            ? `[${selected}|${term}]`
            : `[${term}]`;

    model.value = text.slice(0, start) + link + text.slice(end);

    await nextTick();
    const caret = start + link.length;
    textarea.value?.focus();
    textarea.value?.setSelectionRange(caret, caret);
}
</script>

<template>
    <div
        class="rounded-md border border-input bg-card focus-within:border-primary focus-within:ring-4 focus-within:ring-ring/30"
        :class="{ 'border-destructive': invalid }"
    >
        <div class="flex items-center border-b px-1.5 py-1">
            <TermPicker @open="remember" @pick="insert" />
        </div>
        <textarea
            :id="id"
            ref="textarea"
            v-model="model"
            :rows="rows"
            :aria-invalid="invalid || undefined"
            :aria-describedby="describedby"
            class="block w-full resize-y rounded-b-md bg-transparent px-3 py-2 text-base outline-none"
            @blur="remember"
        ></textarea>
    </div>
</template>
