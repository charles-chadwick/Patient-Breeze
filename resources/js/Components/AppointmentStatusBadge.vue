<script setup>
defineProps({
    status: {
        type: String,
        required: true,
    },
})

const status_classes = {
    Scheduled: 'bg-blue-100 text-blue-700',
    Confirmed: 'bg-green-100 text-green-700',
    Completed: 'bg-gray-100 text-gray-600',
    Cancelled: 'bg-red-100 text-red-700',
    Rescheduled: 'bg-yellow-100 text-yellow-700',
    NoShow: 'bg-orange-100 text-orange-700',
}
</script>

<template>
    <span
        class="rounded-full px-2.5 py-0.5 text-xs font-bold"
        :class="status_classes[status] ?? 'bg-gray-100 text-gray-600'"
    >
        {{ status }}
    </span>
</template>
