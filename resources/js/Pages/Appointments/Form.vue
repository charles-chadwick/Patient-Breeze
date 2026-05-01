<script setup>
import { computed } from 'vue'
import { Link, setLayoutProps } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import AppointmentForm from '@/Pages/Appointments/Partials/AppointmentForm.vue'
import PatientCard from '@/Components/PatientCard.vue'

defineOptions({ layout: DashboardLayout })

const props = defineProps({
    patient: {
        type: Object,
        required: true,
    },
    appointment: {
        type: Object,
        default: null,
    },
    status_options: {
        type: Array,
        required: true,
    },
    role_options: {
        type: Array,
        required: true,
    },
    staff_options: {
        type: Array,
        required: true,
    },
})

const isEditing = computed(() => props.appointment !== null)

setLayoutProps({
    title: computed(() =>
        isEditing.value ? 'Edit Appointment' : 'New Appointment'
    ),
})

const backHref = computed(() => route('patients.show', props.patient.id))

const formAction = computed(() =>
    isEditing.value
        ? route('patients.appointments.update', [props.patient.id, props.appointment.id])
        : route('patients.appointments.store', props.patient.id)
)

const formMethod = computed(() => (isEditing.value ? 'put' : 'post'))
</script>

<template>
    <div class="grid gap-6">
        <div>
            <Link :href="backHref" class="text-sm font-bold text-primary hover:underline">
                ← Back to {{ patient.first_name }} {{ patient.last_name }}
            </Link>
        </div>

        <PatientCard :patient="patient" />

        <AppointmentForm
            :action="formAction"
            :method="formMethod"
            :appointment="appointment"
            :cancel-href="backHref"
            :status_options="status_options"
            :role_options="role_options"
            :staff_options="staff_options"
        />
    </div>
</template>
