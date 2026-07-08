<script setup>
defineProps({
    status: {
        type: String,
        required: true,
    },
})

const status_classes = {
    Scheduled: 'bg-cerulean-100 text-cerulean-700',
    Confirmed: 'bg-tropical-teal-100 text-tropical-teal-700',
    Completed: 'bg-gray-100 text-gray-600',
    Cancelled: 'bg-vibrant-coral-100 text-vibrant-coral-700',
    Rescheduled: 'bg-light-yellow-100 text-light-yellow-700',
    NoShow: 'bg-soft-apricot-100 text-soft-apricot-700',
}
</script>

<template>
    <span
        class="rounded-full px-2.5 py-0.5 text-xs font-bold"
        :class="status_classes[status] ?? 'bg-gray-100 text-gray-600'"
    >
        {{ $t('enums.appointment_status.' + status) }}
    </span>
</template>
