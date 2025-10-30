<!--suppress JSUnresolvedReference -->
<script setup>
import AuthenticatedLayout from "../AuthenticatedLayout.vue";
import Pagination from "../../components/Pagination.vue";
import PatientController from "../../actions/App/Http/Controllers/PatientController";
import { Card } from 'primevue';
import Search from "../../components/Search.vue";
import PatientDetails from "./Partials/Details.vue";

defineProps ( { patients: Array | Object })

</script>

<template>
  <AuthenticatedLayout>
    <Card>
      <template #title>Patients</template>
      <template #subtitle class="flex justify-between items-center">
        <Search :url="PatientController.index().url"/>
      </template>
      <template #content>
        <ul
            role="list"
            class="divide-y divide-darker-200"
        >
          <li
              v-for="patient in patients.data"
              :key="patient.id"
              class="gap-x-2 py-2"
          >
            <PatientDetails :compact="true" :patient="patient" />
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