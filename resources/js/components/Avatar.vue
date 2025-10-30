<script setup>
import { Dialog } from "primevue";
import { ref } from "vue";

const props = defineProps ( {
  avatar: String,
  size: { type: String, default: 'xs' },
  show_large: { type: Boolean, default: false }
} )
const size = props.size;
const avatar = props.avatar;
const show_large_avatar = props.show_large;

const showDialog = ref ( false )

const handleShowDialog = () => {
  if (show_large_avatar) {
    showDialog.value = true;
  }
}

const handleCloseDialog = () => {
  showDialog.value = false;
}

</script>

<template>
  <div>
    <div>
      <img
          @click="handleShowDialog"
          alt="Avatar"
          title="AVATAR!"
          :class="[
            'rounded-2xl mx-auto border-2 border-darker-300',
            show_large_avatar && 'hover:border-primary-600',
            size === 'xs' && 'size-[48px]',
            size === 'sm' && 'size-[64px]',
            size === 'md' && 'size-[98px]',
            size === 'lg' && 'size-[128px]',
            size === 'xl' && 'size-[144px]',
            size === 'xl' && 'size-[144px]'
          ]"
          :src="avatar"
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
          :src="avatar === '' ?? '/avatars/default.jpg'"
          class="rounded-xl border-2 border-darker-300 hover:border-primary-600"
          alt="Avatar"
      />
    </template>
  </Dialog>
</template>

<style scoped>

</style>