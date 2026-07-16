<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    action: {
        type: String,
        required: true,
    },
    positionOptions: {
        type: Array,
        default: () => [],
    },
    temperatureSiteOptions: {
        type: Array,
        default: () => [],
    },
    oxygenDeliveryOptions: {
        type: Array,
        default: () => [],
    },
    staffOptions: {
        type: Array,
        default: () => [],
    },
    formId: {
        type: String,
        default: 'vitals-form',
    },
})

const emit = defineEmits(['success'])

function nowLocal() {
    const now = new Date()
    const offset = now.getTimezoneOffset()
    const local = new Date(now.getTime() - offset * 60000)

    return local.toISOString().slice(0, 16)
}

const form = useForm({
    measured_at: nowLocal(),
    systolic: '',
    diastolic: '',
    position: '',
    heart_rate: '',
    respiratory_rate: '',
    temperature: '',
    temperature_site: '',
    oxygen_saturation: '',
    oxygen_delivery: '',
    weight: '',
    height: '',
    pain_score: '',
    recorded_by: '',
    notes: '',
})

// Live BMI preview so the clinician sees it before saving, mirroring the stored
// derived value (weight kg / (height m)^2).
const bmi_preview = computed(() => {
    const weight_kg = parseFloat(form.weight)
    const height_cm = parseFloat(form.height)

    if (!weight_kg || !height_cm) {
        return null
    }

    const metres = height_cm / 100

    return (weight_kg / (metres * metres)).toFixed(1)
})

function blankToNull(value) {
    return value === '' || value === null ? null : value
}

function submit() {
    form.transform((data) => ({
        ...data,
        // Empty inputs mean "not measured", not zero or empty string.
        systolic: blankToNull(data.systolic),
        diastolic: blankToNull(data.diastolic),
        position: blankToNull(data.position),
        heart_rate: blankToNull(data.heart_rate),
        respiratory_rate: blankToNull(data.respiratory_rate),
        temperature: blankToNull(data.temperature),
        temperature_site: blankToNull(data.temperature_site),
        oxygen_saturation: blankToNull(data.oxygen_saturation),
        oxygen_delivery: blankToNull(data.oxygen_delivery),
        weight: blankToNull(data.weight),
        height: blankToNull(data.height),
        pain_score: blankToNull(data.pain_score),
        recorded_by: blankToNull(data.recorded_by),
    })).post(props.action, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset()
            emit('success')
        },
    })
}

const input_class =
    'w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50'
const label_class = 'mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground'
</script>

<template>
    <form :id="formId" action="#" method="post" @submit.prevent="submit" class="grid max-h-[60vh] gap-5 overflow-y-auto px-1">
        <div>
            <label :class="label_class">
                {{ $t('vitals.form.label_measured_at') }} <span class="text-vibrant-coral-500">*</span>
            </label>
            <input
                v-model="form.measured_at"
                type="datetime-local"
                data-testid="vitals-measured-at"
                :class="[input_class, { 'border-vibrant-coral-400': form.errors.measured_at }]"
            />
            <p v-if="form.errors.measured_at" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.measured_at }}</p>
        </div>

        <!-- Blood pressure -->
        <div>
            <p class="mb-3 text-xs font-bold uppercase tracking-wide text-foreground">
                {{ $t('vitals.form.section_blood_pressure') }}
            </p>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label :class="label_class">{{ $t('vitals.form.label_systolic') }}</label>
                    <input
                        v-model="form.systolic"
                        type="number"
                        min="0"
                        max="300"
                        :placeholder="$t('vitals.units.mmhg')"
                        :class="[input_class, { 'border-vibrant-coral-400': form.errors.systolic }]"
                    />
                    <p v-if="form.errors.systolic" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.systolic }}</p>
                </div>
                <div>
                    <label :class="label_class">{{ $t('vitals.form.label_diastolic') }}</label>
                    <input
                        v-model="form.diastolic"
                        type="number"
                        min="0"
                        max="200"
                        :placeholder="$t('vitals.units.mmhg')"
                        :class="[input_class, { 'border-vibrant-coral-400': form.errors.diastolic }]"
                    />
                    <p v-if="form.errors.diastolic" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.diastolic }}</p>
                </div>
                <div>
                    <label :class="label_class">{{ $t('vitals.form.label_position') }}</label>
                    <select v-model="form.position" :class="input_class">
                        <option value="">{{ $t('vitals.form.none_option') }}</option>
                        <option v-for="option in positionOptions" :key="option" :value="option">
                            {{ $t('enums.body_position.' + option) }}
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Measurements -->
        <div>
            <p class="mb-3 text-xs font-bold uppercase tracking-wide text-foreground">
                {{ $t('vitals.form.section_measurements') }}
            </p>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label :class="label_class">{{ $t('vitals.form.label_heart_rate') }}</label>
                    <input
                        v-model="form.heart_rate"
                        type="number"
                        min="0"
                        max="400"
                        :placeholder="$t('vitals.units.bpm')"
                        :class="[input_class, { 'border-vibrant-coral-400': form.errors.heart_rate }]"
                    />
                    <p v-if="form.errors.heart_rate" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.heart_rate }}</p>
                </div>
                <div>
                    <label :class="label_class">{{ $t('vitals.form.label_respiratory_rate') }}</label>
                    <input
                        v-model="form.respiratory_rate"
                        type="number"
                        min="0"
                        max="120"
                        :placeholder="$t('vitals.units.breaths')"
                        :class="[input_class, { 'border-vibrant-coral-400': form.errors.respiratory_rate }]"
                    />
                    <p v-if="form.errors.respiratory_rate" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.respiratory_rate }}</p>
                </div>
                <div>
                    <label :class="label_class">{{ $t('vitals.form.label_oxygen_saturation') }}</label>
                    <input
                        v-model="form.oxygen_saturation"
                        type="number"
                        min="0"
                        max="100"
                        :placeholder="$t('vitals.units.percent')"
                        :class="[input_class, { 'border-vibrant-coral-400': form.errors.oxygen_saturation }]"
                    />
                    <p v-if="form.errors.oxygen_saturation" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.oxygen_saturation }}</p>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-3 gap-4">
                <div>
                    <label :class="label_class">{{ $t('vitals.form.label_temperature') }}</label>
                    <input
                        v-model="form.temperature"
                        type="number"
                        step="0.1"
                        min="20"
                        max="45"
                        :placeholder="$t('vitals.units.celsius')"
                        :class="[input_class, { 'border-vibrant-coral-400': form.errors.temperature }]"
                    />
                    <p v-if="form.errors.temperature" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.temperature }}</p>
                </div>
                <div>
                    <label :class="label_class">{{ $t('vitals.form.label_temperature_site') }}</label>
                    <select v-model="form.temperature_site" :class="input_class">
                        <option value="">{{ $t('vitals.form.none_option') }}</option>
                        <option v-for="option in temperatureSiteOptions" :key="option" :value="option">
                            {{ $t('enums.temperature_site.' + option) }}
                        </option>
                    </select>
                </div>
                <div>
                    <label :class="label_class">{{ $t('vitals.form.label_oxygen_delivery') }}</label>
                    <select v-model="form.oxygen_delivery" :class="input_class">
                        <option value="">{{ $t('vitals.form.none_option') }}</option>
                        <option v-for="option in oxygenDeliveryOptions" :key="option" :value="option">
                            {{ $t('enums.oxygen_delivery.' + option) }}
                        </option>
                    </select>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-3 gap-4">
                <div>
                    <label :class="label_class">{{ $t('vitals.form.label_weight') }}</label>
                    <input
                        v-model="form.weight"
                        type="number"
                        step="0.01"
                        min="0"
                        max="700"
                        :placeholder="$t('vitals.units.kg')"
                        :class="[input_class, { 'border-vibrant-coral-400': form.errors.weight }]"
                    />
                    <p v-if="form.errors.weight" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.weight }}</p>
                </div>
                <div>
                    <label :class="label_class">{{ $t('vitals.form.label_height') }}</label>
                    <input
                        v-model="form.height"
                        type="number"
                        step="0.01"
                        min="0"
                        max="300"
                        :placeholder="$t('vitals.units.cm')"
                        :class="[input_class, { 'border-vibrant-coral-400': form.errors.height }]"
                    />
                    <p v-if="form.errors.height" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.height }}</p>
                </div>
                <div>
                    <label :class="label_class">{{ $t('vitals.form.label_bmi') }}</label>
                    <div class="flex h-[38px] items-center rounded-lg border border-dashed border-border bg-muted/30 px-3 text-sm font-bold text-foreground">
                        {{ bmi_preview ?? $t('vitals.latest.no_reading') }}
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">{{ $t('vitals.form.bmi_hint') }}</p>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-3 gap-4">
                <div>
                    <label :class="label_class">{{ $t('vitals.form.label_pain_score') }}</label>
                    <input
                        v-model="form.pain_score"
                        type="number"
                        min="0"
                        max="10"
                        :class="[input_class, { 'border-vibrant-coral-400': form.errors.pain_score }]"
                    />
                    <p v-if="form.errors.pain_score" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.pain_score }}</p>
                </div>
                <div class="col-span-2">
                    <label :class="label_class">{{ $t('vitals.form.label_recorded_by') }}</label>
                    <select v-model="form.recorded_by" :class="input_class">
                        <option value="">{{ $t('vitals.form.none_option') }}</option>
                        <option v-for="staff in staffOptions" :key="staff.id" :value="staff.id">
                            {{ staff.name }}
                        </option>
                    </select>
                    <p v-if="form.errors.recorded_by" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.recorded_by }}</p>
                    <p v-else class="mt-1 text-xs text-muted-foreground">{{ $t('vitals.form.recorded_by_hint') }}</p>
                </div>
            </div>
        </div>

        <div>
            <label :class="label_class">{{ $t('vitals.form.label_notes') }}</label>
            <textarea
                v-model="form.notes"
                rows="2"
                :class="[input_class, { 'border-vibrant-coral-400': form.errors.notes }]"
            ></textarea>
            <p v-if="form.errors.notes" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.notes }}</p>
        </div>
    </form>
</template>
