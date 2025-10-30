<!--suppress JSUnresolvedReference -->
<script setup>

import AuthenticatedLayout from "../AuthenticatedLayout.vue";
import AppointmentList from "../Appointments/AppointmentList.vue";
import Details from "./Partials/Details.vue";
import { Card } from "primevue";

const props = defineProps ( { patient: Object, appointments: Object | Array } )
const patient = props.patient.data;

</script>

<template>
  <AuthenticatedLayout>

    <!-- profile -->
    <Card>
      <template #content>
          <!-- start information -->
          <div>
            <Details :patient="patient">
              <template #details>
                <p class="text-sm">Created by: {{  patient.relationships.created_by.attributes.full_name }} on {{  patient.attributes.created_at }}</p>
              </template>
            </Details>
          </div>
      </template>
    </Card>

    <!-- Start appointments -->
    <Card class="mt-4">
      <template #title>Appointments</template>
      <template #content>
        <AppointmentList :appointments="appointments" />
      </template>
    </Card>
    <!-- End appointments -->

  </AuthenticatedLayout>


  <!-- dialogs and such -->


</template>

<style scoped>

</style>