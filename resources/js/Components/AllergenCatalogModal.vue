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
import AllergenForm from '@/Pages/Allergens/Partials/Form.vue'

const props = defineProps({
    open: {
        type: Boolean,
        required: true,
    },
    allergen: {
        type: Object,
        default: null,
    },
    categoryOptions: {
        type: Array,
        required: true,
    },
})

const emit = defineEmits(['update:open', 'saved'])

const isEditing = computed(() => props.allergen !== null)

const formAction = computed(() =>
    isEditing.value
        ? route('allergens.update', props.allergen.id)
        : route('allergens.store')
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
                        ? $t('allergies.catalog.form.edit_title', { name: allergen.name })
                        : $t('allergies.catalog.form.new_title') }}
                </DialogTitle>
                <DialogDescription>{{ $t('allergies.catalog.form.hint') }}</DialogDescription>
            </DialogHeader>

            <AllergenForm
                v-if="open"
                :key="allergen?.id ?? 'new'"
                :action="formAction"
                :method="formMethod"
                :allergen="allergen"
                :category-options="categoryOptions"
                form-id="allergen-catalog-form"
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
                    form="allergen-catalog-form"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90"
                >
                    {{ $t('allergies.catalog.form.submit') }}
                </button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
