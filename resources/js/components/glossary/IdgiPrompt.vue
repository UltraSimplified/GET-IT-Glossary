<script setup lang="ts">
import { ref } from 'vue';
import { useTranslations } from '@/composables/useTranslations';
import { postJson } from '@/lib/http';

/**
 * "Still don't get it?" Readers press the monkey to tell the editors a
 * definition did not help. Each press is counted against the term.
 */
const { termId } = defineProps<{
    termId: number;
}>();

const { t } = useTranslations();

const state = ref<'idle' | 'sending' | 'thanks'>('idle');
const hovering = ref(false);

async function send() {
    if (state.value !== 'idle') {
        return;
    }

    state.value = 'sending';

    try {
        await postJson(`/idgi/${termId}`);
        state.value = 'thanks';
    } catch {
        state.value = 'idle';
    }
}
</script>

<template>
    <section class="rounded-lg border bg-card p-5" aria-labelledby="idgi-title">
        <h2 id="idgi-title" class="text-lg font-bold text-ink">
            {{ t('idgi-title') }}
        </h2>
        <p class="mt-1 text-sm text-muted-foreground" aria-live="polite">
            {{ state === 'thanks' ? t('idgi-thanks') : t('idgi-body') }}
        </p>

        <!-- The artwork is drawn on white, so it keeps a white panel in dark mode too. -->
        <button
            type="button"
            class="mt-4 block w-full max-w-60 rounded-md bg-white p-2 disabled:cursor-default"
            :class="
                state === 'idle'
                    ? 'cursor-pointer hover:ring-2 hover:ring-ring'
                    : ''
            "
            :disabled="state !== 'idle'"
            :aria-label="t('idgi-button')"
            data-test="idgi-button"
            @click="send"
            @mouseenter="hovering = true"
            @mouseleave="hovering = false"
            @focus="hovering = true"
            @blur="hovering = false"
        >
            <img
                :src="
                    state === 'thanks'
                        ? '/assets/img/IDGI/IDGI-thanks.png'
                        : hovering
                          ? '/assets/img/IDGI/IDGI-click.png'
                          : '/assets/img/IDGI/IDGI-off.png'
                "
                alt=""
                width="480"
                height="324"
                class="h-auto w-full"
            />
        </button>
    </section>
</template>
