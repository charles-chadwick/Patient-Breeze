<script setup>
import { useForm } from "@inertiajs/vue3";
import PatientController from "../../../actions/App/Http/Controllers/PatientController.ts";
import {InputText, Select, Password} from 'primevue';

const props = defineProps ( {
  patient: Object,
  statuses: Object | Array,
  genders: Object | Array,
  action: {
    type: String,
    validator: ( value ) => [ 'create', 'update' ].includes ( value ),
    required: true
  },

} );

const patient = props.patient;

const save = () => {

  if ( props.action === "create" ) {
    PatientController.create ( patient );
  } else if ( props.action === "update" ) {
    PatientController.update ( patient );
  }

}

const form = useForm ( {
  id: patient?.id,
  first_name: patient?.first_name,
  middle_name: patient?.middle_name,
  last_name: patient?.last_name,
  email: patient?.email,
  gender: patient?.gender,
  gender_identity: patient?.gender_identity,
  dob: patient?.dob,
  password: null,
  password_confirmation: null
} )
</script>

<template>
  <form
      @submit.prevent="save"
  >
    <div class="space-y-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
      <!-- first name -->
      <div class="field">
        <label class="label">First Name</label>
        <InputText
            v-model="form.first_name"
            placeholder="John"
            class="w-full"
        />
        <Message
            severity="error"
            v-if="form.errors.first_name"
        >{{ form.errors.first_name }}
        </Message>
      </div>
      <!-- middle name -->
      <div class="field">
        <label class="label">Middle Name</label>
        <InputText
            v-model="form.middle_name"
            placeholder="Ben"
            class="w-full"
        />
        <Message
            severity="error"
            v-if="form.errors.middle_name"
        >{{ form.errors.middle_name }}
        </Message>
      </div>
      <!-- last name -->
      <div class="field">
        <label class="label">Last Name</label>
        <InputText
            v-model="form.last_name"
            placeholder="Doe"
            class="w-full"
        />
        <Message
            severity="error"
            v-if="form.errors.last_name"
        >{{ form.errors.last_name }}
        </Message>
      </div>
    </div>

    <div class="space-y-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
      <!-- status -->
      <div class="field">
        <label class="label">Status</label>

        <Select
            v-model="form.status"
            :options="statuses"
            optionLabel="name"
            placeholder="Select a Status"
            class="w-full"
        />
        <Message
            severity="error"
            v-if="form.errors.status"
        >{{ form.errors.status }}
        </Message>
      </div>
      <!-- last name -->
      <div class="field">
        <label class="label">Email</label>
        <InputText
            v-model="form.email"
            placeholder="john.doe@exmple.com"
            class="w-full"
        />
        <Message
            severity="error"
            v-if="form.errors.email"
        >{{ form.errors.email }}
        </Message>
      </div>

      <!-- gender -->
      <div class="field">
        <label class="label">Gender</label>

        <Select
            v-model="form.gender"
            :options="genders"
            optionLabel="name"
            placeholder="Select a Gender"
            class="w-full"
        />
        <Message
            severity="error"
            v-if="form.errors.role"
        >{{ form.errors.role }}
        </Message>
      </div>

      <!-- gender identity -->
      <div class="field">
        <label class="label">Gender Identity</label>

        <InputText
            v-model="form.gender_identity"
            placeholder="Select a Role"
            class="w-full"
        />
        <Message
            severity="error"
            v-if="form.errors.gender_identity"
        >{{ form.errors.gender_identity }}
        </Message>
      </div>

      <!-- password -->
      <div class="field">
        <label class="label">Password</label>
        <InputText
            type="password"
            v-model="form.password"
            class="w-full"
        />
        <Message
            severity="error"
            v-if="form.errors.password"
        >{{ form.errors.password }}
        </Message>
      </div>

      <!-- password confirmation -->
      <div class="field">
        <label class="label">Confirm Password</label>
        <InputText
            type="password"
            v-model="form.password_confirmation"
            class="w-full"
        />
        <Message
            severity="error"
            v-if="form.errors.password_confirmation"
        >{{ form.errors.password_confirmation }}
        </Message>
      </div>
    </div>
      <div class="col-span-3 flex justify-center">
        <Button
            class="btn"
            type="submit"
            label="Create Patient"
            :loading="form.processing"
        >Save Patient</Button>
      </div>

  </form>
</template>

<style scoped>

</style>