<script setup>
import { computed } from "vue";

const props = defineProps ( { status: String, size: { type: String, default: 'normal' } } )
const status = computed ( () => props.status )
const size = computed ( () => props.size )

const severity = {
  Active: 'fill-accent-400',
  Inactive: 'fill-darker-200',
  Deceased: 'fill-red-400',
  Prospective: 'fill-yellow-400',
}

const sizes = {
  small: 'size-1.5',
  normal: 'size-3',
  large: 'size-5',
}

</script>

<template>
  <span class="inline-flex items-center gap-x-1 rounded-md text-sm font-medium inset-ring inset-ring-white/10">
    <svg :class="sizes[size] + ` ` + severity[status]" viewBox="0 0 6 6" aria-hidden="true">
      <circle cx="3" cy="3" r="3" />
    </svg>
    <span class="-mt-0.5">
    {{ status }}
      </span>
  </span>
</template>

<style scoped>

</style>