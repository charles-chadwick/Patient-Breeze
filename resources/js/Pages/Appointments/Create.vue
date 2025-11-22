<script setup>
import Form from "./Partials/Form.vue";
import AuthenticatedLayout from "../AuthenticatedLayout.vue";

const props = defineProps ( { appointment: Object, patient: Object, statuses: Array } )
</script>

<template>
  <AuthenticatedLayout>
    <Form
        action-="create"
        :appointment="appointment"
        :patient="patient"
        :statuses="statuses"
    />
  </AuthenticatedLayout>
</template>
