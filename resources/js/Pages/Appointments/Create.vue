<script setup>
import Form from "./Partials/Form.vue";
import { ref } from "vue";
import AuthenticatedLayout from "../AuthenticatedLayout.vue";
import { DatePicker, Select, InputText, Textarea, Button, Message } from 'primevue'

defineEmits ( [ 'close' ] )
import { useForm } from '@inertiajs/vue3'

const props = defineProps ( {
  action: {
    type: String,
    default: 'create',
    validator: ( value ) => [ 'create', 'update' ].includes ( value )
  },
  appointment: {
    type: Object,
    default: () => ( {} )
  },
  patient: {
    type: Object
  },
  statuses: {
    type: Array,
    default: () => []
  }
} )


const form = useForm ( {
  patient: props.patient.data || '',
  type: props.appointment.type || '',
  start: props.appointment.start || null,
  end: props.appointment.end || null,
  status: props.appointment.status || '',
  title: props.appointment.title || '',
  description: props.appointment.description || ''
} )

const submit = () => {
  if ( props.appointment.id ) {
    form.put ( '/appointments/update', props.appointment.id )
  } else {
    form.post ('/appointments/store')
  }
}
const visible = ref ( false )
</script>

<template>
  <AuthenticatedLayout>
    <form
        @submit.prevent="submit"
        class="space-y-6"
    >
      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
          <label class="block text-sm font-medium text-darker-700">Patient</label>
          For: {{ form.patient.attributes.full_name }}
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
          <label class="block text-sm font-medium text-darker-700">Start Date & Time</label>
          <DatePicker
              v-model="form.start"
              showTime
              hourFormat="24"
              class="w-full"
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
              class="w-full"
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
              optionLabel="name"
              optionValue="value"
              placeholder="Select status"
              class="w-full"
              :class="{ 'p-invalid': form.errors.status }"
          />
          <Message
              severity="error"
              v-if="form.errors.status"
          >{{ form.errors.status }}
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

        <div class="sm:col-span-2">
          <label class="block text-sm font-medium text-darker-700">Description</label>
          <Textarea
              v-model="form.description"
              rows="4"
              class="w-full"
              :class="{ 'p-invalid': form.errors.description }"
          />
          <Message
              severity="error"
              v-if="form.errors.description"
          >{{ form.errors.description }}
          </Message>
        </div>
      </div>

      <div class="flex justify-end space-x-3">
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
