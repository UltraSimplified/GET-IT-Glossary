<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import AdminNav from '@/components/admin/AdminNav.vue';
import UserForm from '@/components/admin/UserForm.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import type { AssignableTerm, EditableUser } from '@/types';

defineOptions({ layout: { width: 'wide' } });

const props = defineProps<{
    user: EditableUser;
    terms: AssignableTerm[];
    isSelf: boolean;
}>();

const { t } = useTranslations();

function destroy() {
    router.delete(`/users/${props.user.id}`);
}
</script>

<template>
    <Head :title="`Editing ${user.name}`" />

    <AdminNav />

    <Link
        href="/users"
        class="inline-flex items-center gap-1.5 text-sm font-semibold text-ink hover:underline"
    >
        <ArrowLeft class="size-4" aria-hidden="true" />
        {{ t('manage-users') }}
    </Link>
    <h1 class="mt-3 mb-8 text-2xl font-bold text-ink">{{ user.name }}</h1>

    <UserForm :key="user.id" :user="user" :terms="terms" :is-self="isSelf" />

    <div v-if="!isSelf" class="mt-12 border-t pt-6">
        <ConfirmDialog
            :title="`Delete ${user.name}?`"
            description="They can no longer sign in and lose their editor assignments. Their comments and the versions they saved are kept. This can’t be undone."
            confirm-label="Delete user"
            @confirm="destroy"
        >
            <Button
                variant="outline"
                class="text-destructive hover:text-destructive"
                >Delete {{ user.name }}</Button
            >
        </ConfirmDialog>
    </div>
</template>
