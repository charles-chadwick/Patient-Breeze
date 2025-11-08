<!--suppress JSUnresolvedReference -->
<script setup>

import AuthenticatedLayout from "../AuthenticatedLayout.vue";
import AppointmentList from "../Appointments/AppointmentList.vue";
import Avatar from "../../components/Avatar.vue";
import { Card } from "primevue";
import Details from "./Partials/Details.vue";

const props = defineProps ( { patient: Object, appointments: Object | Array } )
const patient = props.patient.data;

</script>

<template>
  <AuthenticatedLayout>

    <!-- profile -->
    <Card>
      <template #content>
        <div class="flex justify-between items-start">

          <!-- start information -->
          <Details :patient="patient" :show_avatar="true" />

          <!-- avatar information -->
        </div>
      </template>
    </Card>

    <!-- Start appointments -->
    <Card class="mt-4">
      <template #title>Appointments</template>
      <template #subtitle>
        <a
            :href="`/appointments/create?patient_id=` + patient.attributes.id "
            class="text-primary-500 hover:text-primary-600"
        >Create Appointment</a>
      </template>
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