<script setup>
defineProps ( {
  object: Object,
  type: {
    type: String,
    validator: ( value ) => [ 'created', 'updated', 'deleted' ].includes ( value ),
    required: false,
    default: 'created'
  }
} )
</script>

<template>
  <p class="text-sm">

    <template v-if="type === 'updated' && object.relationships?.updated_by">
      Updated by: {{ object.relationships.updated_by.attributes.full_name }}
      on {{ object.attributes.updated_at }}
    </template>
    <template v-else-if="type === 'deleted' && object.relationships?.deleted_by">
      Deleted by: {{ object.relationships.deleted_by.attributes.full_name }}
      on {{ object.attributes.deleted_at }}
    </template>
    <template v-else>
      Created by: {{ object.relationships.created_by.attributes.full_name }}
      on {{ object.attributes.created_at }}
    </template>
  </p>
</template>

<style scoped>

</style>