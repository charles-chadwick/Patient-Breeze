<script setup>
import { computed, ref } from 'vue'
import { Link, setLayoutProps } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { formatDate, DATE_SHORT } from '@/lib/utils'
import UserCard from '@/Components/UserCard.vue'
import AppointmentStatusBadge from '@/Components/AppointmentStatusBadge.vue'
import SearchInput from '@/Components/SearchInput.vue'
import ContactsTab from '@/Components/ContactsTab.vue'

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
    appointment_search: {
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
})

const active_tab = ref('details')

setLayoutProps({
    breadcrumbs: computed(() => [
        { label: trans('nav.users'), href: route('users.index') },
        { label: `${props.user.first_name} ${props.user.last_name}` },
    ]),
})
</script>

<template>
    <div class="grid gap-6">
        <div class="flex justify-end">
            <Link
                as="button"
                type="button"
                :href="route('users.edit', user.id)"
                class="rounded-lg border border-border px-4 py-2 text-sm font-bold text-foreground hover:bg-muted/40"
            >
                {{ $t('users.show.edit_user') }}
            </Link>
        </div>

        <div class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
            <div class="flex bg-muted/40 p-1">
                <button
                    type="button"
                    @click="active_tab = 'details'"
                    class="flex-1 rounded-lg px-4 py-2 text-sm font-bold transition-colors"
                    :class="active_tab === 'details'
                        ? 'bg-card text-foreground'
                        : 'text-muted-foreground hover:text-foreground'"
                >
                    {{ $t('users.show.tab_details') }}
                </button>
                <button
                    type="button"
                    @click="active_tab = 'contacts'"
                    class="flex-1 rounded-lg px-4 py-2 text-sm font-bold transition-colors"
                    :class="active_tab === 'contacts'
                        ? 'bg-card text-foreground'
                        : 'text-muted-foreground hover:text-foreground'"
                >
                    {{ $t('users.show.tab_contacts') }}
                </button>
            </div>

            <UserCard v-if="active_tab === 'details'" :user="user" flat />

            <ContactsTab
                v-if="active_tab === 'contacts'"
                :contacts="user.contacts"
                :contactable-type="contactable_type"
                :contactable-id="user.id"
                :types="contact_types"
                reload-key="user"
            />
        </div>

        <div class="rounded-xl border border-border bg-card shadow-sm">
            <div class="flex items-center justify-between border-b border-border px-6 py-4">
                <h2 class="font-bold text-foreground">{{ $t('users.show.appointments_heading') }}</h2>
                <SearchInput
                    :model-value="appointment_search"
                    :route-params="user.id"
                    route-name="users.show"
                    :placeholder="$t('users.show.appointments_search_placeholder')"
                    class="w-56"
                />
            </div>

            <div v-if="appointments.data.length === 0" class="px-6 py-8 text-center text-sm text-muted-foreground">
                {{ appointment_search ? $t('users.show.appointments_empty_search') : $t('users.show.appointments_empty') }}
            </div>

            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="border-b border-border text-left">
                        <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('users.show.column_date') }}</th>
                        <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('users.show.column_time') }}</th>
                        <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('users.show.column_patient') }}</th>
                        <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('users.show.column_reason') }}</th>
                        <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('users.show.column_role') }}</th>
                        <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('users.show.column_status') }}</th>
                        <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('users.show.column_notes') }}</th>
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
                        <td class="px-6 py-3 text-foreground">{{ $t('enums.appointment_role.' + appointment.pivot.role) }}</td>
                        <td class="px-6 py-3">
                            <AppointmentStatusBadge :status="appointment.status" />
                        </td>
                        <td class="px-6 py-3 text-muted-foreground">{{ appointment.notes ?? $t('common.placeholders.em_dash') }}</td>
                        <td class="px-6 py-3">
                            <Link
                                as="button"
                                type="button"
                                :href="route('patients.appointments.edit', [appointment.patient_id, appointment.id])"
                                class="text-xs font-bold text-primary hover:underline"
                            >
                                {{ $t('common.actions.edit') }}
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
                    {{ $t('common.pagination.summary', { from: appointments.from, to: appointments.to, total: appointments.total, label: $t('users.show.appointments_record_label') }) }}
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
