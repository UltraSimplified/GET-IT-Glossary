<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { onBeforeUnmount, reactive, ref } from 'vue';
import AdminNav from '@/components/admin/AdminNav.vue';
import Field from '@/components/admin/Field.vue';
import IdgiBubble from '@/components/glossary/IdgiBubble.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { useLeaveGuard } from '@/composables/useLeaveGuard';
import { useTranslations } from '@/composables/useTranslations';
import type { EditableSite, IdgiPictureSlot, Option } from '@/types';

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
        idgi_rest: null as File | null,
        idgi_hover: null as File | null,
        idgi_thanks: null as File | null,
        remove_idgi_rest: false,
        remove_idgi_hover: false,
        remove_idgi_thanks: false,
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

// The "I don't get it" pictures: at rest, on hover and after the click.
const SLOTS: IdgiPictureSlot[] = ['rest', 'hover', 'thanks'];

const fileKey = {
    rest: 'idgi_rest',
    hover: 'idgi_hover',
    thanks: 'idgi_thanks',
} as const;

const removeKey = {
    rest: 'remove_idgi_rest',
    hover: 'remove_idgi_hover',
    thanks: 'remove_idgi_thanks',
} as const;

// Previews of chosen files that haven't been saved yet.
const chosen = reactive<Record<IdgiPictureSlot, string | null>>({
    rest: null,
    hover: null,
    thanks: null,
});

// Changed after saving, so the file inputs start empty again.
const inputsVersion = ref(0);

function forgetChosen() {
    for (const slot of SLOTS) {
        if (chosen[slot]) {
            URL.revokeObjectURL(chosen[slot]);
        }

        chosen[slot] = null;
    }
}

onBeforeUnmount(forgetChosen);

function choosePicture(slot: IdgiPictureSlot, event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;

    if (chosen[slot]) {
        URL.revokeObjectURL(chosen[slot]);
    }

    form[fileKey[slot]] = file;
    chosen[slot] = file ? URL.createObjectURL(file) : null;

    if (file) {
        form[removeKey[slot]] = false;
    }
}

function restPicture(): string | null {
    return chosen.rest ?? (form.remove_idgi_rest ? null : props.site.idgi.rest);
}

/** What the button will show in each state once the form is saved. */
function preview(slot: IdgiPictureSlot): { src: string | null; note: string } {
    const rest = restPicture();

    if (slot === 'rest' || !rest) {
        const own = slot === 'rest' ? rest : null;

        return own
            ? {
                  src: own,
                  note: chosen.rest
                      ? t('idgi-picture-new')
                      : t('idgi-picture-own'),
              }
            : { src: null, note: t('idgi-picture-builtin') };
    }

    const own =
        chosen[slot] ?? (form[removeKey[slot]] ? null : props.site.idgi[slot]);

    if (own) {
        return {
            src: own,
            note: chosen[slot] ? t('idgi-picture-new') : t('idgi-picture-own'),
        };
    }

    return { src: rest, note: t('idgi-picture-uses-rest') };
}

function submit() {
    // Files go up as multipart form data, which PHP only reads from a POST.
    form.transform((data) => ({ ...data, _method: 'put' })).post('/site', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            forgetChosen();
            inputsVersion.value++;
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

        <!-- The "I don't get it" pictures, each previewing what the button will show. -->
        <section class="grid gap-3" aria-labelledby="idgi-pictures-title">
            <h2 id="idgi-pictures-title" class="text-sm font-bold">
                {{ t('idgi-pictures') }}
            </h2>
            <p class="-mt-2 text-sm text-muted-foreground">
                {{ t('idgi-pictures-hint') }}
            </p>
            <div class="grid gap-4 sm:grid-cols-3">
                <div
                    v-for="slot in SLOTS"
                    :key="slot"
                    class="grid content-start gap-2 rounded-lg border bg-card p-3"
                    :data-test="`idgi-picture-${slot}`"
                >
                    <label :for="`idgi-${slot}`" class="text-sm font-bold">{{
                        t(`idgi-picture-${slot}`)
                    }}</label>
                    <p
                        v-if="slot !== 'rest'"
                        class="-mt-1 text-xs text-muted-foreground"
                    >
                        {{ t('idgi-picture-optional') }}
                    </p>
                    <div
                        class="rounded-md"
                        :class="preview(slot).src ? 'bg-white p-2' : 'p-1'"
                    >
                        <img
                            v-if="preview(slot).src"
                            :src="preview(slot).src ?? undefined"
                            alt=""
                            class="h-auto w-full"
                        />
                        <IdgiBubble v-else :state="slot" />
                    </div>
                    <p
                        :id="`idgi-${slot}-note`"
                        class="text-xs text-muted-foreground"
                    >
                        {{ preview(slot).note }}
                    </p>
                    <input
                        :id="`idgi-${slot}`"
                        :key="`idgi-${slot}-${inputsVersion}`"
                        type="file"
                        accept="image/png,image/jpeg,image/gif,image/webp"
                        :aria-describedby="`idgi-${slot}-note`"
                        :aria-invalid="Boolean(form.errors[fileKey[slot]])"
                        class="w-full min-w-0 text-xs file:mr-2 file:rounded-md file:border file:border-input file:bg-card file:px-2 file:py-1 file:font-semibold"
                        @change="choosePicture(slot, $event)"
                    />
                    <InputError :message="form.errors[fileKey[slot]]" />
                    <label
                        v-if="site.idgi[slot]"
                        class="flex items-start gap-2 text-xs"
                    >
                        <input
                            v-model="form[removeKey[slot]]"
                            type="checkbox"
                            :disabled="chosen[slot] !== null"
                            class="mt-0.5 size-4 shrink-0"
                        />
                        {{
                            slot === 'rest'
                                ? t('idgi-picture-remove-rest')
                                : t('idgi-picture-remove')
                        }}
                    </label>
                </div>
            </div>
        </section>

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
