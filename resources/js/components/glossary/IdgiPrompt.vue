<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, nextTick, ref, useTemplateRef } from 'vue';
import IdgiBubble from '@/components/glossary/IdgiBubble.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useTranslations } from '@/composables/useTranslations';
import { HttpError, postJson } from '@/lib/http';

/**
 * "Still don't get it?" Readers press the button to tell the editors a
 * definition did not help. Each press is counted against the term
 * version, then an optional form asks what didn't make sense. Replies
 * are anonymous: only the words are sent.
 *
 * The button shows the site's own pictures when it has them (set on the
 * Site screen), and otherwise the built-in speech bubbles.
 */
const { termId } = defineProps<{
    termId: number;
}>();

const { t } = useTranslations();
const page = usePage();

const MAX_LENGTH = 1000;

type State = 'idle' | 'sending' | 'thanks' | 'replying' | 'replied';

const state = ref<State>('idle');
const hovering = ref(false);
const message = ref('');
const website = ref('');
const problem = ref<string | null>(null);
const box = useTemplateRef<HTMLTextAreaElement>('box');

const counted = computed(
    () => state.value !== 'idle' && state.value !== 'sending',
);
const charactersLeft = computed(() => MAX_LENGTH - message.value.length);

/** Which picture the button shows now: at rest, on hover or after the click. */
const pictureState = computed(() =>
    counted.value ? 'thanks' : hovering.value ? 'hover' : 'rest',
);

const pictures = computed(() => page.props.glossary.idgi);

async function send() {
    if (state.value !== 'idle') {
        return;
    }

    state.value = 'sending';

    try {
        await postJson(`/idgi/${termId}`);
        state.value = 'thanks';

        // The button is now disabled, so keyboard users carry on in the form.
        await nextTick();
        box.value?.focus();
    } catch {
        state.value = 'idle';
    }
}

async function reply() {
    if (state.value !== 'thanks') {
        return;
    }

    problem.value = null;
    state.value = 'replying';

    try {
        await postJson(`/idgi/${termId}/reply`, {
            message: message.value,
            website: website.value,
        });
        state.value = 'replied';
    } catch (failure) {
        const text =
            failure instanceof Error ? failure.message : String(failure);

        // A validation message says what to fix on its own.
        problem.value =
            failure instanceof HttpError && failure.status === 422
                ? text
                : t('idgi-reply-failed', { message: text });

        // Without a click to go with it (the session expired, say), the
        // button has to be pressed again. The words typed are kept.
        state.value =
            failure instanceof HttpError && failure.status === 403
                ? 'idle'
                : 'thanks';
    }
}
</script>

<template>
    <section class="rounded-lg border bg-card p-5" aria-labelledby="idgi-title">
        <h2 id="idgi-title" class="text-lg font-bold text-ink">
            {{ t('idgi-title') }}
        </h2>
        <p class="mt-1 text-sm text-muted-foreground" aria-live="polite">
            {{ counted ? t('idgi-thanks') : t('idgi-body') }}
        </p>

        <!-- Uploaded pictures are usually drawn on white, so they keep a white
             panel in dark mode too; the built-in bubbles use the theme's colours. -->
        <button
            type="button"
            class="mt-4 block w-full max-w-60 rounded-md disabled:cursor-default"
            :class="[
                pictures ? 'bg-white p-2' : 'p-1',
                state === 'idle'
                    ? 'cursor-pointer hover:ring-2 hover:ring-ring'
                    : '',
            ]"
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
                v-if="pictures"
                :src="pictures[pictureState]"
                alt=""
                width="480"
                height="324"
                class="h-auto w-full"
            />
            <IdgiBubble v-else :state="pictureState" />
        </button>

        <!-- After the click: an optional note about what didn't make sense. -->
        <form
            v-if="state === 'thanks' || state === 'replying'"
            class="mt-5 grid gap-2"
            novalidate
            data-test="idgi-reply"
            @submit.prevent="reply"
        >
            <label :for="`idgi-reply-${termId}`" class="text-sm font-bold">
                {{ t('idgi-reply-label') }}
                <span class="font-normal text-muted-foreground"
                    >({{ t('idgi-reply-optional').toLowerCase() }})</span
                >
            </label>
            <p
                :id="`idgi-reply-${termId}-privacy`"
                class="-mt-1 text-sm text-muted-foreground"
            >
                {{ t('idgi-reply-privacy') }}
            </p>
            <textarea
                :id="`idgi-reply-${termId}`"
                ref="box"
                v-model="message"
                rows="4"
                :maxlength="MAX_LENGTH"
                :aria-describedby="`idgi-reply-${termId}-privacy idgi-reply-${termId}-left`"
                :aria-invalid="Boolean(problem)"
                class="block w-full rounded-md border border-input bg-background px-3 py-2 text-sm aria-invalid:border-destructive"
            ></textarea>
            <!-- Left empty by people; filled in only by bots. -->
            <input
                v-model="website"
                type="text"
                name="website"
                tabindex="-1"
                autocomplete="off"
                aria-hidden="true"
                class="sr-only"
            />
            <div class="flex items-center justify-between gap-3">
                <span
                    :id="`idgi-reply-${termId}-left`"
                    class="text-xs text-muted-foreground"
                    >{{ t('idgi-reply-left', { count: charactersLeft }) }}</span
                >
                <Button
                    type="submit"
                    size="sm"
                    :disabled="state === 'replying'"
                >
                    <Spinner v-if="state === 'replying'" />
                    {{ t('idgi-reply-send') }}
                </Button>
            </div>
        </form>

        <p
            v-if="problem"
            role="alert"
            class="mt-3 text-sm text-destructive"
            data-test="idgi-reply-problem"
        >
            {{ problem }}
        </p>

        <p
            v-if="state === 'replied'"
            role="status"
            class="mt-5 rounded-md bg-success-soft px-3 py-2 text-sm text-success"
            data-test="idgi-reply-thanks"
        >
            {{ t('idgi-reply-thanks') }}
        </p>
    </section>
</template>
