<script setup lang="ts">
import InputError from '@/components/InputError.vue';

/**
 * One theme colour: a colour picker and the same value as text, for
 * typing or pasting an exact #rrggbb.
 */
defineProps<{
    id: string;
    label: string;
    error?: string;
}>();

const colour = defineModel<string>({ required: true });

const HEX = /^#[0-9a-f]{6}$/i;
</script>

<template>
    <div class="grid gap-1">
        <label :for="id" class="text-sm font-semibold">{{ label }}</label>
        <div class="flex items-center gap-2">
            <input
                type="color"
                :value="HEX.test(colour) ? colour.toLowerCase() : '#000000'"
                :aria-label="label"
                class="h-9 w-11 shrink-0 cursor-pointer rounded-md border border-input bg-card p-0.5"
                @input="colour = ($event.target as HTMLInputElement).value"
            />
            <input
                :id="id"
                v-model.trim="colour"
                type="text"
                spellcheck="false"
                autocomplete="off"
                maxlength="7"
                :aria-invalid="Boolean(error)"
                class="h-9 w-full min-w-0 rounded-md border border-input bg-card px-2 font-mono text-sm aria-invalid:border-destructive"
            />
        </div>
        <InputError :message="error" />
    </div>
</template>
