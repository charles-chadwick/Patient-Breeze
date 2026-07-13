<script setup>
import { useForm, usePage } from '@inertiajs/vue3'
import RichTextEditor from '@/Components/RichTextEditor.vue'
import DatePicker from '@/Components/ui/DatePicker.vue'

const props = defineProps({
    action: {
        type: String,
        required: true,
    },
    method: {
        type: String,
        required: true,
        validator: (v) => ['post', 'put', 'patch'].includes(v),
    },
    note: {
        type: Object,
        default: null,
    },
    types: {
        type: Array,
        required: true,
    },
    ownerOptions: {
        type: Array,
        default: () => [],
    },
    appointments: {
        type: Array,
        default: () => [],
    },
})

const emit = defineEmits(['success'])

// A new note defaults to the current user as owner; an existing note keeps its
// author. Owner maps to the note's author_id.
const current_user_id = usePage().props.auth.user?.id ?? ''

const form = useForm({
    type: props.note?.type ?? '',
    author_id: props.note?.author_id ?? current_user_id,
    encounter_date: props.note?.encounter_date ?? '',
    title: props.note?.title ?? '',
    content: props.note?.content ?? '',
    appointment_id: props.note?.appointment_id ?? '',
    sign: false,
})

function submit(sign = false) {
    form.sign = sign
    form[props.method](props.action, {
        preserveScroll: true,
        onSuccess: () => emit('success'),
    })
}

defineExpose({ submit })
</script>

<template>
    <form id="encounter-note-form" action="#" method="post" @submit.prevent="submit(false)" class="grid gap-5">
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                    {{ $t('encounter_notes.form.label_type') }} <span class="text-vibrant-coral-500">*</span>
                </label>
                <select
                    v-model="form.type"
                    class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                    :class="{ 'border-vibrant-coral-400': form.errors.type }"
                >
                    <option value="">{{ $t('common.actions.select_placeholder') }}</option>
                    <option v-for="opt in types" :key="opt" :value="opt">{{ $t('enums.encounter_note_type.' + opt) }}</option>
                </select>
                <p v-if="form.errors.type" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.type }}</p>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                    {{ $t('encounter_notes.form.label_encounter_date') }} <span class="text-vibrant-coral-500">*</span>
                </label>
                <DatePicker
                    v-model="form.encounter_date"
                    :placeholder="$t('encounter_notes.form.placeholder_encounter_date')"
                    :class="{ 'ring-2 ring-vibrant-coral-400 rounded-lg': form.errors.encounter_date }"
                />
                <p v-if="form.errors.encounter_date" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.encounter_date }}</p>
            </div>
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('encounter_notes.form.label_owner') }} <span class="text-vibrant-coral-500">*</span>
            </label>
            <select
                v-model="form.author_id"
                data-testid="encounter-note-owner"
                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                :class="{ 'border-vibrant-coral-400': form.errors.author_id }"
            >
                <option value="">{{ $t('common.actions.select_placeholder') }}</option>
                <option v-for="owner in ownerOptions" :key="owner.id" :value="owner.id">{{ owner.name }}</option>
            </select>
            <p v-if="form.errors.author_id" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.author_id }}</p>
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('encounter_notes.form.label_title') }} <span class="text-vibrant-coral-500">*</span>
            </label>
            <input
                v-model="form.title"
                type="text"
                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                :class="{ 'border-vibrant-coral-400': form.errors.title }"
                :placeholder="$t('encounter_notes.form.placeholder_title')"
            />
            <p v-if="form.errors.title" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.title }}</p>
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('encounter_notes.form.label_appointment') }}
            </label>
            <select
                v-model="form.appointment_id"
                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                :class="{ 'border-vibrant-coral-400': form.errors.appointment_id }"
            >
                <option value="">{{ $t('encounter_notes.form.appointment_none') }}</option>
                <option v-for="appointment in appointments" :key="appointment.id" :value="appointment.id">
                    {{ appointment.date }} · {{ appointment.reason }}
                </option>
            </select>
            <p v-if="form.errors.appointment_id" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.appointment_id }}</p>
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('encounter_notes.form.label_content') }} <span class="text-vibrant-coral-500">*</span>
            </label>
            <RichTextEditor v-model="form.content" :placeholder="$t('encounter_notes.form.placeholder_content')" />
            <p v-if="form.errors.content" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.content }}</p>
        </div>
    </form>
</template>
