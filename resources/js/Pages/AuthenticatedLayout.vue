<!--suppress JSUnresolvedReference -->
<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import Profile from "./Users/Partials/Profile.vue";

const page = usePage ();
</script>

<template>
  <div class="mx-auto">
    <nav class="px-6 h-16 bg-darker-800 border-b-2 border-b-primary-700 shadow-lg shadow-darker-600/25 text-darker-50 flex justify-between items-center">
      <div class="flex justify-start">
        <h1 class="font-bold text-2xl ml-2">
          <Link href="/" class="hover:text-shadow-lg hover:text-shadow-primary-600/25">
          {{ page.props.header }}
          </Link>
        </h1>
      </div>
      <div class="font-bold mr-2">
        <Profile :user="$page.props.auth.user.data" />
      </div>
    </nav>
    <main class="mt-4 px-6 text-darker-800">
      <slot />
    </main>
  </div>
</template>

<style scoped>

</style>