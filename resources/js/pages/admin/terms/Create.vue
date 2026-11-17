<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import AdminNav from '@/components/admin/AdminNav.vue';
import TermForm from '@/components/admin/TermForm.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { EditorChoices, TermStatus } from '@/types';

defineOptions({ layout: { width: 'wide' } });

defineProps<{
    tagOptions: string[];
    statuses: TermStatus[];
    editors: EditorChoices;
}>();

const { t } = useTranslations();
</script>

<template>
    <Head title="New term" />

    <AdminNav />

    <Link
        href="/terms"
        class="inline-flex items-center gap-1.5 text-sm font-semibold text-ink hover:underline"
    >
        <ArrowLeft class="size-4" aria-hidden="true" />
        {{ t('manage-terms') }}
    </Link>
    <h1 class="mt-3 mb-8 text-2xl font-bold text-ink">New term</h1>

    <TermForm
        :tag-options="tagOptions"
        :statuses="statuses"
        :editors="editors"
    />
</template>
