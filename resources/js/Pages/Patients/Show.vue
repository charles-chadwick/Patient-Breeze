<script setup>
import { computed, ref } from 'vue'
import { Link, router, setLayoutProps, usePage } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { formatDate, DATE_SHORT } from '@/lib/utils'
import PatientCard from '@/Components/PatientCard.vue'
import AppointmentStatusBadge from '@/Components/AppointmentStatusBadge.vue'
import SearchInput from '@/Components/SearchInput.vue'
import ContactsTab from '@/Components/ContactsTab.vue'
import NotesTab from '@/Components/NotesTab.vue'
import EncountersTab from '@/Components/EncountersTab.vue'
import DiscussionList from '@/Components/DiscussionList.vue'
import DocumentsBlock from '@/Components/DocumentsBlock.vue'
import MedicationsBlock from '@/Components/MedicationsBlock.vue'
import DiagnosesBlock from '@/Components/DiagnosesBlock.vue'
import LabResultsBlock from '@/Components/LabResultsBlock.vue'
import AppointmentModal from '@/Components/AppointmentModal.vue'
import UserPopover from '@/Components/UserPopover.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'
import TabBar from '@/Components/ui/TabBar.vue'

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
    status_options: {
        type: Array,
        default: () => [],
    },
    role_options: {
        type: Array,
        default: () => [],
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
    frequency_options: {
        type: Array,
        default: () => [],
    },
    patient_diagnoses: {
        type: Array,
        default: () => [],
    },
    lab_results: {
        type: Array,
        default: () => [],
    },
    diagnosis_status_options: {
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
    encounter_notes: {
        type: Array,
        default: null,
    },
    encounter_note_types: {
        type: Array,
        default: () => [],
    },
    owner_options: {
        type: Array,
        default: () => [],
    },
    patient_appointments: {
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

const primary_tabs = [
    { key: 'demographics', label: 'patients.show.tab_demographics' },
    { key: 'contacts', label: 'patients.show.tab_contacts' },
    { key: 'notes', label: 'patients.show.tab_notes', testid: 'patient-tab-notes' },
    { key: 'discussions', label: 'patients.show.tab_discussions' },
]

const records_tabs = [
    { key: 'appointments', label: 'patients.show.tab_appointments', testid: 'records-tab-appointments' },
    { key: 'encounters', label: 'patients.show.tab_encounters', testid: 'patient-tab-encounters' },
    { key: 'documents', label: 'patients.show.tab_documents', testid: 'records-tab-documents' },
]

const care_tabs = [
    { key: 'medications', label: 'patients.show.tab_medications', testid: 'records-tab-medications' },
    { key: 'diagnoses', label: 'patients.show.tab_diagnoses', testid: 'records-tab-diagnoses' },
    { key: 'lab_results', label: 'patients.show.tab_lab_results', testid: 'records-tab-lab-results' },
]

const active_tab = ref(primary_tabs.some((tab) => tab.key === initial_tab) ? initial_tab : 'demographics')

const records_tab = ref('appointments')

const care_tab = ref('medications')

const appointment_modal_open = ref(false)
const editing_appointment = ref(null)

function editAppointment(appointment) {
    editing_appointment.value = appointment
    appointment_modal_open.value = true
}

setLayoutProps({
    breadcrumbs: computed(() => [
        { label: trans('nav.patients'), href: route('patients.index') },
        { label: `${props.patient.first_name} ${props.patient.last_name}` },
    ]),
})

const page = usePage()
const can_delete = computed(() => page.props.auth?.permissions?.includes('delete_patients') ?? false)

// Roles permitted to reach the audit log; keep in sync with AuditLogController.
const audit_log_roles = ['Super Admin', 'Doctor', 'Staff']
const can_view_audit_log = computed(
    () => page.props.auth?.roles?.some((role) => audit_log_roles.includes(role)) ?? false,
)

const confirm_open = ref(false)
const deleting = ref(false)

function askDeletePatient() {
    confirm_open.value = true
}

function confirmDeletePatient() {
    deleting.value = true

    router.delete(route('patients.destroy', props.patient.id), {
        onFinish: () => {
            deleting.value = false
            confirm_open.value = false
        },
    })
}
</script>

<template>
    <div class="grid gap-6">
        <div class="flex justify-end gap-3">
            <button
                v-if="can_delete"
                type="button"
                @click="askDeletePatient"
                class="inline-flex h-10 items-center rounded-lg border border-vibrant-coral-300 px-4 text-sm font-bold text-vibrant-coral-600 hover:bg-vibrant-coral-50"
            >
                {{ $t('patients.show.delete_patient') }}
            </button>
            <Link
                v-if="can_view_audit_log"
                as="button"
                type="button"
                data-testid="patient-audit-log-link"
                :href="route('audit-log.index', { patient_id: patient.id })"
                class="inline-flex h-10 items-center rounded-lg border border-border px-4 text-sm font-bold text-foreground hover:bg-muted/40"
            >
                {{ $t('patients.show.view_audit_log') }}
            </Link>
            <Link
                as="button"
                type="button"
                :href="route('patients.edit', patient.id)"
                class="inline-flex h-10 items-center rounded-lg border border-border px-4 text-sm font-bold text-foreground hover:bg-muted/40"
            >
                {{ $t('patients.show.edit_patient') }}
            </Link>
        </div>

        <div class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
            <TabBar v-model="active_tab" :tabs="primary_tabs" />

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

        <div class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
            <TabBar v-model="records_tab" :tabs="records_tabs" />

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
                                    <UserPopover :user="user">
                                        <button
                                            type="button"
                                            class="flex items-center gap-1.5 rounded-md hover:bg-muted/40 focus:outline-none"
                                        >
                                            <img
                                                :src="user.avatar_url"
                                                :alt="`${user.first_name} ${user.last_name}`"
                                                class="size-6 rounded-full object-cover ring-1 ring-border"
                                            />
                                            <span class="text-foreground">{{ user.first_name }} {{ user.last_name }}</span>
                                        </button>
                                    </UserPopover>
                                    <span class="text-xs text-muted-foreground">({{ $t('enums.appointment_role.' + user.pivot.role) }})</span>
                                </div>
                            </div>
                            <span v-else class="text-muted-foreground">{{ $t('common.placeholders.em_dash') }}</span>
                        </td>
                        <td class="px-6 py-3">
                            <AppointmentStatusBadge :status="appointment.status" />
                        </td>
                        <td class="px-6 py-3">
                            <button
                                type="button"
                                data-testid="appointment-edit-button"
                                @click="editAppointment(appointment)"
                                class="rounded-lg border border-border px-3 py-1.5 text-xs font-bold text-foreground hover:bg-muted/40"
                            >
                                {{ $t('common.actions.edit') }}
                            </button>
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

            <AppointmentModal
                v-model:open="appointment_modal_open"
                :patient-id="patient.id"
                :appointment="editing_appointment"
                :status_options="status_options"
                :role_options="role_options"
            />

            <ConfirmDialog
                v-model:open="confirm_open"
                :title="trans('patients.show.delete_patient')"
                :description="trans('patients.show.delete_confirm')"
                :confirm-label="trans('patients.show.delete_patient')"
                :processing="deleting"
                @confirm="confirmDeletePatient"
            />
            </div>

            <EncountersTab
                v-if="records_tab === 'encounters'"
                :patient-id="patient.id"
                :notes="encounter_notes"
                :types="encounter_note_types"
                :owner-options="owner_options"
                :appointments="patient_appointments"
            />

            <DocumentsBlock
                v-if="records_tab === 'documents'"
                :patient-id="patient.id"
                :documents="documents"
                :types="document_type_options"
                flat
            />
        </div>

        <div class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
            <TabBar v-model="care_tab" :tabs="care_tabs" />

            <MedicationsBlock
                v-if="care_tab === 'medications'"
                :patient-id="patient.id"
                :medications="medications"
                :dose-form-options="dose_form_options"
                :frequency-options="frequency_options"
                flat
            />

            <DiagnosesBlock
                v-if="care_tab === 'diagnoses'"
                :patient-id="patient.id"
                :diagnoses="patient_diagnoses"
                :status-options="diagnosis_status_options"
                flat
            />

            <LabResultsBlock
                v-if="care_tab === 'lab_results'"
                :patient-id="patient.id"
                :lab-results="lab_results"
                flat
            />
        </div>

    </div>
</template>
