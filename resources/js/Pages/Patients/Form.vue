<script setup>
import { computed } from 'vue'
import { Link, setLayoutProps } from '@inertiajs/vue3'
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
    title: computed(() =>
        isEditing.value
            ? `Edit ${props.patient.first_name} ${props.patient.last_name}`
            : 'New Patient'
    ),
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
        <div>
            <Link :href="backHref" class="text-sm font-bold text-primary hover:underline">
                {{ isEditing ? '← Back to Patient' : '← Back to Patients' }}
            </Link>
        </div>

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
