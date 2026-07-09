<script setup>
import { useForm } from '@inertiajs/vue3'
import RichTextEditor from '@/Components/RichTextEditor.vue'

const props = defineProps({
    action: {
        type: String,
        required: true,
    },
    method: {
        type: String,
        required: true,
        validator: (v) => ['post', 'patch', 'put'].includes(v),
    },
    note: {
        type: Object,
        default: null,
    },
    types: {
        type: Array,
        required: true,
    },
    notableType: {
        type: String,
        default: null,
    },
    notableId: {
        type: [Number, String],
        default: null,
    },
})

const emit = defineEmits(['success'])

const form = useForm({
    type: props.note?.type ?? '',
    title: props.note?.title ?? '',
    content: props.note?.content ?? '',
    notable_type: props.notableType ?? '',
    notable_id: props.notableId ?? '',
})

function submit() {
    form[props.method](props.action, {
        preserveScroll: true,
        onSuccess: () => emit('success'),
    })
}
</script>

<template>
    <form id="note-form" action="#" method="post" @submit.prevent="submit" class="grid gap-5">
        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('notes.form.label_type') }} <span class="text-vibrant-coral-500">*</span>
            </label>
            <select
                v-model="form.type"
                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                :class="{ 'border-vibrant-coral-400': form.errors.type }"
            >
                <option value="">{{ $t('common.actions.select_placeholder') }}</option>
                <option v-for="opt in types" :key="opt" :value="opt">{{ $t('enums.note_type.' + opt) }}</option>
            </select>
            <p v-if="form.errors.type" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.type }}</p>
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('notes.form.label_title') }} <span class="text-vibrant-coral-500">*</span>
            </label>
            <input
                v-model="form.title"
                type="text"
                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                :class="{ 'border-vibrant-coral-400': form.errors.title }"
                :placeholder="$t('notes.form.placeholder_title')"
            />
            <p v-if="form.errors.title" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.title }}</p>
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('notes.form.label_content') }} <span class="text-vibrant-coral-500">*</span>
            </label>
            <RichTextEditor v-model="form.content" :placeholder="$t('notes.form.placeholder_content')" />
            <p v-if="form.errors.content" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.content }}</p>
        </div>
    </form>
</template>
