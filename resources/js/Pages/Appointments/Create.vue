<script setup>
import Form from "./Partials/Form.vue";
import { Dialog, Button } from "primevue";
import { ref } from "vue";
import AuthenticatedLayout from "../AuthenticatedLayout.vue";

const props = defineProps ( { appointment: Object, statuses: Array } )
defineEmits ( [ 'close' ] )
console.log(props.statuses)

const visible = ref ( false )
</script>

<template>
  <AuthenticatedLayout>
  <Button @click="visible = true">Create Appt</Button>
  <Dialog
      v-model:visible="visible"
      modal
      :style="{ width: '50rem' }"
      :breakpoints="{ '960px': '75vw', '641px': '90vw' }"
      :header="props.appointment.id ? 'Edit Appointment' : 'Create Appointment'"
  >
    <Form :appointment="props.appointment" :statuses="statuses" />
  </Dialog>
  </AuthenticatedLayout>
</template>
