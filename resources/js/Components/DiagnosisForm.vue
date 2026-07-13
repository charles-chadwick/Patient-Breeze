<script setup>
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    action: {
        type: String,
        required: true,
    },
    statusOptions: {
        type: Array,
        required: true,
    },
    initial: {
        type: Object,
        default: () => ({}),
    },
    formId: {
        type: String,
        default: 'diagnosis-form',
    },
})

const emit = defineEmits(['success'])

const today = new Date().toISOString().slice(0, 10)

const form = useForm({
    diagnosis: props.initial.diagnosis ?? '',
    icd10_code: props.initial.icd10_code ?? '',
    diagnosed_on: today,
    status: props.statusOptions[0] ?? '',
})

function submit() {
    form.post(props.action, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset()
            emit('success')
        },
    })
}
</script>

<template>
    <form :id="formId" action="#" method="post" @submit.prevent="submit" class="grid gap-5">
        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('diagnoses.form.label_diagnosis') }} <span class="text-vibrant-coral-500">*</span>
            </label>
            <input
                v-model="form.diagnosis"
                type="text"
                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                :class="{ 'border-vibrant-coral-400': form.errors.diagnosis }"
            />
            <p v-if="form.errors.diagnosis" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.diagnosis }}</p>
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('diagnoses.form.label_icd10_code') }} <span class="text-vibrant-coral-500">*</span>
            </label>
            <input
                v-model="form.icd10_code"
                type="text"
                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                :class="{ 'border-vibrant-coral-400': form.errors.icd10_code }"
            />
            <p v-if="form.errors.icd10_code" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.icd10_code }}</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                    {{ $t('diagnoses.form.label_diagnosed_on') }} <span class="text-vibrant-coral-500">*</span>
                </label>
                <input
                    v-model="form.diagnosed_on"
                    type="date"
                    class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                    :class="{ 'border-vibrant-coral-400': form.errors.diagnosed_on }"
                />
                <p v-if="form.errors.diagnosed_on" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.diagnosed_on }}</p>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                    {{ $t('diagnoses.form.label_status') }} <span class="text-vibrant-coral-500">*</span>
                </label>
                <select
                    v-model="form.status"
                    class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                    :class="{ 'border-vibrant-coral-400': form.errors.status }"
                >
                    <option v-for="option in statusOptions" :key="option" :value="option">
                        {{ $t('enums.diagnosis_status.' + option) }}
                    </option>
                </select>
                <p v-if="form.errors.status" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.status }}</p>
            </div>
        </div>
    </form>
</template>
