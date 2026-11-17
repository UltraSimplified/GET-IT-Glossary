<script setup lang="ts">
import { ref } from 'vue';
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
import { useTranslations } from '@/composables/useTranslations';

/**
 * Asks before doing something that cannot be undone. Wrap the control
 * that starts the action in the default slot.
 */
const { destructive = true } = defineProps<{
    title: string;
    description?: string;
    confirmLabel: string;
    destructive?: boolean;
}>();

const emit = defineEmits<{ confirm: [] }>();

const { t } = useTranslations();
const open = ref(false);

function confirm() {
    open.value = false;
    emit('confirm');
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <slot />
        </DialogTrigger>
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription v-if="description">{{
                    description
                }}</DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <Button variant="outline" @click="open = false">{{
                    t('cancel')
                }}</Button>
                <Button
                    :variant="destructive ? 'destructive' : 'default'"
                    data-test="confirm-button"
                    @click="confirm"
                >
                    {{ confirmLabel }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
