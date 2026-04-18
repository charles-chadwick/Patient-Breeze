<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import DatePicker from '@/Components/ui/DatePicker.vue'

let _uid = 0

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
        required: true,
    },
    status_options: {
        type: Array,
        required: true,
    },
    role_options: {
        type: Array,
        required: true,
    },
    staff_options: {
        type: Array,
        required: true,
    },
})

const form = useForm({
    date: props.appointment?.date?.substring(0, 10) ?? '',
    start_time: props.appointment?.start_time?.substring(0, 5) ?? '',
    end_time: props.appointment?.end_time?.substring(0, 5) ?? '',
    status: props.appointment?.status ?? '',
    reason: props.appointment?.reason ?? '',
    notes: props.appointment?.notes ?? '',
    staff: props.appointment?.users?.map((u) => ({
        _key: ++_uid,
        user_id: u.id,
        role: u.pivot.role,
    })) ?? [{ _key: ++_uid, user_id: '', role: 'Assistant' }],
})

function addStaff() {
    form.staff.push({ _key: ++_uid, user_id: '', role: 'Assistant' })
}

function removeStaff(index) {
    form.staff.splice(index, 1)
}

function submit() {
    form[props.method](props.action)
}
</script>

<template>
    <form @submit.prevent="submit" class="grid gap-6">
        <!-- Scheduling -->
        <div class="rounded-xl border border-border bg-white shadow-sm">
            <div class="border-b border-border px-6 py-4">
                <h2 class="font-bold text-foreground">Scheduling</h2>
            </div>
            <div class="grid gap-5 px-6 py-5 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Date -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        Date <span class="text-red-500">*</span>
                    </label>
                    <DatePicker
                        v-model="form.date"
                        placeholder="Select date"
                        :class="{ 'ring-2 ring-red-400 rounded-lg': form.errors.date }"
                    />
                    <p v-if="form.errors.date" class="mt-1 text-xs text-red-600">{{ form.errors.date }}</p>
                </div>

                <!-- Start Time -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        Start Time <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.start_time"
                        type="time"
                        class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-red-400': form.errors.start_time }"
                    />
                    <p v-if="form.errors.start_time" class="mt-1 text-xs text-red-600">{{ form.errors.start_time }}</p>
                </div>

                <!-- End Time -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        End Time <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.end_time"
                        type="time"
                        class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-red-400': form.errors.end_time }"
                    />
                    <p v-if="form.errors.end_time" class="mt-1 text-xs text-red-600">{{ form.errors.end_time }}</p>
                </div>

                <!-- Status -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        Status <span class="text-red-500">*</span>
                    </label>
                    <select
                        v-model="form.status"
                        class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-red-400': form.errors.status }"
                    >
                        <option value="">Select…</option>
                        <option v-for="opt in status_options" :key="opt" :value="opt">{{ opt }}</option>
                    </select>
                    <p v-if="form.errors.status" class="mt-1 text-xs text-red-600">{{ form.errors.status }}</p>
                </div>
            </div>
        </div>

        <!-- Details -->
        <div class="rounded-xl border border-border bg-white shadow-sm">
            <div class="border-b border-border px-6 py-4">
                <h2 class="font-bold text-foreground">Details</h2>
            </div>
            <div class="grid gap-5 px-6 py-5">
                <!-- Reason -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        Reason <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.reason"
                        type="text"
                        class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-red-400': form.errors.reason }"
                        placeholder="Reason for visit"
                    />
                    <p v-if="form.errors.reason" class="mt-1 text-xs text-red-600">{{ form.errors.reason }}</p>
                </div>

                <!-- Notes -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        Notes
                    </label>
                    <textarea
                        v-model="form.notes"
                        rows="3"
                        class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-red-400': form.errors.notes }"
                        placeholder="Optional notes…"
                    />
                    <p v-if="form.errors.notes" class="mt-1 text-xs text-red-600">{{ form.errors.notes }}</p>
                </div>
            </div>
        </div>

        <!-- Staff -->
        <div class="rounded-xl border border-border bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-border px-6 py-4">
                <h2 class="font-bold text-foreground">Staff</h2>
                <button
                    type="button"
                    class="rounded-lg border border-border px-3 py-1.5 text-xs font-bold text-foreground hover:bg-muted/40"
                    @click="addStaff"
                >
                    + Add Staff
                </button>
            </div>
            <div class="grid gap-3 px-6 py-5">
                <div
                    v-for="(entry, index) in form.staff"
                    :key="entry._key"
                    class="flex items-center gap-3"
                >
                    <select
                        v-model="entry.user_id"
                        class="flex-1 rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                    >
                        <option value="">Select staff…</option>
                        <option v-for="opt in staff_options" :key="opt.id" :value="opt.id">
                            {{ opt.last_name }}, {{ opt.first_name }}
                        </option>
                    </select>
                    <select
                        v-model="entry.role"
                        class="w-36 rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                    >
                        <option v-for="role in role_options" :key="role" :value="role">{{ role }}</option>
                    </select>
                    <button
                        v-if="form.staff.length > 1"
                        type="button"
                        class="shrink-0 rounded-lg border border-red-200 px-3 py-2 text-xs font-bold text-red-600 hover:bg-red-50"
                        @click="removeStaff(index)"
                    >
                        Remove
                    </button>
                </div>

                <!-- Conflict / staff error banner -->
                <div
                    v-if="form.errors.staff"
                    class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                >
                    {{ form.errors.staff }}
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3">
            <Link
                :href="cancelHref"
                class="rounded-lg border border-border px-4 py-2 text-sm font-bold text-foreground hover:bg-muted/40"
            >
                Cancel
            </Link>
            <button
                type="submit"
                :disabled="form.processing"
                class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90 disabled:opacity-50"
            >
                {{ form.processing ? 'Saving…' : 'Save Appointment' }}
            </button>
        </div>
    </form>
</template>
