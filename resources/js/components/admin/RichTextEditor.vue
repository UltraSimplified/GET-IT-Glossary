<script setup lang="ts">
import Image from '@tiptap/extension-image';
import { Placeholder } from '@tiptap/extensions';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import {
    Bold,
    Code,
    Heading2,
    Heading3,
    ImagePlus,
    Italic,
    Link,
    List,
    ListOrdered,
    LoaderCircle,
    Quote,
    Redo2,
    Undo2,
} from '@lucide/vue';
import { computed, onBeforeUnmount, ref, useTemplateRef, watch } from 'vue';
import { toast } from 'vue-sonner';
import TermPicker from '@/components/admin/TermPicker.vue';
import { postJson } from '@/lib/http';

/**
 * Rich text editing for term examples, cartoons and page bodies.
 *
 * The stored HTML is only replaced when the editor actually changes it,
 * so opening and saving a record never rewrites content nobody touched.
 * "HTML" switches to the raw markup for anything the visual editor
 * cannot represent (embedded video, tables, pasted Word styling).
 */
const model = defineModel<string | null>({ required: true });

const {
    id,
    headings = false,
    images = false,
    termLinks = false,
    placeholder = '',
    invalid = false,
    describedby,
} = defineProps<{
    id: string;
    headings?: boolean;
    images?: boolean;
    termLinks?: boolean;
    placeholder?: string;
    invalid?: boolean;
    describedby?: string;
}>();

// Markup the visual editor would throw away. Content containing it opens as HTML.
const LOSSY =
    /<(iframe|table|script|style|object|embed|video|audio|form|xml)\b|<[a-z]+:[a-z]/i;

const source = ref(LOSSY.test(model.value ?? ''));
const lossyNotice = ref(source.value);
const uploading = ref(false);
const linkOpen = ref(false);
const linkUrl = ref('');
const fileInput = useTemplateRef<HTMLInputElement>('fileInput');

const editor = useEditor({
    content: model.value ?? '',
    extensions: [
        StarterKit.configure({
            heading: headings ? { levels: [2, 3, 4] } : false,
            codeBlock: false,
            link: {
                openOnClick: false,
                autolink: true,
                defaultProtocol: 'https',
            },
        }),
        Image.configure({ inline: false }),
        Placeholder.configure({ placeholder }),
    ],
    editorProps: {
        attributes: {
            id,
            class: 'tiptap rich-html',
            role: 'textbox',
            'aria-multiline': 'true',
        },
    },
    onUpdate: ({ editor }) => {
        model.value = editor.isEmpty ? '' : editor.getHTML();
    },
});

watch(
    () => [describedby, invalid] as const,
    ([described, isInvalid]) => {
        editor.value?.setOptions({
            editorProps: {
                attributes: {
                    id,
                    class: 'tiptap rich-html',
                    role: 'textbox',
                    'aria-multiline': 'true',
                    ...(described ? { 'aria-describedby': described } : {}),
                    ...(isInvalid ? { 'aria-invalid': 'true' } : {}),
                },
            },
        });
    },
    { immediate: true },
);

// Keep the editor in step when the value is replaced from outside (form reset).
watch(model, (value) => {
    const instance = editor.value;

    if (
        instance &&
        !source.value &&
        value !== instance.getHTML() &&
        !(instance.isEmpty && !value)
    ) {
        instance.commands.setContent(value ?? '', { emitUpdate: false });
    }
});

onBeforeUnmount(() => editor.value?.destroy());

function toggleSource() {
    if (source.value) {
        editor.value?.commands.setContent(model.value ?? '', {
            emitUpdate: false,
        });
    }

    source.value = !source.value;
    linkOpen.value = false;
}

type Mark =
    | 'bold'
    | 'italic'
    | 'heading2'
    | 'heading3'
    | 'bulletList'
    | 'orderedList'
    | 'blockquote'
    | 'link';

function isActive(mark: Mark) {
    const instance = editor.value;

    if (!instance) {
        return false;
    }

    switch (mark) {
        case 'heading2':
            return instance.isActive('heading', { level: 2 });
        case 'heading3':
            return instance.isActive('heading', { level: 3 });
        default:
            return instance.isActive(mark);
    }
}

const tools = computed(() => {
    const list: {
        label: string;
        icon: typeof Bold;
        mark?: Mark;
        run: () => void;
    }[] = [
        {
            label: 'Bold',
            icon: Bold,
            mark: 'bold',
            run: () => editor.value?.chain().focus().toggleBold().run(),
        },
        {
            label: 'Italic',
            icon: Italic,
            mark: 'italic',
            run: () => editor.value?.chain().focus().toggleItalic().run(),
        },
    ];

    if (headings) {
        list.push(
            {
                label: 'Heading',
                icon: Heading2,
                mark: 'heading2',
                run: () =>
                    editor.value
                        ?.chain()
                        .focus()
                        .toggleHeading({ level: 2 })
                        .run(),
            },
            {
                label: 'Subheading',
                icon: Heading3,
                mark: 'heading3',
                run: () =>
                    editor.value
                        ?.chain()
                        .focus()
                        .toggleHeading({ level: 3 })
                        .run(),
            },
        );
    }

    list.push(
        {
            label: 'Bulleted list',
            icon: List,
            mark: 'bulletList',
            run: () => editor.value?.chain().focus().toggleBulletList().run(),
        },
        {
            label: 'Numbered list',
            icon: ListOrdered,
            mark: 'orderedList',
            run: () => editor.value?.chain().focus().toggleOrderedList().run(),
        },
        {
            label: 'Quote',
            icon: Quote,
            mark: 'blockquote',
            run: () => editor.value?.chain().focus().toggleBlockquote().run(),
        },
    );

    return list;
});

function openLink() {
    linkUrl.value =
        (editor.value?.getAttributes('link').href as string | undefined) ?? '';
    linkOpen.value = !linkOpen.value;
}

function applyLink() {
    const url = linkUrl.value.trim();
    const chain = editor.value?.chain().focus().extendMarkRange('link');

    if (url) {
        chain?.setLink({ href: url }).run();
    } else {
        chain?.unsetLink().run();
    }

    linkOpen.value = false;
}

function insertTerm(term: string) {
    const instance = editor.value;

    if (!instance) {
        return;
    }

    const { from, to } = instance.state.selection;
    const selected = instance.state.doc.textBetween(from, to, ' ').trim();
    const text =
        selected && selected.toLowerCase() !== term.toLowerCase()
            ? `[${selected}|${term}]`
            : `[${term}]`;

    instance.chain().focus().insertContentAt({ from, to }, text).run();
}

async function upload(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    input.value = '';

    if (!file) {
        return;
    }

    const data = new FormData();
    data.append('file', file);
    uploading.value = true;

    try {
        const { url } = await postJson<{ url: string }>('/upload', data);
        editor.value?.chain().focus().setImage({ src: url, alt: '' }).run();
    } catch (error) {
        toast.error(
            error instanceof Error
                ? error.message
                : 'The image could not be uploaded.',
        );
    } finally {
        uploading.value = false;
    }
}
</script>

<template>
    <div
        class="editor-surface rounded-md border border-input bg-card focus-within:border-primary focus-within:ring-4 focus-within:ring-ring/30"
        :class="{ 'border-destructive': invalid }"
    >
        <div
            class="flex flex-wrap items-center gap-0.5 border-b px-1.5 py-1"
            role="toolbar"
            :aria-controls="id"
        >
            <template v-if="!source">
                <button
                    v-for="tool in tools"
                    :key="tool.label"
                    type="button"
                    class="grid size-8 place-items-center rounded-md hover:bg-brand-soft"
                    :class="
                        tool.mark && isActive(tool.mark)
                            ? 'bg-primary text-primary-foreground hover:bg-primary'
                            : 'text-ink'
                    "
                    :aria-label="tool.label"
                    :title="tool.label"
                    :aria-pressed="tool.mark ? isActive(tool.mark) : undefined"
                    @click="tool.run"
                >
                    <component
                        :is="tool.icon"
                        class="size-4"
                        aria-hidden="true"
                    />
                </button>

                <button
                    type="button"
                    class="grid size-8 place-items-center rounded-md hover:bg-brand-soft"
                    :class="
                        isActive('link')
                            ? 'bg-primary text-primary-foreground hover:bg-primary'
                            : 'text-ink'
                    "
                    aria-label="Web link"
                    title="Web link"
                    :aria-expanded="linkOpen"
                    @click="openLink"
                >
                    <Link class="size-4" aria-hidden="true" />
                </button>

                <template v-if="images">
                    <button
                        type="button"
                        class="grid size-8 place-items-center rounded-md text-ink hover:bg-brand-soft disabled:opacity-50"
                        aria-label="Add an image"
                        title="Add an image"
                        :disabled="uploading"
                        @click="fileInput?.click()"
                    >
                        <LoaderCircle
                            v-if="uploading"
                            class="size-4 animate-spin"
                            aria-hidden="true"
                        />
                        <ImagePlus v-else class="size-4" aria-hidden="true" />
                    </button>
                    <input
                        ref="fileInput"
                        type="file"
                        accept="image/png,image/jpeg,image/gif,image/webp"
                        class="hidden"
                        @change="upload"
                    />
                </template>

                <TermPicker v-if="termLinks" @pick="insertTerm" />

                <span class="mx-1 h-5 w-px bg-border" aria-hidden="true"></span>

                <button
                    type="button"
                    class="grid size-8 place-items-center rounded-md text-ink hover:bg-brand-soft disabled:opacity-40"
                    aria-label="Undo"
                    title="Undo"
                    :disabled="!editor?.can().undo()"
                    @click="editor?.chain().focus().undo().run()"
                >
                    <Undo2 class="size-4" aria-hidden="true" />
                </button>
                <button
                    type="button"
                    class="grid size-8 place-items-center rounded-md text-ink hover:bg-brand-soft disabled:opacity-40"
                    aria-label="Redo"
                    title="Redo"
                    :disabled="!editor?.can().redo()"
                    @click="editor?.chain().focus().redo().run()"
                >
                    <Redo2 class="size-4" aria-hidden="true" />
                </button>
            </template>

            <button
                type="button"
                class="ml-auto inline-flex h-8 items-center gap-1.5 rounded-md px-2.5 text-sm font-semibold hover:bg-brand-soft"
                :class="
                    source
                        ? 'bg-primary text-primary-foreground hover:bg-primary'
                        : 'text-ink'
                "
                :aria-pressed="source"
                @click="toggleSource"
            >
                <Code class="size-4" aria-hidden="true" />
                HTML
            </button>
        </div>

        <div
            v-if="linkOpen && !source"
            class="flex flex-wrap items-center gap-2 border-b px-2 py-2"
        >
            <label :for="`${id}-link`" class="text-sm font-semibold"
                >Link to</label
            >
            <input
                :id="`${id}-link`"
                v-model="linkUrl"
                type="url"
                placeholder="https://"
                class="h-8 min-w-0 flex-1 rounded-md border border-input bg-background px-2 text-sm"
                @keydown.enter.prevent="applyLink"
                @keydown.esc.prevent="linkOpen = false"
            />
            <button
                type="button"
                class="h-8 rounded-md bg-primary px-3 text-sm font-semibold text-primary-foreground"
                @click="applyLink"
            >
                {{ linkUrl.trim() ? 'Apply' : 'Remove link' }}
            </button>
        </div>

        <p
            v-if="source && lossyNotice"
            class="border-b bg-warning-soft px-3 py-2 text-sm text-warning"
        >
            This content contains embedded media or formatting the visual editor
            would remove, so it opens as HTML.
        </p>

        <textarea
            v-if="source"
            :id="id"
            v-model="model"
            rows="10"
            spellcheck="false"
            :aria-invalid="invalid || undefined"
            :aria-describedby="describedby"
            class="block w-full resize-y rounded-b-md bg-transparent px-3 py-2 font-mono text-sm outline-none"
        ></textarea>
        <EditorContent v-else :editor="editor" />
    </div>
</template>
