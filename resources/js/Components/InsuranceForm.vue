<script setup>
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    action: {
        type: String,
        required: true,
    },
    company: {
        type: Object,
        required: true,
    },
    planTypeOptions: {
        type: Array,
        default: () => [],
    },
    priorityOptions: {
        type: Array,
        default: () => [],
    },
    relationshipOptions: {
        type: Array,
        default: () => [],
    },
    formId: {
        type: String,
        default: 'patient-insurance-form',
    },
})

const emit = defineEmits(['success'])

const form = useForm({
    insurance_company_id: props.company.id,
    member_id: '',
    group_number: '',
    plan_type: '',
    priority: props.priorityOptions[0] ?? '',
    subscriber_name: '',
    relationship_to_subscriber: props.relationshipOptions[0] ?? '',
    effective_on: '',
    terminates_on: '',
    notes: '',
})

function submit() {
    form.transform((data) => ({
        ...data,
        // Blank selects and dates are "not recorded", not empty strings.
        plan_type: data.plan_type || null,
        subscriber_name: data.subscriber_name || null,
        effective_on: data.effective_on || null,
        terminates_on: data.terminates_on || null,
    })).post(props.action, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset()
            emit('success')
        },
    })
}

const input_class =
    'w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50'
const label_class = 'mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground'
</script>

<template>
    <form :id="formId" action="#" method="post" @submit.prevent="submit" class="grid max-h-[60vh] gap-5 overflow-y-auto px-1">
        <!-- Selected company, carried through from the search step. -->
        <div class="rounded-lg border border-border bg-muted/30 px-4 py-3">
            <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">{{ $t('insurances.heading') }}</p>
            <p class="mt-0.5 font-bold text-foreground">{{ company.name }}</p>
            <p v-if="company.payer_id" class="font-mono text-xs text-muted-foreground">{{ company.payer_id }}</p>
        </div>

        <div>
            <p class="mb-3 text-xs font-bold uppercase tracking-wide text-foreground">{{ $t('insurances.form.section_policy') }}</p>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label :class="label_class">
                        {{ $t('insurances.form.label_member_id') }} <span class="text-vibrant-coral-500">*</span>
                    </label>
                    <input v-model="form.member_id" type="text" data-testid="insurance-member-id" :class="[input_class, 'font-mono', { 'border-vibrant-coral-400': form.errors.member_id }]" />
                    <p v-if="form.errors.member_id" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.member_id }}</p>
                </div>
                <div>
                    <label :class="label_class">{{ $t('insurances.form.label_group_number') }}</label>
                    <input v-model="form.group_number" type="text" :class="[input_class, 'font-mono', { 'border-vibrant-coral-400': form.errors.group_number }]" />
                    <p v-if="form.errors.group_number" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.group_number }}</p>
                </div>
                <div>
                    <label :class="label_class">{{ $t('insurances.form.label_plan_type') }}</label>
                    <select v-model="form.plan_type" :class="input_class">
                        <option value="">{{ $t('insurances.form.none_option') }}</option>
                        <option v-for="option in planTypeOptions" :key="option" :value="option">
                            {{ $t('enums.insurance_plan_type.' + option) }}
                        </option>
                    </select>
                </div>
                <div>
                    <label :class="label_class">
                        {{ $t('insurances.form.label_priority') }} <span class="text-vibrant-coral-500">*</span>
                    </label>
                    <select v-model="form.priority" :class="[input_class, { 'border-vibrant-coral-400': form.errors.priority }]">
                        <option v-for="option in priorityOptions" :key="option" :value="option">
                            {{ $t('enums.insurance_priority.' + option) }}
                        </option>
                    </select>
                    <p v-if="form.errors.priority" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.priority }}</p>
                </div>
            </div>
        </div>

        <div>
            <p class="mb-3 text-xs font-bold uppercase tracking-wide text-foreground">{{ $t('insurances.form.section_subscriber') }}</p>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label :class="label_class">{{ $t('insurances.form.label_relationship') }} <span class="text-vibrant-coral-500">*</span></label>
                    <select v-model="form.relationship_to_subscriber" :class="[input_class, { 'border-vibrant-coral-400': form.errors.relationship_to_subscriber }]">
                        <option v-for="option in relationshipOptions" :key="option" :value="option">
                            {{ $t('enums.subscriber_relationship.' + option) }}
                        </option>
                    </select>
                    <p v-if="form.errors.relationship_to_subscriber" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.relationship_to_subscriber }}</p>
                </div>
                <div>
                    <label :class="label_class">{{ $t('insurances.form.label_subscriber_name') }}</label>
                    <input v-model="form.subscriber_name" type="text" :class="[input_class, { 'border-vibrant-coral-400': form.errors.subscriber_name }]" />
                    <p v-if="form.errors.subscriber_name" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.subscriber_name }}</p>
                    <p v-else class="mt-1 text-xs text-muted-foreground">{{ $t('insurances.form.subscriber_name_hint') }}</p>
                </div>
            </div>
        </div>

        <div>
            <p class="mb-3 text-xs font-bold uppercase tracking-wide text-foreground">{{ $t('insurances.form.section_coverage') }}</p>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label :class="label_class">{{ $t('insurances.form.label_effective_on') }}</label>
                    <input v-model="form.effective_on" type="date" :class="[input_class, { 'border-vibrant-coral-400': form.errors.effective_on }]" />
                    <p v-if="form.errors.effective_on" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.effective_on }}</p>
                </div>
                <div>
                    <label :class="label_class">{{ $t('insurances.form.label_terminates_on') }}</label>
                    <input v-model="form.terminates_on" type="date" :class="[input_class, { 'border-vibrant-coral-400': form.errors.terminates_on }]" />
                    <p v-if="form.errors.terminates_on" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.terminates_on }}</p>
                </div>
            </div>
        </div>

        <div>
            <label :class="label_class">{{ $t('insurances.form.label_notes') }}</label>
            <textarea v-model="form.notes" rows="2" :class="[input_class, { 'border-vibrant-coral-400': form.errors.notes }]"></textarea>
            <p v-if="form.errors.notes" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.notes }}</p>
        </div>
    </form>
</template>
