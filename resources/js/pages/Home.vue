<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import AlphabetNav from '@/components/glossary/AlphabetNav.vue';
import SearchBox from '@/components/glossary/SearchBox.vue';
import { useTranslations } from '@/composables/useTranslations';

const page = usePage();
const { t } = useTranslations();
</script>

<template>
    <Head :title="page.props.glossary.tagline" />

    <div class="pb-6 md:pt-8">
        <div class="flex items-center gap-4 md:gap-6">
            <!-- The heading beside it names the site, so the logo is decorative. -->
            <img
                :src="page.props.glossary.logo"
                alt=""
                class="h-20 w-auto shrink-0 md:h-28"
            />
            <div>
                <h1 class="text-2xl font-bold text-ink md:text-4xl">
                    {{ page.props.glossary.title }}
                </h1>
                <p
                    class="mt-1 max-w-xl text-lg text-foreground md:mt-2 md:text-xl"
                >
                    {{ page.props.glossary.tagline }}
                </p>
            </div>
        </div>

        <SearchBox size="large" autofocus class="mt-10 max-w-2xl md:mt-12" />

        <h2 class="mt-14 mb-5 text-lg font-bold text-ink">
            {{ t('browse-glossary') }}
        </h2>
        <AlphabetNav variant="home" />
    </div>
</template>
