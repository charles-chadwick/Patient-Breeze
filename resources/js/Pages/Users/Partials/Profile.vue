<!--suppress JSUnresolvedReference -->
<script setup>
import { Link } from "@inertiajs/vue3";
import UserController from "../../../actions/App/Http/Controllers/UserController";
import { ref, computed } from "vue";

const props = defineProps ( {
  user: Object,
  show_avatar: { type: Boolean, default: true },
  show_large_avatar: { type: Boolean, default: true },
  show_name: { type: Boolean, default: true },
} )

const user = computed ( () => props.user )
const showDialog = ref ( false )

const handleShowDialog = () => {
  showDialog.value = true;
}

const handleCloseDialog = () => {
  showDialog.value = false;
}

</script>

<template>
  <div v-if="user?.attributes" class="flex justify-center items-start gap-x-2">
    <div v-if="show_name">
      <Link
          :href="UserController.profile(user.id)">
        <h1 class="font-bold ">{{ user.attributes.full_name }}</h1>
        <p class="text-sm font-bold">{{ user.attributes.role }}</p>
      </Link>
    </div>
    <div v-if="show_avatar">
      <img
          @click="handleShowDialog"
          :src="user.attributes.avatar"
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
          :src="user.data.attributes.avatar ?? '/avatars/default.jpg'"
          class="rounded-xl border-2 border-darker-300 hover:border-primary-600"
          alt="Avatar"
      />

    </template>
  </Dialog>
</template>

<style scoped>

</style>