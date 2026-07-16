<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import { TriangleAlert } from 'lucide-vue-next'
import { formatDate, DATE_SHORT } from '@/lib/utils'
import VaccineModal from '@/Components/VaccineModal.vue'
import UserPopover from '@/Components/UserPopover.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'

const props = defineProps({
    patientId: {
        type: Number,
        required: true,
    },
    vaccines: {
        type: Array,
        default: () => [],
    },
    statusOptions: {
        type: Array,
        default: () => [],
    },
    routeOptions: {
        type: Array,
        default: () => [],
    },
    siteOptions: {
        type: Array,
        default: () => [],
    },
    staffOptions: {
        type: Array,
        default: () => [],
    },
    flat: {
        type: Boolean,
        default: false,
    },
})

const add_open = ref(false)
const confirm_open = ref(false)
const deleting_vaccine = ref(null)
const deleting = ref(false)

function askDelete(vaccine) {
    deleting_vaccine.value = vaccine
    confirm_open.value = true
}

function confirmDelete() {
    if (!deleting_vaccine.value) {
        return
    }

    deleting.value = true

    router.delete(route('patients.vaccines.destroy', [props.patientId, deleting_vaccine.value.id]), {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false
            confirm_open.value = false
            deleting_vaccine.value = null
        },
    })
}
</script>

<template>
    <div :class="flat ? '' : 'rounded-xl border border-border bg-card shadow-sm'">
        <div class="flex items-center justify-between border-b border-border px-6 py-4">
            <h2 class="font-bold text-foreground">{{ $t('vaccines.heading') }}</h2>
            <button
                type="button"
                data-testid="vaccine-add-button"
                @click="add_open = true"
                class="inline-flex h-10 items-center rounded-lg bg-primary px-4 text-sm font-bold text-white hover:bg-primary/90"
            >
                {{ $t('vaccines.add') }}
            </button>
        </div>

        <div v-if="vaccines.length === 0" class="px-6 py-8 text-center text-sm text-muted-foreground">
            {{ $t('vaccines.empty') }}
        </div>

        <table v-else class="w-full text-sm">
            <thead>
                <tr class="border-b border-border text-left">
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('vaccines.column_vaccine') }}</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('vaccines.column_administered_on') }}</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('vaccines.column_dose') }}</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('vaccines.column_lot') }}</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('vaccines.column_administered_by') }}</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('vaccines.column_status') }}</th>
                    <th class="px-6 py-3 text-right font-bold text-muted-foreground">{{ $t('vaccines.column_actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                <tr
                    v-for="vaccine in vaccines"
                    :key="vaccine.id"
                    data-testid="vaccine-row"
                    class="hover:bg-muted/40"
                >
                    <td class="px-6 py-3">
                        <span class="block font-bold text-foreground">{{ vaccine.vaccine }}</span>
                        <span v-if="vaccine.cvx_code" class="font-mono text-xs text-muted-foreground">
                            {{ vaccine.cvx_code }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-muted-foreground">
                        {{ formatDate(vaccine.administered_on, DATE_SHORT) }}
                    </td>
                    <td class="px-6 py-3">
                        <span v-if="vaccine.dose_number" class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary">
                            {{ $t('vaccines.dose_number', { number: vaccine.dose_number }) }}
                        </span>
                        <span v-else class="text-muted-foreground">{{ $t('common.placeholders.em_dash') }}</span>
                        <span v-if="vaccine.route_label || vaccine.site_label" class="mt-0.5 block text-xs text-muted-foreground">
                            {{ [vaccine.route_label, vaccine.site_label].filter(Boolean).join(' · ') }}
                        </span>
                    </td>
                    <td class="px-6 py-3">
                        <span v-if="vaccine.lot_number" class="block font-mono text-xs text-foreground">
                            {{ vaccine.lot_number }}
                        </span>
                        <span v-else class="text-muted-foreground">{{ $t('common.placeholders.em_dash') }}</span>
                        <span v-if="vaccine.manufacturer" class="block text-xs text-muted-foreground">
                            {{ vaccine.manufacturer }}
                        </span>
                        <!-- A dose given past its lot expiry may not count towards the series. -->
                        <span
                            v-if="vaccine.was_expired_when_administered"
                            :title="$t('vaccines.expired_lot')"
                            class="mt-0.5 inline-flex items-center gap-1 text-xs font-bold text-vibrant-coral-600"
                        >
                            <TriangleAlert class="size-3" />
                            {{ $t('vaccines.expired_lot') }}
                        </span>
                    </td>
                    <td class="px-6 py-3">
                        <UserPopover v-if="vaccine.administered_by" :user="vaccine.administered_by">
                            <button
                                type="button"
                                class="flex items-center gap-1.5 rounded-md hover:bg-muted/40 focus:outline-none"
                            >
                                <img
                                    :src="vaccine.administered_by.avatar_url"
                                    :alt="`${vaccine.administered_by.first_name} ${vaccine.administered_by.last_name}`"
                                    class="size-6 rounded-full object-cover ring-1 ring-border"
                                />
                                <span class="text-foreground">
                                    {{ vaccine.administered_by.first_name }} {{ vaccine.administered_by.last_name }}
                                </span>
                            </button>
                        </UserPopover>
                        <span v-else class="text-muted-foreground">{{ $t('vaccines.unknown_administrator') }}</span>
                    </td>
                    <td class="px-6 py-3">
                        <span
                            class="rounded-full px-2.5 py-0.5 text-xs font-bold"
                            :class="vaccine.is_administered
                                ? 'bg-tropical-teal-100 text-tropical-teal-700'
                                : 'bg-muted text-foreground'"
                        >
                            {{ vaccine.status_label }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-right">
                        <button
                            type="button"
                            @click="askDelete(vaccine)"
                            class="rounded-lg border border-vibrant-coral-200 px-3 py-1.5 text-xs font-bold text-vibrant-coral-600 hover:bg-vibrant-coral-50"
                        >
                            {{ $t('vaccines.delete') }}
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>

        <VaccineModal
            v-model:open="add_open"
            :action="route('patients.vaccines.store', patientId)"
            :status-options="statusOptions"
            :route-options="routeOptions"
            :site-options="siteOptions"
            :staff-options="staffOptions"
        />

        <ConfirmDialog
            v-model:open="confirm_open"
            :title="trans('vaccines.delete')"
            :description="deleting_vaccine ? trans('vaccines.delete_confirm') : ''"
            :confirm-label="trans('vaccines.delete')"
            :processing="deleting"
            @confirm="confirmDelete"
        />
    </div>
</template>
