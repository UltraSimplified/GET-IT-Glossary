<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { SquarePen } from '@lucide/vue';
import { computed } from 'vue';
import RichHtml from '@/components/glossary/RichHtml.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';
import type { ContactFormKind, ContentPage } from '@/types';

const props = defineProps<{
    page: ContentPage | null;
    /** Set on the three "Get in touch" pages, which end in a form. */
    form: ContactFormKind | null;
    sent: boolean;
    canEdit: boolean;
}>();

const { t } = useTranslations();

// Each form posts its message under a different field name, as the original site did.
const forms = {
    feedback: {
        action: '/contact/send_feedback',
        field: 'feedback',
        label: 'feedback',
        submit: 'send-feedback',
        thanks: 'thank-you-feedback',
        title: 'feedback',
        rows: 8,
    },
    'suggest-term': {
        action: '/contact/suggest_term',
        field: 'terms',
        label: 'suggested-terms',
        submit: 'send-suggestion',
        thanks: 'thank-you-suggestion',
        title: 'suggest-a-term',
        rows: 5,
    },
    'suggest-translation': {
        action: '/contact/suggest_translation',
        field: 'suggestion',
        label: 'suggestion',
        submit: 'send-suggestion',
        thanks: 'thank-you-suggestion',
        title: 'suggestion',
        rows: 8,
    },
} as const;

const config = computed(() => (props.form ? forms[props.form] : null));
const title = computed(
    () => props.page?.title ?? (config.value ? t(config.value.title) : ''),
);

const contact = useForm({ name: '', email: '', message: '', website: '' });

function submit() {
    if (!config.value) {
        return;
    }

    const { field, action } = config.value;

    contact
        .transform(({ message, ...rest }) => ({ ...rest, [field]: message }))
        .post(action, {
            preserveScroll: true,
            onError: (errors) => {
                // Show the server's message for the renamed field under the textarea.
                if (errors[field]) {
                    contact.setError('message', errors[field]);
                }
            },
        });
}
</script>

<template>
    <Head :title="title" />

    <div class="flex items-start gap-4">
        <h1 class="flex-1 text-2xl font-bold text-balance text-ink md:text-3xl">
            {{ title }}
        </h1>
        <Link
            v-if="canEdit && page"
            :href="`/pages/${page.id}/edit`"
            class="mt-1 inline-flex shrink-0 items-center gap-1.5 rounded-md border bg-card px-3 py-1.5 text-sm font-semibold text-ink hover:bg-brand-soft"
        >
            <SquarePen class="size-4" aria-hidden="true" />
            {{ t('edit') }}
        </Link>
    </div>

    <RichHtml v-if="page" class="mt-6" :html="page.body_html" />

    <template v-if="config">
        <p
            v-if="sent"
            class="mt-8 rounded-lg bg-success-soft p-5 text-lg font-semibold text-success"
            role="status"
        >
            {{ t(config.thanks) }}
        </p>

        <form v-else class="mt-8 space-y-5" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="contact-name">{{ t('your-name') }}</Label>
                <Input
                    id="contact-name"
                    v-model="contact.name"
                    required
                    autocomplete="name"
                    class="h-11 bg-card text-base"
                />
                <InputError :message="contact.errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="contact-email">{{ t('your-email') }}</Label>
                <Input
                    id="contact-email"
                    v-model="contact.email"
                    type="email"
                    required
                    autocomplete="email"
                    class="h-11 bg-card text-base"
                />
                <InputError :message="contact.errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="contact-message">{{ t(config.label) }}</Label>
                <textarea
                    id="contact-message"
                    v-model="contact.message"
                    :rows="config.rows"
                    required
                    class="block w-full rounded-md border border-input bg-card px-3 py-2 text-base"
                ></textarea>
                <InputError :message="contact.errors.message" />
            </div>

            <!-- Left empty by people; bots fill it in and are quietly ignored. -->
            <div class="hidden" aria-hidden="true">
                <label for="contact-website">Website</label>
                <input
                    id="contact-website"
                    v-model="contact.website"
                    type="text"
                    tabindex="-1"
                    autocomplete="off"
                />
            </div>

            <Button type="submit" size="lg" :disabled="contact.processing">
                {{ t(config.submit) }}
            </Button>
        </form>
    </template>
</template>
