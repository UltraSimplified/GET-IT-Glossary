<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import AdminNav from '@/components/admin/AdminNav.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import { PAGE_MENUS } from '@/types';
import type { AdminPageRow } from '@/types';

defineOptions({ layout: { width: 'wide' } });

defineProps<{
    pages: AdminPageRow[];
}>();

const { t } = useTranslations();

function menuLabel(menu: number | null) {
    return (
        PAGE_MENUS.find((item) => item.value === menu)?.label ?? 'Not listed'
    );
}

function destroy(page: AdminPageRow) {
    router.delete(`/pages/${page.id}`, { preserveScroll: true });
}
</script>

<template>
    <Head :title="t('manage-pages')" />

    <AdminNav />

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-ink">{{ t('manage-pages') }}</h1>
            <p class="mt-1 text-muted-foreground">
                The About, API and Get in touch pages.
            </p>
        </div>
        <Button as-child>
            <Link href="/pages/create">
                <Plus class="size-4" aria-hidden="true" />
                {{ t('add-new-page') }}
            </Link>
        </Button>
    </div>

    <div class="mt-8 overflow-x-auto rounded-lg border bg-card">
        <table class="w-full min-w-[40rem] text-left text-sm">
            <thead class="border-b bg-muted/60 text-xs text-muted-foreground">
                <tr>
                    <th scope="col" class="px-4 py-2.5 font-bold">
                        {{ t('title') }}
                    </th>
                    <th scope="col" class="px-4 py-2.5 font-bold">Address</th>
                    <th scope="col" class="px-4 py-2.5 font-bold">
                        Footer column
                    </th>
                    <th scope="col" class="px-4 py-2.5">
                        <span class="sr-only">Actions</span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr
                    v-for="page in pages"
                    :key="page.id"
                    class="hover:bg-muted/40"
                >
                    <th
                        scope="row"
                        class="max-w-md px-4 py-3 align-top font-normal"
                    >
                        <Link
                            :href="`/pages/${page.id}/edit`"
                            class="text-base font-bold text-ink hover:underline"
                            >{{ page.title }}</Link
                        >
                        <p
                            class="mt-0.5 line-clamp-1 text-xs text-muted-foreground"
                        >
                            {{ page.excerpt }}
                        </p>
                    </th>
                    <td class="px-4 py-3 align-top">
                        <Link
                            v-if="page.path"
                            :href="page.path"
                            class="text-ink underline underline-offset-4"
                            >{{ page.path }}</Link
                        >
                        <span v-else class="text-muted-foreground">None</span>
                    </td>
                    <td class="px-4 py-3 align-top">
                        {{ menuLabel(page.menu) }}
                    </td>
                    <td
                        class="px-4 py-3 text-right align-top whitespace-nowrap"
                    >
                        <Button size="sm" variant="outline" as-child>
                            <Link :href="`/pages/${page.id}/edit`">{{
                                t('edit')
                            }}</Link>
                        </Button>
                        <ConfirmDialog
                            :title="`Delete “${page.title}”?`"
                            description="The page is removed from the site for good. This can’t be undone."
                            confirm-label="Delete page"
                            @confirm="destroy(page)"
                        >
                            <Button
                                size="sm"
                                variant="ghost"
                                class="ml-1 text-destructive hover:text-destructive"
                            >
                                {{ t('delete') }}
                            </Button>
                        </ConfirmDialog>
                    </td>
                </tr>
                <tr v-if="!pages.length">
                    <td
                        colspan="4"
                        class="px-4 py-10 text-center text-muted-foreground"
                    >
                        There are no pages yet.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
