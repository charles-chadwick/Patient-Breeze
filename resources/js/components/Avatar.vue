<script setup>
import { Button, Dialog } from "primevue";
import { ref } from "vue";

const props = defineProps ( {
  avatar: String,
  size: { type: String, default: 'xs' },
  show_large: { type: Boolean, default: false },
  description: { type: String, required: false }
} )
const size = props.size;
const avatar = props.avatar;
const show_large_avatar = props.show_large;
const description = props.description;

const showDialog = ref ( false )

const handleShowDialog = () => {
  if ( show_large_avatar ) {
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


      <div
          @click="handleCloseDialog"
          class="rounded-xl border-2 px-1 border-white hover:border-primary-600"
      >
        <div class="rounded-t-xl bg-white py-2 flex items-center justify-between">
          <p class="px-1 w-full text-center">{{ description }}

          </p>
          <Button
              icon="pi pi-times"
              severity="secondary"
              aria-label="Clear search"
              size="small"
              class="shrink-0"
          />
        </div>
        <img
            class="rounded-md border border-darker-300"
            :src="avatar"
            alt="Avatar"
        />
      </div>
    </template>
  </Dialog>
</template>

<style scoped>

</style>