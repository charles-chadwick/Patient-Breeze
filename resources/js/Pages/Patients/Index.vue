<!--suppress JSUnresolvedReference -->
<script setup>
import AuthenticatedLayout from "../AuthenticatedLayout.vue";
import Pagination from "../../components/Pagination.vue";
import { Card, InputText } from 'primevue';
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import PatientDetails from "./Partials/Details.vue";

defineProps ( { patients: Array | Object } )

const search = ref ( '' )

watch ( search, ( value ) => {
  router.get (
      '/patients',
      { search: value },
      {
        preserveState: true,
        preserveScroll: true,
        replace: true,
      }
  )
} )
</script>

<template>
  <AuthenticatedLayout>
    <Card>
      <template #title>Patients</template>
      <template #subtitle>
        <InputText
            v-model="search"
            placeholder="Search users..."
            class="w-full"
        />
      </template>
      <template #content>
        <ul
            role="list"
            class="divide-y divide-darker-200"
        >
          <li
              v-for="patient in patients.data"
              :key="patient.id"
              class="flex justify-between gap-x-2 py-2"
          >
            <PatientDetails :user="patient" :compact="true" />
          </li>
        </ul>
      </template>
      <template #footer>
        <Pagination :pagination="patients.meta" />
      </template>
    </Card>
  </AuthenticatedLayout>
</template>

<style scoped>

</style>