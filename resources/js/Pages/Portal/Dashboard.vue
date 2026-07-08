<script setup>
import PortalLayout from '@/Layouts/PortalLayout.vue'
import { formatDate, DATE_SHORT, DATE_LONG } from '@/lib/utils'

defineOptions({ layout: PortalLayout })

const props = defineProps({
    patient: { type: Object, required: true },
    appointments: { type: Array, required: true },
    discussions: { type: Array, required: true },
    documents: { type: Array, required: true },
})
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
            <h2 class="mb-4 text-base font-semibold text-slate-800">{{ $t('portal.dashboard.documents_heading') }}</h2>
            <p v-if="documents.length === 0" class="text-sm text-slate-400">{{ $t('portal.dashboard.documents_empty') }}</p>
            <ul v-else class="divide-y divide-slate-100">
                <li
                    v-for="doc in documents"
                    :key="doc.id"
                    class="flex items-center justify-between py-3 first:pt-0 last:pb-0"
                >
                    <p class="text-sm font-medium text-slate-700">{{ doc.name }}</p>
                    <p class="text-xs text-slate-400">{{ formatDate(doc.created_at, DATE_SHORT) }}</p>
                </li>
            </ul>
        </div>
    </div>
</template>
