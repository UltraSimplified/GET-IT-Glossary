<script setup lang="ts">
import InputError from '@/components/InputError.vue';

/**
 * Label, optional hint and error message around one form control. The
 * control in the slot receives the ids it should point at.
 *
 * `hintBelow` puts the hint under the control, so a field with a hint
 * lines up with a neighbour that has none.
 */
const {
    id,
    label,
    hint,
    error,
    hintBelow = false,
} = defineProps<{
    id: string;
    label: string;
    hint?: string;
    error?: string;
    hintBelow?: boolean;
}>();
</script>

<template>
    <!-- content-start: when a row of fields stretches, keep each one packed at the top. -->
    <div class="grid content-start gap-1.5">
        <label :for="id" class="text-sm font-bold text-foreground">{{
            label
        }}</label>
        <p
            v-if="hint && !hintBelow"
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
        <p
            v-if="hint && hintBelow"
            :id="`${id}-hint`"
            class="text-sm text-muted-foreground"
        >
            {{ hint }}
        </p>
    </div>
</template>
