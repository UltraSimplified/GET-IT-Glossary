<script setup lang="ts">
import { computed } from 'vue';
import { useTranslations } from '@/composables/useTranslations';
import type { IdgiPictureSlot } from '@/types';

/**
 * The built-in "I don't get it" picture: a speech bubble drawn in the
 * theme's colours and heading font, with words from the interface
 * language. It keeps the 480 × 324 proportions of an uploaded picture,
 * so the box around it never changes size. Purely decorative: the button
 * it sits in carries the accessible name.
 */
const { state = 'rest' } = defineProps<{
    state?: IdgiPictureSlot;
}>();

const { t } = useTranslations();

const words = computed(() => t(`idgi-bubble-${state}`));

// Longer translations get smaller type so they still fit inside.
const size = computed(() => {
    const length = words.value.length;

    if (length > 34) {
        return 'is-small';
    }

    return length > 18 ? 'is-medium' : '';
});
</script>

<template>
    <span class="idgi-bubble" :data-state="state" aria-hidden="true">
        <span class="idgi-bubble-body">
            <span class="idgi-bubble-words" :class="size">{{ words }}</span>
        </span>
        <svg
            class="idgi-bubble-tail"
            viewBox="0 0 64 56"
            preserveAspectRatio="none"
            focusable="false"
        >
            <!-- The fill reaches up over the bubble's border, so the two join without a seam. -->
            <path d="M6 -6 L46 -6 L14 52 Z" class="idgi-bubble-tail-fill" />
            <path d="M8 0 L14 52 L44 0" class="idgi-bubble-tail-line" />
        </svg>
    </span>
</template>

<style scoped>
.idgi-bubble {
    --bubble-fill: var(--card);
    --bubble-line: var(--ink);

    position: relative;
    display: block;
    width: 100%;
    aspect-ratio: 480 / 324;
    container-type: inline-size;
}

.idgi-bubble[data-state='hover'] {
    --bubble-fill: var(--brand-soft);
}

.idgi-bubble[data-state='thanks'] {
    --bubble-fill: var(--success-soft);
}

.idgi-bubble-body {
    position: absolute;
    inset: 3% 3% 27% 3%;
    display: grid;
    place-items: center;
    padding: 4cqi 8cqi;
    border: max(2px, 1cqi) solid var(--bubble-line);
    border-radius: 50% / 46%;
    background: var(--bubble-fill);
    text-align: center;
    transition: background-color 0.15s ease;
}

.idgi-bubble-words {
    font-family: var(--font-headings);
    font-weight: 700;
    font-size: 11cqi;
    line-height: 1.1;
    color: var(--bubble-line);
    overflow-wrap: anywhere;
}

.idgi-bubble-words.is-medium {
    font-size: 8.5cqi;
}

.idgi-bubble-words.is-small {
    font-size: 6.5cqi;
}

.idgi-bubble-tail {
    position: absolute;
    left: 24%;
    /* Starts at the bubble's bottom edge, behind its border. */
    top: calc(73% - max(2px, 1cqi));
    width: 13%;
    height: 24%;
    overflow: visible;
}

.idgi-bubble-tail-fill {
    fill: var(--bubble-fill);
    transition: fill 0.15s ease;
}

.idgi-bubble-tail-line {
    fill: none;
    stroke: var(--bubble-line);
    stroke-width: max(2px, 1cqi);
    stroke-linejoin: round;
    vector-effect: non-scaling-stroke;
}
</style>
