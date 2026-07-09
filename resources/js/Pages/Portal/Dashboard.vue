<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import PortalLayout from '@/Layouts/PortalLayout.vue'
import { formatDate, DATE_SHORT, DATE_LONG } from '@/lib/utils'
import DocumentForm from '@/Components/DocumentForm.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'
import ProviderSelect from '@/Components/ProviderSelect.vue'
import DatePicker from '@/Components/ui/DatePicker.vue'
import TimePicker from '@/Components/ui/TimePicker.vue'

defineOptions({ layout: PortalLayout })

const props = defineProps({
    patient: { type: Object, required: true },
    appointments: { type: Array, required: true },
    appointment_requests: { type: Array, default: () => [] },
    discussions: { type: Array, required: true },
    documents: { type: Array, required: true },
    document_type_options: { type: Array, default: () => [] },
})

const request_open = ref(false)

const request_form = useForm({
    user_id: null,
    date: '',
    start_time: '',
    end_time: '',
    reason: '',
    notes: '',
})

function submitRequest() {
    request_form.post(route('portal.appointment-requests.store'), {
        preserveScroll: true,
        onSuccess: () => {
            request_form.reset()
            request_open.value = false
        },
    })
}

function statusClasses(status) {
    return {
        Pending: 'bg-amber-50 text-amber-700',
        Approved: 'bg-emerald-50 text-emerald-700',
        Declined: 'bg-slate-100 text-slate-500',
    }[status] ?? 'bg-slate-100 text-slate-500'
}

const upload_open = ref(false)
const confirm_open = ref(false)
const deleting_document = ref(null)
const deleting = ref(false)

function askDelete(document) {
    deleting_document.value = document
    confirm_open.value = true
}

function confirmDelete() {
    if (!deleting_document.value) {
        return
    }

    deleting.value = true

    router.delete(route('portal.documents.destroy', deleting_document.value.id), {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false
            confirm_open.value = false
            deleting_document.value = null
        },
    })
}
</script>

<template>
    <div class="grid gap-6">
        <!-- Health summary -->
        <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
            <p class="mb-1 text-sm text-slate-400">{{ $t('portal.dashboard.welcome_back') }}</p>
            <h1 class="mb-5 text-2xl font-bold text-slate-800">
                {{ patient.first_name }} {{ patient.last_name }}
            </h1>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $t('portal.dashboard.mrn') }}</p>
                    <p class="mt-0.5 text-sm font-medium text-slate-700">{{ patient.mrn }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $t('portal.dashboard.date_of_birth') }}</p>
                    <p class="mt-0.5 text-sm font-medium text-slate-700">{{ formatDate(patient.date_of_birth, DATE_SHORT) }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $t('portal.dashboard.blood_type') }}</p>
                    <p class="mt-0.5 text-sm font-medium text-slate-700">{{ patient.blood_type ?? $t('common.placeholders.em_dash') }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $t('portal.dashboard.gender_identity') }}</p>
                    <p class="mt-0.5 text-sm font-medium text-slate-700">{{ patient.gender_identity ?? $t('common.placeholders.em_dash') }}</p>
                </div>
            </div>
        </div>

        <!-- Appointments + Messages row -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Upcoming appointments -->
            <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-slate-800">{{ $t('portal.dashboard.appointments_heading') }}</h2>
                    <button
                        type="button"
                        @click="request_open = !request_open"
                        class="inline-flex h-9 items-center rounded-lg bg-primary px-4 text-sm font-bold text-white hover:bg-primary/90"
                    >
                        {{ $t('portal.dashboard.request_appointment') }}
                    </button>
                </div>

                <!-- Request form -->
                <form
                    v-if="request_open"
                    @submit.prevent="submitRequest"
                    class="mb-5 grid gap-4 rounded-xl border border-slate-100 bg-slate-50 p-5"
                >
                    <div>
                        <h3 class="text-sm font-semibold text-slate-800">{{ $t('portal.appointments.modal_title') }}</h3>
                        <p class="mt-0.5 text-xs text-slate-400">{{ $t('portal.appointments.modal_subtitle') }}</p>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $t('portal.appointments.label_provider') }}</label>
                        <ProviderSelect
                            v-model="request_form.user_id"
                            search-route="portal.appointment-requests.providers.search"
                            :placeholder="$t('portal.appointments.placeholder_provider')"
                        />
                        <p v-if="request_form.errors.user_id" class="mt-1 text-xs text-vibrant-coral-600">{{ request_form.errors.user_id }}</p>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $t('portal.appointments.label_date') }}</label>
                        <DatePicker v-model="request_form.date" />
                        <p v-if="request_form.errors.date" class="mt-1 text-xs text-vibrant-coral-600">{{ request_form.errors.date }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $t('portal.appointments.label_start_time') }}</label>
                            <TimePicker v-model="request_form.start_time" />
                            <p v-if="request_form.errors.start_time" class="mt-1 text-xs text-vibrant-coral-600">{{ request_form.errors.start_time }}</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $t('portal.appointments.label_end_time') }}</label>
                            <TimePicker v-model="request_form.end_time" />
                            <p v-if="request_form.errors.end_time" class="mt-1 text-xs text-vibrant-coral-600">{{ request_form.errors.end_time }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $t('portal.appointments.label_reason') }}</label>
                        <input
                            v-model="request_form.reason"
                            type="text"
                            :placeholder="$t('portal.appointments.placeholder_reason')"
                            class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                        />
                        <p v-if="request_form.errors.reason" class="mt-1 text-xs text-vibrant-coral-600">{{ request_form.errors.reason }}</p>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $t('portal.appointments.label_notes') }}</label>
                        <textarea
                            v-model="request_form.notes"
                            rows="2"
                            :placeholder="$t('portal.appointments.placeholder_notes')"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                        />
                        <p v-if="request_form.errors.notes" class="mt-1 text-xs text-vibrant-coral-600">{{ request_form.errors.notes }}</p>
                    </div>

                    <div class="flex justify-end gap-2">
                        <button
                            type="button"
                            @click="request_open = false"
                            class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100"
                        >
                            {{ $t('portal.appointments.cancel') }}
                        </button>
                        <button
                            type="submit"
                            :disabled="request_form.processing"
                            class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90 disabled:opacity-60"
                        >
                            {{ request_form.processing ? $t('portal.appointments.submitting') : $t('portal.appointments.submit') }}
                        </button>
                    </div>
                </form>

                <p v-if="appointments.length === 0" class="text-sm text-slate-400">
                    {{ $t('portal.dashboard.appointments_empty') }}
                </p>
                <ul v-else class="divide-y divide-slate-100">
                    <li
                        v-for="appt in appointments"
                        :key="appt.id"
                        class="py-3 first:pt-0 last:pb-0"
                    >
                        <p class="text-sm font-medium text-slate-700">{{ formatDate(appt.date, DATE_LONG) }}</p>
                        <p class="text-xs text-slate-400">{{ appt.start_time?.slice(0, 5) }} – {{ appt.end_time?.slice(0, 5) }}</p>
                        <p v-if="appt.reason" class="mt-0.5 text-xs text-slate-500">{{ appt.reason }}</p>
                    </li>
                </ul>

                <!-- Appointment requests -->
                <div v-if="appointment_requests.length" class="mt-5 border-t border-slate-100 pt-4">
                    <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $t('portal.dashboard.requests_heading') }}</h3>
                    <ul class="divide-y divide-slate-100">
                        <li
                            v-for="request in appointment_requests"
                            :key="request.id"
                            class="flex items-start justify-between gap-3 py-2.5 first:pt-0 last:pb-0"
                        >
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-700">{{ formatDate(request.date, DATE_LONG) }}</p>
                                <p class="text-xs text-slate-400">
                                    {{ request.start_time }} – {{ request.end_time }}
                                    <span v-if="request.provider">· {{ request.provider.first_name }} {{ request.provider.last_name }}</span>
                                </p>
                                <p v-if="request.reason" class="mt-0.5 truncate text-xs text-slate-500">{{ request.reason }}</p>
                            </div>
                            <span
                                class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                :class="statusClasses(request.status)"
                            >
                                {{ request.status_label }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Recent messages -->
            <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-base font-semibold text-slate-800">{{ $t('portal.dashboard.messages_heading') }}</h2>
                <p v-if="discussions.length === 0" class="text-sm text-slate-400">{{ $t('portal.dashboard.messages_empty') }}</p>
                <ul v-else class="divide-y divide-slate-100">
                    <li
                        v-for="discussion in discussions"
                        :key="discussion.id"
                        class="py-3 first:pt-0 last:pb-0"
                    >
                        <p class="text-sm font-medium text-slate-700">{{ discussion.title }}</p>
                        <p class="text-xs text-slate-400">{{ formatDate(discussion.created_at, DATE_SHORT) }}</p>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Documents -->
        <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-800">{{ $t('portal.dashboard.documents_heading') }}</h2>
                <button
                    type="button"
                    @click="upload_open = !upload_open"
                    class="inline-flex h-9 items-center rounded-lg bg-primary px-4 text-sm font-bold text-white hover:bg-primary/90"
                >
                    {{ $t('portal.dashboard.documents_upload') }}
                </button>
            </div>

            <div v-if="upload_open" class="mb-5 rounded-xl border border-slate-100 bg-slate-50 p-5">
                <DocumentForm
                    :action="route('portal.documents.store')"
                    :types="document_type_options"
                    form-id="portal-document-form"
                    @success="upload_open = false"
                />
                <div class="mt-4 flex justify-end gap-2">
                    <button
                        type="button"
                        @click="upload_open = false"
                        class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100"
                    >
                        {{ $t('documents.form.cancel') }}
                    </button>
                    <button
                        type="submit"
                        form="portal-document-form"
                        class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90"
                    >
                        {{ $t('documents.form.submit') }}
                    </button>
                </div>
            </div>

            <p v-if="documents.length === 0" class="text-sm text-slate-400">{{ $t('portal.dashboard.documents_empty') }}</p>
            <ul v-else class="divide-y divide-slate-100">
                <li
                    v-for="doc in documents"
                    :key="doc.id"
                    class="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0"
                >
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-slate-700">{{ doc.name }}</p>
                        <p class="text-xs text-slate-400">
                            {{ doc.type_label }} · {{ formatDate(doc.document_date ?? doc.created_at, DATE_SHORT) }}
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <a
                            :href="doc.download_url"
                            class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-100"
                        >
                            {{ $t('documents.download') }}
                        </a>
                        <button
                            v-if="doc.can_delete"
                            type="button"
                            @click="askDelete(doc)"
                            class="rounded-lg border border-vibrant-coral-200 px-3 py-1.5 text-xs font-bold text-vibrant-coral-600 hover:bg-vibrant-coral-50"
                        >
                            {{ $t('documents.delete') }}
                        </button>
                    </div>
                </li>
            </ul>
        </div>

        <ConfirmDialog
            v-model:open="confirm_open"
            :title="trans('documents.delete')"
            :description="deleting_document ? trans('documents.delete_confirm') : ''"
            :confirm-label="trans('documents.delete')"
            :processing="deleting"
            @confirm="confirmDelete"
        />
    </div>
</template>
