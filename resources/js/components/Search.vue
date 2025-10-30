<script setup>

import { Button, InputText } from "primevue";
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps ( { url: { type: String, required: true } } )
const search = ref ( '' )
const clearSearch = () => {
  search.value = ''
}

watch ( [ search ], ( [ searchValue ] ) => {
  router.get (
      props.url,
      {
        search: searchValue,
      },
      {
        preserveState: true,
        preserveScroll: true,
        replace: true,
      }
  )
} )
</script>

<template>
  <div class="flex gap-2">
    <InputText
        v-model="search"
        type="text"
        placeholder="Search patients..."
        class="w-full"
    />
    <Button
        v-bind:disabled="search === ''"
        icon="pi pi-times"
        severity="secondary"
        @click="clearSearch"
        aria-label="Clear search"
    />
  </div>
</template>

<style scoped>

</style>