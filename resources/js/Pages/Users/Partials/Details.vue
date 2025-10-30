<!--suppress JSUnresolvedReference -->
<script setup>

import { computed } from "vue";

const props = defineProps ( {
  user: Object,
  display_mode: {
    type: String,
    default: 'full_name',
    validator: value => [ 'full_name', 'initials' ].includes ( value )
  },
  show_role: { type: Boolean, default: true },
} )

const user = computed ( () => props.user )

</script>

<template>
  <div class="flex justify-between items-start">
    <div class="text-sm">
      <p>{{ display_mode === 'full_name' ? user.attributes.full_name : user.attributes.initials }}</p>
      <p v-if="show_role">{{ user.attributes.role }}</p>
    </div>
  </div>
</template>

<style scoped>

</style>