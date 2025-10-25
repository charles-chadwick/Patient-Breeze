<!--suppress JSUnresolvedReference -->
<script setup>
import AuthenticatedLayout from "../AuthenticatedLayout.vue";
import { Card, FileUpload, Dialog } from "primevue";
import { Link, useForm } from '@inertiajs/vue3'
import UserController from '../../actions/App/Http/Controllers/UserController';
import { ref } from "vue";

const props = defineProps ( { user: Object } )
const form = useForm ( {
  avatar: null
} )

function uploadAvatar () {
  form.post ( UserController.uploadAvatar ( props.user.data.id ) )
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
  <AuthenticatedLayout>
    <Card>

      <template #content>
        <div class="flex justify-between items-start">
          <div>
            <h1 class="p-card-title">{{ user.data.attributes.full_name }}'s Profile</h1>
            <p><span class="font-bold">Name:</span> {{ user.data.attributes.full_name }}</p>
            <p><span class="font-bold">Role:</span> {{ user.data.attributes.role }}</p>
            <p><span class="font-bold">Created:</span> {{ user.data.attributes.created_at }}</p>
          </div>
          <div>
            <div v-if="user.data.attributes.avatar">
              <img
                  @click="handleShowDialog"
                  alt="Avatar"
                  class="rounded-2xl size-[128px] mx-auto"
                  :src="user.data.attributes.avatar"
              />
              <Link
                  :href="UserController.removeAvatar('users.avatar.remove', user.data.id)"
                  method="post"
                  as="p"
                  class="flex-none cursor-pointer"
              >
                Remove This Avatar
              </Link>
            </div>
            <div v-else>
              <form @submit.prevent="uploadAvatar">
                <FileUpload
                    type="file"
                    @input="form.avatar = $event.target.files[0]"
                />
                <progress
                    v-if="form.progress"
                    :value="form.progress.percentage"
                    max="100"
                >
                  {{ form.progress.percentage }}%
                </progress>
                <Button
                    type="submit"
                    class="btn"
                >Save Avatar
                </Button>
              </form>

            </div>
          </div>
        </div>
      </template>
    </Card>
  </AuthenticatedLayout>
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