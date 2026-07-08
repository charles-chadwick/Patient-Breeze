<script setup>
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    action: {
        type: String,
        required: true,
    },
    doseFormOptions: {
        type: Array,
        required: true,
    },
    initial: {
        type: Object,
        default: () => ({}),
    },
    formId: {
        type: String,
        default: 'medication-form',
    },
})

const emit = defineEmits(['success'])

const form = useForm({
    name: props.initial.name ?? '',
    type: props.initial.type ?? '',
    dosage: props.initial.dosage ?? '',
    dose_form: props.initial.dose_form ?? '',
    ndc: props.initial.ndc ?? '',
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
                {{ $t('medications.form.label_name') }} <span class="text-vibrant-coral-500">*</span>
            </label>
            <input
                v-model="form.name"
                type="text"
                class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                :class="{ 'border-vibrant-coral-400': form.errors.name }"
            />
            <p v-if="form.errors.name" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.name }}</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                    {{ $t('medications.form.label_dosage') }} <span class="text-vibrant-coral-500">*</span>
                </label>
                <input
                    v-model="form.dosage"
                    type="text"
                    class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                    :class="{ 'border-vibrant-coral-400': form.errors.dosage }"
                />
                <p v-if="form.errors.dosage" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.dosage }}</p>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                    {{ $t('medications.form.label_dose_form') }} <span class="text-vibrant-coral-500">*</span>
                </label>
                <select
                    v-model="form.dose_form"
                    class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                    :class="{ 'border-vibrant-coral-400': form.errors.dose_form }"
                >
                    <option value="">{{ $t('common.actions.select_placeholder') }}</option>
                    <option v-for="option in doseFormOptions" :key="option" :value="option">
                        {{ $t('enums.dose_form.' + option) }}
                    </option>
                </select>
                <p v-if="form.errors.dose_form" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.dose_form }}</p>
            </div>
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('medications.form.label_type') }} <span class="text-vibrant-coral-500">*</span>
            </label>
            <input
                v-model="form.type"
                type="text"
                class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                :class="{ 'border-vibrant-coral-400': form.errors.type }"
            />
            <p v-if="form.errors.type" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.type }}</p>
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('medications.form.label_ndc') }}
            </label>
            <input
                v-model="form.ndc"
                type="text"
                class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                :class="{ 'border-vibrant-coral-400': form.errors.ndc }"
            />
            <p v-if="form.errors.ndc" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.ndc }}</p>
        </div>
    </form>
</template>
