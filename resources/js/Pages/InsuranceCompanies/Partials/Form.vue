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
    company: {
        type: Object,
        default: null,
    },
    cancelHref: {
        type: String,
        default: null,
    },
    formId: {
        type: String,
        default: 'insurance-company-form',
    },
    showActions: {
        type: Boolean,
        default: true,
    },
})

const emit = defineEmits(['success'])

const form = useForm({
    name: props.company?.name ?? '',
    payer_id: props.company?.payer_id ?? '',
    address_line1: props.company?.address_line1 ?? '',
    address_line2: props.company?.address_line2 ?? '',
    city: props.company?.city ?? '',
    state: props.company?.state ?? '',
    postal_code: props.company?.postal_code ?? '',
    phone: props.company?.phone ?? '',
    fax: props.company?.fax ?? '',
    website: props.company?.website ?? '',
    notes: props.company?.notes ?? '',
})

function submit() {
    form.transform((data) => ({
        ...data,
        payer_id: data.payer_id || null,
    }))[props.method](props.action, {
        preserveScroll: true,
        onSuccess: () => emit('success'),
    })
}

const input_class =
    'w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50'
const label_class = 'mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground'
</script>

<template>
    <form :id="formId" class="grid gap-5" @submit.prevent="submit">
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label :class="label_class">
                    {{ $t('insurance_companies.form.label_name') }} <span class="text-vibrant-coral-500">*</span>
                </label>
                <input v-model="form.name" type="text" :class="[input_class, { 'border-vibrant-coral-400': form.errors.name }]" />
                <p v-if="form.errors.name" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.name }}</p>
            </div>
            <div>
                <label :class="label_class">{{ $t('insurance_companies.form.label_payer_id') }}</label>
                <input v-model="form.payer_id" type="text" :class="[input_class, 'font-mono', { 'border-vibrant-coral-400': form.errors.payer_id }]" />
                <p v-if="form.errors.payer_id" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.payer_id }}</p>
                <p v-else class="mt-1 text-xs text-muted-foreground">{{ $t('insurance_companies.form.payer_id_hint') }}</p>
            </div>
        </div>

        <div>
            <p class="mb-3 text-xs font-bold uppercase tracking-wide text-foreground">
                {{ $t('insurance_companies.form.section_address') }}
            </p>
            <div class="grid gap-4">
                <div>
                    <label :class="label_class">{{ $t('insurance_companies.form.label_address_line1') }}</label>
                    <input v-model="form.address_line1" type="text" :class="[input_class, { 'border-vibrant-coral-400': form.errors.address_line1 }]" />
                    <p v-if="form.errors.address_line1" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.address_line1 }}</p>
                </div>
                <div>
                    <label :class="label_class">{{ $t('insurance_companies.form.label_address_line2') }}</label>
                    <input v-model="form.address_line2" type="text" :class="[input_class, { 'border-vibrant-coral-400': form.errors.address_line2 }]" />
                    <p v-if="form.errors.address_line2" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.address_line2 }}</p>
                </div>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="sm:col-span-2">
                        <label :class="label_class">{{ $t('insurance_companies.form.label_city') }}</label>
                        <input v-model="form.city" type="text" :class="[input_class, { 'border-vibrant-coral-400': form.errors.city }]" />
                        <p v-if="form.errors.city" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.city }}</p>
                    </div>
                    <div>
                        <label :class="label_class">{{ $t('insurance_companies.form.label_state') }}</label>
                        <input v-model="form.state" type="text" :class="[input_class, { 'border-vibrant-coral-400': form.errors.state }]" />
                        <p v-if="form.errors.state" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.state }}</p>
                    </div>
                    <div>
                        <label :class="label_class">{{ $t('insurance_companies.form.label_postal_code') }}</label>
                        <input v-model="form.postal_code" type="text" :class="[input_class, { 'border-vibrant-coral-400': form.errors.postal_code }]" />
                        <p v-if="form.errors.postal_code" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.postal_code }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <p class="mb-3 text-xs font-bold uppercase tracking-wide text-foreground">
                {{ $t('insurance_companies.form.section_contact') }}
            </p>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label :class="label_class">{{ $t('insurance_companies.form.label_phone') }}</label>
                    <input v-model="form.phone" type="text" :class="[input_class, { 'border-vibrant-coral-400': form.errors.phone }]" />
                    <p v-if="form.errors.phone" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.phone }}</p>
                </div>
                <div>
                    <label :class="label_class">{{ $t('insurance_companies.form.label_fax') }}</label>
                    <input v-model="form.fax" type="text" :class="[input_class, { 'border-vibrant-coral-400': form.errors.fax }]" />
                    <p v-if="form.errors.fax" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.fax }}</p>
                </div>
                <div class="sm:col-span-2">
                    <label :class="label_class">{{ $t('insurance_companies.form.label_website') }}</label>
                    <input v-model="form.website" type="text" :class="[input_class, { 'border-vibrant-coral-400': form.errors.website }]" />
                    <p v-if="form.errors.website" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.website }}</p>
                </div>
            </div>
        </div>

        <div>
            <label :class="label_class">{{ $t('insurance_companies.form.label_notes') }}</label>
            <textarea v-model="form.notes" rows="2" :class="[input_class, { 'border-vibrant-coral-400': form.errors.notes }]"></textarea>
            <p v-if="form.errors.notes" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.notes }}</p>
        </div>

        <div v-if="showActions" class="flex items-center justify-end gap-3">
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
                {{ form.processing ? $t('insurance_companies.form.submitting') : $t('insurance_companies.form.submit') }}
            </button>
        </div>
    </form>
</template>
