<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import PortalLayout from '@/Layouts/PortalLayout.vue'
import { formatDate, DATE_SHORT, DATE_LONG } from '@/lib/utils'
import DocumentForm from '@/Components/DocumentForm.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'

defineOptions({ layout: PortalLayout })

const props = defineProps({
    patient: { type: Object, required: true },
    appointments: { type: Array, required: true },
    discussions: { type: Array, required: true },
    documents: { type: Array, required: true },
    document_type_options: { type: Array, default: () => [] },
})

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
                <h2 class="mb-4 text-base font-semibold text-slate-800">{{ $t('portal.dashboard.appointments_heading') }}</h2>
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
