<script setup>
import { computed, ref } from 'vue'
import { Link, setLayoutProps } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { formatDate, DATE_SHORT } from '@/lib/utils'

defineOptions({ layout: DashboardLayout })

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
})

setLayoutProps({
    title: `${props.user.first_name} ${props.user.last_name}`,
})

const fullName = computed(() =>
    [props.user.prefix, props.user.first_name, props.user.middle_name, props.user.last_name, props.user.suffix]
        .filter(Boolean).join(' ')
)

const userInitials = computed(() =>
    `${props.user.first_name[0]}${props.user.last_name[0]}`.toUpperCase()
)

const showAvatarModal = ref(false)

const role_badge_classes = {
    'Super Admin': 'bg-purple-100 text-purple-700',
    'Doctor': 'bg-blue-100 text-blue-700',
    'Nurse': 'bg-teal-100 text-teal-700',
    'Medical Assistant': 'bg-cyan-100 text-cyan-700',
    'Staff': 'bg-gray-100 text-gray-600',
}

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

        <div class="rounded-xl border border-border bg-white shadow-sm">
            <div class="flex items-center gap-5 border-b border-border px-6 py-5">
                <button
                    type="button"
                    class="shrink-0 cursor-zoom-in focus:outline-none"
                    @click="showAvatarModal = true"
                >
                    <img
                        :src="user.avatar_url"
                        :alt="userInitials"
                        class="size-16 rounded-full object-cover ring-2 ring-primary/20"
                    />
                </button>
                <div>
                    <h2 class="text-lg font-bold text-foreground">{{ fullName }}</h2>
                    <span
                        v-if="user.roles[0]"
                        class="mt-1 inline-block rounded-full px-2.5 py-0.5 text-xs font-bold"
                        :class="role_badge_classes[user.roles[0].name] ?? 'bg-gray-100 text-gray-600'"
                    >
                        {{ user.roles[0].name }}
                    </span>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-x-8 gap-y-4 px-6 py-5 sm:grid-cols-3 lg:grid-cols-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">Full Name</p>
                    <p class="mt-1 text-sm font-bold text-foreground">{{ fullName }}</p>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">Email</p>
                    <p class="mt-1 text-sm text-foreground">{{ user.email }}</p>
                </div>
                <div v-if="user.roles[0]">
                    <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">Role</p>
                    <p class="mt-1 text-sm text-foreground">{{ user.roles[0].name }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-border bg-white shadow-sm">
            <div class="border-b border-border px-6 py-4">
                <h2 class="font-bold text-foreground">Appointments</h2>
            </div>

            <div v-if="user.appointments.length === 0" class="px-6 py-8 text-center text-sm text-muted-foreground">
                No appointments on record.
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
                        v-for="appointment in user.appointments"
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
                                    :href="route('patients.show', appointment.patient_record.id)"
                                    class="font-bold text-primary hover:underline"
                                >
                                    {{ appointment.patient.first_name }} {{ appointment.patient.last_name }}
                                </Link>
                            </div>
                        </td>
                        <td class="px-6 py-3 text-foreground">{{ appointment.reason }}</td>
                        <td class="px-6 py-3 text-foreground">{{ appointment.pivot.role }}</td>
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
                                :href="route('patients.appointments.edit', [appointment.patient_record.id, appointment.id])"
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

    <Teleport to="body">
        <div
            v-if="showAvatarModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
            @click.self="showAvatarModal = false"
        >
            <div class="relative max-w-sm w-full">
                <button
                    type="button"
                    class="absolute -right-3 -top-3 flex size-8 items-center justify-center rounded-full bg-white shadow-md text-muted-foreground hover:text-foreground focus:outline-none"
                    @click="showAvatarModal = false"
                >
                    ✕
                </button>
                <img
                    :src="user.avatar_url"
                    :alt="userInitials"
                    class="w-full rounded-2xl object-cover shadow-xl ring-4 ring-white bg-white"
                />
            </div>
        </div>
    </Teleport>
</template>
