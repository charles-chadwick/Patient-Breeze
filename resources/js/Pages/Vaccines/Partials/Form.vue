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
    vaccine: {
        type: Object,
        default: null,
    },
    cancelHref: {
        type: String,
        default: null,
    },
    formId: {
        type: String,
        default: 'vaccine-form',
    },
    showActions: {
        type: Boolean,
        default: true,
    },
})

const emit = defineEmits(['success'])

const form = useForm({
    name: props.vaccine?.name ?? '',
    cvx_code: props.vaccine?.cvx_code ?? '',
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
        <!-- Vaccine -->
        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('vaccines.catalog.form.label_name') }} <span class="text-vibrant-coral-500">*</span>
            </label>
            <input
                v-model="form.name"
                type="text"
                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                :class="{ 'border-vibrant-coral-400': form.errors.name }"
            />
            <p v-if="form.errors.name" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.name }}</p>
        </div>

        <!-- CVX code -->
        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('vaccines.catalog.form.label_cvx_code') }} <span class="text-vibrant-coral-500">*</span>
            </label>
            <input
                v-model="form.cvx_code"
                type="text"
                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                :class="{ 'border-vibrant-coral-400': form.errors.cvx_code }"
            />
            <p v-if="form.errors.cvx_code" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.cvx_code }}</p>
            <p v-else class="mt-1 text-xs text-muted-foreground">{{ $t('vaccines.catalog.form.cvx_code_hint') }}</p>
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
                {{ form.processing ? $t('vaccines.catalog.form.submitting') : $t('vaccines.catalog.form.submit') }}
            </button>
        </div>
    </form>
</template>
