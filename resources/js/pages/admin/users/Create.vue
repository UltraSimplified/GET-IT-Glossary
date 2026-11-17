<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import AdminNav from '@/components/admin/AdminNav.vue';
import UserForm from '@/components/admin/UserForm.vue';
import { useTranslations } from '@/composables/useTranslations';

defineOptions({ layout: { width: 'wide' } });

const { t } = useTranslations();
</script>

<template>
    <Head title="Add a user" />

    <AdminNav />

    <Link
        href="/users"
        class="inline-flex items-center gap-1.5 text-sm font-semibold text-ink hover:underline"
    >
        <ArrowLeft class="size-4" aria-hidden="true" />
        {{ t('manage-users') }}
    </Link>
    <h1 class="mt-3 mb-2 text-2xl font-bold text-ink">Add a user</h1>
    <p class="mb-8 max-w-2xl text-muted-foreground">
        Once added, open the person again to choose the terms they edit.
    </p>

    <UserForm />
</template>
