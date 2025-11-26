<!--suppress JSUnresolvedReference -->
<script setup>
import { ref } from "vue";
import Status from "../../../components/Status.vue";
import Avatar from "../../../components/Avatar.vue";
import {Dialog} from "primevue";
import Form from "../Form.vue";
const props = defineProps ( { appointment: Object } )
const dialog = ref ( false );
</script>

<template>
  <div>
    <h2
        class="font-bold cursor-pointer"
        @click="dialog = true"
    >{{ appointment.attributes.title }}
    </h2>
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
  <Dialog
      :key="appointment.id"
      v-model:visible="dialog"
      header="Appointment Details"
  >
<Form :appointment="appointment" />
  </Dialog>
</template>
