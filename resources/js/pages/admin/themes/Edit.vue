<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Plus, X } from '@lucide/vue';
import { computed, onBeforeUnmount, watch } from 'vue';
import AdminNav from '@/components/admin/AdminNav.vue';
import ColourField from '@/components/admin/ColourField.vue';
import Field from '@/components/admin/Field.vue';
import ThemePreview from '@/components/admin/ThemePreview.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { useLeaveGuard } from '@/composables/useLeaveGuard';
import { useTranslations } from '@/composables/useTranslations';
import { contrastIssues } from '@/lib/themeContrast';
import type { ContrastIssue } from '@/lib/themeContrast';
import { COLOUR_ROLES } from '@/types';
import type {
    EditableTheme,
    FontChoice,
    Palette,
    TermSummary,
    ThemeSettings,
} from '@/types';

defineOptions({ layout: { width: 'wide' } });

const props = defineProps<{
    /** Not `theme`, which is the shared prop the site layout draws with. */
    editing: EditableTheme;
    fonts: { bundled: string[]; site: string[] };
    sample: TermSummary[];
}>();

const { t } = useTranslations();

/** Where a new dark set starts (the AIM theme's dark colours). */
const STARTING_DARK: Palette = {
    background: '#0d1630',
    surface: '#131f43',
    text: '#e7edf7',
    muted_text: '#a4b2cd',
    border: '#283a6f',
    link: '#cfe0ff',
    primary: '#8ad4d9',
    primary_text: '#0d1630',
    accent: '#7fd2d7',
    highlight: '#f5cf6b',
    highlight_text: '#1b2540',
};

const MAX_ACCENTS = 6;

// A plain copy: structuredClone can't copy Vue's reactive props.
function copy<T>(value: T): T {
    return JSON.parse(JSON.stringify(value)) as T;
}

const form = useForm({
    name: props.editing.name,
    settings: copy(props.editing.settings) as ThemeSettings,
    logo: null as File | null,
    remove_logo: false,
});

useLeaveGuard(() => form.isDirty && !form.processing);

function error(key: string): string | undefined {
    return (form.errors as Record<string, string | undefined>)[key];
}

// Turning dark colours off and on again brings back the last ones.
let lastDark: Palette = copy(
    props.editing.settings.colours.dark ?? STARTING_DARK,
);

const hasDark = computed({
    get: () => form.settings.colours.dark !== null,
    set: (on: boolean) => {
        if (on) {
            form.settings.colours.dark = copy(lastDark);
        } else {
            lastDark = copy(form.settings.colours.dark ?? lastDark);
            form.settings.colours.dark = null;
        }
    },
});

function addAccent() {
    if (form.settings.accents.length < MAX_ACCENTS) {
        form.settings.accents.push(form.settings.colours.light.accent);
    }
}

function removeAccent(index: number) {
    if (form.settings.accents.length > 1) {
        form.settings.accents.splice(index, 1);
    }
}

// Fonts are offered as "bundled|Family" or "site|family".
function fontValue(font: FontChoice): string {
    return `${font.source}|${font.family}`;
}

function setFont(slot: 'body' | 'heading', value: string) {
    const [source, family] = value.split('|');
    const current = form.settings.fonts[slot];

    form.settings.fonts[slot] =
        source === 'site'
            ? {
                  family,
                  source: 'site',
                  fallback: props.fonts.bundled.includes(current.fallback)
                      ? current.fallback
                      : props.fonts.bundled[0],
              }
            : { family, source: 'bundled', fallback: family };
}

// The honeycomb needs hexagon or circle letters; cycling needs two accents.
const honeycombAllowed = computed(() =>
    ['hexagon', 'circle'].includes(form.settings.letter_shape),
);
const cycleAllowed = computed(() => form.settings.accents.length >= 2);

watch(honeycombAllowed, (allowed) => {
    if (!allowed && form.settings.home_alphabet === 'honeycomb') {
        form.settings.home_alphabet = 'grid';
    }
});

watch(cycleAllowed, (allowed) => {
    if (!allowed && form.settings.letter_colours === 'cycle') {
        form.settings.letter_colours = 'single';
    }
});

const switches = computed(() => [
    {
        name: 'letter_shape' as const,
        label: t('theme-letter-shape'),
        hint: t('theme-letter-shape-hint'),
        options: (['plain', 'square', 'hexagon', 'circle'] as const).map(
            (value) => ({ value, label: t(`shape-${value}`), note: '' }),
        ),
    },
    {
        name: 'home_alphabet' as const,
        label: t('theme-home-alphabet'),
        hint: '',
        options: (['row', 'grid', 'honeycomb'] as const).map((value) => ({
            value,
            label: t(`alphabet-${value}`),
            note:
                value === 'honeycomb' && !honeycombAllowed.value
                    ? t('theme-honeycomb-needs')
                    : '',
        })),
    },
    {
        name: 'definition_style' as const,
        label: t('theme-definition-style'),
        hint: '',
        options: (['plain', 'italic-dash'] as const).map((value) => ({
            value,
            label: t(`definition-${value}`),
            note: '',
        })),
    },
    {
        name: 'letter_colours' as const,
        label: t('theme-letter-colours'),
        hint: '',
        options: (['single', 'cycle'] as const).map((value) => ({
            value,
            label: t(`letters-${value}`),
            note:
                value === 'cycle' && !cycleAllowed.value
                    ? t('theme-cycle-needs')
                    : '',
        })),
    },
]);

const contrast = computed(() => [
    ...contrastIssues(form.settings.colours.light).map((issue) => ({
        issue,
        dark: false,
    })),
    ...(form.settings.colours.dark
        ? contrastIssues(form.settings.colours.dark).map((issue) => ({
              issue,
              dark: true,
          }))
        : []),
]);

function describe(issue: ContrastIssue): string {
    return t('theme-contrast-pair', {
        text: t(`colour-${issue.text}`),
        background: t(`colour-${issue.background}`).toLowerCase(),
        ratio: issue.ratio.toFixed(1),
    });
}

// The logo the preview shows: a newly chosen file, none, or the saved one.
const chosenLogoUrl = computed(() =>
    form.logo ? URL.createObjectURL(form.logo) : null,
);

watch(chosenLogoUrl, (_url, previous) => {
    if (previous) {
        URL.revokeObjectURL(previous);
    }
});

onBeforeUnmount(() => {
    if (chosenLogoUrl.value) {
        URL.revokeObjectURL(chosenLogoUrl.value);
    }
});

const previewLogo = computed(
    () =>
        chosenLogoUrl.value ??
        (form.remove_logo ? null : props.editing.settings.logo),
);

function chooseLogo(event: Event) {
    const input = event.target as HTMLInputElement;
    form.logo = input.files?.[0] ?? null;

    if (form.logo) {
        form.remove_logo = false;
    }
}

function submit() {
    const url = `/themes/${props.editing.key}`;
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            form.defaults({
                name: form.name,
                settings: copy(form.settings),
                logo: null,
                remove_logo: false,
            });
            form.reset();
        },
    };

    // A logo goes up as multipart form data, which PHP only reads from a POST.
    if (form.logo) {
        form.transform((data) => ({ ...data, _method: 'put' })).post(url, {
            ...options,
            forceFormData: true,
        });
    } else {
        form.transform((data) => data).put(url, options);
    }
}
</script>

<template>
    <Head :title="t('theme-editing', { theme: editing.name })" />

    <AdminNav />

    <Link
        href="/themes"
        class="inline-flex items-center gap-1.5 text-sm font-semibold text-ink hover:underline"
    >
        <ArrowLeft class="size-4" aria-hidden="true" />
        {{ t('manage-themes') }}
    </Link>
    <h1 class="mt-3 text-2xl font-bold text-ink">
        {{ t('theme-editing', { theme: editing.name }) }}
    </h1>
    <p v-if="editing.based_on" class="mt-1 text-sm text-muted-foreground">
        {{ t('theme-based-on', { theme: editing.based_on }) }}
    </p>
    <p
        v-if="editing.active"
        class="mt-4 max-w-2xl rounded-md border-l-4 border-brand bg-brand-soft px-4 py-2 text-sm"
    >
        {{ t('theme-active-note') }}
    </p>

    <div
        class="mt-8 grid gap-x-10 gap-y-10 lg:grid-cols-[minmax(0,1fr)_minmax(0,26rem)]"
    >
        <form class="grid min-w-0 gap-10" novalidate @submit.prevent="submit">
            <Field id="name" :label="t('theme-name')" :error="form.errors.name">
                <template #default="{ invalid }">
                    <Input
                        id="name"
                        v-model="form.name"
                        required
                        class="h-11 max-w-md bg-card text-lg font-bold"
                        :aria-invalid="invalid"
                    />
                </template>
            </Field>

            <!-- Colours -->
            <fieldset class="grid gap-4">
                <legend class="text-lg font-bold text-ink">
                    {{ t('theme-colours') }}
                </legend>
                <p class="-mt-2 text-sm text-muted-foreground">
                    {{ t('theme-colours-hint') }}
                </p>

                <h2 class="text-base font-bold">
                    {{ t('theme-light-colours') }}
                </h2>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <ColourField
                        v-for="role in COLOUR_ROLES"
                        :id="`light-${role}`"
                        :key="role"
                        v-model="form.settings.colours.light[role]"
                        :label="t(`colour-${role}`)"
                        :error="error(`settings.colours.light.${role}`)"
                    />
                </div>

                <label class="mt-2 flex items-start gap-2.5">
                    <input
                        v-model="hasDark"
                        type="checkbox"
                        class="mt-1 size-4"
                        aria-describedby="has-dark-hint"
                    />
                    <span>
                        <span class="font-bold">{{ t('theme-has-dark') }}</span>
                        <span
                            id="has-dark-hint"
                            class="block text-sm text-muted-foreground"
                            >{{ t('theme-has-dark-hint') }}</span
                        >
                    </span>
                </label>

                <template v-if="form.settings.colours.dark">
                    <h2 class="text-base font-bold">
                        {{ t('theme-dark-colours') }}
                    </h2>
                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        <ColourField
                            v-for="role in COLOUR_ROLES"
                            :id="`dark-${role}`"
                            :key="role"
                            v-model="form.settings.colours.dark[role]"
                            :label="t(`colour-${role}`)"
                            :error="error(`settings.colours.dark.${role}`)"
                        />
                    </div>
                </template>

                <!-- Contrast warnings: advice only, never a reason to refuse saving -->
                <div
                    role="status"
                    class="rounded-md border-l-4 px-4 py-3 text-sm"
                    :class="
                        contrast.length
                            ? 'border-warning bg-warning-soft'
                            : 'border-success bg-success-soft'
                    "
                    data-test="contrast"
                >
                    <p class="font-bold">{{ t('theme-contrast') }}</p>
                    <template v-if="contrast.length">
                        <p>{{ t('theme-contrast-warning') }}</p>
                        <ul class="mt-1 list-disc pl-5">
                            <li v-for="(entry, index) in contrast" :key="index">
                                {{
                                    entry.dark
                                        ? t('theme-contrast-dark', {
                                              pair: describe(entry.issue),
                                          })
                                        : describe(entry.issue)
                                }}
                            </li>
                        </ul>
                    </template>
                    <p v-else>{{ t('theme-contrast-ok') }}</p>
                </div>
            </fieldset>

            <!-- Accent colours -->
            <fieldset class="grid gap-3">
                <legend class="text-lg font-bold text-ink">
                    {{ t('theme-accents') }}
                </legend>
                <p class="-mt-1 text-sm text-muted-foreground">
                    {{ t('theme-accents-hint') }}
                </p>
                <ol class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    <li
                        v-for="(_accent, index) in form.settings.accents"
                        :key="index"
                        class="flex items-end gap-1.5"
                    >
                        <ColourField
                            :id="`accent-${index}`"
                            v-model="form.settings.accents[index]"
                            class="flex-1"
                            :label="`${index + 1}`"
                            :error="error(`settings.accents.${index}`)"
                        />
                        <Button
                            v-if="form.settings.accents.length > 1"
                            type="button"
                            variant="ghost"
                            size="icon"
                            class="mb-0.5"
                            :aria-label="
                                t('theme-remove-accent', { number: index + 1 })
                            "
                            @click="removeAccent(index)"
                        >
                            <X class="size-4" aria-hidden="true" />
                        </Button>
                    </li>
                </ol>
                <InputError :message="error('settings.accents')" />
                <div>
                    <Button
                        v-if="form.settings.accents.length < MAX_ACCENTS"
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="addAccent"
                    >
                        <Plus class="size-4" aria-hidden="true" />
                        {{ t('theme-add-accent') }}
                    </Button>
                </div>
            </fieldset>

            <!-- Fonts, size and corners -->
            <fieldset class="grid gap-5">
                <legend class="text-lg font-bold text-ink">
                    {{ t('theme-fonts') }}
                </legend>

                <div
                    v-for="slot in ['body', 'heading'] as const"
                    :key="slot"
                    class="grid gap-3 sm:grid-cols-2"
                >
                    <Field
                        :id="`font-${slot}`"
                        :label="
                            slot === 'body'
                                ? t('theme-body-font')
                                : t('theme-heading-font')
                        "
                        :error="error(`settings.fonts.${slot}.family`)"
                    >
                        <select
                            :id="`font-${slot}`"
                            :value="fontValue(form.settings.fonts[slot])"
                            class="h-10 w-full rounded-md border border-input bg-card px-2"
                            @change="
                                setFont(
                                    slot,
                                    ($event.target as HTMLSelectElement).value,
                                )
                            "
                        >
                            <optgroup :label="t('theme-fonts-bundled')">
                                <option
                                    v-for="family in fonts.bundled"
                                    :key="family"
                                    :value="`bundled|${family}`"
                                >
                                    {{ family }}
                                </option>
                            </optgroup>
                            <optgroup
                                v-if="fonts.site.length"
                                :label="t('theme-fonts-site')"
                            >
                                <option
                                    v-for="family in fonts.site"
                                    :key="family"
                                    :value="`site|${family}`"
                                >
                                    {{ family }}
                                </option>
                            </optgroup>
                        </select>
                    </Field>
                    <Field
                        v-if="form.settings.fonts[slot].source === 'site'"
                        :id="`fallback-${slot}`"
                        :label="t('theme-fallback-font')"
                        :error="error(`settings.fonts.${slot}.fallback`)"
                    >
                        <select
                            :id="`fallback-${slot}`"
                            v-model="form.settings.fonts[slot].fallback"
                            class="h-10 w-full rounded-md border border-input bg-card px-2"
                        >
                            <option
                                v-for="family in fonts.bundled"
                                :key="family"
                                :value="family"
                            >
                                {{ family }}
                            </option>
                        </select>
                    </Field>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <Field
                        id="base_size"
                        :label="t('theme-size')"
                        :hint="t('theme-size-hint')"
                        :error="error('settings.base_size')"
                    >
                        <template #default="{ describedby }">
                            <div class="flex items-center gap-3">
                                <input
                                    id="base_size"
                                    v-model.number="form.settings.base_size"
                                    type="range"
                                    min="15"
                                    max="20"
                                    step="1"
                                    class="flex-1"
                                    :aria-describedby="describedby"
                                />
                                <output
                                    for="base_size"
                                    class="w-12 text-right font-mono text-sm"
                                    >{{ form.settings.base_size }}px</output
                                >
                            </div>
                        </template>
                    </Field>
                    <Field
                        id="radius"
                        :label="t('theme-radius')"
                        :hint="t('theme-radius-hint')"
                        :error="error('settings.radius')"
                    >
                        <template #default="{ describedby }">
                            <div class="flex items-center gap-3">
                                <input
                                    id="radius"
                                    v-model.number="form.settings.radius"
                                    type="range"
                                    min="0"
                                    max="16"
                                    step="1"
                                    class="flex-1"
                                    :aria-describedby="describedby"
                                />
                                <output
                                    for="radius"
                                    class="w-12 text-right font-mono text-sm"
                                    >{{ form.settings.radius }}px</output
                                >
                            </div>
                        </template>
                    </Field>
                </div>
            </fieldset>

            <!-- Default logo -->
            <Field
                id="logo"
                :label="t('theme-logo')"
                :hint="t('theme-logo-hint')"
                :error="form.errors.logo"
            >
                <template #default="{ describedby, invalid }">
                    <img
                        v-if="previewLogo"
                        :src="previewLogo"
                        alt=""
                        class="h-16 w-auto self-start rounded border bg-card p-1"
                    />
                    <input
                        id="logo"
                        type="file"
                        accept="image/png,image/jpeg,image/gif,image/webp"
                        :aria-describedby="describedby"
                        :aria-invalid="invalid"
                        class="text-sm file:mr-3 file:rounded-md file:border file:border-input file:bg-card file:px-3 file:py-1.5 file:font-semibold"
                        @change="chooseLogo"
                    />
                    <label
                        v-if="editing.settings.logo"
                        class="flex items-center gap-2 text-sm"
                    >
                        <input
                            v-model="form.remove_logo"
                            type="checkbox"
                            :disabled="form.logo !== null"
                            class="size-4"
                        />
                        {{ t('theme-logo-remove') }}
                    </label>
                </template>
            </Field>

            <!-- The four switches -->
            <section class="grid gap-6">
                <h2 class="text-lg font-bold text-ink">
                    {{ t('theme-layout') }}
                </h2>
                <fieldset
                    v-for="group in switches"
                    :key="group.name"
                    class="grid gap-2"
                >
                    <legend class="font-bold">{{ group.label }}</legend>
                    <p v-if="group.hint" class="text-sm text-muted-foreground">
                        {{ group.hint }}
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <label
                            v-for="option in group.options"
                            :key="option.value"
                            class="flex cursor-pointer items-center gap-2 rounded-md border bg-card px-3 py-2 text-sm has-checked:border-brand has-checked:ring-2 has-checked:ring-brand/40 has-disabled:cursor-not-allowed has-disabled:opacity-60"
                        >
                            <input
                                v-model="form.settings[group.name]"
                                type="radio"
                                :name="group.name"
                                :value="option.value"
                                :disabled="Boolean(option.note)"
                            />
                            <span>
                                {{ option.label }}
                                <span
                                    v-if="option.note"
                                    class="block text-xs text-muted-foreground"
                                    >{{ option.note }}</span
                                >
                            </span>
                        </label>
                    </div>
                    <InputError :message="error(`settings.${group.name}`)" />
                </fieldset>
            </section>

            <div
                class="sticky bottom-0 z-20 -mx-5 border-t bg-card/95 px-5 py-3 backdrop-blur sm:-mx-8 sm:px-8"
            >
                <Button
                    type="submit"
                    class="w-full sm:w-auto"
                    :disabled="form.processing"
                    data-test="save-theme"
                >
                    <Spinner v-if="form.processing" />
                    {{ t('save-theme') }}
                </Button>
            </div>
        </form>

        <aside
            class="min-w-0 lg:sticky lg:top-6 lg:max-h-[calc(100vh-3rem)] lg:self-start lg:overflow-y-auto"
        >
            <ThemePreview
                :theme-key="editing.key"
                :settings="form.settings"
                :logo="previewLogo"
                :sample="sample"
            />
        </aside>
    </div>
</template>
