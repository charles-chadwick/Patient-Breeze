<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import LabResultModal from '@/Components/LabResultModal.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'

const props = defineProps({
    patientId: {
        type: Number,
        required: true,
    },
    labResults: {
        type: Array,
        default: () => [],
    },
    flat: {
        type: Boolean,
        default: false,
    },
})

const flag_classes = {
    Normal: 'bg-accent/15 text-accent-foreground',
    Low: 'bg-vibrant-coral-50 text-vibrant-coral-600',
    High: 'bg-vibrant-coral-50 text-vibrant-coral-600',
    Unknown: 'bg-muted text-muted-foreground',
}

const add_open = ref(false)
const confirm_open = ref(false)
const deleting_result = ref(null)
const deleting = ref(false)

function askDelete(result) {
    deleting_result.value = result
    confirm_open.value = true
}

function confirmDelete() {
    if (!deleting_result.value) {
        return
    }

    deleting.value = true

    router.delete(route('patients.lab-results.destroy', [props.patientId, deleting_result.value.id]), {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false
            confirm_open.value = false
            deleting_result.value = null
        },
    })
}
</script>

<template>
    <div :class="flat ? '' : 'rounded-xl border border-border bg-card shadow-sm'">
        <div class="flex items-center justify-between border-b border-border px-6 py-4">
            <h2 class="font-bold text-foreground">{{ $t('lab_results.heading') }}</h2>
            <button
                type="button"
                data-testid="lab-result-add-button"
                @click="add_open = true"
                class="inline-flex h-10 items-center rounded-lg bg-primary px-4 text-sm font-bold text-white hover:bg-primary/90"
            >
                {{ $t('lab_results.add') }}
            </button>
        </div>

        <div v-if="labResults.length === 0" class="px-6 py-8 text-center text-sm text-muted-foreground">
            {{ $t('lab_results.empty') }}
        </div>

        <table v-else class="w-full text-sm">
            <thead>
                <tr class="border-b border-border text-left">
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('lab_results.column_name') }}</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('lab_results.column_value') }}</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('lab_results.column_reference') }}</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('lab_results.column_flag') }}</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('lab_results.column_collected') }}</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('lab_results.column_performing_lab') }}</th>
                    <th class="px-6 py-3 text-right font-bold text-muted-foreground">{{ $t('lab_results.column_actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                <tr
                    v-for="result in labResults"
                    :key="result.id"
                    class="hover:bg-muted/40"
                >
                    <td class="px-6 py-3 font-bold text-foreground">{{ result.name }}</td>
                    <td class="px-6 py-3 text-foreground">{{ result.value }}<span v-if="result.unit" class="text-muted-foreground"> {{ result.unit }}</span></td>
                    <td class="px-6 py-3 text-muted-foreground">{{ result.reference_label }}</td>
                    <td class="px-6 py-3">
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-bold" :class="flag_classes[result.flag]">
                            {{ result.flag_label }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-muted-foreground">{{ result.collected_at || $t('common.placeholders.em_dash') }}</td>
                    <td class="px-6 py-3 text-muted-foreground">{{ result.performing_lab }}</td>
                    <td class="px-6 py-3 text-right">
                        <button
                            type="button"
                            @click="askDelete(result)"
                            class="rounded-lg border border-vibrant-coral-200 px-3 py-1.5 text-xs font-bold text-vibrant-coral-600 hover:bg-vibrant-coral-50"
                        >
                            {{ $t('lab_results.delete') }}
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>

        <LabResultModal
            v-model:open="add_open"
            :action="route('patients.lab-results.store', patientId)"
            :patient-id="patientId"
        />

        <ConfirmDialog
            v-model:open="confirm_open"
            :title="trans('lab_results.delete')"
            :description="deleting_result ? trans('lab_results.delete_confirm') : ''"
            :confirm-label="trans('lab_results.delete')"
            :processing="deleting"
            @confirm="confirmDelete"
        />
    </div>
</template>
