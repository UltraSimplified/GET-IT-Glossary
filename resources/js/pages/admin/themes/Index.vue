<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Check, Copy, Pencil, Trash2 } from '@lucide/vue';
import AdminNav from '@/components/admin/AdminNav.vue';
import CopyThemeDialog from '@/components/admin/CopyThemeDialog.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import type { ColourRole, ThemeRow } from '@/types';

defineOptions({ layout: { width: 'wide' } });

defineProps<{
    themes: ThemeRow[];
}>();

const page = usePage();
const { t } = useTranslations();

/** The colours shown as a strip on each card. */
const swatches: ColourRole[] = [
    'background',
    'surface',
    'text',
    'link',
    'primary',
    'accent',
    'highlight',
];

function use(theme: ThemeRow) {
    router.put('/site/theme', { theme: theme.key }, { preserveScroll: true });
}

function destroy(theme: ThemeRow) {
    router.delete(`/themes/${theme.key}`, { preserveScroll: true });
}
</script>

<template>
    <Head :title="t('manage-themes')" />

    <AdminNav />

    <h1 class="text-2xl font-bold text-ink">{{ t('manage-themes') }}</h1>
    <p class="mt-2 max-w-2xl text-muted-foreground">{{ t('themes-intro') }}</p>

    <p
        v-if="page.props.errors?.theme"
        role="alert"
        class="mt-6 max-w-2xl rounded-md border-l-4 border-destructive bg-danger-soft px-4 py-3 text-sm"
    >
        {{ page.props.errors.theme }}
    </p>

    <ul class="mt-8 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        <li
            v-for="theme in themes"
            :key="theme.key"
            class="flex flex-col overflow-hidden rounded-lg border bg-card"
            :class="{ 'ring-2 ring-brand': theme.active }"
        >
            <!-- The theme's colours -->
            <div class="flex h-14" aria-hidden="true">
                <span
                    v-for="role in swatches"
                    :key="role"
                    class="flex-1"
                    :style="{ backgroundColor: theme.colours[role] }"
                />
            </div>
            <div class="flex h-2" aria-hidden="true">
                <span
                    v-for="(accent, index) in theme.accents"
                    :key="index"
                    class="flex-1"
                    :style="{ backgroundColor: accent }"
                />
            </div>

            <div class="flex flex-1 flex-col gap-3 p-4">
                <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                    <h2 class="text-lg font-bold text-ink">
                        {{ theme.name }}
                    </h2>
                    <span
                        v-if="theme.active"
                        class="inline-flex items-center gap-1 rounded-full bg-brand-soft px-2 py-0.5 text-xs font-bold text-ink"
                    >
                        <Check class="size-3.5" aria-hidden="true" />
                        {{ t('theme-in-use') }}
                    </span>
                    <span
                        v-if="theme.built_in"
                        class="rounded-full bg-muted px-2 py-0.5 text-xs font-semibold text-muted-foreground"
                        >{{ t('theme-built-in') }}</span
                    >
                </div>

                <p v-if="theme.description" class="text-sm">
                    {{ theme.description }}
                </p>
                <p v-if="theme.based_on" class="text-sm text-muted-foreground">
                    {{ t('theme-based-on', { theme: theme.based_on }) }}
                </p>

                <p class="text-xs text-muted-foreground">
                    {{ theme.font }} · {{ t(`shape-${theme.letter_shape}`) }} ·
                    {{
                        theme.has_dark
                            ? t('theme-light-and-dark')
                            : t('theme-light-only')
                    }}
                </p>

                <div class="mt-auto flex flex-wrap gap-2 pt-2">
                    <Button
                        v-if="!theme.active"
                        size="sm"
                        :data-test="`use-${theme.key}`"
                        @click="use(theme)"
                    >
                        {{ t('theme-use') }}
                    </Button>

                    <CopyThemeDialog :theme="theme">
                        <Button size="sm" variant="outline">
                            <Copy class="size-4" aria-hidden="true" />
                            {{ t('theme-copy') }}
                        </Button>
                    </CopyThemeDialog>

                    <!-- A full page load: the editor loads every font for its preview. -->
                    <Button
                        v-if="!theme.built_in"
                        size="sm"
                        variant="outline"
                        as-child
                    >
                        <a :href="`/themes/${theme.key}/edit`">
                            <Pencil class="size-4" aria-hidden="true" />
                            {{ t('theme-edit') }}
                        </a>
                    </Button>

                    <ConfirmDialog
                        v-if="!theme.built_in && !theme.active"
                        :title="t('theme-delete-title', { theme: theme.name })"
                        :description="t('theme-delete-description')"
                        :confirm-label="t('theme-delete-confirm')"
                        @confirm="destroy(theme)"
                    >
                        <Button
                            size="sm"
                            variant="outline"
                            class="text-destructive hover:text-destructive"
                        >
                            <Trash2 class="size-4" aria-hidden="true" />
                            {{ t('theme-delete') }}
                        </Button>
                    </ConfirmDialog>
                </div>
                <p
                    v-if="!theme.built_in && theme.active"
                    class="text-xs text-muted-foreground"
                >
                    {{ t('theme-delete-in-use') }}
                </p>
            </div>
        </li>
    </ul>
</template>
