<!--suppress JSUnresolvedReference -->
<script setup>
import { usePage } from "@inertiajs/vue3";
const page = usePage ();
</script>

<template>
  <div class="mx-auto">
    <nav class="px-6 h-16 bg-darker-800 border-b-2 border-b-primary-700 shadow-lg shadow-darker-600/25 text-darker-50 flex justify-between items-center">
      <h1 class="font-bold text-2xl ml-4">{{ page.props.header }}</h1>
    </nav>
    <main class="mt-4 px-6 text-darker-800">
      <slot />
    </main>
  </div>
</template>

<style scoped>

</style>