<!--suppress JSUnresolvedReference -->
<script setup>
import Avatar from "../../../components/Avatar.vue";

const props = defineProps ( {
  user: Object,
  compact: { type: Boolean, default: true },
  displayMode: { type: String, default: 'full_name' },
  showAvatar: { type: Boolean, default: true },
} );


</script>

<template>
  <div class="flex justify-between items-start">
    <div>
      <h1
      :class="[
          compact ? 'text-base font-normal' : 'text-lg font-bold',
      ]"
      >{{ displayMode === 'initials' ? user.attributes.initials : user.attributes.full_name }}
      </h1>
      <div v-if="!compact">
        <p>{{ user.attributes.role }}</p>
        <p>{{ user.attributes.email }}</p>
      </div>
      <slot name="details" />
    </div>
    <div v-if="showAvatar">
      <Avatar
          :avatar="user.attributes.avatar"
          :size="compact ? 'sm' : 'md'"
          :show_large="!compact"
          :on="{ type: 'User', id: user.id}"
      />
    </div>
  </div>
</template>

<style scoped>

</style>