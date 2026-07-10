<script setup>
defineProps({
    modelValue: {
        type: String,
        required: true,
    },
    tabs: {
        type: Array,
        default: () => [],
    },
})

const emit = defineEmits(['update:modelValue'])
</script>

<template>
    <div class="flex bg-muted/40 p-1">
        <button
            v-for="tab in tabs"
            :key="tab.key"
            type="button"
            :data-testid="tab.testid"
            @click="emit('update:modelValue', tab.key)"
            class="flex-1 rounded-lg px-4 py-2 text-sm font-bold transition-colors"
            :class="modelValue === tab.key
                ? 'bg-card text-foreground'
                : 'text-muted-foreground hover:text-foreground'"
        >
            {{ $t(tab.label) }}
        </button>
    </div>
</template>
