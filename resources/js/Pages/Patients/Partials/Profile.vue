<!--suppress JSUnresolvedReference -->
<script setup>
import { Link } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import PatientController from "../../../actions/App/Http/Controllers/PatientController";

const props = defineProps ( {
  patient: Object,
  show_avatar: { type: Boolean, default: true },
  show_large_avatar: { type: Boolean, default: true },
  show_name: { type: Boolean, default: true },
} )

const patient = computed ( () => props.patient )
const showDialog = ref ( false )

const handleShowDialog = () => {
  showDialog.value = true;
}

const handleCloseDialog = () => {
  showDialog.value = false;
}

</script>

<template>
  <div v-if="patient?.attributes" class="flex justify-center items-center gap-x-2">
    <div v-if="show_name">
      <Link
          :href="PatientController.chart(patient.id)">
        <h1 class="font-bold text-base">{{ patient.attributes.full_name }}</h1>
        <p class="text-sm font-bold text-darker-400">{{ patient.attributes.role }}</p>
      </Link>
    </div>
    <div v-if="show_avatar">
      <img
          @click="handleShowDialog"
          :src="patient.attributes.avatar"
          alt="Avatar"
          class="w-12 h-12 rounded-lg border-primary-300 border-2"
      >
    </div>
  </div>
  <Dialog
      modal
      :dismissableMask="true"
      v-model:visible="showDialog"
  >
    <template #container>
      <img
          @click="handleCloseDialog"
          :src="patient.data.attributes.avatar ?? '/avatars/default.jpg'"
          class="rounded-xl border-2 border-darker-300 hover:border-primary-600"
          alt="Avatar"
      />
    </template>
  </Dialog>
</template>

<style scoped>

</style>