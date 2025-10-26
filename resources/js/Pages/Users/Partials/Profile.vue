<!--suppress JSUnresolvedReference -->
<script setup>
import { Link } from "@inertiajs/vue3";
import UserController from "../../../actions/App/Http/Controllers/UserController";

import { Dialog } from "primevue";
import { ref, computed } from "vue";

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
const showDialog = ref ( false )

const handleShowDialog = () => {
  if ( props.show_large_avatar ) {
    showDialog.value = true;
  }
}

const handleCloseDialog = () => {
  showDialog.value = false;
}

</script>

<template>
  <div
      v-if="user?.attributes"
      class="flex justify-center items-center gap-x-2"
  >
    <div v-if="show_name">
      <Link
          :href="UserController.profile(user.id)"
      >
        <h1 class="font-bold text-base">{{ user.attributes.full_name }}</h1>
        <p class="text-sm font-bold text-darker-400">{{ user.attributes.role }}</p>
      </Link>
    </div>
    <div v-if="show_avatar">
      <Link
          v-if="link_to_profile"
          :href="UserController.profile(user.id)"
      >
        <img
            @click="handleShowDialog"
            :src="user.attributes.avatar"
            alt="Avatar"
            v-tooltip.bottom="{value: user.attributes.full_name}"
            :class="[size, 'rounded-2xl border-darker-300 hover:border-primary-400 border-2 saturate-50 hover:saturate-100']"
        />
      </Link>
      <img
          v-else
          @click="handleShowDialog"
          :src="user.attributes.avatar"
          alt="Avatar"
          v-tooltip.bottom="{value: user.attributes.full_name}"
          :class="[size, 'rounded-2xl border-darker-300 hover:border-primary-400 border-2 saturate-50 hover:saturate-100']"
      />
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
          :src="user.attributes.avatar ?? '/avatars/default.jpg'"
          class="rounded-xl border-2 border-darker-300 hover:border-primary-400 "
          alt="Avatar"
      />
    </template>
  </Dialog>
</template>

<style scoped>

</style>