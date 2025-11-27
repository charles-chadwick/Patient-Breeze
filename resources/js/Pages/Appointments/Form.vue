<!--suppress JSUnresolvedReference -->
<script setup>
import { useForm } from "@inertiajs/vue3";
import { DatePicker, Select, InputText, Button, Message, Textarea, AutoComplete } from 'primevue'
import { ref } from 'vue'
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
  users: Object | Array
} )

const selectedUsers = ref ( [] )
const filteredUsers = ref ( [] )
const patient = props.patient.data

const searchUsers = ( event ) => {
  const query = event.query.toLowerCase ()
  filteredUsers.value = props.users.filter ( user =>
      user.label.toLowerCase ().includes ( query )
  )
}

const form = useForm ( {
  patient_id: props.patient.data.id || '',
  type: props.appointment?.attributes?.type || '',
  start: props.appointment?.attributes?.start || null,
  end: props.appointment?.attributes?.end || null,
  status: props.appointment?.attributes?.status || '',
  title: props.appointment?.attributes?.title || '',
  description: props.appointment?.attributes?.description || '',
  user_ids: props.appointment?.relationships?.users?.data?.map ( user => user.attributes.id ) || []
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
      <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
        <div>
          <label class="block text-sm font-medium text-darker-700">Patient</label>
          <PatientDetails :patient="patient.data" :compact="true" :show-actions="false" />
          <Message
              severity="error"
              v-if="form.errors.patient_id"
          >{{ form.errors.patient_id }}
          </Message>
        </div>

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

        <div class="sm:col-span-3">
          <label class="block text-sm font-medium text-darker-700">Description</label>
          <Textarea
              v-model="form.description"
              class="w-full"
              rows="6"
              :class="{ 'p-invalid': form.errors.description }"
          />
          <Message
              severity="error"
              v-if="form.errors.description"
          >{{ form.errors.description }}
          </Message>
        </div>

        <div class="sm:col-span-3">
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