<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { getInitials } from '@/composables/useInitials';
import { useTranslations } from '@/composables/useTranslations';
import type { TermComment } from '@/types';

defineProps<{
    comments: TermComment[];
}>();

const { t } = useTranslations();

function destroy(comment: TermComment) {
    router.delete(`/comments/${comment.id}`, { preserveScroll: true });
}
</script>

<template>
    <ul class="space-y-5">
        <li
            v-for="comment in comments"
            :id="`comment-${comment.id}`"
            :key="comment.id"
            class="grid grid-cols-[2.5rem_1fr] gap-x-3"
        >
            <span
                class="letter-token grid w-10 place-items-center bg-brand-soft text-sm font-bold text-ink"
                aria-hidden="true"
            >
                {{ getInitials(comment.author_name) }}
            </span>
            <div>
                <p class="flex flex-wrap items-baseline gap-x-3 text-sm">
                    <span class="font-bold">{{ comment.author_name }}</span>
                    <span class="text-muted-foreground">{{
                        comment.date
                    }}</span>
                </p>
                <!-- Comments are plain text: shown exactly as typed, never as HTML. -->
                <p class="mt-1 whitespace-pre-line">{{ comment.comment }}</p>

                <ConfirmDialog
                    v-if="comment.can_delete"
                    :title="t('ok-delete')"
                    :confirm-label="t('delete-comment')"
                    @confirm="destroy(comment)"
                >
                    <button
                        type="button"
                        class="mt-1.5 inline-flex items-center gap-1.5 text-sm text-muted-foreground underline underline-offset-4 hover:text-destructive"
                    >
                        <Trash2 class="size-3.5" aria-hidden="true" />
                        {{ t('delete-comment') }}
                    </button>
                </ConfirmDialog>
            </div>
        </li>
    </ul>
</template>
