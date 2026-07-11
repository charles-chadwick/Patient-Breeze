<script setup>
import { computed } from 'vue'
import { Link, router, useForm, usePage } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import DatePicker from '@/Components/ui/DatePicker.vue'
import TimePicker from '@/Components/ui/TimePicker.vue'
import StaffSelect from '@/Components/StaffSelect.vue'

const props = defineProps({
    action: {
        type: String,
        required: true,
    },
    method: {
        type: String,
        required: true,
    },
    appointment: {
        type: Object,
        default: null,
    },
    cancelHref: {
        type: String,
        default: null,
    },
    status_options: {
        type: Array,
        required: true,
    },
    role_options: {
        type: Array,
        required: true,
    },
    formId: {
        type: String,
        default: 'appointment-form',
    },
    showActions: {
        type: Boolean,
        default: true,
    },
    deleteAction: {
        type: String,
        default: null,
    },
})

const emit = defineEmits(['success'])

const page = usePage()

const can_delete = computed(() => page.props.auth?.permissions?.includes('delete_appointments') ?? false)

function destroy() {
    if (window.confirm(trans('appointments.form.delete_confirm'))) {
        router.delete(props.deleteAction, {
            preserveScroll: true,
            onSuccess: () => emit('success'),
        })
    }
}

const form = useForm({
    date: props.appointment?.date?.substring(0, 10) ?? '',
    start_time: props.appointment?.start_time?.substring(0, 5) ?? '',
    end_time: props.appointment?.end_time?.substring(0, 5) ?? '',
    status: props.appointment?.status ?? '',
    reason: props.appointment?.reason ?? '',
    notes: props.appointment?.notes ?? '',
    staff: props.appointment?.users?.map((u) => ({
        user_id: u.id,
        role: u.pivot.role,
    })) ?? [],
})

// Seed the staff picker's display rows from the appointment's assigned providers.
const initial_staff = props.appointment?.users?.map((u) => ({
    id: u.id,
    first_name: u.first_name,
    last_name: u.last_name,
    avatar_url: u.avatar_url,
    role: u.pivot.role,
})) ?? []

function submit() {
    form[props.method](props.action, {
        preserveScroll: true,
        onSuccess: () => emit('success'),
    })
}
</script>

<template>
    <form :id="formId" @submit.prevent="submit" class="grid gap-6">
        <!-- Scheduling -->
        <div class="rounded-xl border border-border bg-card shadow-sm">
            <div class="border-b border-border px-6 py-4">
                <h2 class="font-bold text-foreground">{{ $t('appointments.form.section_scheduling') }}</h2>
            </div>
            <div class="grid gap-5 px-6 py-5 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Date -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('appointments.form.label_date') }} <span class="text-vibrant-coral-500">*</span>
                    </label>
                    <DatePicker
                        v-model="form.date"
                        :placeholder="$t('appointments.form.placeholder_date')"
                        :class="{ 'ring-2 ring-vibrant-coral-400 rounded-lg': form.errors.date }"
                    />
                    <p v-if="form.errors.date" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.date }}</p>
                </div>

                <!-- Start Time -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('appointments.form.label_start_time') }} <span class="text-vibrant-coral-500">*</span>
                    </label>
                    <TimePicker
                        v-model="form.start_time"
                        :placeholder="$t('appointments.form.placeholder_start_time')"
                        :class="{ 'ring-2 ring-vibrant-coral-400 rounded-lg': form.errors.start_time }"
                    />
                    <p v-if="form.errors.start_time" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.start_time }}</p>
                </div>

                <!-- End Time -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('appointments.form.label_end_time') }} <span class="text-vibrant-coral-500">*</span>
                    </label>
                    <TimePicker
                        v-model="form.end_time"
                        :placeholder="$t('appointments.form.placeholder_end_time')"
                        :class="{ 'ring-2 ring-vibrant-coral-400 rounded-lg': form.errors.end_time }"
                    />
                    <p v-if="form.errors.end_time" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.end_time }}</p>
                </div>

                <!-- Status -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('appointments.form.label_status') }} <span class="text-vibrant-coral-500">*</span>
                    </label>
                    <select
                        v-model="form.status"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-vibrant-coral-400': form.errors.status }"
                    >
                        <option value="">{{ $t('common.actions.select_placeholder') }}</option>
                        <option v-for="opt in status_options" :key="opt" :value="opt">{{ $t('enums.appointment_status.' + opt) }}</option>
                    </select>
                    <p v-if="form.errors.status" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.status }}</p>
                </div>
            </div>
        </div>

        <!-- Details -->
        <div class="rounded-xl border border-border bg-card shadow-sm">
            <div class="border-b border-border px-6 py-4">
                <h2 class="font-bold text-foreground">{{ $t('appointments.form.section_details') }}</h2>
            </div>
            <div class="grid gap-5 px-6 py-5">
                <!-- Reason -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('appointments.form.label_reason') }} <span class="text-vibrant-coral-500">*</span>
                    </label>
                    <input
                        v-model="form.reason"
                        type="text"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-vibrant-coral-400': form.errors.reason }"
                        :placeholder="$t('appointments.form.placeholder_reason')"
                    />
                    <p v-if="form.errors.reason" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.reason }}</p>
                </div>

                <!-- Notes -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('appointments.form.label_notes') }}
                    </label>
                    <textarea
                        v-model="form.notes"
                        rows="3"
                        data-testid="appointment-notes-input"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-vibrant-coral-400': form.errors.notes }"
                        :placeholder="$t('appointments.form.placeholder_notes')"
                    />
                    <p v-if="form.errors.notes" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.notes }}</p>
                </div>
            </div>
        </div>

        <!-- Staff -->
        <div class="rounded-xl border border-border bg-card shadow-sm">
            <div class="border-b border-border px-6 py-4">
                <h2 class="font-bold text-foreground">{{ $t('appointments.form.section_staff') }}</h2>
            </div>
            <div class="grid gap-4 px-6 py-5">
                <StaffSelect
                    v-model="form.staff"
                    :initial-staff="initial_staff"
                    :role-options="role_options"
                    :placeholder="$t('appointments.form.placeholder_staff')"
                />

                <div
                    v-if="form.errors.staff"
                    class="rounded-lg border border-vibrant-coral-200 bg-vibrant-coral-50 px-4 py-3 text-sm text-vibrant-coral-700"
                >
                    {{ form.errors.staff }}
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div v-if="showActions" class="flex items-center justify-end gap-3">
            <button
                v-if="deleteAction && can_delete"
                type="button"
                @click="destroy"
                class="mr-auto rounded-lg border border-vibrant-coral-300 px-4 py-2 text-sm font-bold text-vibrant-coral-600 hover:bg-vibrant-coral-50"
            >
                {{ $t('appointments.form.delete') }}
            </button>
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
                {{ form.processing ? $t('appointments.form.submitting') : $t('appointments.form.submit') }}
            </button>
        </div>
    </form>
</template>
