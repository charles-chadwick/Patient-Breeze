<!--suppress JSUnresolvedReference -->
<script setup>
import { computed } from "vue";
import AppointmentDetails from "./Details.vue";

const props = defineProps ( { appointments: Object | Array } )
const appointments = computed ( () => props.appointments.data );
</script>

<template>

  <ul
      role="list"
      class="divide-y divide-darker-200"
  >
    <li
        v-for="appointment in appointments"
        :key="appointment.id"
        class="flex justify-between gap-x-2 py-2"
    >
      <AppointmentDetails :appointment="appointment" />
    </li>
  </ul>
</template>

<style scoped>

</style>