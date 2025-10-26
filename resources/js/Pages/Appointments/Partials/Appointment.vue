<script setup>
import { computed } from "vue";

const props = defineProps({appointment: Object})
const appointment = computed(() => props.appointment.data)
</script>

<template>
  <div>
    <h1>{{ appointment.attributes.type }} | {{ appointment.attributes.title }}</h1>
    <p>{{ appointment.attributes.status }}</p>
    <p>{{ appointment.attributes.start }} to {{ appointment.attributes.end }}</p>
  </div>

</template>

<style scoped>

</style>