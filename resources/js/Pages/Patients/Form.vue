<script setup>
import { computed } from 'vue'
import { setLayoutProps } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import PatientForm from '@/Pages/Patients/Partials/Form.vue'

defineOptions({ layout: DashboardLayout })

const props = defineProps({
    patient: {
        type: Object,
        default: null,
    },
    gender_at_birth_options: {
        type: Array,
        required: true,
    },
    gender_identity_options: {
        type: Array,
        required: true,
    },
    blood_type_options: {
        type: Array,
        required: true,
    },
})

const isEditing = computed(() => props.patient !== null)

setLayoutProps({
    breadcrumbs: computed(() => {
        if (isEditing.value) {
            return [
                { label: 'Patients', href: route('patients.index') },
                { label: `${props.patient.first_name} ${props.patient.last_name}`, href: route('patients.show', props.patient.id) },
                { label: `Edit ${props.patient.first_name} ${props.patient.last_name}` },
            ]
        }
        return [
            { label: 'Patients', href: route('patients.index') },
            { label: 'New Patient' },
        ]
    }),
})

const backHref = computed(() =>
    isEditing.value ? route('patients.show', props.patient.id) : route('patients.index')
)

const formAction = computed(() =>
    isEditing.value ? route('patients.update', props.patient.id) : route('patients.store')
)

const formMethod = computed(() => (isEditing.value ? 'put' : 'post'))
</script>

<template>
    <div class="grid gap-6">
        <PatientForm
            :action="formAction"
            :method="formMethod"
            :patient="patient"
            :cancel-href="backHref"
            :gender_at_birth_options="gender_at_birth_options"
            :gender_identity_options="gender_identity_options"
            :blood_type_options="blood_type_options"
        />
    </div>
</template>
