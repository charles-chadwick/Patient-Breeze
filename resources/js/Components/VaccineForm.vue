<script setup>
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    action: {
        type: String,
        required: true,
    },
    statusOptions: {
        type: Array,
        required: true,
    },
    routeOptions: {
        type: Array,
        required: true,
    },
    siteOptions: {
        type: Array,
        required: true,
    },
    staffOptions: {
        type: Array,
        default: () => [],
    },
    initial: {
        type: Object,
        default: () => ({}),
    },
    formId: {
        type: String,
        default: 'vaccine-form',
    },
})

const emit = defineEmits(['success'])

const today = new Date().toISOString().slice(0, 10)

const form = useForm({
    vaccine: props.initial.name ?? '',
    cvx_code: props.initial.cvx_code ?? '',
    administered_on: today,
    dose_number: null,
    status: props.statusOptions[0] ?? '',
    route: props.routeOptions[0] ?? '',
    site: props.siteOptions[0] ?? '',
    dose_amount: '',
    manufacturer: '',
    lot_number: '',
    expires_on: '',
    administered_by: '',
    notes: '',
})

function submit() {
    form.transform((data) => ({
        ...data,
        // Blank selects and text fields are "not recorded", not empty strings.
        dose_number: data.dose_number || null,
        route: data.route || null,
        site: data.site || null,
        expires_on: data.expires_on || null,
        administered_by: data.administered_by || null,
    })).post(props.action, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset()
            emit('success')
        },
    })
}
</script>

<template>
    <form :id="formId" action="#" method="post" @submit.prevent="submit" class="grid max-h-[60vh] gap-5 overflow-y-auto px-1">
        <div class="grid grid-cols-3 gap-4">
            <div class="col-span-2">
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                    {{ $t('vaccines.form.label_vaccine') }} <span class="text-vibrant-coral-500">*</span>
                </label>
                <input
                    v-model="form.vaccine"
                    type="text"
                    class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                    :class="{ 'border-vibrant-coral-400': form.errors.vaccine }"
                />
                <p v-if="form.errors.vaccine" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.vaccine }}</p>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                    {{ $t('vaccines.form.label_cvx_code') }}
                </label>
                <input
                    v-model="form.cvx_code"
                    type="text"
                    class="w-full rounded-lg border border-border bg-background px-3 py-2 font-mono text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                    :class="{ 'border-vibrant-coral-400': form.errors.cvx_code }"
                />
                <p v-if="form.errors.cvx_code" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.cvx_code }}</p>
            </div>
        </div>

        <!-- Administration -->
        <div>
            <p class="mb-3 text-xs font-bold uppercase tracking-wide text-foreground">
                {{ $t('vaccines.form.section_administration') }}
            </p>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('vaccines.form.label_administered_on') }} <span class="text-vibrant-coral-500">*</span>
                    </label>
                    <input
                        v-model="form.administered_on"
                        type="date"
                        data-testid="vaccine-administered-on"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-vibrant-coral-400': form.errors.administered_on }"
                    />
                    <p v-if="form.errors.administered_on" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.administered_on }}</p>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('vaccines.form.label_status') }} <span class="text-vibrant-coral-500">*</span>
                    </label>
                    <select
                        v-model="form.status"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-vibrant-coral-400': form.errors.status }"
                    >
                        <option v-for="option in statusOptions" :key="option" :value="option">
                            {{ $t('enums.vaccine_status.' + option) }}
                        </option>
                    </select>
                    <p v-if="form.errors.status" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.status }}</p>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('vaccines.form.label_dose_number') }}
                    </label>
                    <input
                        v-model="form.dose_number"
                        type="number"
                        min="1"
                        max="10"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-vibrant-coral-400': form.errors.dose_number }"
                    />
                    <p v-if="form.errors.dose_number" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.dose_number }}</p>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-3 gap-4">
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('vaccines.form.label_route') }}
                    </label>
                    <select
                        v-model="form.route"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-vibrant-coral-400': form.errors.route }"
                    >
                        <option value="">{{ $t('vaccines.form.none_option') }}</option>
                        <option v-for="option in routeOptions" :key="option" :value="option">
                            {{ $t('enums.vaccine_route.' + option) }}
                        </option>
                    </select>
                    <p v-if="form.errors.route" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.route }}</p>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('vaccines.form.label_site') }}
                    </label>
                    <select
                        v-model="form.site"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-vibrant-coral-400': form.errors.site }"
                    >
                        <option value="">{{ $t('vaccines.form.none_option') }}</option>
                        <option v-for="option in siteOptions" :key="option" :value="option">
                            {{ $t('enums.vaccine_site.' + option) }}
                        </option>
                    </select>
                    <p v-if="form.errors.site" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.site }}</p>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('vaccines.form.label_dose_amount') }}
                    </label>
                    <input
                        v-model="form.dose_amount"
                        type="text"
                        :placeholder="$t('vaccines.form.dose_amount_placeholder')"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-vibrant-coral-400': form.errors.dose_amount }"
                    />
                    <p v-if="form.errors.dose_amount" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.dose_amount }}</p>
                </div>
            </div>

            <div class="mt-4">
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                    {{ $t('vaccines.form.label_administered_by') }}
                </label>
                <select
                    v-model="form.administered_by"
                    class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                    :class="{ 'border-vibrant-coral-400': form.errors.administered_by }"
                >
                    <option value="">{{ $t('vaccines.form.none_option') }}</option>
                    <option v-for="staff in staffOptions" :key="staff.id" :value="staff.id">
                        {{ staff.name }}
                    </option>
                </select>
                <p v-if="form.errors.administered_by" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.administered_by }}</p>
                <p v-else class="mt-1 text-xs text-muted-foreground">{{ $t('vaccines.form.administered_by_hint') }}</p>
            </div>
        </div>

        <!-- Lot & manufacturer -->
        <div>
            <p class="mb-3 text-xs font-bold uppercase tracking-wide text-foreground">
                {{ $t('vaccines.form.section_lot') }}
            </p>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('vaccines.form.label_manufacturer') }}
                    </label>
                    <input
                        v-model="form.manufacturer"
                        type="text"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-vibrant-coral-400': form.errors.manufacturer }"
                    />
                    <p v-if="form.errors.manufacturer" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.manufacturer }}</p>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('vaccines.form.label_lot_number') }}
                    </label>
                    <input
                        v-model="form.lot_number"
                        type="text"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 font-mono text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-vibrant-coral-400': form.errors.lot_number }"
                    />
                    <p v-if="form.errors.lot_number" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.lot_number }}</p>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('vaccines.form.label_expires_on') }}
                    </label>
                    <input
                        v-model="form.expires_on"
                        type="date"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-vibrant-coral-400': form.errors.expires_on }"
                    />
                    <p v-if="form.errors.expires_on" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.expires_on }}</p>
                </div>
            </div>
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('vaccines.form.label_notes') }}
            </label>
            <textarea
                v-model="form.notes"
                rows="2"
                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                :class="{ 'border-vibrant-coral-400': form.errors.notes }"
            ></textarea>
            <p v-if="form.errors.notes" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.notes }}</p>
        </div>
    </form>
</template>
