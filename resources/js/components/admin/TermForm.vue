<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Field from '@/components/admin/Field.vue';
import IdgiScore from '@/components/admin/IdgiScore.vue';
import PronunciationRecorder from '@/components/admin/PronunciationRecorder.vue';
import RichTextEditor from '@/components/admin/RichTextEditor.vue';
import TermTextarea from '@/components/admin/TermTextarea.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { useLeaveGuard } from '@/composables/useLeaveGuard';
import { useTranslations } from '@/composables/useTranslations';
import type {
    EditableTerm,
    EditorChoices,
    TermFields,
    TermStatus,
    VersionMeta,
} from '@/types';

/**
 * The term form, used to create a term and to edit one.
 *
 * Editing offers two ways to save: overwrite the current version (for
 * small corrections), or keep it as history and save a new version
 * (for changes worth being able to compare against or roll back).
 */
const props = defineProps<{
    term?: EditableTerm;
    meta?: VersionMeta;
    tagOptions: string[];
    statuses: TermStatus[];
    /** Null when the person editing is not an administrator. */
    editors: EditorChoices | null;
}>();

const { t } = useTranslations();

// Text fields are edited as strings; empty ones are saved as empty.
type TextField = Exclude<
    keyof TermFields,
    'term_status' | 'do_not_use' | 'tags'
>;
type TermFormData = { [K in TextField]: string } & {
    term_status: TermStatus;
    do_not_use: boolean;
    tags: string[];
    editors: number[];
};

const textFields: TextField[] = [
    'term',
    'term_ordered',
    'see_also',
    'synonyms',
    'warning',
    'definition',
    'explanation',
    'common_variants',
    'pronunciation',
    'video',
    'test',
    'cartoon',
    'example',
];

const initial = Object.fromEntries(
    textFields.map((key) => [key, props.term?.[key] ?? '']),
) as { [K in TextField]: string };

const form = useForm<TermFormData>({
    ...initial,
    term_status: props.term?.term_status ?? 'LIVE',
    do_not_use: props.term?.do_not_use ?? false,
    tags: [...(props.term?.tags ?? [])],
    editors: props.editors ? [...props.editors.assigned] : [],
});

const saving = ref<'update' | 'version' | 'create' | null>(null);

useLeaveGuard(() => form.isDirty && !form.processing);

const statusLabels: Record<string, string> = {
    LIVE: 'Live: shown on the glossary',
    DRAFT: 'Draft: hidden while you work on it',
    ARCHIVED: 'Archived: hidden, kept for reference',
};

const editorFilter = ref('');

const editorList = computed(() => {
    const needle = editorFilter.value.trim().toLowerCase();

    return (props.editors?.all ?? []).filter(
        (user) => !needle || user.name.toLowerCase().includes(needle),
    );
});

function payload(data: TermFormData): Record<string, unknown> {
    // Only administrators decide who edits a term.
    if (props.editors) {
        return { ...data };
    }

    const { editors: _ignored, ...rest } = data;

    return rest;
}

function create() {
    saving.value = 'create';
    form.transform(payload).post('/terms', {
        onFinish: () => (saving.value = null),
    });
}

function save(mode: 'update' | 'version') {
    if (!props.term) {
        return;
    }

    saving.value = mode;
    form.transform((data) => ({ ...payload(data), mode })).put(
        `/terms/${props.term.id}`,
        {
            preserveScroll: 'errors',
            onFinish: () => (saving.value = null),
        },
    );
}

function submit() {
    if (props.term) {
        save('update');
    } else {
        create();
    }
}

const errorCount = computed(() => Object.keys(form.errors).length);
</script>

<template>
    <form
        class="grid gap-x-12 gap-y-10 lg:grid-cols-[minmax(0,1fr)_19rem]"
        novalidate
        @submit.prevent="submit"
    >
        <div class="space-y-7">
            <p
                v-if="errorCount"
                class="rounded-lg bg-danger-soft px-4 py-3 text-sm font-semibold text-destructive"
                role="alert"
            >
                {{
                    errorCount === 1
                        ? 'One field needs'
                        : `${errorCount} fields need`
                }}
                attention before this term can be saved.
            </p>

            <div class="grid gap-6 sm:grid-cols-2">
                <Field id="term" :label="t('term')" :error="form.errors.term">
                    <Input
                        id="term"
                        v-model="form.term"
                        required
                        class="h-11 bg-card text-base"
                        :aria-invalid="!!form.errors.term"
                    />
                </Field>

                <!-- The hint sits below, so this box lines up with the term box beside it. -->
                <Field
                    id="term_ordered"
                    :label="t('term-ordered')"
                    hint="Only if it should also be filed under another word, such as “risk, absolute”."
                    hint-below
                    :error="form.errors.term_ordered"
                >
                    <template #default="{ describedby }">
                        <Input
                            id="term_ordered"
                            v-model="form.term_ordered"
                            class="h-11 bg-card text-base"
                            :aria-describedby="describedby"
                        />
                    </template>
                </Field>
            </div>

            <Field
                id="pronunciation"
                :label="t('pronunciation')"
                :hint="t('pronunciation-hint')"
                :error="form.errors.pronunciation"
            >
                <template #default="{ describedby, invalid }">
                    <PronunciationRecorder
                        id="pronunciation"
                        v-model="form.pronunciation"
                        :describedby="describedby"
                        :invalid="invalid"
                    />
                </template>
            </Field>

            <Field
                id="definition"
                :label="t('definition')"
                :hint="t('square-brackets')"
                :error="form.errors.definition"
            >
                <template #default="{ describedby, invalid }">
                    <TermTextarea
                        id="definition"
                        v-model="form.definition"
                        :rows="3"
                        :describedby="describedby"
                        :invalid="invalid"
                    />
                </template>
            </Field>

            <Field
                id="explanation"
                :label="t('explanation')"
                :hint="t('square-brackets')"
                :error="form.errors.explanation"
            >
                <template #default="{ describedby, invalid }">
                    <RichTextEditor
                        id="explanation"
                        v-model="form.explanation"
                        term-links
                        :describedby="describedby"
                        :invalid="invalid"
                    />
                </template>
            </Field>

            <details class="rounded-lg border bg-card px-4 py-3 text-sm">
                <summary class="cursor-pointer font-bold text-ink">
                    {{ t('tip-linking-terms') }}
                </summary>
                <div class="mt-3 space-y-2">
                    <p>
                        Put a term in square brackets to link to its definition:
                    </p>
                    <p>
                        <code class="rounded bg-muted px-1.5 py-0.5 font-mono"
                            >[{{ t('term') }}]</code
                        >
                    </p>
                    <p>
                        To link other words to a term, put the words, a vertical
                        bar and then the term:
                    </p>
                    <p>
                        <code class="rounded bg-muted px-1.5 py-0.5 font-mono"
                            >[trials|randomised trial]</code
                        >
                    </p>
                    <p>
                        “Link a term” does this for you. Select some words first
                        to link them.
                    </p>
                </div>
            </details>

            <!-- Hints below, so the two boxes line up whatever the hints' lengths. -->
            <div class="grid gap-6 sm:grid-cols-2">
                <Field
                    id="synonyms"
                    :label="t('synonyms')"
                    hint="Separate with semicolons. Start one with !! to mark it as not recommended."
                    hint-below
                    :error="form.errors.synonyms"
                >
                    <template #default="{ describedby }">
                        <Input
                            id="synonyms"
                            v-model="form.synonyms"
                            class="h-11 bg-card text-base"
                            :aria-describedby="describedby"
                        />
                    </template>
                </Field>

                <Field
                    id="common_variants"
                    :label="t('common-variants')"
                    :hint="t('separate-terms')"
                    hint-below
                    :error="form.errors.common_variants"
                >
                    <template #default="{ describedby }">
                        <Input
                            id="common_variants"
                            v-model="form.common_variants"
                            class="h-11 bg-card text-base"
                            :aria-describedby="describedby"
                        />
                    </template>
                </Field>
            </div>

            <Field
                id="see_also"
                :label="t('see-also')"
                hint="Related terms, separated with semicolons."
                :error="form.errors.see_also"
            >
                <template #default="{ describedby }">
                    <Input
                        id="see_also"
                        v-model="form.see_also"
                        class="h-11 bg-card text-base"
                        :aria-describedby="describedby"
                    />
                </template>
            </Field>

            <Field
                id="warning"
                label="Reason not to use this term"
                hint="If this term or any of its synonyms should be avoided, explain why. Readers see it under the definition."
                :error="form.errors.warning"
            >
                <template #default="{ describedby }">
                    <textarea
                        id="warning"
                        v-model="form.warning"
                        rows="3"
                        :aria-describedby="describedby"
                        class="block w-full rounded-md border border-input bg-card px-3 py-2 text-base"
                    ></textarea>
                </template>
            </Field>

            <Field
                id="example"
                :label="t('example')"
                :hint="t('example-term-in-use')"
                :error="form.errors.example"
            >
                <template #default="{ describedby, invalid }">
                    <RichTextEditor
                        id="example"
                        v-model="form.example"
                        images
                        :describedby="describedby"
                        :invalid="invalid"
                    />
                </template>
            </Field>

            <Field
                id="cartoon"
                :label="t('cartoon')"
                :hint="t('upload-cartoons')"
                :error="form.errors.cartoon"
            >
                <template #default="{ describedby, invalid }">
                    <RichTextEditor
                        id="cartoon"
                        v-model="form.cartoon"
                        images
                        :describedby="describedby"
                        :invalid="invalid"
                    />
                </template>
            </Field>

            <Field
                id="video"
                :label="t('video')"
                hint="Paste the embed code from Vimeo or YouTube."
                :error="form.errors.video"
            >
                <template #default="{ describedby }">
                    <textarea
                        id="video"
                        v-model="form.video"
                        rows="4"
                        spellcheck="false"
                        :aria-describedby="describedby"
                        class="block w-full rounded-md border border-input bg-card px-3 py-2 font-mono text-sm"
                    ></textarea>
                </template>
            </Field>

            <Field
                id="test"
                :label="t('test')"
                :hint="t('simple-question')"
                :error="form.errors.test"
            >
                <template #default="{ describedby }">
                    <textarea
                        id="test"
                        v-model="form.test"
                        rows="3"
                        :aria-describedby="describedby"
                        class="block w-full rounded-md border border-input bg-card px-3 py-2 text-base"
                    ></textarea>
                </template>
            </Field>
            <!-- On small screens the sidebar comes last, so the save buttons ride along at the bottom. -->
            <div
                class="sticky bottom-0 z-20 -mx-5 flex gap-2 border-t bg-card/95 px-5 py-3 backdrop-blur sm:-mx-8 sm:px-8 lg:hidden"
            >
                <template v-if="term">
                    <Button
                        type="button"
                        size="sm"
                        class="flex-1"
                        :disabled="form.processing"
                        @click="save('update')"
                    >
                        <Spinner v-if="saving === 'update'" />
                        {{ t('update-version') }}
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        class="flex-1"
                        :disabled="form.processing"
                        @click="save('version')"
                    >
                        <Spinner v-if="saving === 'version'" />
                        {{ t('save-new') }}
                    </Button>
                </template>
                <Button
                    v-else
                    type="submit"
                    class="flex-1"
                    :disabled="form.processing"
                >
                    <Spinner v-if="saving === 'create'" />
                    {{ t('create-term') }}
                </Button>
            </div>
        </div>

        <!-- Publishing details and the save buttons stay in view while scrolling the form. -->
        <aside class="space-y-6 lg:sticky lg:top-6 lg:self-start">
            <section
                class="space-y-3 rounded-lg border bg-card p-4"
                aria-label="Save"
            >
                <template v-if="term">
                    <p v-if="meta" class="text-sm text-muted-foreground">
                        {{ t('version') }}
                        <strong class="text-foreground">{{
                            meta.version
                        }}</strong
                        >.
                        <template v-if="meta.updated_by"
                            >{{ t('updated-by') }} {{ meta.updated_by }}
                            {{ meta.date }}.</template
                        >
                        <template v-else-if="meta.version === 1"
                            >{{ t('imported-on') }} {{ meta.date }}.</template
                        >
                    </p>

                    <Button
                        type="button"
                        class="w-full"
                        :disabled="form.processing"
                        data-test="update-version"
                        @click="save('update')"
                    >
                        <Spinner v-if="saving === 'update'" />
                        {{ t('update-version') }}
                    </Button>
                    <p class="-mt-1 text-xs text-muted-foreground">
                        For corrections. Replaces version {{ term.version }}.
                    </p>

                    <Button
                        type="button"
                        variant="outline"
                        class="w-full"
                        :disabled="form.processing"
                        data-test="save-new-version"
                        @click="save('version')"
                    >
                        <Spinner v-if="saving === 'version'" />
                        {{ t('save-new') }}
                    </Button>
                    <p class="-mt-1 text-xs text-muted-foreground">
                        Keeps version {{ term.version }} so it can be compared
                        or brought back. Administrators are emailed.
                    </p>

                    <p
                        class="flex flex-wrap gap-x-4 gap-y-1 border-t pt-3 text-sm"
                    >
                        <Link
                            v-if="term.version > 1"
                            :href="`/terms/${term.master_id}/version/${term.version - 1}`"
                            class="font-semibold text-ink underline underline-offset-4"
                        >
                            {{ t('compare-versions') }}
                        </Link>
                        <Link
                            href="/terms"
                            class="text-muted-foreground underline underline-offset-4"
                            >{{ t('cancel') }}</Link
                        >
                    </p>
                </template>

                <template v-else>
                    <Button
                        type="submit"
                        class="w-full"
                        :disabled="form.processing"
                        data-test="create-term"
                    >
                        <Spinner v-if="saving === 'create'" />
                        {{ t('create-term') }}
                    </Button>
                    <Link
                        href="/terms"
                        class="block text-center text-sm text-muted-foreground underline underline-offset-4"
                    >
                        {{ t('cancel') }}
                    </Link>
                </template>
            </section>

            <IdgiScore v-if="meta?.idgi" :score="meta.idgi" />

            <section class="space-y-4 rounded-lg border bg-card p-4">
                <Field
                    id="term_status"
                    :label="t('status')"
                    :error="form.errors.term_status"
                >
                    <select
                        id="term_status"
                        v-model="form.term_status"
                        class="h-10 w-full rounded-md border border-input bg-card px-2 text-sm"
                    >
                        <option
                            v-for="status in statuses"
                            :key="status"
                            :value="status"
                        >
                            {{ statusLabels[status] ?? status }}
                        </option>
                    </select>
                </Field>

                <label class="flex items-start gap-2.5 text-sm">
                    <input
                        v-model="form.do_not_use"
                        type="checkbox"
                        class="mt-0.5 size-4 accent-[var(--primary)]"
                    />
                    <span>
                        <span class="font-bold">Not recommended</span>
                        <span class="block text-muted-foreground"
                            >Readers see a warning next to the term.</span
                        >
                    </span>
                </label>
            </section>

            <fieldset class="rounded-lg border bg-card p-4">
                <legend class="px-1 text-sm font-bold">{{ t('tags') }}</legend>
                <div class="mt-1 grid gap-1.5">
                    <label
                        v-for="tag in tagOptions"
                        :key="tag"
                        class="flex items-center gap-2.5 text-sm"
                    >
                        <input
                            v-model="form.tags"
                            type="checkbox"
                            :value="tag"
                            class="size-4 accent-[var(--primary)]"
                        />
                        {{ tag }}
                    </label>
                </div>
                <p
                    v-if="form.errors.tags"
                    class="mt-2 text-sm text-destructive"
                >
                    {{ form.errors.tags }}
                </p>
            </fieldset>

            <fieldset v-if="editors" class="rounded-lg border bg-card p-4">
                <legend class="px-1 text-sm font-bold">
                    {{ t('editors') }}
                </legend>
                <p class="text-xs text-muted-foreground">
                    Editors can change this term. Administrators can edit every
                    term. New editors are emailed.
                </p>

                <p
                    v-if="!editors.all.length"
                    class="mt-3 text-sm text-muted-foreground"
                >
                    Everyone with an account is an administrator, so there is
                    nobody to assign yet.
                </p>

                <template v-else>
                    <input
                        v-if="editors.all.length > 8"
                        v-model="editorFilter"
                        type="search"
                        aria-label="Find a person"
                        placeholder="Find a person"
                        class="mt-3 h-9 w-full rounded-md border border-input bg-background px-2.5 text-sm"
                    />
                    <div class="mt-2 grid max-h-64 gap-1.5 overflow-auto">
                        <label
                            v-for="user in editorList"
                            :key="user.id"
                            class="flex items-center gap-2.5 text-sm"
                        >
                            <input
                                v-model="form.editors"
                                type="checkbox"
                                :value="user.id"
                                class="size-4 accent-[var(--primary)]"
                            />
                            {{ user.name }}
                        </label>
                    </div>
                </template>
            </fieldset>
        </aside>
    </form>
</template>
