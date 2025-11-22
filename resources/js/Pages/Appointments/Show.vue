<script setup>
  import AuthenticatedLayout from "../AuthenticatedLayout.vue";

  defineProps({appointment: Object})
</script>

<template>
  <AuthenticatedLayout>
    {{  appointment }}
  </AuthenticatedLayout>
</template>
