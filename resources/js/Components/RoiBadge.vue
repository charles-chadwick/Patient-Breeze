<script setup>
defineProps({
    value: {
        type: [Boolean, null],
        default: null,
    },
})
</script>

<template>
    <span
        v-if="value === true"
        class="rounded-full bg-accent/20 px-2.5 py-0.5 text-xs font-bold text-accent-foreground"
    >{{ $t('common.labels.yes') }}</span>
    <span
        v-else-if="value === false"
        class="rounded-full bg-muted px-2.5 py-0.5 text-xs font-bold text-muted-foreground"
    >{{ $t('common.labels.no') }}</span>
    <span v-else class="text-muted-foreground">{{ $t('common.placeholders.em_dash') }}</span>
</template>
