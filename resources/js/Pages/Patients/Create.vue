<script setup>
import PatientForm from "./Partials/Form.vue";
import AuthenticatedLayout from "../AuthenticatedLayout.vue";
import {Card } from 'primevue';
defineProps ( { user: Object, genders: Object | Array, statuses: Object | Array } )
</script>

<template>
  <AuthenticatedLayout>
    <Card>
      <template #content>
      <PatientForm
          action="create"
          :user="user"
          :genders="genders"
          :statuses="statuses"
      />
      </template>
    </Card>
  </AuthenticatedLayout>

</template>