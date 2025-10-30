<!--suppress JSUnresolvedReference -->
<script setup>
import AuthenticatedLayout from "../AuthenticatedLayout.vue";
import Pagination from "../../components/Pagination.vue";
import PatientController from "../../actions/App/Http/Controllers/PatientController";
import { Card } from 'primevue';
import { Link } from "@inertiajs/vue3";
import Status from "./Partials/Status.vue";
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

defineProps ( { patients: Array | Object })

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
        <input
            v-model="search"
            type="text"
            placeholder="Search patients..."
            class="w-full rounded-md border-0 py-3 px-2.5 text-sm ring-1 ring-inset ring-darker-300 placeholder:text-darker-400 focus:ring-2 focus:ring-inset focus:ring-accent-600"
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