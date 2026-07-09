<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import DatePicker from '@/Components/ui/DatePicker.vue'

const props = defineProps({
    action: {
        type: String,
        required: true,
    },
    types: {
        type: Array,
        required: true,
    },
    formId: {
        type: String,
        default: 'document-form',
    },
})

const emit = defineEmits(['success'])

const file_input = ref(null)

const form = useForm({
    type: '',
    name: '',
    document_date: '',
    notes: '',
    file: null,
})

function onFileChange(event) {
    form.file = event.target.files[0] ?? null
}

function submit() {
    form.post(props.action, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset()
            if (file_input.value) {
                file_input.value.value = ''
            }
            emit('success')
        },
    })
}
</script>

<template>
    <form :id="formId" action="#" method="post" @submit.prevent="submit" class="grid gap-5">
        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('documents.form.label_file') }} <span class="text-vibrant-coral-500">*</span>
            </label>
            <input
                ref="file_input"
                type="file"
                accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.txt"
                @change="onFileChange"
                class="block w-full text-sm text-foreground file:mr-4 file:rounded-lg file:border-0 file:bg-primary file:px-4 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-primary/90"
                :class="{ 'rounded-lg ring-2 ring-vibrant-coral-400': form.errors.file }"
            />
            <p class="mt-1 text-xs text-muted-foreground">{{ $t('documents.form.hint_file') }}</p>
            <p v-if="form.errors.file" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.file }}</p>
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('documents.form.label_type') }} <span class="text-vibrant-coral-500">*</span>
            </label>
            <select
                v-model="form.type"
                class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                :class="{ 'border-vibrant-coral-400': form.errors.type }"
            >
                <option value="">{{ $t('common.actions.select_placeholder') }}</option>
                <option v-for="option in types" :key="option" :value="option">{{ $t('enums.document_type.' + option) }}</option>
            </select>
            <p v-if="form.errors.type" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.type }}</p>
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('documents.form.label_name') }}
            </label>
            <input
                v-model="form.name"
                type="text"
                class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                :class="{ 'border-vibrant-coral-400': form.errors.name }"
                :placeholder="$t('documents.form.placeholder_name')"
            />
            <p v-if="form.errors.name" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.name }}</p>
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('documents.form.label_date') }}
            </label>
            <DatePicker
                v-model="form.document_date"
                :placeholder="$t('common.actions.select_placeholder')"
                :class="{ 'ring-2 ring-vibrant-coral-400 rounded-lg': form.errors.document_date }"
            />
            <p v-if="form.errors.document_date" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.document_date }}</p>
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('documents.form.label_notes') }}
            </label>
            <textarea
                v-model="form.notes"
                rows="3"
                class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                :class="{ 'border-vibrant-coral-400': form.errors.notes }"
                :placeholder="$t('documents.form.placeholder_notes')"
            ></textarea>
            <p v-if="form.errors.notes" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.notes }}</p>
        </div>

        <p v-if="form.progress" class="text-xs text-muted-foreground">
            {{ form.progress.percentage }}%
        </p>
    </form>
</template>
