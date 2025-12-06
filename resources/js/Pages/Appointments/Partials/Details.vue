<!--suppress JSUnresolvedReference -->
<script setup>
import { ref } from "vue";
import Status from "../../../components/Status.vue";
import Avatar from "../../../components/Avatar.vue";

import { Card } from "primevue";
import { Link } from "@inertiajs/vue3";

const props = defineProps ( { appointment: Object, patient: Object } )
const dialog = ref ( false );
</script>

<template>

        <div>
          <Link
              :href="route('appointments.edit', appointment.id)"
              class="hover:underline hover:text-primary-600"
          >
            <h2
                class="font-bold cursor-pointer"
                @click="dialog = true"
            >{{ appointment.attributes.title }}
            </h2>
          </Link>
          <div v-html="appointment.attributes.description"></div>
          <div class="flex ml-4">
          <span
              class="-ml-2"
              v-for="user in appointment.relationships.users"
              :key="user.id"
          >
      <Avatar
          :avatar="user.attributes.avatar"
          size="sm"
          :on="{ type: 'User', id: user.id}"
      />          </span>
          </div>
        </div>
        <div class="text-right text-sm">
          <p class="font-bold">{{ appointment.attributes.date }}</p>
          <p>{{ appointment.attributes.from }} - {{ appointment.attributes.to }}</p>
          <p>{{ appointment.attributes.type }}</p>
          <Status
              :status="appointment.attributes.status"
              type="appointment"
          />
        </div>

</template>
