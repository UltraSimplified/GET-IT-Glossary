<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import Field from '@/components/admin/Field.vue';
import RichTextEditor from '@/components/admin/RichTextEditor.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { useLeaveGuard } from '@/composables/useLeaveGuard';
import { PAGE_MENUS } from '@/types';
import type { EditablePage } from '@/types';

const props = defineProps<{
    page?: EditablePage;
}>();

const form = useForm({
    title: props.page?.title ?? '',
    body: props.page?.body ?? '',
    menu: props.page?.menu ?? (null as number | null),
    position: props.page?.position ?? (null as number | '' | null),
});

useLeaveGuard(() => form.isDirty && !form.processing);

function submit() {
    if (props.page) {
        form.put(`/pages/${props.page.id}`, { preserveScroll: 'errors' });
    } else {
        form.post('/pages', { preserveScroll: 'errors' });
    }
}
</script>

<template>
    <form
        class="grid gap-x-12 gap-y-8 lg:grid-cols-[minmax(0,1fr)_19rem]"
        novalidate
        @submit.prevent="submit"
    >
        <div class="space-y-7">
            <Field id="title" label="Title" :error="form.errors.title">
                <Input
                    id="title"
                    v-model="form.title"
                    required
                    class="h-11 bg-card text-lg font-bold"
                    :aria-invalid="!!form.errors.title"
                />
            </Field>

            <Field id="body" label="Content" :error="form.errors.body">
                <template #default="{ invalid }">
                    <RichTextEditor
                        id="body"
                        v-model="form.body"
                        headings
                        images
                        :invalid="invalid"
                    />
                </template>
            </Field>
            <!-- On small screens the sidebar comes last, so the save buttons ride along at the bottom. -->
            <div
                class="sticky bottom-0 z-20 -mx-5 flex gap-2 border-t bg-card/95 px-5 py-3 backdrop-blur sm:-mx-8 sm:px-8 lg:hidden"
            >
                <Button
                    type="submit"
                    class="flex-1"
                    :disabled="form.processing"
                >
                    <Spinner v-if="form.processing" />
                    {{ page ? 'Save page' : 'Create page' }}
                </Button>
            </div>
        </div>

        <aside class="space-y-6 lg:sticky lg:top-6 lg:self-start">
            <section
                class="space-y-3 rounded-lg border bg-card p-4"
                aria-label="Save"
            >
                <Button
                    type="submit"
                    class="w-full"
                    :disabled="form.processing"
                    data-test="save-page"
                >
                    <Spinner v-if="form.processing" />
                    {{ page ? 'Save page' : 'Create page' }}
                </Button>
                <Link
                    href="/pages"
                    class="block text-center text-sm text-muted-foreground underline underline-offset-4"
                    >Cancel</Link
                >
            </section>

            <section class="space-y-4 rounded-lg border bg-card p-4">
                <p v-if="page" class="text-sm">
                    <span class="font-bold">Address</span><br />
                    <Link
                        v-if="page.path"
                        :href="page.path"
                        class="break-all text-ink underline underline-offset-4"
                        >{{ page.path }}</Link
                    >
                    <span v-else class="text-muted-foreground"
                        >None: this page can’t be visited.</span
                    >
                </p>
                <p v-else class="text-sm text-muted-foreground">
                    The address is made from the title when the page is created.
                </p>

                <Field
                    id="menu"
                    label="Footer column"
                    hint="Where the page is listed at the foot of every page."
                    :error="form.errors.menu"
                >
                    <template #default="{ describedby }">
                        <select
                            id="menu"
                            v-model="form.menu"
                            :aria-describedby="describedby"
                            class="h-10 w-full rounded-md border border-input bg-card px-2 text-sm"
                        >
                            <option :value="null">Not listed</option>
                            <option
                                v-for="menu in PAGE_MENUS"
                                :key="menu.value"
                                :value="menu.value"
                            >
                                {{ menu.label }}
                            </option>
                        </select>
                    </template>
                </Field>

                <Field
                    id="position"
                    label="Order in the column"
                    hint="Lower numbers come first."
                    :error="form.errors.position"
                >
                    <template #default="{ describedby }">
                        <input
                            id="position"
                            v-model.number="form.position"
                            type="number"
                            min="0"
                            :disabled="form.menu === null"
                            :aria-describedby="describedby"
                            class="h-10 w-full rounded-md border border-input bg-card px-3 text-sm disabled:opacity-50"
                        />
                    </template>
                </Field>
            </section>
        </aside>
    </form>
</template>
