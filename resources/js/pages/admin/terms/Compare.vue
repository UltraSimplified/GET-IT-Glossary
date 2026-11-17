<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, History, SquarePen } from '@lucide/vue';
import { computed, ref } from 'vue';
import AdminNav from '@/components/admin/AdminNav.vue';
import IdgiScore from '@/components/admin/IdgiScore.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import RichHtml from '@/components/glossary/RichHtml.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useTranslations } from '@/composables/useTranslations';
import type { ComparedVersion } from '@/types';

defineOptions({ layout: { width: 'wide' } });

const props = defineProps<{
    masterId: number;
    selected: ComparedVersion;
    current: ComparedVersion;
    /** The earlier version numbers that can be compared. */
    versions: number[];
}>();

const { t } = useTranslations();
const rollingBack = ref(false);

type Section = {
    key: string;
    label: string;
    html: (v: ComparedVersion) => string;
};

const sections = computed<Section[]>(() => [
    {
        key: 'definition',
        label: t('definition'),
        html: (v) => v.definition_html,
    },
    { key: 'synonyms', label: t('synonyms'), html: (v) => v.synonyms_html },
    {
        key: 'explanation',
        label: t('full-explanation'),
        html: (v) => v.explanation_html,
    },
    { key: 'example', label: t('example'), html: (v) => v.example_html },
    { key: 'cartoon', label: t('cartoon'), html: (v) => v.cartoon_html },
    { key: 'warning', label: 'Reason not to use', html: (v) => v.warning_html },
    {
        key: 'see_also',
        label: t('see-also'),
        html: (v) => v.see_also.map((link) => link.term).join(', '),
    },
    { key: 'tags', label: t('tags'), html: (v) => v.tags.join(', ') },
]);

// Only show a section when one of the two versions has something in it.
const shown = computed(() =>
    sections.value
        .map((section) => {
            const before = section.html(props.selected);
            const after = section.html(props.current);

            return {
                ...section,
                before,
                after,
                changed: before.trim() !== after.trim(),
            };
        })
        .filter((section) => section.before || section.after),
);

const changedCount = computed(
    () => shown.value.filter((section) => section.changed).length,
);

function rollback() {
    rollingBack.value = true;
    router.post(
        `/terms/${props.selected.id}/rollback`,
        {},
        { onFinish: () => (rollingBack.value = false) },
    );
}
</script>

<template>
    <Head :title="`${t('comparing')} “${current.term}”`" />

    <AdminNav />

    <Link
        href="/terms"
        class="inline-flex items-center gap-1.5 text-sm font-semibold text-ink hover:underline"
    >
        <ArrowLeft class="size-4" aria-hidden="true" />
        {{ t('manage-terms') }}
    </Link>

    <div class="mt-3 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-ink">
                {{ t('comparing') }} versions of “{{ current.term }}”
            </h1>
            <p class="mt-1 text-muted-foreground">
                <template v-if="changedCount">
                    {{
                        changedCount === 1
                            ? 'One section differs'
                            : `${changedCount} sections differ`
                    }}
                    between version {{ selected.version }} and the current
                    version.
                </template>
                <template v-else
                    >Version {{ selected.version }} has the same wording as the
                    current version.</template
                >
            </p>
        </div>
        <Button variant="outline" as-child>
            <Link :href="`/terms/${current.id}/edit`">
                <SquarePen class="size-4" aria-hidden="true" />
                Edit the current version
            </Link>
        </Button>
    </div>

    <nav
        v-if="versions.length > 1"
        class="mt-6 flex flex-wrap items-center gap-2"
        aria-label="Earlier versions"
    >
        <span class="text-sm font-semibold">Compare version</span>
        <Link
            v-for="number in versions"
            :key="number"
            :href="`/terms/${masterId}/version/${number}`"
            :aria-current="number === selected.version ? 'page' : undefined"
            preserve-scroll
            class="letter-token grid w-9 place-items-center text-sm font-bold"
            :class="
                number === selected.version
                    ? 'letter-token-current'
                    : 'bg-muted text-ink hover:bg-brand-soft'
            "
        >
            {{ number }}
        </Link>
        <span class="text-sm text-muted-foreground"
            >with version {{ current.version }} (current)</span
        >
    </nav>

    <div class="mt-8 overflow-hidden rounded-lg border bg-card">
        <!-- Column headings -->
        <div class="grid border-b bg-muted/60 md:grid-cols-2 md:divide-x">
            <div class="space-y-1.5 p-4">
                <p class="flex flex-wrap items-center gap-2 font-bold">
                    <History
                        class="size-4 text-muted-foreground"
                        aria-hidden="true"
                    />
                    {{ t('version') }} {{ selected.version }}
                    <StatusBadge :status="selected.status" />
                </p>
                <p class="text-sm text-muted-foreground">
                    <template v-if="selected.meta.updated_by"
                        >{{ t('updated-by') }} {{ selected.meta.updated_by }}
                        {{ selected.meta.date }}</template
                    >
                    <template v-else-if="selected.meta.date"
                        >{{ t('imported-on') }}
                        {{ selected.meta.date }}</template
                    >
                </p>
            </div>
            <div class="space-y-1.5 border-t p-4 md:border-t-0">
                <p class="flex flex-wrap items-center gap-2 font-bold">
                    {{ t('version') }} {{ current.version }}, current
                    <StatusBadge :status="current.status" />
                </p>
                <p class="text-sm text-muted-foreground">
                    <template v-if="current.meta.updated_by"
                        >{{ t('updated-by') }} {{ current.meta.updated_by }}
                        {{ current.meta.date }}</template
                    >
                    <template v-else-if="current.meta.date">{{
                        current.meta.date
                    }}</template>
                </p>
            </div>
        </div>

        <section
            v-for="section in shown"
            :key="section.key"
            class="border-b last:border-b-0"
        >
            <h2
                class="flex items-center gap-2 px-4 pt-4 text-sm font-bold text-ink"
            >
                {{ section.label }}
                <span
                    v-if="section.changed"
                    class="rounded-full bg-highlight px-2 py-0.5 text-xs text-highlight-foreground"
                    >Changed</span
                >
            </h2>
            <div class="grid md:grid-cols-2 md:divide-x">
                <div
                    class="p-4 pt-2"
                    :class="{ 'text-muted-foreground': !section.changed }"
                >
                    <RichHtml v-if="section.before" :html="section.before" />
                    <p v-else class="text-sm text-muted-foreground italic">
                        Empty
                    </p>
                </div>
                <div
                    class="p-4 pt-2"
                    :class="{ 'text-muted-foreground': !section.changed }"
                >
                    <p
                        class="mb-1 text-xs font-bold text-muted-foreground md:hidden"
                    >
                        Current
                    </p>
                    <RichHtml v-if="section.after" :html="section.after" />
                    <p v-else class="text-sm text-muted-foreground italic">
                        Empty
                    </p>
                </div>
            </div>
        </section>

        <div
            v-if="selected.meta.idgi || current.meta.idgi"
            class="grid border-t bg-muted/30 md:grid-cols-2 md:divide-x"
        >
            <div class="p-4"><IdgiScore :score="selected.meta.idgi" /></div>
            <div class="p-4"><IdgiScore :score="current.meta.idgi" /></div>
        </div>
    </div>

    <div class="mt-8 max-w-xl rounded-lg border bg-card p-5">
        <h2 class="font-bold text-ink">
            Bring back version {{ selected.version }}
        </h2>
        <p class="mt-1 text-sm text-muted-foreground">
            A copy of version {{ selected.version }} becomes version
            {{ current.version + 1 }}, keeping the current status. Version
            {{ current.version }} stays in the history, so nothing is lost.
            Administrators are emailed.
        </p>
        <ConfirmDialog
            :title="`Make version ${selected.version} current again?`"
            :description="`Readers will see the wording from version ${selected.version}. Version ${current.version} stays in the history.`"
            :confirm-label="`Bring back version ${selected.version}`"
            :destructive="false"
            @confirm="rollback"
        >
            <Button class="mt-4" :disabled="rollingBack" data-test="rollback">
                <Spinner v-if="rollingBack" />
                Bring back version {{ selected.version }}
            </Button>
        </ConfirmDialog>
    </div>
</template>
