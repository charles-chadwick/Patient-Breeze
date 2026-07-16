<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import { formatDate, DATE_SHORT } from '@/lib/utils'
import InsuranceModal from '@/Components/InsuranceModal.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'

const props = defineProps({
    patientId: {
        type: Number,
        required: true,
    },
    insurances: {
        type: Array,
        default: () => [],
    },
    planTypeOptions: {
        type: Array,
        default: () => [],
    },
    priorityOptions: {
        type: Array,
        default: () => [],
    },
    relationshipOptions: {
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
const deleting_insurance = ref(null)
const deleting = ref(false)

function askDelete(insurance) {
    deleting_insurance.value = insurance
    confirm_open.value = true
}

function confirmDelete() {
    if (!deleting_insurance.value) {
        return
    }

    deleting.value = true

    router.delete(route('patients.insurances.destroy', [props.patientId, deleting_insurance.value.id]), {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false
            confirm_open.value = false
            deleting_insurance.value = null
        },
    })
}
</script>

<template>
    <div :class="flat ? '' : 'rounded-xl border border-border bg-card shadow-sm'">
        <div class="flex items-center justify-between border-b border-border px-6 py-4">
            <h2 class="font-bold text-foreground">{{ $t('insurances.heading') }}</h2>
            <button
                type="button"
                data-testid="insurance-add-button"
                @click="add_open = true"
                class="inline-flex h-10 items-center rounded-lg bg-primary px-4 text-sm font-bold text-white hover:bg-primary/90"
            >
                {{ $t('insurances.add') }}
            </button>
        </div>

        <div v-if="insurances.length === 0" class="px-6 py-8 text-center text-sm text-muted-foreground">
            {{ $t('insurances.empty') }}
        </div>

        <ul v-else class="divide-y divide-border">
            <li
                v-for="insurance in insurances"
                :key="insurance.id"
                data-testid="insurance-row"
                class="px-6 py-4"
            >
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary">
                                {{ insurance.priority_label }}
                            </span>
                            <span class="font-bold text-foreground">{{ insurance.company_name }}</span>
                            <span v-if="insurance.plan_type_label" class="rounded-full bg-muted px-2 py-0.5 text-xs font-bold text-foreground">
                                {{ insurance.plan_type_label }}
                            </span>
                            <span
                                v-if="!insurance.is_active"
                                class="rounded-full bg-muted px-2 py-0.5 text-xs font-bold text-muted-foreground"
                            >
                                {{ $t('insurances.inactive') }}
                            </span>
                        </div>

                        <dl class="mt-2 grid grid-cols-1 gap-x-6 gap-y-1 text-sm sm:grid-cols-2">
                            <div class="flex gap-2">
                                <dt class="text-muted-foreground">{{ $t('insurances.member_id') }}:</dt>
                                <dd class="font-mono font-bold text-foreground">{{ insurance.member_id }}</dd>
                            </div>
                            <div v-if="insurance.group_number" class="flex gap-2">
                                <dt class="text-muted-foreground">{{ $t('insurances.group_number') }}:</dt>
                                <dd class="font-mono text-foreground">{{ insurance.group_number }}</dd>
                            </div>
                            <div v-if="insurance.subscriber_name" class="flex gap-2">
                                <dt class="text-muted-foreground">{{ $t('insurances.subscriber') }}:</dt>
                                <dd class="text-foreground">
                                    {{ insurance.subscriber_name }}
                                    <span class="text-muted-foreground">({{ insurance.relationship_to_subscriber_label }})</span>
                                </dd>
                            </div>
                            <div v-if="insurance.effective_on || insurance.terminates_on" class="flex gap-3 text-muted-foreground">
                                <span v-if="insurance.effective_on">{{ $t('insurances.effective', { date: formatDate(insurance.effective_on, DATE_SHORT) }) }}</span>
                                <span v-if="insurance.terminates_on">{{ $t('insurances.terminates', { date: formatDate(insurance.terminates_on, DATE_SHORT) }) }}</span>
                            </div>
                        </dl>

                        <p v-if="insurance.notes" class="mt-1.5 text-sm text-muted-foreground">{{ insurance.notes }}</p>
                    </div>

                    <button
                        type="button"
                        @click="askDelete(insurance)"
                        class="shrink-0 rounded-lg border border-vibrant-coral-200 px-3 py-1.5 text-xs font-bold text-vibrant-coral-600 hover:bg-vibrant-coral-50"
                    >
                        {{ $t('insurances.delete') }}
                    </button>
                </div>
            </li>
        </ul>

        <InsuranceModal
            v-model:open="add_open"
            :action="route('patients.insurances.store', patientId)"
            :plan-type-options="planTypeOptions"
            :priority-options="priorityOptions"
            :relationship-options="relationshipOptions"
        />

        <ConfirmDialog
            v-model:open="confirm_open"
            :title="trans('insurances.delete')"
            :description="deleting_insurance ? trans('insurances.delete_confirm') : ''"
            :confirm-label="trans('insurances.delete')"
            :processing="deleting"
            @confirm="confirmDelete"
        />
    </div>
</template>
