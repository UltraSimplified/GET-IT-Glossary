<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Check, RotateCcw, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import type { IdgiReply } from '@/types';

/**
 * What readers said after pressing "I don't get it", for every version of
 * the term. Editors mark replies dealt with; administrators can delete
 * spam. Each action reloads only the replies, so unsaved changes in the
 * term form around this panel are kept.
 *
 * Replies are anonymous reader text, so they are shown as plain text only.
 */
const { replies } = defineProps<{
    replies: IdgiReply[];
}>();

const page = usePage();
const { t } = useTranslations();

const isAdmin = computed(() => Boolean(page.props.auth.user?.is_admin));
const fresh = computed(() => replies.filter((reply) => !reply.handled));
const handled = computed(() => replies.filter((reply) => reply.handled));

const busy = ref<number | null>(null);

const visit = {
    preserveState: true,
    preserveScroll: true,
    only: ['replies'],
    onFinish: () => (busy.value = null),
};

function mark(reply: IdgiReply, dealtWith: boolean) {
    busy.value = reply.id;
    router.patch(`/idgi-replies/${reply.id}`, { handled: dealtWith }, visit);
}

function destroy(reply: IdgiReply) {
    busy.value = reply.id;
    router.delete(`/idgi-replies/${reply.id}`, visit);
}
</script>

<template>
    <section
        class="space-y-3 rounded-lg border bg-card p-4"
        aria-labelledby="idgi-replies-title"
        data-test="idgi-replies"
    >
        <h2
            id="idgi-replies-title"
            class="flex flex-wrap items-center gap-2 text-sm font-bold"
        >
            {{ t('idgi-replies-title') }}
            <span
                v-if="fresh.length"
                class="rounded-full bg-warning-soft px-2 py-0.5 text-xs font-bold text-warning"
                >{{ t('idgi-replies-new', { count: fresh.length }) }}</span
            >
        </h2>

        <p v-if="!replies.length" class="text-sm text-muted-foreground">
            {{ t('idgi-replies-none') }}
        </p>

        <ul v-if="fresh.length" class="space-y-3">
            <li
                v-for="reply in fresh"
                :key="reply.id"
                class="rounded-md border bg-background p-3 text-sm"
            >
                <p class="break-words whitespace-pre-line">
                    {{ reply.message }}
                </p>
                <p class="mt-1.5 text-xs text-muted-foreground">
                    <time :datetime="reply.datetime ?? undefined">{{
                        reply.date
                    }}</time>
                    ·
                    {{ t('idgi-replies-version', { version: reply.version }) }}
                    <template v-if="reply.earlier"
                        >({{ t('idgi-replies-earlier') }})</template
                    >
                </p>
                <div class="mt-2 flex flex-wrap gap-2">
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        :disabled="busy === reply.id"
                        @click="mark(reply, true)"
                    >
                        <Check class="size-4" aria-hidden="true" />
                        {{ t('idgi-replies-mark-handled') }}
                    </Button>
                    <ConfirmDialog
                        v-if="isAdmin"
                        :title="t('idgi-replies-delete-title')"
                        :description="t('idgi-replies-delete-description')"
                        :confirm-label="t('idgi-replies-delete-confirm')"
                        @confirm="destroy(reply)"
                    >
                        <Button
                            type="button"
                            size="sm"
                            variant="ghost"
                            class="text-destructive hover:text-destructive"
                            :disabled="busy === reply.id"
                        >
                            <Trash2 class="size-4" aria-hidden="true" />
                            {{ t('idgi-replies-delete') }}
                        </Button>
                    </ConfirmDialog>
                </div>
            </li>
        </ul>

        <details v-if="handled.length" class="text-sm">
            <summary class="cursor-pointer font-semibold text-ink">
                {{ t('idgi-replies-handled', { count: handled.length }) }}
            </summary>
            <ul class="mt-3 space-y-3">
                <li
                    v-for="reply in handled"
                    :key="reply.id"
                    class="rounded-md border p-3 text-muted-foreground"
                >
                    <p class="break-words whitespace-pre-line text-foreground">
                        {{ reply.message }}
                    </p>
                    <p class="mt-1.5 text-xs">
                        <time :datetime="reply.datetime ?? undefined">{{
                            reply.date
                        }}</time>
                        ·
                        {{
                            t('idgi-replies-version', {
                                version: reply.version,
                            })
                        }}
                        <template v-if="reply.earlier"
                            >({{ t('idgi-replies-earlier') }})</template
                        >
                    </p>
                    <p class="mt-1 text-xs">
                        {{
                            t('idgi-replies-handled-by', {
                                name: reply.handled?.by ?? '',
                                date: reply.handled?.date ?? '',
                            })
                        }}
                    </p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            :disabled="busy === reply.id"
                            @click="mark(reply, false)"
                        >
                            <RotateCcw class="size-4" aria-hidden="true" />
                            {{ t('idgi-replies-mark-new') }}
                        </Button>
                        <ConfirmDialog
                            v-if="isAdmin"
                            :title="t('idgi-replies-delete-title')"
                            :description="t('idgi-replies-delete-description')"
                            :confirm-label="t('idgi-replies-delete-confirm')"
                            @confirm="destroy(reply)"
                        >
                            <Button
                                type="button"
                                size="sm"
                                variant="ghost"
                                class="text-destructive hover:text-destructive"
                                :disabled="busy === reply.id"
                            >
                                <Trash2 class="size-4" aria-hidden="true" />
                                {{ t('idgi-replies-delete') }}
                            </Button>
                        </ConfirmDialog>
                    </div>
                </li>
            </ul>
        </details>
    </section>
</template>
