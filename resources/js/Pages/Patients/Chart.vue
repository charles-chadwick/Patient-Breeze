<!--suppress JSUnresolvedReference -->
<script setup>
import { ref } from "vue";
import { Link, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from "../AuthenticatedLayout.vue";
import AppointmentList from "../Appointments/AppointmentList.vue";
import Status from "./Partials/Status.vue";
import { Card, FileUpload, Dialog } from "primevue";
import PatientController from '../../actions/App/Http/Controllers/PatientController';

const props = defineProps ( { patient: Object, appointments: Object|Array } )

const patient = props.patient.data;

const form = useForm ( {
  avatar: null
} )

function uploadAvatar () {
  form.post ( PatientController.uploadAvatar ( props.patient.id ) )
}

const showDialog = ref ( false )

const handleShowDialog = () => {
  showDialog.value = true;
}

const handleCloseDialog = () => {
  showDialog.value = false;
}
</script>

<template>
  <AuthenticatedLayout>

    <!-- profile -->
    <Card>
      <template #content>
        <div class="flex justify-between items-start">
          <div>
            <h1 class="p-card-title">{{ patient.attributes.full_name }}'s Profile</h1>
            <p><span class="font-bold">Name:</span> {{ patient.attributes.first_name }} {{ patient.attributes.middle_name}} {{ patient.attributes.last_name }}</p>
            <p><span class="font-bold">DOB:</span> {{ patient.attributes.dob }}</p>
            <p><span class="font-bold">Gender:</span> {{ patient.attributes.gender }}
              <span v-if="patient.attributes.gender_identity !== ''">
                / {{ patient.attributes.gender_identity }}
              </span>
            </p>
            <p><span class="font-bold">Status:</span> <Status :status="patient.attributes.status" /></p>
            <p><span class="font-bold">Created:</span> {{ patient.attributes.created_at }}</p>
          </div>
          <div>
            <div v-if="patient.attributes.avatar">
              <img
                  @click="handleShowDialog"
                  alt="Avatar"
                  class="rounded-2xl size-[128px] mx-auto"
                  :src="patient.attributes.avatar"
              />
              <Link
                  :href="PatientController.removeAvatar('patients.avatar.remove', patient.id)"
                  method="post"
                  as="p"
                  class="flex-none cursor-pointer"
              >
                Remove This Avatar
              </Link>
            </div>
            <div v-else>
              <form @submit.prevent="uploadAvatar">
                <FileUpload
                    type="file"
                    @input="form.avatar = $event.target.files[0]"
                />
                <progress
                    v-if="form.progress"
                    :value="form.progress.percentage"
                    max="100"
                >
                  {{ form.progress.percentage }}%
                </progress>
                <Button
                    type="submit"
                    class="btn"
                >Save Avatar
                </Button>
              </form>

            </div>
          </div>
        </div>
      </template>
    </Card>

    <Card class="mt-4">
      <template #title>Appointments</template>
      <template #content>
        <AppointmentList :appointments="appointments" />
      </template>
    </Card>

  </AuthenticatedLayout>


  <!-- dialogs and such -->
  <Dialog
      modal
      :dismissableMask="true"
      v-model:visible="showDialog"
  >
    <template #container>
      <img
          @click="handleCloseDialog"
          :src="patient.attributes.avatar ?? '/avatars/default.jpg'"
          class="rounded-xl border-2 border-darker-300 hover:border-primary-600"
          alt="Avatar"
      />

    </template>
  </Dialog>

</template>

<style scoped>

</style>