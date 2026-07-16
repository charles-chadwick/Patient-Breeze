<script setup>
import { computed } from 'vue'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog'
import InsuranceCompanyForm from '@/Pages/InsuranceCompanies/Partials/Form.vue'

const props = defineProps({
    open: {
        type: Boolean,
        required: true,
    },
    company: {
        type: Object,
        default: null,
    },
})

const emit = defineEmits(['update:open', 'saved'])

const isEditing = computed(() => props.company !== null)

const formAction = computed(() =>
    isEditing.value
        ? route('insurance-companies.update', props.company.id)
        : route('insurance-companies.store')
)

const formMethod = computed(() => (isEditing.value ? 'put' : 'post'))

function handleOpenUpdate(value) {
    emit('update:open', value)
}

function handleSuccess() {
    emit('saved')
    emit('update:open', false)
}
</script>

<template>
    <Dialog :open="open" @update:open="handleOpenUpdate">
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>
                    {{ isEditing
                        ? $t('insurance_companies.form.edit_title', { name: company.name })
                        : $t('insurance_companies.form.new_title') }}
                </DialogTitle>
                <DialogDescription>{{ $t('insurance_companies.form.hint') }}</DialogDescription>
            </DialogHeader>

            <div class="max-h-[65vh] overflow-y-auto px-1">
                <InsuranceCompanyForm
                    v-if="open"
                    :key="company?.id ?? 'new'"
                    :action="formAction"
                    :method="formMethod"
                    :company="company"
                    form-id="insurance-company-catalog-form"
                    :show-actions="false"
                    @success="handleSuccess"
                />
            </div>

            <DialogFooter>
                <button
                    type="button"
                    @click="handleOpenUpdate(false)"
                    class="rounded-lg border border-border px-4 py-2 text-sm font-bold text-foreground hover:bg-muted/40"
                >
                    {{ $t('common.actions.cancel') }}
                </button>
                <button
                    type="submit"
                    form="insurance-company-catalog-form"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90"
                >
                    {{ $t('insurance_companies.form.submit') }}
                </button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
