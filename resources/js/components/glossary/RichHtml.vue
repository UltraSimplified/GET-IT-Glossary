<script setup lang="ts">
import { router } from '@inertiajs/vue3';

/**
 * Renders HTML written by glossary editors (definitions, explanations,
 * page bodies).
 *
 * Links inside it are ordinary <a> tags from the database, so clicks on
 * same-site links are handed to Inertia here. That is what lets a reader
 * hop from term to term without a full page load.
 */
const { html, as = 'div' } = defineProps<{
    html: string;
    as?: string;
}>();

function onClick(event: MouseEvent) {
    if (
        event.defaultPrevented ||
        event.button !== 0 ||
        event.metaKey ||
        event.ctrlKey ||
        event.shiftKey ||
        event.altKey
    ) {
        return;
    }

    const link = (event.target as HTMLElement).closest('a');

    if (!link || link.target === '_blank' || link.hasAttribute('download')) {
        return;
    }

    const url = new URL(link.href, window.location.href);
    const isPage = !/\.[a-z0-9]{2,5}$/i.test(url.pathname);

    if (url.origin !== window.location.origin || !isPage) {
        return;
    }

    // Leave same-page anchors (#warning) to the browser.
    if (url.pathname === window.location.pathname && url.hash) {
        return;
    }

    event.preventDefault();
    router.visit(url.pathname + url.search + url.hash);
}
</script>

<template>
    <!-- eslint-disable-next-line vue/no-v-html -->
    <component :is="as" class="rich-html" @click="onClick" v-html="html" />
</template>
