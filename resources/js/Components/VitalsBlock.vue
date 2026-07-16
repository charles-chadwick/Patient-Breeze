<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import { formatDate, DATE_SHORT } from '@/lib/utils'
import RecordVitalsModal from '@/Components/RecordVitalsModal.vue'
import VitalsFlowsheet from '@/Components/VitalsFlowsheet.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'

const props = defineProps({
    patientId: {
        type: Number,
        required: true,
    },
    vitals: {
        type: Array,
        default: () => [],
    },
    vitalTypes: {
        type: Array,
        default: () => [],
    },
    positionOptions: {
        type: Array,
        default: () => [],
    },
    temperatureSiteOptions: {
        type: Array,
        default: () => [],
    },
    oxygenDeliveryOptions: {
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
const deleting_set = ref(null)
const deleting = ref(false)

// The most recent set drives the summary tiles at the top of the block.
const latest = computed(() => props.vitals[0] ?? null)

function isAbnormal(type_value) {
    return latest.value?.abnormal_flags?.includes(type_value) ?? false
}

const tiles = computed(() => {
    if (!latest.value) {
        return []
    }

    const set = latest.value

    return [
        { key: 'bp', label: trans('enums.vital_type.Systolic'), value: set.blood_pressure, unit: trans('vitals.units.mmhg'), abnormal: isAbnormal('Systolic') || isAbnormal('Diastolic') },
        { key: 'hr', label: trans('enums.vital_type.Heart Rate'), value: set.heart_rate, unit: trans('vitals.units.bpm'), abnormal: isAbnormal('Heart Rate') },
        { key: 'temp', label: trans('enums.vital_type.Temperature'), value: set.temperature, unit: trans('vitals.units.celsius'), abnormal: isAbnormal('Temperature') },
        { key: 'spo2', label: trans('enums.vital_type.Oxygen Saturation'), value: set.oxygen_saturation, unit: trans('vitals.units.percent'), abnormal: isAbnormal('Oxygen Saturation') },
        { key: 'weight', label: trans('enums.vital_type.Weight'), value: set.weight, unit: trans('vitals.units.kg'), abnormal: false },
        { key: 'bmi', label: trans('vitals.latest.bmi'), value: set.bmi, unit: '', abnormal: false },
    ]
})

function askDelete(set) {
    deleting_set.value = set
    confirm_open.value = true
}

function confirmDelete() {
    if (!deleting_set.value) {
        return
    }

    deleting.value = true

    router.delete(route('patients.vitals.destroy', [props.patientId, deleting_set.value.id]), {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false
            confirm_open.value = false
            deleting_set.value = null
        },
    })
}
</script>

<template>
    <div :class="flat ? '' : 'rounded-xl border border-border bg-card shadow-sm'">
        <div class="flex items-center justify-between border-b border-border px-6 py-4">
            <h2 class="font-bold text-foreground">{{ $t('vitals.heading') }}</h2>
            <button
                type="button"
                data-testid="vitals-add-button"
                @click="add_open = true"
                class="inline-flex h-10 items-center rounded-lg bg-primary px-4 text-sm font-bold text-white hover:bg-primary/90"
            >
                {{ $t('vitals.add') }}
            </button>
        </div>

        <div v-if="vitals.length === 0" class="px-6 py-8 text-center text-sm text-muted-foreground">
            {{ $t('vitals.empty') }}
        </div>

        <template v-else>
            <!-- Latest readings summary -->
            <div class="border-b border-border px-6 py-4">
                <p class="mb-3 text-xs font-bold uppercase tracking-wide text-muted-foreground">
                    {{ $t('vitals.latest.heading') }}
                    <span class="ml-1 font-normal normal-case">
                        · {{ $t('vitals.latest.measured', { date: formatDate(latest.measured_on, DATE_SHORT) }) }}
                    </span>
                </p>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                    <div
                        v-for="tile in tiles"
                        :key="tile.key"
                        class="rounded-lg border border-border bg-background px-3 py-2.5"
                        :class="{ 'border-vibrant-coral-200 bg-vibrant-coral-50': tile.abnormal }"
                    >
                        <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">{{ tile.label }}</p>
                        <p
                            class="mt-1 text-lg font-bold tabular-nums"
                            :class="tile.abnormal ? 'text-vibrant-coral-600' : 'text-foreground'"
                        >
                            <template v-if="tile.value !== null && tile.value !== undefined && tile.value !== ''">
                                {{ tile.value }}<span v-if="tile.unit" class="ml-0.5 text-xs font-normal text-muted-foreground">{{ tile.unit }}</span>
                            </template>
                            <template v-else>{{ $t('vitals.latest.no_reading') }}</template>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Flowsheet -->
            <div class="px-2 py-2">
                <VitalsFlowsheet :vitals="vitals" :vital-types="vitalTypes" deletable @delete="askDelete" />
            </div>
        </template>

        <RecordVitalsModal
            v-model:open="add_open"
            :action="route('patients.vitals.store', patientId)"
            :position-options="positionOptions"
            :temperature-site-options="temperatureSiteOptions"
            :oxygen-delivery-options="oxygenDeliveryOptions"
            :staff-options="staffOptions"
        />

        <ConfirmDialog
            v-model:open="confirm_open"
            :title="trans('vitals.delete')"
            :description="deleting_set ? trans('vitals.delete_confirm') : ''"
            :confirm-label="trans('vitals.delete')"
            :processing="deleting"
            @confirm="confirmDelete"
        />
    </div>
</template>
