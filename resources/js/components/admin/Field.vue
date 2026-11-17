<script setup lang="ts">
import InputError from '@/components/InputError.vue';

/**
 * Label, optional hint and error message around one form control. The
 * control in the slot receives the ids it should point at.
 */
const { id, label, hint, error } = defineProps<{
    id: string;
    label: string;
    hint?: string;
    error?: string;
}>();
</script>

<template>
    <div class="grid gap-1.5">
        <label :for="id" class="text-sm font-bold text-foreground">{{
            label
        }}</label>
        <p
            v-if="hint"
            :id="`${id}-hint`"
            class="-mt-1 text-sm text-muted-foreground"
        >
            {{ hint }}
        </p>
        <slot
            :describedby="hint ? `${id}-hint` : undefined"
            :invalid="Boolean(error)"
        />
        <InputError :message="error" />
    </div>
</template>
