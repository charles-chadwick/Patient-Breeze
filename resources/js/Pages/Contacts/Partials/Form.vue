<script setup>
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    action: {
        type: String,
        required: true,
    },
    method: {
        type: String,
        required: true,
        validator: (v) => ['post', 'patch', 'put'].includes(v),
    },
    contact: {
        type: Object,
        default: null,
    },
    types: {
        type: Array,
        required: true,
    },
    contactableType: {
        type: String,
        default: null,
    },
    contactableId: {
        type: [Number, String],
        default: null,
    },
})

const emit = defineEmits(['success'])

const form = useForm({
    name: props.contact?.name ?? '',
    type: props.contact?.type ?? '',
    phone: props.contact?.phone ?? '',
    street_address: props.contact?.street_address ?? '',
    roi: props.contact?.roi ?? false,
    contactable_type: props.contactableType ?? '',
    contactable_id: props.contactableId ?? '',
})

function submit() {
    form[props.method](props.action, {
        preserveScroll: true,
        onSuccess: () => emit('success'),
    })
}
</script>

<template>
    <form id="contact-form" action="#" method="post" @submit.prevent="submit" class="grid gap-5">
        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('contacts.form.label_name') }} <span class="text-vibrant-coral-500">*</span>
            </label>
            <input
                v-model="form.name"
                type="text"
                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                :class="{ 'border-vibrant-coral-400': form.errors.name }"
                :placeholder="$t('contacts.form.placeholder_name')"
            />
            <p v-if="form.errors.name" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.name }}</p>
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('contacts.form.label_type') }} <span class="text-vibrant-coral-500">*</span>
            </label>
            <select
                v-model="form.type"
                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                :class="{ 'border-vibrant-coral-400': form.errors.type }"
            >
                <option value="">{{ $t('common.actions.select_placeholder') }}</option>
                <option v-for="opt in types" :key="opt" :value="opt">{{ $t('enums.contact_type.' + opt) }}</option>
            </select>
            <p v-if="form.errors.type" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.type }}</p>
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('contacts.form.label_phone') }}
            </label>
            <input
                v-model="form.phone"
                type="text"
                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                :class="{ 'border-vibrant-coral-400': form.errors.phone }"
                :placeholder="$t('contacts.form.placeholder_phone')"
            />
            <p v-if="form.errors.phone" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.phone }}</p>
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('contacts.form.label_street_address') }}
            </label>
            <input
                v-model="form.street_address"
                type="text"
                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                :class="{ 'border-vibrant-coral-400': form.errors.street_address }"
                :placeholder="$t('contacts.form.placeholder_street_address')"
            />
            <p v-if="form.errors.street_address" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.street_address }}</p>
        </div>

        <div>
            <label class="flex items-center gap-2">
                <input
                    v-model="form.roi"
                    type="checkbox"
                    class="size-4 rounded border-border text-primary focus:ring-primary/50"
                />
                <span class="text-sm font-bold text-foreground">{{ $t('contacts.form.label_roi') }}</span>
            </label>
            <p v-if="form.errors.roi" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.roi }}</p>
        </div>
    </form>
</template>
