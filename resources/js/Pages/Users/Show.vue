<!--suppress JSUnresolvedReference -->
<script setup>
  import Layout from '../Layout.vue'
  import { Card } from 'primevue';

  defineProps({user: Object})

</script>

<template>
  <Layout>
    <Card>
      <template #title>{{ user.data.attributes.full_name }}</template>
    </Card>

  </Layout>
</template>
