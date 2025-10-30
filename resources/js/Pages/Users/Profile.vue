<!--suppress JSUnresolvedReference -->
<script setup>
import AuthenticatedLayout from "../AuthenticatedLayout.vue";
import { Card } from "primevue";
import UserDetails from "./Partials/Details.vue";
const props = defineProps ( { user: Object } )


</script>

<template>
  <AuthenticatedLayout>
    <Card>

      <template #content>
        <UserDetails
            :user="user"
            :show_details="true"
            :compact="false"
            avatar_size="sm"
        />
      </template>
    </Card>
  </AuthenticatedLayout>


</template>

<style scoped>

</style>