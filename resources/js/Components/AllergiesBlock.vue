<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import { formatDate, DATE_SHORT } from '@/lib/utils'
import AllergyModal from '@/Components/AllergyModal.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'

const props = defineProps({
    patientId: {
        type: Number,
        required: true,
    },
    allergies: {
        type: Array,
        default: () => [],
    },
    categoryOptions: {
        type: Array,
        default: () => [],
    },
    reactionOptions: {
        type: Array,
        default: () => [],
    },
    severityOptions: {
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
const deleting_allergy = ref(null)
const deleting = ref(false)

function askDelete(allergy) {
    deleting_allergy.value = allergy
    confirm_open.value = true
}

function confirmDelete() {
    if (!deleting_allergy.value) {
        return
    }

    deleting.value = true

    router.delete(route('patients.allergies.destroy', [props.patientId, deleting_allergy.value.id]), {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false
            confirm_open.value = false
            deleting_allergy.value = null
        },
    })
}
</script>

<template>
    <div :class="flat ? '' : 'rounded-xl border border-border bg-card shadow-sm'">
        <div class="flex items-center justify-between border-b border-border px-6 py-4">
            <h2 class="font-bold text-foreground">{{ $t('allergies.heading') }}</h2>
            <button
                type="button"
                data-testid="allergy-add-button"
                @click="add_open = true"
                class="inline-flex h-10 items-center rounded-lg bg-primary px-4 text-sm font-bold text-white hover:bg-primary/90"
            >
                {{ $t('allergies.add') }}
            </button>
        </div>

        <div v-if="allergies.length === 0" class="px-6 py-8 text-center text-sm text-muted-foreground">
            {{ $t('allergies.empty') }}
        </div>

        <table v-else class="w-full text-sm">
            <thead>
                <tr class="border-b border-border text-left">
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('allergies.column_allergen') }}</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('allergies.column_category') }}</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('allergies.column_reactions') }}</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('allergies.column_severity') }}</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('allergies.column_onset_on') }}</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('allergies.column_status') }}</th>
                    <th class="px-6 py-3 text-right font-bold text-muted-foreground">{{ $t('allergies.column_actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                <tr
                    v-for="allergy in allergies"
                    :key="allergy.id"
                    data-testid="allergy-row"
                    class="hover:bg-muted/40"
                >
                    <td class="px-6 py-3 font-bold text-foreground">{{ allergy.allergen }}</td>
                    <td class="px-6 py-3">
                        <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary">
                            {{ allergy.category_label }}
                        </span>
                    </td>
                    <td class="px-6 py-3">
                        <div class="flex flex-wrap gap-1">
                            <span
                                v-for="reaction in allergy.reaction_labels"
                                :key="reaction"
                                class="rounded-full bg-muted px-2 py-0.5 text-xs font-bold text-foreground"
                            >
                                {{ reaction }}
                            </span>
                        </div>
                    </td>
                    <td class="px-6 py-3">
                        <span
                            class="rounded-full px-2.5 py-0.5 text-xs font-bold"
                            :class="allergy.is_critical
                                ? 'bg-vibrant-coral-100 text-vibrant-coral-700'
                                : 'bg-muted text-foreground'"
                        >
                            {{ allergy.severity_label }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-muted-foreground">
                        {{ allergy.onset_on ? formatDate(allergy.onset_on, DATE_SHORT) : $t('common.placeholders.em_dash') }}
                    </td>
                    <td class="px-6 py-3">
                        <span class="rounded-full bg-muted px-2.5 py-0.5 text-xs font-bold text-foreground">
                            {{ allergy.status_label }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-right">
                        <button
                            type="button"
                            @click="askDelete(allergy)"
                            class="rounded-lg border border-vibrant-coral-200 px-3 py-1.5 text-xs font-bold text-vibrant-coral-600 hover:bg-vibrant-coral-50"
                        >
                            {{ $t('allergies.delete') }}
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>

        <AllergyModal
            v-model:open="add_open"
            :action="route('patients.allergies.store', patientId)"
            :category-options="categoryOptions"
            :reaction-options="reactionOptions"
            :severity-options="severityOptions"
            :status-options="statusOptions"
        />

        <ConfirmDialog
            v-model:open="confirm_open"
            :title="trans('allergies.delete')"
            :description="deleting_allergy ? trans('allergies.delete_confirm') : ''"
            :confirm-label="trans('allergies.delete')"
            :processing="deleting"
            @confirm="confirmDelete"
        />
    </div>
</template>
