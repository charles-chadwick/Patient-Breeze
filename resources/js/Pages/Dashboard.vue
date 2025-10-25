<script setup>
import AuthenticatedLayout from "./AuthenticatedLayout.vue";
import { Card } from 'primevue';
</script>

<template>
  <AuthenticatedLayout>
    <Card>
      <template #title>
        <h1>Dashboard</h1>
      </template>
      <template #content>
        hey kid, I'm a dashboard.
      </template>
    </Card>
  </AuthenticatedLayout>
</template>

<style scoped>

</style>