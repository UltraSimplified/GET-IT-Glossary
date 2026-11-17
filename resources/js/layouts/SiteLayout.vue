<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import SiteFooter from '@/components/glossary/SiteFooter.vue';
import SiteHeader from '@/components/glossary/SiteHeader.vue';
import { Toaster } from '@/components/ui/sonner';
import { useTranslations } from '@/composables/useTranslations';

/**
 * The frame around every glossary page. It stays mounted while Inertia
 * swaps the page inside it, so moving between terms only replaces the
 * content below the A–Z index.
 */
const { width = 'reading' } = defineProps<{
    /** `reading` keeps lines short for text; `wide` suits tables and forms. */
    width?: 'reading' | 'wide';
}>();

const page = usePage();
const { t } = useTranslations();

const isHome = computed(() => page.component === 'Home');
</script>

<template>
    <div class="flex min-h-svh flex-col">
        <a
            href="#content"
            class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:rounded-md focus:bg-primary focus:px-4 focus:py-2 focus:text-primary-foreground"
        >
            {{ t('skip-to-content') }}
        </a>

        <SiteHeader :home="isHome" />

        <main
            id="content"
            tabindex="-1"
            class="mx-auto w-full max-w-6xl flex-1 px-5 pt-10 outline-none sm:px-8"
        >
            <div :class="width === 'wide' || isHome ? '' : 'max-w-[44rem]'">
                <slot />
            </div>
        </main>

        <SiteFooter />
        <Toaster />
    </div>
</template>
