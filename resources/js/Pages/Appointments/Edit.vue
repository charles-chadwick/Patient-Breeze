<script setup>
import Form from "./Partials/Form.vue";

defineProps({appointment: Object})
</script>

<template>
  <Form :appointment="appointment" action-="edit" />
</template>
