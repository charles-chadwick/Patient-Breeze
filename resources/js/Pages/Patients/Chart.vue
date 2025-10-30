<!--suppress JSUnresolvedReference -->
<script setup>
import { ref } from "vue";

import AuthenticatedLayout from "../AuthenticatedLayout.vue";
import AppointmentList from "../Appointments/AppointmentList.vue";
import Status from "./Partials/Status.vue";
import Avatar from "../../components/Avatar.vue";
import { Card } from "primevue";

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
          <div>
            <h1 class="p-card-title">{{ patient.attributes.full_name }}'s Profile</h1>

            <p><span class="font-bold">Name:</span>
              {{ patient.attributes.first_name }}
              {{ patient.attributes.middle_name }}
              {{ patient.attributes.last_name }}</p>

            <p><span class="font-bold">DOB:</span>
              {{ patient.attributes.dob }} - {{ patient.attributes.age.years }} Years {{  patient.attributes.age.months }} Months</p>
            <p><span class="font-bold">Gender:</span> {{ patient.attributes.gender }}
              <span v-if="patient.attributes.gender_identity !== ''"> / {{ patient.attributes.gender_identity }}</span>
            </p>

            <p><span class="font-bold">Status:</span>
              <Status :status="patient.attributes.status" class="ml-2" />
            </p>

            <p><span class="font-bold">Created:</span> {{ patient.attributes.created_at }}</p>
          </div>

          <!-- avatar information -->
          <Avatar
              :avatar="patient.attributes.avatar"
              :on="{ type: 'Patient', id: patient.id}"
          />
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