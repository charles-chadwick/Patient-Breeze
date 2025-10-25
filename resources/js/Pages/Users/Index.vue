<!--suppress JSUnresolvedReference -->
<script setup>
import AuthenticatedLayout from "../AuthenticatedLayout.vue";
import Pagination from "../../components/Pagination.vue";
import UserController from "../../actions/App/Http/Controllers/UserController";
import { Card } from 'primevue';
import { Link } from "@inertiajs/vue3";

defineProps ( { users: Array | Object } )
</script>

<template>
  <AuthenticatedLayout>
    <Card>
      <template #title>Users</template>
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
            <div class="w-full">
              <h2 class="font-bold">
                <Link :href="UserController.profile(user.id)">
                  {{ user.attributes.full_name }}
                </Link>
              </h2>
              <p>{{ user.attributes.role }}</p>
              <p>{{ user.attributes.email }}</p>
            </div>
            <div class="shrink-0">
              <img
                  :src="user.attributes.avatar"
                  :alt="user.attributes.full_name + ' avatar'"
                  :title="user.attributes.full_name + ' avatar'"
                  class="w-16 h-16 rounded-xl"
              />
            </div>
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