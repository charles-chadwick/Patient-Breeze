<!--suppress JSUnresolvedReference -->
<script setup>
import { computed } from "vue";
import Details from "./Partials/Details.vue";
import Create from "./Create.vue";

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
      <Details :appointment="appointment" />
    </li>
  </ul>
</template>

<style scoped>

</style>