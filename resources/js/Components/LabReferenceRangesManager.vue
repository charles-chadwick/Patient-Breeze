<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import LabReferenceRangeModal from '@/Components/LabReferenceRangeModal.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'

const props = defineProps({
    labOrderId: {
        type: Number,
        required: true,
    },
    ranges: {
        type: Array,
        default: () => [],
    },
    genderOptions: {
        type: Array,
        default: () => [],
    },
})

const modal_open = ref(false)
const editing_range = ref(null)

function openCreate() {
    editing_range.value = null
    modal_open.value = true
}

function openEdit(range) {
    editing_range.value = range
    modal_open.value = true
}

function sexLabel(range) {
    return range.gender_at_birth
        ? trans('enums.gender_at_birth.' + range.gender_at_birth)
        : trans('lab_orders.catalog.ranges.any')
}

function ageLabel(range) {
    if (range.min_age != null && range.max_age != null) {
        return trans('lab_orders.catalog.ranges.age_between', { min: range.min_age, max: range.max_age })
    }
    if (range.min_age != null) {
        return trans('lab_orders.catalog.ranges.age_from', { min: range.min_age })
    }
    if (range.max_age != null) {
        return trans('lab_orders.catalog.ranges.age_to', { max: range.max_age })
    }
    return trans('lab_orders.catalog.ranges.any')
}

const confirm_open = ref(false)
const deleting_range = ref(null)
const deleting = ref(false)

function askDelete(range) {
    deleting_range.value = range
    confirm_open.value = true
}

function confirmDelete() {
    if (!deleting_range.value) {
        return
    }

    deleting.value = true

    router.delete(route('lab-orders.reference-ranges.destroy', [props.labOrderId, deleting_range.value.id]), {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false
            confirm_open.value = false
            deleting_range.value = null
        },
    })
}
</script>

<template>
    <div class="rounded-xl border border-border bg-card shadow-sm">
        <div class="flex flex-col gap-1 border-b border-border px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-bold text-foreground">{{ $t('lab_orders.catalog.ranges.heading') }}</h2>
                <p class="mt-1 text-xs text-muted-foreground">{{ $t('lab_orders.catalog.ranges.hint') }}</p>
            </div>
            <button
                type="button"
                data-testid="reference-range-add-button"
                @click="openCreate"
                class="inline-flex h-10 shrink-0 items-center rounded-lg bg-primary px-4 text-sm font-bold text-white hover:bg-primary/90"
            >
                {{ $t('lab_orders.catalog.ranges.add') }}
            </button>
        </div>

        <div v-if="ranges.length === 0" class="px-6 py-8 text-center text-sm text-muted-foreground">
            {{ $t('lab_orders.catalog.ranges.empty') }}
        </div>

        <table v-else class="w-full text-sm">
            <thead>
                <tr class="border-b border-border text-left">
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('lab_orders.catalog.ranges.column_sex') }}</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('lab_orders.catalog.ranges.column_age') }}</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('lab_orders.catalog.ranges.column_low') }}</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('lab_orders.catalog.ranges.column_high') }}</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('lab_orders.catalog.ranges.column_unit') }}</th>
                    <th class="px-6 py-3 text-right font-bold text-muted-foreground">{{ $t('lab_orders.catalog.ranges.column_actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                <tr v-for="range in ranges" :key="range.id" class="hover:bg-muted/40">
                    <td class="px-6 py-3 font-bold text-foreground">{{ sexLabel(range) }}</td>
                    <td class="px-6 py-3 text-muted-foreground">{{ ageLabel(range) }}</td>
                    <td class="px-6 py-3 text-foreground">{{ range.low_value ?? $t('common.placeholders.em_dash') }}</td>
                    <td class="px-6 py-3 text-foreground">{{ range.high_value ?? $t('common.placeholders.em_dash') }}</td>
                    <td class="px-6 py-3 text-muted-foreground">{{ range.unit }}</td>
                    <td class="px-6 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button
                                type="button"
                                @click="openEdit(range)"
                                class="rounded-lg border border-border px-3 py-1.5 text-xs font-bold text-foreground hover:bg-muted/40"
                            >
                                {{ $t('common.actions.edit') }}
                            </button>
                            <button
                                type="button"
                                @click="askDelete(range)"
                                class="rounded-lg border border-vibrant-coral-200 px-3 py-1.5 text-xs font-bold text-vibrant-coral-600 hover:bg-vibrant-coral-50"
                            >
                                {{ $t('common.actions.delete') }}
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <LabReferenceRangeModal
            v-model:open="modal_open"
            :lab-order-id="labOrderId"
            :range="editing_range"
            :gender-options="genderOptions"
        />

        <ConfirmDialog
            v-model:open="confirm_open"
            :title="trans('common.actions.delete')"
            :description="trans('lab_orders.catalog.ranges.delete_confirm')"
            :confirm-label="trans('common.actions.delete')"
            :processing="deleting"
            @confirm="confirmDelete"
        />
    </div>
</template>
