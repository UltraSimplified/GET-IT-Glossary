<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTranslations } from '@/composables/useTranslations';

const page = usePage();
const { t } = useTranslations();

const sections = computed(() => {
    const items = [{ label: t('manage-terms'), href: '/terms' }];

    if (page.props.auth.user?.is_admin) {
        items.push(
            { label: t('manage-pages'), href: '/pages' },
            { label: t('manage-users'), href: '/users' },
            { label: t('manage-site'), href: '/site' },
            { label: t('manage-themes'), href: '/themes' },
        );
    }

    return items;
});

function current(href: string) {
    return (
        page.url === href ||
        page.url.startsWith(`${href}/`) ||
        page.url.startsWith(`${href}?`)
    );
}
</script>

<template>
    <nav aria-label="Manage the glossary" class="mb-8 border-b">
        <ul class="-mb-px flex flex-wrap gap-x-6">
            <li v-for="section in sections" :key="section.href">
                <Link
                    :href="section.href"
                    :aria-current="current(section.href) ? 'page' : undefined"
                    class="inline-block border-b-[3px] pb-2.5 text-sm font-bold"
                    :class="
                        current(section.href)
                            ? 'border-brand text-ink'
                            : 'border-transparent text-muted-foreground hover:text-ink'
                    "
                >
                    {{ section.label }}
                </Link>
            </li>
        </ul>
    </nav>
</template>
