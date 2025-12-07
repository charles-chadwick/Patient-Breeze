<!--suppress JSUnresolvedReference -->
<script setup>
import { ref } from 'vue'
import { useForm } from "@inertiajs/vue3";
import { DatePicker, Select, InputText, Button, Message, AutoComplete } from 'primevue'
import Editor from 'primevue/editor';
import AuthenticatedLayout from "../AuthenticatedLayout.vue";
import PatientDetails from "../Patients/Partials/Details.vue";

const props = defineProps ( {
  action: {
    type: String,
    default: 'create',
    validator: ( value ) => [ 'create', 'update' ].includes ( value )
  },
  appointment: Object,
  patient: Object,
  statuses: Array,
  users: Array
} )

const selectedUsers = ref ( [] )
const filteredUsers = ref ( [] )
const patient = props.patient.data
const appointment = props.appointment.data

const searchUsers = ( event ) => {
  const query = event.query.toLowerCase ()
  filteredUsers.value = props.users.filter ( user =>
      user.label.toLowerCase ().includes ( query )
  )
}
console.log(props.users)

const form = useForm ( {
  patient_id: patient.attributes.id || '',
  type: appointment?.attributes?.type || '',
  start: appointment?.attributes?.start || null,
  end: appointment?.attributes?.end || null,
  status: appointment?.attributes?.status || '',
  title: appointment?.attributes?.title || '',
  description: appointment?.attributes?.description || '',
  user_ids: appointment?.relationships?.users?.data?.map ( user => user.attributes.id ) || []
} )

const submit = () => {
  if ( props.appointment.id ) {
    form.put ( `/appointments/${ props.appointment.id }` )
  } else {
    form.post ( '/appointments' )
  }
}

</script>
<template>
  <AuthenticatedLayout>
    <form
        @submit.prevent="submit"
        class="space-y-6"
    >
      <div class="flex gap-6">

        <!-- Left column: Patient -->
        <div class="flex-none w-1/3">
          <label class="block text-sm font-medium text-darker-700">Patient</label>
          <PatientDetails
              :patient="patient"
              :compact="true"
              :show-actions="false"
          />
          <Message
              severity="error"
              v-if="form.errors.patient_id"
          >{{ form.errors.patient_id }}
          </Message>
        </div>

        <!-- Right column -->
        <div class="flex-1 space-y-6">
          <!-- First row: Type, Title, Status -->
          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-darker-700">Type</label>
              <InputText
                  v-model="form.type"
                  type="text"
                  class="w-full"
                  :class="{ 'p-invalid': form.errors.type }"
              />
              <Message
                  severity="error"
                  v-if="form.errors.type"
              >{{ form.errors.type }}
              </Message>
            </div>

            <div>
              <label class="block text-sm font-medium text-darker-700">Title</label>
              <InputText
                  v-model="form.title"
                  type="text"
                  class="w-full"
                  :class="{ 'p-invalid': form.errors.title }"
              />
              <Message
                  severity="error"
                  v-if="form.errors.title"
              >{{ form.errors.title }}
              </Message>
            </div>

            <div>
              <label class="block text-sm font-medium text-darker-700">Status</label>
              <Select
                  v-model="form.status"
                  :options="statuses"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Select status"
                  fluid
                  :class="{ 'p-invalid': form.errors.status }"
              />
              <Message
                  severity="error"
                  v-if="form.errors.status"
              >{{ form.errors.status }}
              </Message>
            </div>
          </div>

          <!-- Second row: Start and End dates -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-darker-700">Start Date & Time</label>
              <DatePicker
                  v-model="form.start"
                  showTime
                  hourFormat="24"
                  fluid
                  :class="{ 'p-invalid': form.errors.start }"
              />
              <Message
                  severity="error"
                  v-if="form.errors.start"
              >{{ form.errors.start }}
              </Message>
            </div>

            <div>
              <label class="block text-sm font-medium text-darker-700">End Date & Time</label>
              <DatePicker
                  v-model="form.end"
                  showTime
                  hourFormat="24"
                  fluid
                  :class="{ 'p-invalid': form.errors.end }"
              />
              <Message
                  severity="error"
                  v-if="form.errors.end"
              >{{ form.errors.end }}
              </Message>
            </div>
          </div>
        </div>
      </div>

      <!-- description -->
      <div>
        <label class="block text-sm font-medium text-darker-700">Description</label>
        <Editor
            v-model="form.description"
            class="w-full h-"
            :class="{ 'p-invalid': form.errors.description }"
        />
        <Message
            severity="error"
            v-if="form.errors.description"
        >{{ form.errors.description }}
        </Message>
      </div>

      <!-- users -->
      <div>
        <label class="block text-sm font-medium text-darker-700">Select Users</label>
        <AutoComplete
            v-model="selectedUsers"
            :suggestions="filteredUsers"
            @complete="searchUsers"
            optionLabel="label"
            placeholder="Search users"
            :multiple="true"
            class="w-full"
            @item-select="(e) => form.user_ids = [...new Set([...form.user_ids, e.value.value])]"
            @item-unselect="(e) => form.user_ids = form.user_ids.filter(id => id !== e.value.value)"
        />
        <Message
            severity="error"
            v-if="form.errors.user_ids"
        >{{ form.errors.user_ids }}
        </Message>
      </div>


      <div class="flex justify-center space-x-3">
        <Button
            type="submit"
            :loading="form.processing"
            :disabled="form.processing"
            label="Save Appointment"
        />
      </div>
    </form>
  </AuthenticatedLayout>
</template>