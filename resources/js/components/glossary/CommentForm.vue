<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { useId } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';

const { termId } = defineProps<{
    termId: number;
}>();

const { t } = useTranslations();
const fieldId = useId();

const form = useForm({ term_id: termId, comment: '' });

function submit() {
    form.transform((data) => ({ ...data, term_id: termId })).post('/comments', {
        preserveScroll: true,
        onSuccess: () => form.reset('comment'),
    });
}
</script>

<template>
    <form class="space-y-3" @submit.prevent="submit">
        <label :for="fieldId" class="block text-sm font-bold">{{
            t('comment')
        }}</label>
        <textarea
            :id="fieldId"
            v-model="form.comment"
            rows="3"
            required
            class="block w-full rounded-md border border-input bg-card px-3 py-2 text-base"
        ></textarea>
        <InputError :message="form.errors.comment" />
        <Button type="submit" :disabled="form.processing">{{
            t('post-comment')
        }}</Button>
    </form>
</template>
