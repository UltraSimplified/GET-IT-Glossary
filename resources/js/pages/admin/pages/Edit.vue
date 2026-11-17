<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import AdminNav from '@/components/admin/AdminNav.vue';
import PageForm from '@/components/admin/PageForm.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import type { EditablePage } from '@/types';

defineOptions({ layout: { width: 'wide' } });

const props = defineProps<{
    page: EditablePage;
}>();

const { t } = useTranslations();

function destroy() {
    router.delete(`/pages/${props.page.id}`);
}
</script>

<template>
    <Head :title="`Editing “${page.title}”`" />

    <AdminNav />

    <Link
        href="/pages"
        class="inline-flex items-center gap-1.5 text-sm font-semibold text-ink hover:underline"
    >
        <ArrowLeft class="size-4" aria-hidden="true" />
        {{ t('manage-pages') }}
    </Link>
    <h1 class="mt-3 mb-8 text-2xl font-bold text-ink">
        Editing “{{ page.title }}”
    </h1>

    <PageForm :key="page.id" :page="page" />

    <div class="mt-12 border-t pt-6">
        <ConfirmDialog
            :title="`Delete “${page.title}”?`"
            description="The page is removed from the site for good. This can’t be undone."
            confirm-label="Delete page"
            @confirm="destroy"
        >
            <Button
                variant="outline"
                class="text-destructive hover:text-destructive"
                >Delete this page</Button
            >
        </ConfirmDialog>
    </div>
</template>
