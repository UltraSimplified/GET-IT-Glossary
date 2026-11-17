<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { UserPlus } from '@lucide/vue';
import AdminNav from '@/components/admin/AdminNav.vue';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import type { AdminUserRow } from '@/types';

defineOptions({ layout: { width: 'wide' } });

defineProps<{
    users: AdminUserRow[];
}>();

const { t } = useTranslations();

const roleClass: Record<AdminUserRow['role'], string> = {
    Admin: 'bg-primary text-primary-foreground',
    Editor: 'bg-brand-soft text-ink',
    Contributor: 'bg-muted text-muted-foreground',
};
</script>

<template>
    <Head :title="t('manage-users')" />

    <AdminNav />

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-ink">{{ t('manage-users') }}</h1>
            <p class="mt-1 max-w-2xl text-muted-foreground">
                Administrators manage everything. Editors change the terms
                assigned to them. Contributors can comment.
            </p>
        </div>
        <Button as-child>
            <Link href="/users/create">
                <UserPlus class="size-4" aria-hidden="true" />
                Add a user
            </Link>
        </Button>
    </div>

    <div class="mt-8 overflow-x-auto rounded-lg border bg-card">
        <table class="w-full min-w-[36rem] text-left text-sm">
            <thead class="border-b bg-muted/60 text-xs text-muted-foreground">
                <tr>
                    <th scope="col" class="px-4 py-2.5 font-bold">Name</th>
                    <th scope="col" class="px-4 py-2.5 font-bold">Email</th>
                    <th scope="col" class="px-4 py-2.5 font-bold">Role</th>
                    <th scope="col" class="px-4 py-2.5 font-bold">Terms</th>
                    <th scope="col" class="px-4 py-2.5">
                        <span class="sr-only">Actions</span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr
                    v-for="user in users"
                    :key="user.id"
                    class="hover:bg-muted/40"
                >
                    <th scope="row" class="px-4 py-3 font-normal">
                        <Link
                            :href="`/users/${user.id}/edit`"
                            class="text-base font-bold text-ink hover:underline"
                            >{{ user.name }}</Link
                        >
                    </th>
                    <td class="px-4 py-3 break-all">{{ user.email }}</td>
                    <td class="px-4 py-3">
                        <span
                            class="rounded-full px-2.5 py-0.5 text-xs font-bold"
                            :class="roleClass[user.role]"
                            >{{ user.role }}</span
                        >
                    </td>
                    <td class="px-4 py-3 tabular-nums">
                        <template v-if="user.role === 'Admin'"
                            ><span class="text-muted-foreground"
                                >All</span
                            ></template
                        >
                        <template v-else>{{ user.terms || '–' }}</template>
                    </td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <Button size="sm" variant="outline" as-child>
                            <Link :href="`/users/${user.id}/edit`">{{
                                t('edit')
                            }}</Link>
                        </Button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
