<!--suppress JSUnresolvedReference -->
<script setup>

import { computed } from "vue";
import Avatar from "../../../components/Avatar.vue";

const props = defineProps ( {
  user: Object,
  show_avatar: { type: Boolean, default: true },
  avatar_size: { type: String, default: 'md' },
  show_large_avatar: { type: Boolean, default: false },
  show_name: { type: Boolean, default: true },
  link_to_profile: true
} )

const size = computed ( () => {
  const sizes = {
    'xs': 'w-6 h-6',
    'sm': 'w-8 h-8',
    'md': 'w-12 h-12',
    'lg': 'w-16 h-16',
    'xl': 'w-20 h-20'
  }
  return sizes[ props.avatar_size ] || sizes[ 'md' ]
} );

const user = computed ( () => props.user )
</script>

<template>
  <div class="flex justify-between items-start">
    <div>
      <h1 class="p-card-title">{{ user.attributes.full_name }}'s Profile</h1>
      <p><span class="font-bold">Name:</span> {{ user.attributes.full_name }}</p>
      <p><span class="font-bold">Role:</span> {{ user.attributes.role }}</p>
      <p><span class="font-bold">Created:</span> {{ user.attributes.created_at }}</p>
    </div>
    <Avatar :avatar="user.attributes.avatar" :show_large_avatar="true" size="lg" />
  </div>
</template>

<style scoped>

</style>