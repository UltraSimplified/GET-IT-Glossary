<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { useTranslations } from '@/composables/useTranslations';
import type { ThemeRow } from '@/types';

/**
 * Asks for a name, then copies the theme. The server opens the copy in
 * the theme editor.
 */
const props = defineProps<{ theme: ThemeRow }>();

const { t } = useTranslations();
const open = ref(false);

const form = useForm({
    from: props.theme.key,
    name: t('theme-copy-name-default', { theme: props.theme.name }),
});

function submit() {
    form.post('/themes', { preserveScroll: true });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <slot />
        </DialogTrigger>
        <DialogContent>
            <form class="grid gap-4" novalidate @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>{{
                        t('theme-copy-title', { theme: theme.name })
                    }}</DialogTitle>
                    <DialogDescription>{{
                        t('theme-copy-description')
                    }}</DialogDescription>
                </DialogHeader>

                <div class="grid gap-1.5">
                    <label
                        :for="`copy-name-${theme.key}`"
                        class="text-sm font-bold"
                        >{{ t('theme-copy-name') }}</label
                    >
                    <Input
                        :id="`copy-name-${theme.key}`"
                        v-model="form.name"
                        v-focus
                        required
                        class="h-10"
                        :aria-invalid="Boolean(form.errors.name)"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <DialogFooter class="gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="open = false"
                        >{{ t('cancel') }}</Button
                    >
                    <Button type="submit" :disabled="form.processing">
                        <Spinner v-if="form.processing" />
                        {{ t('theme-copy-confirm') }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
