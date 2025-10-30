<!--suppress JSUnresolvedReference -->
<script setup>
import { computed } from "vue";

import UserDetails from "../Users/Partials/Details.vue";

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
        <h2 class="font-bold text-primary-800">{{ appointment.attributes.title }}</h2>
        <div v-html="appointment.attributes.description"></div>
        <div class="flex">
          <span
              class="pr-2"
              v-for="user in appointment.relationships.users"
              :key="user.id"
          >
            <UserDetails
                :user="user"
                :show_role="false"
                display_mode="initials"
            />
          </span>
        </div>
      </div>
      <div class="text-right text-sm">
        <p class="font-bold">{{ appointment.attributes.date }}</p>
        <p>{{ appointment.attributes.from }} - {{ appointment.attributes.to }}</p>
        <p>{{ appointment.attributes.type }}</p>
      </div>
    </li>
  </ul>
</template>

<style scoped>

</style>