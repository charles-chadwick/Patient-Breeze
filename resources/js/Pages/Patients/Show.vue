<script setup>
import { computed, ref } from 'vue'
import { Link, setLayoutProps } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { formatDate, DATE_SHORT } from '@/lib/utils'
import PatientCard from '@/Components/PatientCard.vue'
import AppointmentStatusBadge from '@/Components/AppointmentStatusBadge.vue'
import SearchInput from '@/Components/SearchInput.vue'
import ContactsTab from '@/Components/ContactsTab.vue'
import NotesTab from '@/Components/NotesTab.vue'
import DiscussionList from '@/Components/DiscussionList.vue'
import DocumentsBlock from '@/Components/DocumentsBlock.vue'
import MedicationsBlock from '@/Components/MedicationsBlock.vue'

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
    appointment_search: {
        type: String,
        default: '',
    },
    documents: {
        type: Array,
        default: () => [],
    },
    document_type_options: {
        type: Array,
        default: () => [],
    },
    medications: {
        type: Array,
        default: () => [],
    },
    dose_form_options: {
        type: Array,
        default: () => [],
    },
    contact_types: {
        type: Array,
        default: () => [],
    },
    contactable_type: {
        type: String,
        required: true,
    },
    notes: {
        type: Array,
        default: null,
    },
    note_types: {
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

const url_params = new URLSearchParams(typeof window !== 'undefined' ? window.location.search : '')
const initial_tab = url_params.get('tab')
const initial_discussion_id = url_params.get('discussion')
    ? Number(url_params.get('discussion'))
    : null

const active_tab = ref(['demographics', 'contacts', 'notes', 'discussions'].includes(initial_tab) ? initial_tab : 'demographics')

const records_tab = ref('appointments')

setLayoutProps({
    breadcrumbs: computed(() => [
        { label: trans('nav.patients'), href: route('patients.index') },
        { label: `${props.patient.first_name} ${props.patient.last_name}` },
    ]),
})
</script>

<template>
    <div class="grid gap-6">
        <div class="flex justify-end">
            <Link
                :href="route('patients.edit', patient.id)"
                class="inline-flex h-10 items-center rounded-lg border border-border px-4 text-sm font-bold text-foreground hover:bg-muted/40"
            >
                {{ $t('patients.show.edit_patient') }}
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
                    {{ $t('patients.show.tab_demographics') }}
                </button>
                <button
                    type="button"
                    @click="active_tab = 'contacts'"
                    class="flex-1 rounded-lg px-4 py-2 text-sm font-bold transition-colors"
                    :class="active_tab === 'contacts'
                        ? 'bg-white text-foreground'
                        : 'text-muted-foreground hover:text-foreground'"
                >
                    {{ $t('patients.show.tab_contacts') }}
                </button>
                <button
                    type="button"
                    data-testid="patient-tab-notes"
                    @click="active_tab = 'notes'"
                    class="flex-1 rounded-lg px-4 py-2 text-sm font-bold transition-colors"
                    :class="active_tab === 'notes'
                        ? 'bg-white text-foreground'
                        : 'text-muted-foreground hover:text-foreground'"
                >
                    {{ $t('patients.show.tab_notes') }}
                </button>
                <button
                    type="button"
                    @click="active_tab = 'discussions'"
                    class="flex-1 rounded-lg px-4 py-2 text-sm font-bold transition-colors"
                    :class="active_tab === 'discussions'
                        ? 'bg-white text-foreground'
                        : 'text-muted-foreground hover:text-foreground'"
                >
                    {{ $t('patients.show.tab_discussions') }}
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

            <NotesTab
                v-if="active_tab === 'notes'"
                :notes="notes"
                :notable-type="contactable_type"
                :notable-id="patient.id"
                :types="note_types"
            />

            <DiscussionList
                v-if="active_tab === 'discussions'"
                :discussions="discussions"
                :discussionable-type="contactable_type"
                :discussionable-id="patient.id"
                :types="discussion_types"
                :patient="patient"
                :initial-discussion-id="initial_discussion_id"
            />
        </div>

        <div class="overflow-hidden rounded-xl border border-border bg-white shadow-sm">
            <div class="flex bg-muted/40 p-1">
                <button
                    type="button"
                    data-testid="records-tab-appointments"
                    @click="records_tab = 'appointments'"
                    class="flex-1 rounded-lg px-4 py-2 text-sm font-bold transition-colors"
                    :class="records_tab === 'appointments'
                        ? 'bg-white text-foreground'
                        : 'text-muted-foreground hover:text-foreground'"
                >
                    {{ $t('patients.show.tab_appointments') }}
                </button>
                <button
                    type="button"
                    data-testid="records-tab-medications"
                    @click="records_tab = 'medications'"
                    class="flex-1 rounded-lg px-4 py-2 text-sm font-bold transition-colors"
                    :class="records_tab === 'medications'
                        ? 'bg-white text-foreground'
                        : 'text-muted-foreground hover:text-foreground'"
                >
                    {{ $t('patients.show.tab_medications') }}
                </button>
                <button
                    type="button"
                    data-testid="records-tab-documents"
                    @click="records_tab = 'documents'"
                    class="flex-1 rounded-lg px-4 py-2 text-sm font-bold transition-colors"
                    :class="records_tab === 'documents'
                        ? 'bg-white text-foreground'
                        : 'text-muted-foreground hover:text-foreground'"
                >
                    {{ $t('patients.show.tab_documents') }}
                </button>
            </div>

            <div v-if="records_tab === 'appointments'">
            <div class="flex items-center justify-between border-b border-border px-6 py-4">
                <h2 class="font-bold text-foreground">{{ $t('patients.show.appointments_heading') }}</h2>
                <div class="flex items-center gap-3">
                    <SearchInput
                        :model-value="appointment_search"
                        :route-params="patient.id"
                        route-name="patients.show"
                        :placeholder="$t('patients.show.appointments_search_placeholder')"
                        class="w-56"
                    />
                    <Link
                        :href="route('patients.appointments.create', patient.id)"
                        class="inline-flex h-10 items-center rounded-lg bg-primary px-4 text-sm font-bold text-white hover:bg-primary/90"
                    >
                        {{ $t('patients.show.new_appointment') }}
                    </Link>
                </div>
            </div>

            <div v-if="appointments.data.length === 0" class="px-6 py-8 text-center text-sm text-muted-foreground">
                {{ appointment_search ? $t('patients.show.appointments_empty_search') : $t('patients.show.appointments_empty') }}
            </div>

            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="border-b border-border text-left">
                        <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('patients.show.column_date') }}</th>
                        <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('patients.show.column_time') }}</th>
                        <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('patients.show.column_reason') }}</th>
                        <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('patients.show.column_staff') }}</th>
                        <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('patients.show.column_status') }}</th>
                        <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('patients.show.column_notes') }}</th>
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
                                    <span class="text-xs text-muted-foreground">({{ $t('enums.appointment_role.' + user.pivot.role) }})</span>
                                </div>
                            </div>
                            <span v-else class="text-muted-foreground">{{ $t('common.placeholders.em_dash') }}</span>
                        </td>
                        <td class="px-6 py-3">
                            <AppointmentStatusBadge :status="appointment.status" />
                        </td>
                        <td class="px-6 py-3 text-muted-foreground">{{ appointment.notes ?? $t('common.placeholders.em_dash') }}</td>
                        <td class="px-6 py-3">
                            <Link
                                :href="route('patients.appointments.edit', [patient.id, appointment.id])"
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
                    {{ $t('common.pagination.summary', { from: appointments.from, to: appointments.to, total: appointments.total, label: $t('patients.show.appointments_record_label') }) }}
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

            <MedicationsBlock
                v-if="records_tab === 'medications'"
                :patient-id="patient.id"
                :medications="medications"
                :dose-form-options="dose_form_options"
                flat
            />

            <DocumentsBlock
                v-if="records_tab === 'documents'"
                :patient-id="patient.id"
                :documents="documents"
                :types="document_type_options"
                flat
            />
        </div>

    </div>
</template>
