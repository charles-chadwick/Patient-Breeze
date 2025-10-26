<script setup>
import { computed } from "vue";

const props = defineProps ( { status: String, size: { type: String, default: 'normal' } } )
const status = computed ( () => props.status )
const size = computed ( () => props.size )

const severity = {
  Confirmed: 'fill-accent-400',
  Cancelled: 'fill-darker-500',
  Completed: 'fill-darker-200',
  Pending: 'fill-yellow-400',
  Rescheduled: 'fill-darker-500',
  'No Show': 'fill-red-400',
}

const sizes = {
  small: 'size-1.5',
  normal: 'size-3',
  large: 'size-5',
}

</script>

<template>
  <span class="inline-flex items-center gap-x-1.5 rounded-md py-1 text-sm font-medium inset-ring inset-ring-white/10">
    <svg :class="sizes[size] + ` ` + severity[status]" viewBox="0 0 6 6" aria-hidden="true">
      <circle cx="3" cy="3" r="3" />
    </svg>
    {{ status }}
  </span>
</template>

<style scoped>

</style>