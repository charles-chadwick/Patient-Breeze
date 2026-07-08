<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import DatePicker from '@/Components/ui/DatePicker.vue'
import AvatarUpload from '@/Components/AvatarUpload.vue'

const props = defineProps({
    action: {
        type: String,
        required: true,
    },
    method: {
        type: String,
        required: true,
    },
    patient: {
        type: Object,
        default: null,
    },
    cancelHref: {
        type: String,
        required: true,
    },
    gender_at_birth_options: {
        type: Array,
        required: true,
    },
    gender_identity_options: {
        type: Array,
        required: true,
    },
    blood_type_options: {
        type: Array,
        required: true,
    },
})

const form = useForm({
    prefix: props.patient?.prefix ?? '',
    first_name: props.patient?.first_name ?? '',
    middle_name: props.patient?.middle_name ?? '',
    last_name: props.patient?.last_name ?? '',
    suffix: props.patient?.suffix ?? '',
    email: props.patient?.email ?? '',
    date_of_birth: props.patient?.date_of_birth ?? '',
    gender_at_birth: props.patient?.gender_at_birth ?? '',
    gender_identity: props.patient?.gender_identity ?? '',
    blood_type: props.patient?.blood_type ?? '',
    avatar: null,
    remove_avatar: false,
})

function submit() {
    form[props.method](props.action)
}
</script>

<template>
    <form @submit.prevent="submit" class="grid gap-6">
        <!-- Avatar -->
        <div class="rounded-xl border border-border bg-white shadow-sm">
            <div class="border-b border-border px-6 py-4">
                <h2 class="font-bold text-foreground">{{ $t('patients.form.section_avatar') }}</h2>
            </div>
            <div class="px-6 py-5">
                <AvatarUpload
                    v-model="form.avatar"
                    v-model:removed="form.remove_avatar"
                    :current-url="patient?.avatar_url ?? null"
                    :error="form.errors.avatar"
                />
            </div>
        </div>

        <!-- Identity -->
        <div class="rounded-xl border border-border bg-white shadow-sm">
            <div class="border-b border-border px-6 py-4">
                <h2 class="font-bold text-foreground">{{ $t('patients.form.section_identity') }}</h2>
            </div>
            <div class="grid gap-5 px-6 py-5 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Prefix -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('patients.form.label_prefix') }}
                    </label>
                    <input
                        v-model="form.prefix"
                        type="text"
                        class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :placeholder="$t('patients.form.placeholder_prefix')"
                    />
                    <p v-if="form.errors.prefix" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.prefix }}</p>
                </div>

                <!-- First Name -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('patients.form.label_first_name') }} <span class="text-vibrant-coral-500">*</span>
                    </label>
                    <input
                        v-model="form.first_name"
                        type="text"
                        class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-vibrant-coral-400': form.errors.first_name }"
                        :placeholder="$t('patients.form.placeholder_first_name')"
                    />
                    <p v-if="form.errors.first_name" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.first_name }}</p>
                </div>

                <!-- Middle Name -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('patients.form.label_middle_name') }}
                    </label>
                    <input
                        v-model="form.middle_name"
                        type="text"
                        class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :placeholder="$t('patients.form.placeholder_middle_name')"
                    />
                </div>

                <!-- Last Name -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('patients.form.label_last_name') }} <span class="text-vibrant-coral-500">*</span>
                    </label>
                    <input
                        v-model="form.last_name"
                        type="text"
                        class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-vibrant-coral-400': form.errors.last_name }"
                        :placeholder="$t('patients.form.placeholder_last_name')"
                    />
                    <p v-if="form.errors.last_name" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.last_name }}</p>
                </div>

                <!-- Suffix -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('patients.form.label_suffix') }}
                    </label>
                    <input
                        v-model="form.suffix"
                        type="text"
                        class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :placeholder="$t('patients.form.placeholder_suffix')"
                    />
                </div>

                <!-- Email -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('patients.form.label_email') }} <span class="text-vibrant-coral-500">*</span>
                    </label>
                    <input
                        v-model="form.email"
                        type="email"
                        class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-vibrant-coral-400': form.errors.email }"
                        :placeholder="$t('patients.form.placeholder_email')"
                    />
                    <p v-if="form.errors.email" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.email }}</p>
                </div>
            </div>
        </div>

        <!-- Medical -->
        <div class="rounded-xl border border-border bg-white shadow-sm">
            <div class="border-b border-border px-6 py-4">
                <h2 class="font-bold text-foreground">{{ $t('patients.form.section_medical') }}</h2>
            </div>
            <div class="grid gap-5 px-6 py-5 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Date of Birth -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('patients.form.label_date_of_birth') }} <span class="text-vibrant-coral-500">*</span>
                    </label>
                    <DatePicker
                        v-model="form.date_of_birth"
                        :placeholder="$t('patients.form.placeholder_date_of_birth')"
                        :class="{ 'ring-2 ring-vibrant-coral-400 rounded-lg': form.errors.date_of_birth }"
                    />
                    <p v-if="form.errors.date_of_birth" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.date_of_birth }}</p>
                </div>

                <!-- Gender at Birth -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('patients.form.label_gender_at_birth') }} <span class="text-vibrant-coral-500">*</span>
                    </label>
                    <select
                        v-model="form.gender_at_birth"
                        class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-vibrant-coral-400': form.errors.gender_at_birth }"
                    >
                        <option value="">{{ $t('common.actions.select_placeholder') }}</option>
                        <option v-for="opt in gender_at_birth_options" :key="opt" :value="opt">
                            {{ $t('enums.gender_at_birth.' + opt) }}
                        </option>
                    </select>
                    <p v-if="form.errors.gender_at_birth" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.gender_at_birth }}</p>
                </div>

                <!-- Gender Identity -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('patients.form.label_gender_identity') }}
                    </label>
                    <select
                        v-model="form.gender_identity"
                        class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                    >
                        <option value="">{{ $t('common.actions.select_placeholder') }}</option>
                        <option v-for="opt in gender_identity_options" :key="opt" :value="opt">
                            {{ $t('enums.gender_identity.' + opt) }}
                        </option>
                    </select>
                </div>

                <!-- Blood Type -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('patients.form.label_blood_type') }}
                    </label>
                    <select
                        v-model="form.blood_type"
                        class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                    >
                        <option value="">{{ $t('common.placeholders.em_dash') }}</option>
                        <option v-for="opt in blood_type_options" :key="opt" :value="opt">
                            {{ $t('enums.blood_type.' + opt) }}
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3">
            <Link
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
                {{ form.processing ? $t('patients.form.submitting') : $t('patients.form.submit') }}
            </button>
        </div>
    </form>
</template>
