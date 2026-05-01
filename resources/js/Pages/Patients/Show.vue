<script setup>
import { Link, setLayoutProps } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { formatDate, DATE_SHORT } from '@/lib/utils'
import PatientCard from '@/Components/PatientCard.vue'

defineOptions({ layout: DashboardLayout })

const props = defineProps({
    patient: {
        type: Object,
        required: true,
    },
})

setLayoutProps({
    title: `${props.patient.first_name} ${props.patient.last_name}`,
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
        <div class="flex items-center justify-between">
            <Link
                :href="route('patients.index')"
                class="text-sm font-bold text-primary hover:underline"
            >
                ← Back to Patients
            </Link>
            <Link
                :href="route('patients.edit', patient.id)"
                class="rounded-lg border border-border px-4 py-2 text-sm font-bold text-foreground hover:bg-muted/40"
            >
                Edit Patient
            </Link>
        </div>

        <PatientCard :patient="patient" />

        <div class="rounded-xl border border-border bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-border px-6 py-4">
                <h2 class="font-bold text-foreground">Appointments</h2>
                <Link
                    :href="route('patients.appointments.create', patient.id)"
                    class="rounded-lg bg-primary px-3 py-1.5 text-xs font-bold text-white hover:bg-primary/90"
                >
                    + New Appointment
                </Link>
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
                        <th class="px-6 py-3 font-bold text-muted-foreground">Staff</th>
                        <th class="px-6 py-3 font-bold text-muted-foreground">Status</th>
                        <th class="px-6 py-3 font-bold text-muted-foreground">Notes</th>
                        <th class="px-6 py-3"></th>
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
                            <div v-if="appointment.users.length" class="flex flex-wrap gap-2">
                                <div
                                    v-for="user in appointment.users"
                                    :key="user.id"
                                    class="flex items-center gap-1.5"
                                >
                                    <img
                                        :src="user.avatar_url"
                                        :alt="`${user.first_name} ${user.last_name}`"
                                        class="size-6 rounded-full object-cover ring-1 ring-border"
                                    />
                                    <span class="text-foreground">{{ user.first_name }} {{ user.last_name }}</span>
                                    <span class="text-xs text-muted-foreground">({{ user.pivot.role }})</span>
                                </div>
                            </div>
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                        <td class="px-6 py-3">
                            <span
                                class="rounded-full px-2.5 py-0.5 text-xs font-bold"
                                :class="status_classes[appointment.status] ?? 'bg-gray-100 text-gray-600'"
                            >
                                {{ appointment.status }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-muted-foreground">{{ appointment.notes ?? '—' }}</td>
                        <td class="px-6 py-3">
                            <Link
                                :href="route('patients.appointments.edit', [patient.id, appointment.id])"
                                class="text-xs font-bold text-primary hover:underline"
                            >
                                Edit
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</template>
