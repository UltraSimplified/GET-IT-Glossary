<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
</script>

<template>
    <footer class="mt-24 border-t-4 border-primary bg-card">
        <div
            class="mx-auto grid max-w-6xl gap-10 px-5 py-12 sm:grid-cols-3 sm:px-8"
        >
            <nav
                v-for="column in page.props.glossary.footer"
                :key="column.title"
                :aria-label="column.title"
            >
                <h2 class="text-lg font-bold text-ink">{{ column.title }}</h2>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li v-for="link in column.links" :key="link.href">
                        <Link
                            :href="link.href"
                            class="text-foreground underline decoration-border decoration-2 underline-offset-4 hover:decoration-brand"
                        >
                            {{ link.label }}
                        </Link>
                    </li>
                </ul>
            </nav>
        </div>
    </footer>
</template>
