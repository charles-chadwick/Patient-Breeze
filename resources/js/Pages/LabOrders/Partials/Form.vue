<script setup>
import { Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
    action: {
        type: String,
        required: true,
    },
    method: {
        type: String,
        required: true,
    },
    labOrder: {
        type: Object,
        default: null,
    },
    cancelHref: {
        type: String,
        default: null,
    },
    formId: {
        type: String,
        default: 'lab-order-form',
    },
    showActions: {
        type: Boolean,
        default: true,
    },
})

const emit = defineEmits(['success'])

const form = useForm({
    name: props.labOrder?.name ?? '',
    performing_lab: props.labOrder?.performing_lab ?? '',
    cpt_code: props.labOrder?.cpt_code ?? '',
})

function submit() {
    form[props.method](props.action, {
        preserveScroll: true,
        onSuccess: () => emit('success'),
    })
}
</script>

<template>
    <form :id="formId" class="grid gap-5 sm:grid-cols-2" @submit.prevent="submit">
        <!-- Order Name -->
        <div class="sm:col-span-2">
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('lab_orders.catalog.form.label_name') }} <span class="text-vibrant-coral-500">*</span>
            </label>
            <input
                v-model="form.name"
                type="text"
                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                :class="{ 'border-vibrant-coral-400': form.errors.name }"
            />
            <p v-if="form.errors.name" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.name }}</p>
        </div>

        <!-- Performing Lab -->
        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('lab_orders.catalog.form.label_performing_lab') }} <span class="text-vibrant-coral-500">*</span>
            </label>
            <input
                v-model="form.performing_lab"
                type="text"
                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                :class="{ 'border-vibrant-coral-400': form.errors.performing_lab }"
            />
            <p v-if="form.errors.performing_lab" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.performing_lab }}</p>
        </div>

        <!-- CPT Code -->
        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('lab_orders.catalog.form.label_cpt_code') }} <span class="text-vibrant-coral-500">*</span>
            </label>
            <input
                v-model="form.cpt_code"
                type="text"
                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                :class="{ 'border-vibrant-coral-400': form.errors.cpt_code }"
            />
            <p v-if="form.errors.cpt_code" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.cpt_code }}</p>
        </div>

        <!-- Actions (full-page usage; the modal renders its own footer) -->
        <div v-if="showActions" class="flex items-center justify-end gap-3 sm:col-span-2">
            <Link
                v-if="cancelHref"
                :href="cancelHref"
                class="rounded-lg border border-border px-4 py-2 text-sm font-bold text-foreground hover:bg-muted/40"
            >
                {{ $t('common.actions.cancel') }}
            </Link>
            <button
                type="submit"
                :disabled="form.processing"
                class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90 disabled:opacity-50"
            >
                {{ form.processing ? $t('lab_orders.catalog.form.submitting') : $t('lab_orders.catalog.form.submit') }}
            </button>
        </div>
    </form>
</template>
