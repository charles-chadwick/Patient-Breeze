<script setup>
import { Badge } from 'primevue';
import { computed } from "vue";

const props = defineProps ( { status: String, size: { type: String, default: 'small' } } )
const status = computed ( () => props.status )

const severity = {
  Active: '',
  Inactive: 'secondary',
  Deceased: 'danger',
  Prospective: 'warn'
}

</script>

<template>
  <Badge
      :size="size"
      :severity="severity[status]"
  >
    {{ status }}
  </Badge>
</template>

<style scoped>

</style>