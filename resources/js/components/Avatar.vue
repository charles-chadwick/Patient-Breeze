<script setup>
import { useForm } from "@inertiajs/vue3";
import { Dialog } from "primevue";
import { FileUpload } from "primevue";
import { ref } from "vue";

const props = defineProps ( { avatar: String, on: Object } )
const on = props.on;
let avatar = props.avatar;

const form = useForm ( {
  on_type: on.type,
  on_id: on.id,
  avatar: null,
} )

const uploadAvatar = () => {
  form.post ( '/avatar/upload' )
}

const removeAvatar = () => {
  form.post ( '/avatar/remove' )
  avatar = null;
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
          class="rounded-2xl size-[128px] mx-auto border-2 border-darker-300 hover:border-primary-600"
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
      <FileUpload
          :maxFileSize="2000000"
          accept="image/*"
          :customUpload="true"
          @uploader="uploadAvatar"
          @select="(e) => form.avatar = e.files[0]"
      >
        <template #empty>
          <p>Drag and drop image here to upload.</p>
        </template>
      </FileUpload>
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