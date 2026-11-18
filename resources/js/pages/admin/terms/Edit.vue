<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ExternalLink } from '@lucide/vue';
import AdminNav from '@/components/admin/AdminNav.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import TermForm from '@/components/admin/TermForm.vue';
import { useTranslations } from '@/composables/useTranslations';
import type {
    EditableTerm,
    EditorChoices,
    IdgiReply,
    TermStatus,
    VersionMeta,
} from '@/types';

defineOptions({ layout: { width: 'wide' } });

defineProps<{
    term: EditableTerm;
    meta: VersionMeta;
    tagOptions: string[];
    statuses: TermStatus[];
    editors: EditorChoices | null;
    replies: IdgiReply[];
}>();

const { t } = useTranslations();
</script>

<template>
    <Head :title="`${t('editing')} “${term.term}”`" />

    <AdminNav />

    <Link
        href="/terms"
        class="inline-flex items-center gap-1.5 text-sm font-semibold text-ink hover:underline"
    >
        <ArrowLeft class="size-4" aria-hidden="true" />
        {{ t('manage-terms') }}
    </Link>

    <div class="mt-3 mb-8 flex flex-wrap items-center gap-x-4 gap-y-2">
        <h1 class="text-2xl font-bold text-ink">
            {{ t('editing') }} “{{ term.term }}”
        </h1>
        <StatusBadge :status="term.term_status" />
        <Link
            v-if="term.term_status === 'LIVE'"
            :href="term.path"
            class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-ink"
        >
            <ExternalLink class="size-4" aria-hidden="true" />
            View on the glossary
        </Link>
    </div>

    <!-- Keyed so moving between terms starts a fresh form. -->
    <TermForm
        :key="term.id"
        :term="term"
        :meta="meta"
        :replies="replies"
        :tag-options="tagOptions"
        :statuses="statuses"
        :editors="editors"
    />
</template>
