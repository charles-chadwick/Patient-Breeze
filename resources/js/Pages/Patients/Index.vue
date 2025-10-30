<!--suppress JSUnresolvedReference -->
<script setup>
import AuthenticatedLayout from "../AuthenticatedLayout.vue";
import Pagination from "../../components/Pagination.vue";
import PatientController from "../../actions/App/Http/Controllers/PatientController";
import { Card } from 'primevue';
import { Link } from "@inertiajs/vue3";
import Status from "./Partials/Status.vue";
import Search from "../../components/Search.vue";

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
              class="flex justify-between gap-x-2 py-2"
          >
            <div class="w-full">
              <h2 class="font-bold">
                <Link :href="PatientController.chart(patient.id)">
                  {{ patient.attributes.full_name }}
                </Link>
              </h2>
              <p>{{ patient.attributes.dob }} - {{ patient.attributes.age.years }} Years {{  patient.attributes.age.months }} Months</p>
              <p><Status :status="patient.attributes.status" /></p>
            </div>
            <div class="shrink-0">
              <img
                  :src="patient.attributes.avatar"
                  :alt="patient.attributes.full_name + ' avatar'"
                  :title="patient.attributes.full_name + ' avatar'"
                  class="w-16 h-16 rounded-xl"
              />
            </div>
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