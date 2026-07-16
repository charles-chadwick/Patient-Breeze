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
import VaccineCatalogForm from '@/Pages/Vaccines/Partials/Form.vue'

const props = defineProps({
    open: {
        type: Boolean,
        required: true,
    },
    vaccine: {
        type: Object,
        default: null,
    },
})

const emit = defineEmits(['update:open', 'saved'])

const isEditing = computed(() => props.vaccine !== null)

const formAction = computed(() =>
    isEditing.value
        ? route('vaccines.update', props.vaccine.id)
        : route('vaccines.store')
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
                        ? $t('vaccines.catalog.form.edit_title', { name: vaccine.name })
                        : $t('vaccines.catalog.form.new_title') }}
                </DialogTitle>
                <DialogDescription>{{ $t('vaccines.catalog.form.hint') }}</DialogDescription>
            </DialogHeader>

            <VaccineCatalogForm
                v-if="open"
                :key="vaccine?.id ?? 'new'"
                :action="formAction"
                :method="formMethod"
                :vaccine="vaccine"
                form-id="vaccine-catalog-form"
                :show-actions="false"
                @success="handleSuccess"
            />

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
                    form="vaccine-catalog-form"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90"
                >
                    {{ $t('vaccines.catalog.form.submit') }}
                </button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
