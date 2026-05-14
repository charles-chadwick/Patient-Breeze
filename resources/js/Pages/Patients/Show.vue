<script setup>
import { ref } from 'vue'
import { Link, setLayoutProps } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { formatDate, DATE_SHORT } from '@/lib/utils'
import PatientCard from '@/Components/PatientCard.vue'
import AppointmentStatusBadge from '@/Components/AppointmentStatusBadge.vue'
import SearchInput from '@/Components/SearchInput.vue'
import ContactsTab from '@/Components/ContactsTab.vue'
import DiscussionList from '@/Components/DiscussionList.vue'

defineOptions({ layout: DashboardLayout })

const props = defineProps({
    patient: {
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
    contact_types: {
        type: Array,
        default: () => [],
    },
    contactable_type: {
        type: String,
        required: true,
    },
    users: {
        type: Array,
        default: () => [],
    },
    discussions: {
        type: Array,
        default: null,
    },
    discussion_types: {
        type: Array,
        default: () => [],
    },
})

const active_tab = ref('demographics')

setLayoutProps({
    title: `${props.patient.first_name} ${props.patient.last_name}`,
})
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
                class="inline-flex h-10 items-center rounded-lg border border-border px-4 text-sm font-bold text-foreground hover:bg-muted/40"
            >
                Edit Patient
            </Link>
        </div>

        <div class="overflow-hidden rounded-xl border border-border bg-white shadow-sm">
            <div class="flex bg-muted/40 p-1">
                <button
                    type="button"
                    @click="active_tab = 'demographics'"
                    class="flex-1 rounded-lg px-4 py-2 text-sm font-bold transition-colors"
                    :class="active_tab === 'demographics'
                        ? 'bg-white text-foreground'
                        : 'text-muted-foreground hover:text-foreground'"
                >
                    Demographics
                </button>
                <button
                    type="button"
                    @click="active_tab = 'contacts'"
                    class="flex-1 rounded-lg px-4 py-2 text-sm font-bold transition-colors"
                    :class="active_tab === 'contacts'
                        ? 'bg-white text-foreground'
                        : 'text-muted-foreground hover:text-foreground'"
                >
                    Contacts
                </button>
                <button
                    type="button"
                    @click="active_tab = 'discussions'"
                    class="flex-1 rounded-lg px-4 py-2 text-sm font-bold transition-colors"
                    :class="active_tab === 'discussions'
                        ? 'bg-white text-foreground'
                        : 'text-muted-foreground hover:text-foreground'"
                >
                    Discussions
                </button>
            </div>

            <PatientCard v-if="active_tab === 'demographics'" :patient="patient" flat />

            <ContactsTab
                v-if="active_tab === 'contacts'"
                :contacts="patient.contacts"
                :contactable-type="contactable_type"
                :contactable-id="patient.id"
                :types="contact_types"
                reload-key="patient"
            />

            <DiscussionList
                v-if="active_tab === 'discussions'"
                :discussions="discussions"
                :discussionable-type="contactable_type"
                :discussionable-id="patient.id"
                :users="users"
                :types="discussion_types"
                :patient="patient"
            />
        </div>

        <div class="rounded-xl border border-border bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-border px-6 py-4">
                <h2 class="font-bold text-foreground">Appointments</h2>
                <div class="flex items-center gap-3">
                    <SearchInput
                        :model-value="appointmentSearch"
                        :route-params="patient.id"
                        route-name="patients.show"
                        placeholder="Search reason or notes…"
                        class="w-56"
                    />
                    <Link
                        :href="route('patients.appointments.create', patient.id)"
                        class="inline-flex h-10 items-center rounded-lg bg-primary px-4 text-sm font-bold text-white hover:bg-primary/90"
                    >
                        + New Appointment
                    </Link>
                </div>
            </div>

            <div v-if="appointments.data.length === 0" class="px-6 py-8 text-center text-sm text-muted-foreground">
                {{ appointmentSearch ? 'No appointments match your search.' : 'No appointments on record.' }}
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
                        v-for="appointment in appointments.data"
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
                            <AppointmentStatusBadge :status="appointment.status" />
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
