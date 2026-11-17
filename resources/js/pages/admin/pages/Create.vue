<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import AdminNav from '@/components/admin/AdminNav.vue';
import PageForm from '@/components/admin/PageForm.vue';
import { useTranslations } from '@/composables/useTranslations';

defineOptions({ layout: { width: 'wide' } });

const { t } = useTranslations();
</script>

<template>
    <Head title="New page" />

    <AdminNav />

    <Link
        href="/pages"
        class="inline-flex items-center gap-1.5 text-sm font-semibold text-ink hover:underline"
    >
        <ArrowLeft class="size-4" aria-hidden="true" />
        {{ t('manage-pages') }}
    </Link>
    <h1 class="mt-3 mb-8 text-2xl font-bold text-ink">New page</h1>

    <PageForm />
</template>
