<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'

const props = defineProps({
    action: {
        type: String,
        required: true,
    },
    // The selected lab order from the catalog search.
    initial: {
        type: Object,
        default: () => ({}),
    },
    // Resolved reference range for this patient, or null.
    referenceRange: {
        type: Object,
        default: null,
    },
    // { gender, age } context for the patient.
    patientContext: {
        type: Object,
        default: () => ({}),
    },
    formId: {
        type: String,
        default: 'lab-result-form',
    },
})

const emit = defineEmits(['success'])

const today = new Date().toISOString().slice(0, 10)

const form = useForm({
    lab_order_id: props.initial.id ?? null,
    value: '',
    unit: props.referenceRange?.unit ?? '',
    collected_at: today,
    notes: '',
})

const reference_summary = computed(() => {
    const gender = props.patientContext.gender ?? trans('enums.gender_at_birth.Unknown')
    const age = props.patientContext.age ?? '—'

    return trans('lab_results.form.reference_for', { gender, age })
})

// Live mirror of the server-side flag logic, so the user sees Normal/Low/High as they type.
const preview_flag = computed(() => {
    const value = Number.parseFloat(form.value)

    if (Number.isNaN(value)) {
        return null
    }

    const range = props.referenceRange

    if (!range || (range.low == null && range.high == null)) {
        return 'Unknown'
    }

    if (range.low != null && value < range.low) {
        return 'Low'
    }

    if (range.high != null && value > range.high) {
        return 'High'
    }

    return 'Normal'
})

const flag_classes = {
    Normal: 'bg-accent/15 text-accent-foreground',
    Low: 'bg-vibrant-coral-50 text-vibrant-coral-600',
    High: 'bg-vibrant-coral-50 text-vibrant-coral-600',
    Unknown: 'bg-muted text-muted-foreground',
}

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
        <!-- Selected test + reference range -->
        <div class="rounded-lg border border-border bg-muted/30 px-4 py-3">
            <p class="font-bold text-foreground">{{ initial.name }}</p>
            <p class="text-xs text-muted-foreground">{{ initial.performing_lab }} · CPT {{ initial.cpt_code }}</p>

            <div class="mt-3 border-t border-border pt-3">
                <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">
                    {{ $t('lab_results.form.reference_heading') }}
                    <span class="ml-1 font-medium normal-case">({{ reference_summary }})</span>
                </p>
                <p v-if="referenceRange" class="mt-1 font-bold text-foreground">{{ referenceRange.label }}</p>
                <p v-else class="mt-1 text-sm text-muted-foreground">{{ $t('lab_results.form.reference_none') }}</p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                    {{ $t('lab_results.form.label_value') }} <span class="text-vibrant-coral-500">*</span>
                </label>
                <input
                    v-model="form.value"
                    type="text"
                    class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                    :class="{ 'border-vibrant-coral-400': form.errors.value }"
                />
                <p v-if="form.errors.value" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.value }}</p>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                    {{ $t('lab_results.form.label_unit') }}
                </label>
                <input
                    v-model="form.unit"
                    type="text"
                    class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                    :class="{ 'border-vibrant-coral-400': form.errors.unit }"
                />
                <p v-if="form.errors.unit" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.unit }}</p>
            </div>
        </div>

        <!-- Live flag preview -->
        <div v-if="preview_flag" class="flex items-center gap-2 text-sm">
            <span class="text-muted-foreground">{{ $t('lab_results.form.preview_flag') }}</span>
            <span class="rounded-full px-2.5 py-0.5 text-xs font-bold" :class="flag_classes[preview_flag]">
                {{ $t('enums.result_flag.' + preview_flag) }}
            </span>
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('lab_results.form.label_collected_at') }}
            </label>
            <input
                v-model="form.collected_at"
                type="date"
                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                :class="{ 'border-vibrant-coral-400': form.errors.collected_at }"
            />
            <p v-if="form.errors.collected_at" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.collected_at }}</p>
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('lab_results.form.label_notes') }}
            </label>
            <textarea
                v-model="form.notes"
                rows="2"
                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                :class="{ 'border-vibrant-coral-400': form.errors.notes }"
            ></textarea>
            <p v-if="form.errors.notes" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.notes }}</p>
        </div>
    </form>
</template>
