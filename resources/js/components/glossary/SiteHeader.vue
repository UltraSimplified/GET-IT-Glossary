<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import AlphabetNav from '@/components/glossary/AlphabetNav.vue';
import SearchBox from '@/components/glossary/SearchBox.vue';
import UserMenu from '@/components/glossary/UserMenu.vue';

/**
 * `home` drops the search field and A–Z strip, because the home page
 * shows both at full size in its own content.
 */
const { home = false } = defineProps<{
    home?: boolean;
}>();

const page = usePage();
</script>

<template>
    <header class="border-b bg-card">
        <div
            class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-6 gap-y-3 px-5 py-3 sm:px-8"
        >
            <Link
                v-if="!home"
                href="/"
                class="flex items-center gap-2.5 text-lg font-bold text-ink"
            >
                <img
                    :src="page.props.glossary.logo"
                    alt=""
                    class="h-11 w-auto"
                />
                {{ page.props.glossary.title }}
            </Link>

            <div
                v-if="!home"
                class="order-last w-full sm:order-none sm:w-auto sm:flex-1"
            >
                <SearchBox class="sm:max-w-md" />
            </div>

            <div class="ml-auto">
                <UserMenu />
            </div>
        </div>

        <div v-if="!home" class="mx-auto max-w-6xl px-5 pb-2 sm:px-8">
            <AlphabetNav />
        </div>
    </header>
</template>
