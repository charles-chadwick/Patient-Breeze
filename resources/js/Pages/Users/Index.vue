<!--suppress JSUnresolvedReference -->
<script setup>
import AuthenticatedLayout from "../AuthenticatedLayout.vue";
import Pagination from "../../components/Pagination.vue";
import UserController from "../../actions/App/Http/Controllers/UserController";
import { Card } from 'primevue';
import Search from "../../components/Search.vue";
import UserDetails from "./Partials/Details.vue";

defineProps ( { users: Array | Object } )
</script>

<template>
  <AuthenticatedLayout>
    <Card>
      <template #title>Users</template>
      <template #subtitle>
        <Search :url="UserController.index().url"/>
      </template>
      <template #content>
        <ul
            role="list"
            class="divide-y divide-darker-200"
        >
          <li
              v-for="user in users.data"
              :key="user.id"
              class="flex justify-between gap-x-2 py-2"
          >
            <UserDetails :user="user" />
          </li>
        </ul>
      </template>
      <template #footer>
        <Pagination :pagination="users.meta" />
      </template>
    </Card>
  </AuthenticatedLayout>
</template>

<style scoped>

</style>