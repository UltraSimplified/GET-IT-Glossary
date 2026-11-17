<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ExternalLink, Plus, Search } from '@lucide/vue';
import { useUrlSearchParams } from '@vueuse/core';
import { computed } from 'vue';
import AdminNav from '@/components/admin/AdminNav.vue';
import IdgiScore from '@/components/admin/IdgiScore.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import type { AdminTermRow } from '@/types';

defineOptions({ layout: { width: 'wide' } });

const props = defineProps<{
    terms: AdminTermRow[];
    canCreate: boolean;
}>();

const { t } = useTranslations();

// Kept in the address so the list comes back filtered after editing a term.
const params = useUrlSearchParams('history', { removeFalsyValues: true }) as {
    q?: string;
    status?: string;
    sort?: string;
};

const statuses = [
    { value: '', label: 'All' },
    { value: 'LIVE', label: 'Live' },
    { value: 'DRAFT', label: 'Draft' },
    { value: 'ARCHIVED', label: 'Archived' },
];

const counts = computed(() => {
    const totals: Record<string, number> = { '': props.terms.length };

    for (const term of props.terms) {
        totals[term.status] = (totals[term.status] ?? 0) + 1;
    }

    return totals;
});

const shown = computed(() => {
    const needle = (params.q ?? '').trim().toLowerCase();

    const list = props.terms.filter(
        (term) =>
            (!params.status || term.status === params.status) &&
            (!needle ||
                term.term.toLowerCase().includes(needle) ||
                term.tags.some((tag) => tag.toLowerCase().includes(needle))),
    );

    if (params.sort === 'idgi') {
        return [...list].sort((a, b) => (b.idgi ?? -1) - (a.idgi ?? -1));
    }

    return list;
});
</script>

<template>
    <Head :title="t('manage-terms')" />

    <AdminNav />

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-ink">{{ t('manage-terms') }}</h1>
            <p class="mt-1 text-muted-foreground">
                {{
                    canCreate
                        ? 'Every term in the glossary.'
                        : 'The terms you can edit.'
                }}
            </p>
        </div>
        <Button v-if="canCreate" as-child>
            <Link href="/terms/create">
                <Plus class="size-4" aria-hidden="true" />
                {{ t('create-term') }}
            </Link>
        </Button>
    </div>

    <div class="mt-8 flex flex-wrap items-center gap-x-6 gap-y-3">
        <div class="relative w-full sm:w-72">
            <Search
                class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                aria-hidden="true"
            />
            <input
                v-model="params.q"
                type="search"
                aria-label="Find a term or tag"
                placeholder="Find a term or tag"
                class="h-10 w-full rounded-md border border-input bg-card pr-3 pl-9 text-base"
            />
        </div>

        <div role="group" aria-label="Status" class="flex flex-wrap gap-1">
            <button
                v-for="option in statuses"
                :key="option.value"
                type="button"
                class="rounded-full px-3 py-1 text-sm font-semibold"
                :class="
                    (params.status ?? '') === option.value
                        ? 'bg-primary text-primary-foreground'
                        : 'text-ink hover:bg-brand-soft'
                "
                :aria-pressed="(params.status ?? '') === option.value"
                @click="params.status = option.value"
            >
                {{ option.label }}
                <span class="tabular-nums opacity-75">{{
                    counts[option.value] ?? 0
                }}</span>
            </button>
        </div>

        <label class="flex items-center gap-2 text-sm sm:ml-auto">
            <span class="font-semibold">Sort by</span>
            <select
                :value="params.sort ?? ''"
                @change="
                    params.sort = ($event.target as HTMLSelectElement).value
                "
                class="h-9 rounded-md border border-input bg-card px-2 text-sm"
            >
                <option value="">Name</option>
                <option value="idgi">“I don’t get it” score</option>
            </select>
        </label>
    </div>

    <p class="sr-only" aria-live="polite">{{ shown.length }} terms shown</p>

    <div class="mt-5 overflow-x-auto rounded-lg border bg-card">
        <table class="w-full min-w-[46rem] text-left text-sm">
            <thead class="border-b bg-muted/60 text-xs text-muted-foreground">
                <tr>
                    <th scope="col" class="px-4 py-2.5 font-bold">
                        {{ t('term') }}
                    </th>
                    <th scope="col" class="px-4 py-2.5 font-bold">
                        {{ t('status') }}
                    </th>
                    <th scope="col" class="px-4 py-2.5 font-bold">
                        <abbr
                            title="Share of readers who pressed “I don’t get it”"
                            class="no-underline"
                            >IDGI</abbr
                        >
                    </th>
                    <th scope="col" class="px-4 py-2.5 font-bold">Versions</th>
                    <th scope="col" class="px-4 py-2.5 font-bold">
                        {{ t('editors') }}
                    </th>
                    <th scope="col" class="px-4 py-2.5">
                        <span class="sr-only">Actions</span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr
                    v-for="term in shown"
                    :key="term.id"
                    class="hover:bg-muted/40"
                >
                    <th scope="row" class="px-4 py-3 align-top font-normal">
                        <Link
                            :href="`/terms/${term.id}/edit`"
                            class="text-base font-bold text-ink hover:underline"
                        >
                            {{ term.term }}
                        </Link>
                        <p
                            v-if="term.tags.length"
                            class="mt-0.5 text-xs text-muted-foreground"
                        >
                            {{ term.tags.join(', ') }}
                        </p>
                    </th>
                    <td class="px-4 py-3 align-top">
                        <StatusBadge :status="term.status" />
                    </td>
                    <td class="px-4 py-3 align-top">
                        <IdgiScore :percent="term.idgi" compact />
                    </td>
                    <td class="px-4 py-3 align-top tabular-nums">
                        <Link
                            v-if="term.version > 1"
                            :href="`/terms/${term.master_id}/version/${term.version - 1}`"
                            class="text-ink underline underline-offset-4 hover:bg-brand-soft"
                        >
                            {{ term.version }} versions
                        </Link>
                        <span v-else class="text-muted-foreground">1</span>
                    </td>
                    <td class="px-4 py-3 align-top">
                        <span v-if="term.editors.length">{{
                            term.editors.join(', ')
                        }}</span>
                        <span v-else class="text-muted-foreground">None</span>
                    </td>
                    <td
                        class="px-4 py-3 text-right align-top whitespace-nowrap"
                    >
                        <Link
                            v-if="term.status === 'LIVE'"
                            :href="term.path"
                            class="mr-3 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-ink"
                            :aria-label="`View ${term.term} on the glossary`"
                        >
                            <ExternalLink class="size-4" aria-hidden="true" />
                            View
                        </Link>
                        <Button size="sm" variant="outline" as-child>
                            <Link :href="`/terms/${term.id}/edit`">{{
                                t('edit')
                            }}</Link>
                        </Button>
                    </td>
                </tr>
                <tr v-if="!shown.length">
                    <td
                        colspan="6"
                        class="px-4 py-10 text-center text-muted-foreground"
                    >
                        <template v-if="terms.length"
                            >No terms match these filters.</template
                        >
                        <template v-else-if="canCreate"
                            >There are no terms yet. Create the first
                            one.</template
                        >
                        <template v-else>
                            You aren’t an editor of any terms yet. An
                            administrator can assign terms to you.
                        </template>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
