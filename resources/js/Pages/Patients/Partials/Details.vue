<!--suppress JSUnresolvedReference -->
<script setup>
import Avatar from "../../../components/Avatar.vue";
import Status from "../../../components/Status.vue";

const props = defineProps ( {
  patient: Object,
  compact: { type: Boolean, default: false },
  showAvatar: { type: Boolean, default: true },
} );
</script>

<template>
  <div class="flex justify-between items-start">
    <div>
      <h1
          :class="[
          'text-lg font-bold',
      ]"
      >
        <a
            :href="route('patients.chart', patient.attributes.id)"
            class="hover:underline hover:text-primary-600"
        >
          <span v-if="compact">
          {{ patient.attributes.full_name }}
            </span>
          <span v-else>
            {{ patient.attributes.first_name }} {{ patient.attributes.middle_name }} {{ patient.attributes.last_name }}
          </span>
        </a>
      </h1>
      <p>{{ patient.attributes.dob }} /
         {{ patient.attributes.age.years }} Years, {{ patient.attributes.age.months }} Months</p>
      <p>{{ patient.attributes.gender }} <span v-if="!compact"> / {{ patient.attributes.gender_identity }}</span></p>
      <p>Status:
        <Status
            :status="patient.attributes.status"
            type="patient"
        />
      </p>
      <slot name="details" />
    </div>
    <div v-if="showAvatar">
      <Avatar
          :avatar="patient.attributes.avatar"
          :description="patient.attributes.full_name + ' Avatar'"
          :show_large="!compact"
          :size="compact ? 'md' : 'lg'"
          :on="{ type: 'Patient', id: patient.attributes.id}"
      />
    </div>
  </div>
</template>

<style scoped>

</style>