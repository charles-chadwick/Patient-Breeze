<!--suppress JSUnresolvedReference -->
<script setup>
import AuthenticatedLayout from "../AuthenticatedLayout.vue";
import Avatar from "../../components/Avatar.vue";
import { Card } from "primevue";
const props = defineProps ( { user: Object } )


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
          <Avatar :avatar="user.data.attributes.avatar" :on="{ type: 'User', id: user.data.id}" />
        </div>
      </template>
    </Card>
  </AuthenticatedLayout>


</template>

<style scoped>

</style>