<script setup>
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog'
import AppointmentForm from '@/Pages/Appointments/Partials/AppointmentForm.vue'

const props = defineProps({
    open: {
        type: Boolean,
        required: true,
    },
    patientId: {
        type: Number,
        required: true,
    },
    appointment: {
        type: Object,
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
})

const emit = defineEmits(['update:open', 'saved'])

const page = usePage()

const can_delete = computed(() => page.props.auth?.permissions?.includes('delete_appointments') ?? false)

function handleOpenUpdate(value) {
    emit('update:open', value)
}

function handleSuccess() {
    emit('saved')
    emit('update:open', false)
}

function handleDelete() {
    if (window.confirm(trans('appointments.form.delete_confirm'))) {
        router.delete(route('patients.appointments.destroy', [props.patientId, props.appointment.id]), {
            preserveScroll: true,
            onSuccess: handleSuccess,
        })
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="handleOpenUpdate">
        <DialogContent class="sm:max-w-3xl">
            <DialogHeader>
                <DialogTitle>{{ $t('appointments.form.edit_title') }}</DialogTitle>
                <DialogDescription>{{ $t('appointments.form.edit_hint') }}</DialogDescription>
            </DialogHeader>

            <AppointmentForm
                v-if="appointment"
                :key="appointment.id"
                :action="route('patients.appointments.update', [patientId, appointment.id])"
                method="put"
                :appointment="appointment"
                :status_options="status_options"
                :role_options="role_options"
                form-id="appointment-form"
                :show-actions="false"
                @success="handleSuccess"
            />

            <DialogFooter>
                <button
                    v-if="appointment && can_delete"
                    type="button"
                    @click="handleDelete"
                    class="mr-auto rounded-lg border border-vibrant-coral-300 px-4 py-2 text-sm font-bold text-vibrant-coral-600 hover:bg-vibrant-coral-50"
                >
                    {{ $t('appointments.form.delete') }}
                </button>
                <button
                    type="button"
                    @click="handleOpenUpdate(false)"
                    class="rounded-lg border border-border px-4 py-2 text-sm font-bold text-foreground hover:bg-muted/40"
                >
                    {{ $t('common.actions.cancel') }}
                </button>
                <button
                    type="submit"
                    form="appointment-form"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90"
                >
                    {{ $t('appointments.form.submit') }}
                </button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
