<script setup>
import { Link, setLayoutProps } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { formatDate, DATE_SHORT } from '@/lib/utils'
import UserCard from '@/Components/UserCard.vue'
import AppointmentStatusBadge from '@/Components/AppointmentStatusBadge.vue'
import SearchInput from '@/Components/SearchInput.vue'

defineOptions({ layout: DashboardLayout })

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    appointments: {
        type: Object,
        required: true,
    },
    appointmentSearch: {
        type: String,
        default: '',
    },
})

setLayoutProps({
    title: `${props.user.first_name} ${props.user.last_name}`,
})
</script>

<template>
    <div class="grid gap-6">
        <div class="flex items-center justify-between">
            <Link
                :href="route('users.index')"
                class="text-sm font-bold text-primary hover:underline"
            >
                ← Back to Users
            </Link>
            <Link
                :href="route('users.edit', user.id)"
                class="rounded-lg border border-border px-4 py-2 text-sm font-bold text-foreground hover:bg-muted/40"
            >
                Edit User
            </Link>
        </div>

        <UserCard :user="user" />

        <div class="rounded-xl border border-border bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-border px-6 py-4">
                <h2 class="font-bold text-foreground">Appointments</h2>
                <SearchInput
                    :model-value="appointmentSearch"
                    :route-params="user.id"
                    route-name="users.show"
                    placeholder="Search patient or reason…"
                    class="w-56"
                />
            </div>

            <div v-if="appointments.data.length === 0" class="px-6 py-8 text-center text-sm text-muted-foreground">
                {{ appointmentSearch ? 'No appointments match your search.' : 'No appointments on record.' }}
            </div>

            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="border-b border-border text-left">
                        <th class="px-6 py-3 font-bold text-muted-foreground">Date</th>
                        <th class="px-6 py-3 font-bold text-muted-foreground">Time</th>
                        <th class="px-6 py-3 font-bold text-muted-foreground">Patient</th>
                        <th class="px-6 py-3 font-bold text-muted-foreground">Reason</th>
                        <th class="px-6 py-3 font-bold text-muted-foreground">Role</th>
                        <th class="px-6 py-3 font-bold text-muted-foreground">Status</th>
                        <th class="px-6 py-3 font-bold text-muted-foreground">Notes</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr
                        v-for="appointment in appointments.data"
                        :key="appointment.id"
                        class="hover:bg-muted/40"
                    >
                        <td class="px-6 py-3 text-foreground">{{ formatDate(appointment.date, DATE_SHORT) }}</td>
                        <td class="px-6 py-3 text-foreground">
                            {{ appointment.start_time.slice(0, 5) }}–{{ appointment.end_time.slice(0, 5) }}
                        </td>
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-2">
                                <img
                                    :src="appointment.patient.avatar_url"
                                    :alt="`${appointment.patient.first_name} ${appointment.patient.last_name}`"
                                    class="size-6 rounded-full object-cover ring-1 ring-border"
                                />
                                <Link
                                    :href="route('patients.show', appointment.patient_id)"
                                    class="font-bold text-primary hover:underline"
                                >
                                    {{ appointment.patient.first_name }} {{ appointment.patient.last_name }}
                                </Link>
                            </div>
                        </td>
                        <td class="px-6 py-3 text-foreground">{{ appointment.reason }}</td>
                        <td class="px-6 py-3 text-foreground">{{ appointment.pivot.role }}</td>
                        <td class="px-6 py-3">
                            <AppointmentStatusBadge :status="appointment.status" />
                        </td>
                        <td class="px-6 py-3 text-muted-foreground">{{ appointment.notes ?? '—' }}</td>
                        <td class="px-6 py-3">
                            <Link
                                :href="route('patients.appointments.edit', [appointment.patient_id, appointment.id])"
                                class="text-xs font-bold text-primary hover:underline"
                            >
                                Edit
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div
                v-if="appointments.total > 0"
                class="flex items-center justify-between border-t border-border px-6 py-4"
            >
                <p class="text-sm text-muted-foreground">
                    Showing {{ appointments.from }}–{{ appointments.to }} of {{ appointments.total }} appointments
                </p>
                <div class="flex items-center gap-1">
                    <Link
                        v-if="appointments.prev_page_url"
                        :href="appointments.prev_page_url"
                        class="rounded-lg border border-border px-3 py-1.5 text-sm font-bold text-foreground hover:bg-muted/40"
                    >
                        ←
                    </Link>
                    <template v-for="link in appointments.links.slice(1, -1)" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="rounded-lg border px-3 py-1.5 text-sm font-bold"
                            :class="link.active
                                ? 'border-primary bg-primary text-white'
                                : 'border-border text-foreground hover:bg-muted/40'"
                        >
                            {{ link.label }}
                        </Link>
                        <span
                            v-else
                            class="px-2 py-1.5 text-sm text-muted-foreground"
                        >
                            {{ link.label }}
                        </span>
                    </template>
                    <Link
                        v-if="appointments.next_page_url"
                        :href="appointments.next_page_url"
                        class="rounded-lg border border-border px-3 py-1.5 text-sm font-bold text-foreground hover:bg-muted/40"
                    >
                        →
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
