<script setup>
import { Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
    action: {
        type: String,
        required: true,
    },
    method: {
        type: String,
        required: true,
    },
    medication: {
        type: Object,
        default: null,
    },
    cancelHref: {
        type: String,
        default: null,
    },
    dose_form_options: {
        type: Array,
        required: true,
    },
    formId: {
        type: String,
        default: 'medication-form',
    },
    showActions: {
        type: Boolean,
        default: true,
    },
})

const emit = defineEmits(['success'])

const form = useForm({
    name: props.medication?.name ?? '',
    type: props.medication?.type ?? '',
    dosage: props.medication?.dosage ?? '',
    dose_form: props.medication?.dose_form ?? '',
    ndc: props.medication?.ndc ?? '',
})

function submit() {
    form[props.method](props.action, {
        preserveScroll: true,
        onSuccess: () => emit('success'),
    })
}
</script>

<template>
    <form :id="formId" class="grid gap-5 sm:grid-cols-2" @submit.prevent="submit">
        <!-- Name -->
        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('medications.catalog.form.label_name') }} <span class="text-vibrant-coral-500">*</span>
            </label>
            <input
                v-model="form.name"
                type="text"
                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                :class="{ 'border-vibrant-coral-400': form.errors.name }"
            />
            <p v-if="form.errors.name" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.name }}</p>
        </div>

        <!-- Type -->
        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('medications.catalog.form.label_type') }} <span class="text-vibrant-coral-500">*</span>
            </label>
            <input
                v-model="form.type"
                type="text"
                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                :class="{ 'border-vibrant-coral-400': form.errors.type }"
            />
            <p v-if="form.errors.type" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.type }}</p>
        </div>

        <!-- Dosage -->
        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('medications.catalog.form.label_dosage') }} <span class="text-vibrant-coral-500">*</span>
            </label>
            <input
                v-model="form.dosage"
                type="text"
                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                :class="{ 'border-vibrant-coral-400': form.errors.dosage }"
            />
            <p v-if="form.errors.dosage" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.dosage }}</p>
        </div>

        <!-- Dose Form -->
        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('medications.catalog.form.label_dose_form') }} <span class="text-vibrant-coral-500">*</span>
            </label>
            <select
                v-model="form.dose_form"
                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                :class="{ 'border-vibrant-coral-400': form.errors.dose_form }"
            >
                <option value="">{{ $t('common.actions.select_placeholder') }}</option>
                <option v-for="opt in dose_form_options" :key="opt" :value="opt">
                    {{ $t('enums.dose_form.' + opt) }}
                </option>
            </select>
            <p v-if="form.errors.dose_form" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.dose_form }}</p>
        </div>

        <!-- NDC -->
        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('medications.catalog.form.label_ndc') }} <span class="text-vibrant-coral-500">*</span>
            </label>
            <input
                v-model="form.ndc"
                type="text"
                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                :class="{ 'border-vibrant-coral-400': form.errors.ndc }"
            />
            <p v-if="form.errors.ndc" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.ndc }}</p>
        </div>

        <!-- Actions (full-page usage; the modal renders its own footer) -->
        <div v-if="showActions" class="flex items-center justify-end gap-3 sm:col-span-2">
            <Link
                v-if="cancelHref"
                :href="cancelHref"
                class="rounded-lg border border-border px-4 py-2 text-sm font-bold text-foreground hover:bg-muted/40"
            >
                {{ $t('common.actions.cancel') }}
            </Link>
            <button
                type="submit"
                :disabled="form.processing"
                class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90 disabled:opacity-50"
            >
                {{ form.processing ? $t('medications.catalog.form.submitting') : $t('medications.catalog.form.submit') }}
            </button>
        </div>
    </form>
</template>
