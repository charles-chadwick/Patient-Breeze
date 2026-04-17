<script setup>
import { Link, setLayoutProps } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { formatDate, DATE_SHORT, DATE_LONG } from '@/lib/utils'

defineOptions({ layout: DashboardLayout })

const props = defineProps({
    patient: {
        type: Object,
        required: true,
    },
})

setLayoutProps({
    title: `${props.patient.user.first_name} ${props.patient.user.last_name}`,
})

const status_classes = {
    Scheduled: 'bg-blue-100 text-blue-700',
    Confirmed: 'bg-green-100 text-green-700',
    Completed: 'bg-gray-100 text-gray-600',
    Cancelled: 'bg-red-100 text-red-700',
    Rescheduled: 'bg-yellow-100 text-yellow-700',
    NoShow: 'bg-orange-100 text-orange-700',
}
</script>

<template>
    <div class="grid gap-6">
        <div>
            <Link
                :href="route('patients.index')"
                class="text-sm font-bold text-primary hover:underline"
            >
                ← Back to Patients
            </Link>
        </div>

        <div class="rounded-xl border border-border bg-white shadow-sm">
            <div class="border-b border-border px-6 py-4">
                <h2 class="font-bold text-foreground">Demographics</h2>
            </div>
            <div class="grid grid-cols-2 gap-x-8 gap-y-4 px-6 py-5 sm:grid-cols-3 lg:grid-cols-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">Full Name</p>
                    <p class="mt-1 text-sm font-bold text-foreground">
                        {{ [patient.user.prefix, patient.user.first_name, patient.user.middle_name, patient.user.last_name, patient.user.suffix].filter(Boolean).join(' ') }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">Date of Birth</p>
                    <p class="mt-1 text-sm text-foreground">{{ formatDate(patient.date_of_birth, DATE_LONG) }}</p>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">Gender at Birth</p>
                    <p class="mt-1 text-sm text-foreground">{{ patient.gender_at_birth }}</p>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">Gender Identity</p>
                    <p class="mt-1 text-sm text-foreground">{{ patient.gender_identity }}</p>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">Blood Type</p>
                    <p class="mt-1 text-sm text-foreground">{{ patient.blood_type ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">Email</p>
                    <p class="mt-1 text-sm text-foreground">{{ patient.user.email }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-border bg-white shadow-sm">
            <div class="border-b border-border px-6 py-4">
                <h2 class="font-bold text-foreground">Appointments</h2>
            </div>

            <div v-if="patient.appointments.length === 0" class="px-6 py-8 text-center text-sm text-muted-foreground">
                No appointments on record.
            </div>

            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="border-b border-border text-left">
                        <th class="px-6 py-3 font-bold text-muted-foreground">Date</th>
                        <th class="px-6 py-3 font-bold text-muted-foreground">Time</th>
                        <th class="px-6 py-3 font-bold text-muted-foreground">Reason</th>
                        <th class="px-6 py-3 font-bold text-muted-foreground">Status</th>
                        <th class="px-6 py-3 font-bold text-muted-foreground">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr
                        v-for="appointment in patient.appointments"
                        :key="appointment.id"
                        class="hover:bg-muted/40"
                    >
                        <td class="px-6 py-3 text-foreground">{{ formatDate(appointment.date, DATE_SHORT) }}</td>
                        <td class="px-6 py-3 text-foreground">
                            {{ appointment.start_time.slice(0, 5) }}–{{ appointment.end_time.slice(0, 5) }}
                        </td>
                        <td class="px-6 py-3 text-foreground">{{ appointment.reason }}</td>
                        <td class="px-6 py-3">
                            <span
                                class="rounded-full px-2.5 py-0.5 text-xs font-bold"
                                :class="status_classes[appointment.status] ?? 'bg-gray-100 text-gray-600'"
                            >
                                {{ appointment.status }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-muted-foreground">{{ appointment.notes ?? '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
