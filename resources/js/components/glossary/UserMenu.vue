<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    BookOpenText,
    ChevronDown,
    FileText,
    LogOut,
    Settings,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useTranslations } from '@/composables/useTranslations';
import { login, logout } from '@/routes';
import { edit as editProfile } from '@/routes/profile';

const page = usePage();
const { t } = useTranslations();

const user = computed(() => page.props.auth.user);
</script>

<template>
    <Link
        v-if="!user"
        :href="login()"
        class="rounded-md px-3 py-2 text-sm font-semibold text-ink underline decoration-brand decoration-2 underline-offset-4 hover:bg-brand-soft"
    >
        {{ t('sign-in') }}
    </Link>

    <DropdownMenu v-else>
        <DropdownMenuTrigger
            class="flex items-center gap-1 rounded-md px-3 py-2 text-sm font-semibold text-ink hover:bg-brand-soft data-[state=open]:bg-brand-soft"
            data-test="user-menu"
        >
            {{ t('hi') }} {{ user.first_name }}
            <ChevronDown class="size-4" aria-hidden="true" />
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-56">
            <template v-if="user.is_admin">
                <DropdownMenuItem as-child>
                    <Link href="/users" class="w-full cursor-pointer">
                        <Users class="size-4" aria-hidden="true" />
                        {{ t('manage-users') }}
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuItem as-child>
                    <Link href="/pages" class="w-full cursor-pointer">
                        <FileText class="size-4" aria-hidden="true" />
                        {{ t('manage-pages') }}
                    </Link>
                </DropdownMenuItem>
            </template>
            <DropdownMenuItem as-child>
                <Link href="/terms" class="w-full cursor-pointer">
                    <BookOpenText class="size-4" aria-hidden="true" />
                    {{ t('manage-terms') }}
                </Link>
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem as-child>
                <Link :href="editProfile()" class="w-full cursor-pointer">
                    <Settings class="size-4" aria-hidden="true" />
                    {{ t('settings') }}
                </Link>
            </DropdownMenuItem>
            <DropdownMenuItem as-child>
                <Link
                    :href="logout()"
                    as="button"
                    class="w-full cursor-pointer"
                    data-test="logout-button"
                    @click="router.flushAll()"
                >
                    <LogOut class="size-4" aria-hidden="true" />
                    {{ t('sign-out') }}
                </Link>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
