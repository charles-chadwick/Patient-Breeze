<script setup>
import Layout from "./Layout.vue";
import { Card } from 'primevue';
</script>

<template>
  <Layout>
    <Card>
      <template #title>
        <h1>Dashboard</h1>
      </template>
      <template #content>
        hey kid, I'm a dashboard.
      </template>
    </Card>
  </Layout>
</template>

<style scoped>

</style>