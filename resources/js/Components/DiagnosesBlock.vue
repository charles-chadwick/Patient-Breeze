<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import DiagnosisModal from '@/Components/DiagnosisModal.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'

const props = defineProps({
    patientId: {
        type: Number,
        required: true,
    },
    diagnoses: {
        type: Array,
        default: () => [],
    },
    statusOptions: {
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
const deleting_diagnosis = ref(null)
const deleting = ref(false)

function askDelete(diagnosis) {
    deleting_diagnosis.value = diagnosis
    confirm_open.value = true
}

function confirmDelete() {
    if (!deleting_diagnosis.value) {
        return
    }

    deleting.value = true

    router.delete(route('patients.diagnoses.destroy', [props.patientId, deleting_diagnosis.value.id]), {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false
            confirm_open.value = false
            deleting_diagnosis.value = null
        },
    })
}
</script>

<template>
    <div :class="flat ? '' : 'rounded-xl border border-border bg-card shadow-sm'">
        <div class="flex items-center justify-between border-b border-border px-6 py-4">
            <h2 class="font-bold text-foreground">{{ $t('diagnoses.heading') }}</h2>
            <button
                type="button"
                data-testid="diagnosis-add-button"
                @click="add_open = true"
                class="inline-flex h-10 items-center rounded-lg bg-primary px-4 text-sm font-bold text-white hover:bg-primary/90"
            >
                {{ $t('diagnoses.add') }}
            </button>
        </div>

        <div v-if="diagnoses.length === 0" class="px-6 py-8 text-center text-sm text-muted-foreground">
            {{ $t('diagnoses.empty') }}
        </div>

        <table v-else class="w-full text-sm">
            <thead>
                <tr class="border-b border-border text-left">
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('diagnoses.column_diagnosis') }}</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('diagnoses.column_icd10_code') }}</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('diagnoses.column_diagnosed_on') }}</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('diagnoses.column_status') }}</th>
                    <th class="px-6 py-3 text-right font-bold text-muted-foreground">{{ $t('diagnoses.column_actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                <tr
                    v-for="diagnosis in diagnoses"
                    :key="diagnosis.id"
                    class="hover:bg-muted/40"
                >
                    <td class="px-6 py-3 font-bold text-foreground">{{ diagnosis.diagnosis }}</td>
                    <td class="px-6 py-3">
                        <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary">
                            {{ diagnosis.icd10_code }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-muted-foreground">{{ diagnosis.diagnosed_on || $t('common.placeholders.em_dash') }}</td>
                    <td class="px-6 py-3">
                        <span class="rounded-full bg-muted px-2.5 py-0.5 text-xs font-bold text-foreground">
                            {{ diagnosis.status_label }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-right">
                        <button
                            type="button"
                            @click="askDelete(diagnosis)"
                            class="rounded-lg border border-vibrant-coral-200 px-3 py-1.5 text-xs font-bold text-vibrant-coral-600 hover:bg-vibrant-coral-50"
                        >
                            {{ $t('diagnoses.delete') }}
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>

        <DiagnosisModal
            v-model:open="add_open"
            :action="route('patients.diagnoses.store', patientId)"
            :status-options="statusOptions"
        />

        <ConfirmDialog
            v-model:open="confirm_open"
            :title="trans('diagnoses.delete')"
            :description="deleting_diagnosis ? trans('diagnoses.delete_confirm') : ''"
            :confirm-label="trans('diagnoses.delete')"
            :processing="deleting"
            @confirm="confirmDelete"
        />
    </div>
</template>
