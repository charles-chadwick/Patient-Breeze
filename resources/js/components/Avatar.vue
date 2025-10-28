<script setup>
import { useForm } from "@inertiajs/vue3";
import { Dialog, FileUpload } from "primevue";
import { ref } from "vue";

const props = defineProps ( { avatar: String, on: Object } )
const on = props.on;
const avatar = props.avatar;

const form = useForm ( {
  on_type: on.type,
  on_id: on.id,
} )

const uploadAvatar = () => {
  form.post ( '/avatar/upload' )
}

const removeAvatar = () => {
  form.post ( '/avatar/remove' )
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
  <div>
    <div v-if="avatar">
      <img
          @click="handleShowDialog"
          alt="Avatar"
          class="rounded-2xl size-[128px] mx-auto"
          :src="avatar"
      />
      <Button
          @click="removeAvatar"
          class="flex-none cursor-pointer"
      >
        Remove This Avatar
      </Button>
    </div>
    <div v-else>
      <form @submit.prevent="uploadAvatar">
        <FileUpload
            ref="fileupload"
            name="avatar"
            url="/avatar/upload"
            accept="image/*"
            :maxFileSize="1000000"
            :withCredentials="true"
            @input="form.avatar = $event.target.files[0]"
        />
        <progress
            v-if="form.progress"
            :value="form.progress.percentage"
            max="100"
        >
          {{ form.progress.percentage }}%
        </progress>

      </form>

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
          :src="avatar === '' ?? '/avatars/default.jpg'"
          class="rounded-xl border-2 border-darker-300 hover:border-primary-600"
          alt="Avatar"
      />

    </template>
  </Dialog>
</template>

<style scoped>

</style>