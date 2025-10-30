<!--suppress JSUnresolvedReference -->
<script setup>
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";
import PatientController from "../../../actions/App/Http/Controllers/PatientController";

const props = defineProps ( {
  patient: Object,
  show_avatar: { type: Boolean, default: true },
  show_large_avatar: { type: Boolean, default: true },
  show_name: { type: Boolean, default: true },
} )

const patient = computed ( () => props.patient )

</script>

<template>
  <div
      v-if="patient?.attributes"
      class="flex justify-center items-center gap-x-2"
  >
    <div v-if="show_name">
      <Link
          :href="PatientController.chart(patient.id)"
      >
        <h1 class="font-bold text-base">{{ patient.attributes.full_name }}</h1>
      </Link>
      <p class="text-sm font-bold text-darker-400">
        {{ patient.attributes.dob }} -
        {{ patient.attributes.age.years }} Years
        {{ patient.attributes.age.months }} Months
      </p>

    </div>
    <div v-if="show_avatar">
      <Avatar
          :avatar="patient.attributes.avatar"
          :show_large_avatar="show_large_avatar"
          size="md"
      />
    </div>
  </div>
</template>

<style scoped>

</style>