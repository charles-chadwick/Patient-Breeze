<!--suppress JSUnresolvedReference -->
<script setup>
import Profile from "../../Users/Partials/Profile.vue";
import Status from "./Status.vue";

const props = defineProps({appointment: Object})
</script>

<template>
  <div>
    <h2 class="font-bold">{{ appointment.attributes.title }}</h2>
    <div v-html="appointment.attributes.description" ></div>
    <div class="flex ml-4">
          <span class="-ml-2" v-for="user in appointment.relationships.users" :key="user.id">
            <Profile :show_name="false" :user="user" />
          </span>
    </div>
  </div>
  <div class="text-right text-sm">
    <p class="font-bold">{{ appointment.attributes.date }}</p>
    <p>{{ appointment.attributes.from }} - {{ appointment.attributes.to }}</p>
    <p>{{ appointment.attributes.type }}</p>
    <Status :status="appointment.attributes.status" />
  </div>
</template>

<style scoped>

</style>