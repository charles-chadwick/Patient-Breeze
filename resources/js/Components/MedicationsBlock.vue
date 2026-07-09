<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import MedicationModal from '@/Components/MedicationModal.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'

const props = defineProps({
    patientId: {
        type: Number,
        required: true,
    },
    medications: {
        type: Array,
        default: () => [],
    },
    doseFormOptions: {
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
const deleting_medication = ref(null)
const deleting = ref(false)

function askDelete(medication) {
    deleting_medication.value = medication
    confirm_open.value = true
}

function confirmDelete() {
    if (!deleting_medication.value) {
        return
    }

    deleting.value = true

    router.delete(route('patients.medications.destroy', [props.patientId, deleting_medication.value.id]), {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false
            confirm_open.value = false
            deleting_medication.value = null
        },
    })
}
</script>

<template>
    <div :class="flat ? '' : 'rounded-xl border border-border bg-white shadow-sm'">
        <div class="flex items-center justify-between border-b border-border px-6 py-4">
            <h2 class="font-bold text-foreground">{{ $t('medications.heading') }}</h2>
            <button
                type="button"
                data-testid="medication-add-button"
                @click="add_open = true"
                class="inline-flex h-10 items-center rounded-lg bg-primary px-4 text-sm font-bold text-white hover:bg-primary/90"
            >
                {{ $t('medications.add') }}
            </button>
        </div>

        <div v-if="medications.length === 0" class="px-6 py-8 text-center text-sm text-muted-foreground">
            {{ $t('medications.empty') }}
        </div>

        <table v-else class="w-full text-sm">
            <thead>
                <tr class="border-b border-border text-left">
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('medications.column_name') }}</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('medications.column_dosage') }}</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('medications.column_dose_form') }}</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('medications.column_type') }}</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('medications.column_ndc') }}</th>
                    <th class="px-6 py-3 text-right font-bold text-muted-foreground">{{ $t('medications.column_actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                <tr
                    v-for="medication in medications"
                    :key="medication.id"
                    class="hover:bg-muted/40"
                >
                    <td class="px-6 py-3 font-bold text-foreground">{{ medication.name }}</td>
                    <td class="px-6 py-3 text-foreground">{{ medication.dosage }}</td>
                    <td class="px-6 py-3">
                        <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary">
                            {{ medication.dose_form_label }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-muted-foreground">{{ medication.type }}</td>
                    <td class="px-6 py-3 text-muted-foreground">{{ medication.ndc || $t('common.placeholders.em_dash') }}</td>
                    <td class="px-6 py-3 text-right">
                        <button
                            type="button"
                            @click="askDelete(medication)"
                            class="rounded-lg border border-vibrant-coral-200 px-3 py-1.5 text-xs font-bold text-vibrant-coral-600 hover:bg-vibrant-coral-50"
                        >
                            {{ $t('medications.delete') }}
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>

        <MedicationModal
            v-model:open="add_open"
            :action="route('patients.medications.store', patientId)"
            :dose-form-options="doseFormOptions"
        />

        <ConfirmDialog
            v-model:open="confirm_open"
            :title="trans('medications.delete')"
            :description="deleting_medication ? trans('medications.delete_confirm') : ''"
            :confirm-label="trans('medications.delete')"
            :processing="deleting"
            @confirm="confirmDelete"
        />
    </div>
</template>
