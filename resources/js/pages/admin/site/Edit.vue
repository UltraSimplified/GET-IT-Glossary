<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AdminNav from '@/components/admin/AdminNav.vue';
import Field from '@/components/admin/Field.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { useLeaveGuard } from '@/composables/useLeaveGuard';
import { useTranslations } from '@/composables/useTranslations';
import type { EditableSite, Option } from '@/types';

defineOptions({ layout: { width: 'wide' } });

const props = defineProps<{
    site: EditableSite;
    themeLogo: string | null;
    themeName: string;
    locales: Option[];
}>();

const { t } = useTranslations();

function fields() {
    return {
        name: props.site.name,
        tagline: props.site.tagline,
        locale: props.site.locale,
        font_stylesheet_url: props.site.font_stylesheet_url ?? '',
        logo: null as File | null,
        remove_logo: false,
    };
}

const form = useForm(fields());

useLeaveGuard(() => form.isDirty && !form.processing);

function chooseLogo(event: Event) {
    const input = event.target as HTMLInputElement;
    form.logo = input.files?.[0] ?? null;

    if (form.logo) {
        form.remove_logo = false;
    }
}

function submit() {
    // Files go up as multipart form data, which PHP only reads from a POST.
    form.transform((data) => ({ ...data, _method: 'put' })).post('/site', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.defaults(fields());
            form.reset();
        },
    });
}
</script>

<template>
    <Head :title="t('site-details')" />

    <AdminNav />

    <h1 class="text-2xl font-bold text-ink">{{ t('site-details') }}</h1>
    <p class="mt-2 max-w-2xl text-muted-foreground">
        {{ t('site-details-intro') }}
    </p>

    <form class="mt-8 grid max-w-2xl gap-7" novalidate @submit.prevent="submit">
        <Field
            id="name"
            :label="t('site-name')"
            :hint="t('site-name-hint')"
            :error="form.errors.name"
        >
            <template #default="{ describedby, invalid }">
                <Input
                    id="name"
                    v-model="form.name"
                    required
                    class="h-11 bg-card text-lg font-bold"
                    :aria-describedby="describedby"
                    :aria-invalid="invalid"
                />
            </template>
        </Field>

        <Field
            id="tagline"
            :label="t('site-tagline')"
            :hint="t('site-tagline-hint')"
            :error="form.errors.tagline"
        >
            <template #default="{ describedby, invalid }">
                <Input
                    id="tagline"
                    v-model="form.tagline"
                    class="h-10 bg-card"
                    :aria-describedby="describedby"
                    :aria-invalid="invalid"
                />
            </template>
        </Field>

        <Field
            id="locale"
            :label="t('site-language')"
            :hint="t('site-language-hint')"
            :error="form.errors.locale"
        >
            <template #default="{ describedby, invalid }">
                <select
                    id="locale"
                    v-model="form.locale"
                    :aria-describedby="describedby"
                    :aria-invalid="invalid"
                    class="h-10 w-full max-w-xs rounded-md border border-input bg-card px-2"
                >
                    <option
                        v-for="locale in locales"
                        :key="locale.value"
                        :value="locale.value"
                    >
                        {{ locale.label }}
                    </option>
                </select>
            </template>
        </Field>

        <Field
            id="logo"
            :label="t('site-logo')"
            :hint="t('site-logo-hint')"
            :error="form.errors.logo"
        >
            <template #default="{ describedby, invalid }">
                <div
                    class="flex flex-wrap items-center gap-4 rounded-lg border bg-card p-4"
                >
                    <img
                        v-if="site.logo && !form.remove_logo"
                        :src="site.logo"
                        alt=""
                        class="h-16 w-auto"
                    />
                    <img
                        v-else-if="themeLogo"
                        :src="themeLogo"
                        alt=""
                        class="h-16 w-auto opacity-80"
                    />
                    <p class="min-w-0 flex-1 text-sm text-muted-foreground">
                        {{
                            site.logo && !form.remove_logo
                                ? t('site-logo-own')
                                : t('site-logo-theme', { theme: themeName })
                        }}
                    </p>
                </div>
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
                    v-if="site.logo"
                    class="flex items-center gap-2 text-sm"
                    for="remove_logo"
                >
                    <input
                        id="remove_logo"
                        v-model="form.remove_logo"
                        type="checkbox"
                        :disabled="form.logo !== null"
                        class="size-4"
                    />
                    {{ t('site-logo-remove') }}
                </label>
            </template>
        </Field>

        <Field
            id="font_stylesheet_url"
            :label="t('site-font-stylesheet')"
            :hint="t('site-font-stylesheet-hint')"
            :error="form.errors.font_stylesheet_url"
        >
            <template #default="{ describedby, invalid }">
                <Input
                    id="font_stylesheet_url"
                    v-model="form.font_stylesheet_url"
                    type="url"
                    inputmode="url"
                    class="h-10 bg-card"
                    :aria-describedby="
                        [describedby, 'font-stylesheet-note'].join(' ')
                    "
                    :aria-invalid="invalid"
                />
                <p
                    id="font-stylesheet-note"
                    class="rounded-md border-l-4 border-warning bg-warning-soft px-3 py-2 text-sm"
                >
                    {{ t('site-font-stylesheet-note') }}
                </p>
                <p
                    v-if="site.font_stylesheet_url"
                    class="text-sm text-muted-foreground"
                >
                    {{
                        site.font_families.length
                            ? t('site-font-families', {
                                  families: site.font_families.join(', '),
                              })
                            : t('site-font-families-none')
                    }}
                </p>
            </template>
        </Field>

        <div
            class="sticky bottom-0 z-20 -mx-5 border-t bg-card/95 px-5 py-3 backdrop-blur sm:static sm:mx-0 sm:border-0 sm:bg-transparent sm:p-0"
        >
            <Button
                type="submit"
                class="w-full sm:w-auto"
                :disabled="form.processing"
                data-test="save-site"
            >
                <Spinner v-if="form.processing" />
                {{ t('save-site') }}
            </Button>
        </div>
    </form>
</template>
