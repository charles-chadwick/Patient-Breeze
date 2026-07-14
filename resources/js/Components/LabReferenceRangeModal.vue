<script setup>
import { computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog'

const props = defineProps({
    open: {
        type: Boolean,
        required: true,
    },
    labOrderId: {
        type: Number,
        required: true,
    },
    range: {
        type: Object,
        default: null,
    },
    genderOptions: {
        type: Array,
        default: () => [],
    },
})

const emit = defineEmits(['update:open', 'saved'])

const isEditing = computed(() => props.range !== null)

const form = useForm({
    gender_at_birth: '',
    min_age: '',
    max_age: '',
    low_value: '',
    high_value: '',
    unit: '',
})

// Re-seed the form each time the modal opens, for either create or edit.
watch(() => props.open, (is_open) => {
    if (!is_open) {
        return
    }

    form.clearErrors()
    form.gender_at_birth = props.range?.gender_at_birth ?? ''
    form.min_age = props.range?.min_age ?? ''
    form.max_age = props.range?.max_age ?? ''
    form.low_value = props.range?.low_value ?? ''
    form.high_value = props.range?.high_value ?? ''
    form.unit = props.range?.unit ?? ''
})

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            emit('saved')
            emit('update:open', false)
        },
    }

    if (isEditing.value) {
        form.put(route('lab-orders.reference-ranges.update', [props.labOrderId, props.range.id]), options)
    } else {
        form.post(route('lab-orders.reference-ranges.store', props.labOrderId), options)
    }
}

function handleOpenUpdate(value) {
    emit('update:open', value)
}
</script>

<template>
    <Dialog :open="open" @update:open="handleOpenUpdate">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>
                    {{ isEditing ? $t('lab_orders.catalog.ranges.modal.edit_title') : $t('lab_orders.catalog.ranges.modal.new_title') }}
                </DialogTitle>
                <DialogDescription>{{ $t('lab_orders.catalog.ranges.modal.hint') }}</DialogDescription>
            </DialogHeader>

            <form id="lab-reference-range-form" class="grid gap-5" @submit.prevent="submit">
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('lab_orders.catalog.ranges.modal.label_sex') }}
                    </label>
                    <select
                        v-model="form.gender_at_birth"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-vibrant-coral-400': form.errors.gender_at_birth }"
                    >
                        <option value="">{{ $t('lab_orders.catalog.ranges.modal.sex_any') }}</option>
                        <option v-for="option in genderOptions" :key="option" :value="option">
                            {{ $t('enums.gender_at_birth.' + option) }}
                        </option>
                    </select>
                    <p v-if="form.errors.gender_at_birth" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.gender_at_birth }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                            {{ $t('lab_orders.catalog.ranges.modal.label_min_age') }}
                        </label>
                        <input
                            v-model="form.min_age"
                            type="number"
                            min="0"
                            max="150"
                            class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                            :class="{ 'border-vibrant-coral-400': form.errors.min_age }"
                        />
                        <p v-if="form.errors.min_age" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.min_age }}</p>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                            {{ $t('lab_orders.catalog.ranges.modal.label_max_age') }}
                        </label>
                        <input
                            v-model="form.max_age"
                            type="number"
                            min="0"
                            max="150"
                            class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                            :class="{ 'border-vibrant-coral-400': form.errors.max_age }"
                        />
                        <p v-if="form.errors.max_age" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.max_age }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                            {{ $t('lab_orders.catalog.ranges.modal.label_low') }}
                        </label>
                        <input
                            v-model="form.low_value"
                            type="text"
                            class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                            :class="{ 'border-vibrant-coral-400': form.errors.low_value }"
                        />
                        <p v-if="form.errors.low_value" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.low_value }}</p>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                            {{ $t('lab_orders.catalog.ranges.modal.label_high') }}
                        </label>
                        <input
                            v-model="form.high_value"
                            type="text"
                            class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                            :class="{ 'border-vibrant-coral-400': form.errors.high_value }"
                        />
                        <p v-if="form.errors.high_value" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.high_value }}</p>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                            {{ $t('lab_orders.catalog.ranges.modal.label_unit') }} <span class="text-vibrant-coral-500">*</span>
                        </label>
                        <input
                            v-model="form.unit"
                            type="text"
                            class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                            :class="{ 'border-vibrant-coral-400': form.errors.unit }"
                        />
                        <p v-if="form.errors.unit" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.unit }}</p>
                    </div>
                </div>
            </form>

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
                    form="lab-reference-range-form"
                    :disabled="form.processing"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90 disabled:opacity-50"
                >
                    {{ $t('lab_orders.catalog.ranges.modal.submit') }}
                </button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
