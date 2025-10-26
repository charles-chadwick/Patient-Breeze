<script setup>
import { computed } from "vue";

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
      <div>
      <h2 class="font-bold">{{ appointment.attributes.title }}</h2>
        <p>
          {{ appointment.attributes.type }}
        </p>
      </div>
      <div>
        {{ appointment.attributes.start }}
      </div>
    </li>
  </ul>
</template>

<style scoped>

</style>