<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Field from '@/components/admin/Field.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { useLeaveGuard } from '@/composables/useLeaveGuard';
import type { AssignableTerm, EditableUser } from '@/types';

const props = defineProps<{
    user?: EditableUser;
    /** The terms this person can be made an editor of (editing only). */
    terms?: AssignableTerm[];
    isSelf?: boolean;
}>();

const form = useForm({
    first_name: props.user?.first_name ?? '',
    last_name: props.user?.last_name ?? '',
    username: props.user?.username ?? '',
    email: props.user?.email ?? '',
    password: '',
    is_admin: props.user?.is_admin ?? false,
    terms: (props.terms ?? [])
        .filter((term) => term.assigned)
        .map((term) => term.master_id),
});

useLeaveGuard(() => form.isDirty && !form.processing);

const termFilter = ref('');
const onlyAssigned = ref(false);

const termList = computed(() => {
    const needle = termFilter.value.trim().toLowerCase();
    const chosen = new Set(form.terms);

    return (props.terms ?? []).filter(
        (term) =>
            (!onlyAssigned.value || chosen.has(term.master_id)) &&
            (!needle || term.term.toLowerCase().includes(needle)),
    );
});

function submit() {
    const options = {
        preserveScroll: 'errors' as const,
        onSuccess: () => form.reset('password'),
    };

    if (props.user) {
        form.put(`/users/${props.user.id}`, options);
    } else {
        form.transform(({ terms: _terms, ...data }) => data).post(
            '/users',
            options,
        );
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
            <div class="grid gap-6 sm:grid-cols-2">
                <Field
                    id="first_name"
                    label="First name"
                    :error="form.errors.first_name"
                >
                    <Input
                        id="first_name"
                        v-model="form.first_name"
                        required
                        autocomplete="off"
                        class="h-11 bg-card text-base"
                    />
                </Field>
                <Field
                    id="last_name"
                    label="Last name"
                    :error="form.errors.last_name"
                >
                    <Input
                        id="last_name"
                        v-model="form.last_name"
                        required
                        autocomplete="off"
                        class="h-11 bg-card text-base"
                    />
                </Field>
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <Field
                    id="email"
                    label="Email address"
                    hint="Used to sign in."
                    :error="form.errors.email"
                >
                    <template #default="{ describedby }">
                        <Input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            autocomplete="off"
                            class="h-11 bg-card text-base"
                            :aria-describedby="describedby"
                        />
                    </template>
                </Field>
                <Field
                    id="username"
                    label="Username"
                    hint="Optional."
                    :error="form.errors.username"
                >
                    <template #default="{ describedby }">
                        <Input
                            id="username"
                            v-model="form.username"
                            autocomplete="off"
                            class="h-11 bg-card text-base"
                            :aria-describedby="describedby"
                        />
                    </template>
                </Field>
            </div>

            <Field
                id="password"
                :label="user ? 'New password' : 'Password'"
                :hint="
                    user
                        ? 'Leave empty to keep the current password.'
                        : 'Share it with the person securely; they can change it in their settings.'
                "
                :error="form.errors.password"
            >
                <template #default="{ describedby }">
                    <PasswordInput
                        id="password"
                        v-model="form.password"
                        :required="!user"
                        autocomplete="new-password"
                        class="h-11 bg-card text-base"
                        :aria-describedby="describedby"
                    />
                </template>
            </Field>

            <fieldset
                v-if="user && terms"
                class="rounded-lg border bg-card p-4"
            >
                <legend class="px-1 font-bold">Terms this person edits</legend>
                <p class="text-sm text-muted-foreground">
                    <template v-if="form.is_admin"
                        >Administrators can edit every term; ticks here only
                        matter if admin rights are removed.</template
                    >
                    <template v-else
                        >They can change the terms ticked here. Ticking a term
                        here doesn’t email them; adding them on the term’s own
                        edit screen does.</template
                    >
                </p>

                <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2">
                    <input
                        v-model="termFilter"
                        type="search"
                        aria-label="Find a term"
                        placeholder="Find a term"
                        class="h-9 w-full rounded-md border border-input bg-background px-2.5 text-sm sm:w-64"
                    />
                    <label class="flex items-center gap-2 text-sm">
                        <input
                            v-model="onlyAssigned"
                            type="checkbox"
                            class="size-4 accent-[var(--primary)]"
                        />
                        Only ticked terms
                    </label>
                    <span
                        class="text-sm text-muted-foreground tabular-nums sm:ml-auto"
                        >{{ form.terms.length }} ticked</span
                    >
                </div>

                <div
                    class="mt-3 grid max-h-96 gap-x-6 gap-y-1.5 overflow-auto rounded-md border bg-background p-3 sm:grid-cols-2"
                >
                    <label
                        v-for="term in termList"
                        :key="term.master_id"
                        class="flex items-center gap-2.5 text-sm"
                    >
                        <input
                            v-model="form.terms"
                            type="checkbox"
                            :value="term.master_id"
                            class="size-4 shrink-0 accent-[var(--primary)]"
                        />
                        {{ term.term }}
                    </label>
                    <p
                        v-if="!termList.length"
                        class="text-sm text-muted-foreground"
                    >
                        No terms match.
                    </p>
                </div>
            </fieldset>
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
                    {{ user ? 'Save changes' : 'Add user' }}
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
                    data-test="save-user"
                >
                    <Spinner v-if="form.processing" />
                    {{ user ? 'Save changes' : 'Add user' }}
                </Button>
                <Link
                    href="/users"
                    class="block text-center text-sm text-muted-foreground underline underline-offset-4"
                    >Cancel</Link
                >
            </section>

            <section class="rounded-lg border bg-card p-4">
                <label class="flex items-start gap-2.5 text-sm">
                    <input
                        v-model="form.is_admin"
                        type="checkbox"
                        :disabled="isSelf"
                        class="mt-0.5 size-4 accent-[var(--primary)]"
                    />
                    <span>
                        <span class="font-bold">Administrator</span>
                        <span class="block text-muted-foreground">
                            <template v-if="isSelf"
                                >You can’t remove your own administrator
                                rights.</template
                            >
                            <template v-else
                                >Can edit every term and manage pages and
                                users.</template
                            >
                        </span>
                    </span>
                </label>
            </section>
        </aside>
    </form>
</template>
