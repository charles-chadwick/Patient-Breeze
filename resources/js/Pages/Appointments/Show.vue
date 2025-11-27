<script setup>
  import AuthenticatedLayout from "../AuthenticatedLayout.vue";
  import AppointmentDetails from "./Partials/Details.vue";

  defineProps({appointment: Object})
</script>

<template>
  <AuthenticatedLayout>
    <AppointmentDetails :appointment="appointment" />
  </AuthenticatedLayout>
</template>
