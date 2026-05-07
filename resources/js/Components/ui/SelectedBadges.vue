<script setup>
import { XIcon } from 'lucide-vue-next'

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
    options: {
        type: Array,
        default: () => [],
    },
})

const emit = defineEmits(['update:modelValue'])

function optionFor(value) {
    return props.options.find((o) => o.value === value)
}

function removeValue(value) {
    emit('update:modelValue', props.modelValue.filter((v) => v !== value))
}
</script>

<template>
    <div v-if="modelValue.length" class="flex flex-wrap gap-1.5">
        <span
            v-for="val in modelValue"
            :key="val"
            class="inline-flex items-center gap-1.5 rounded-md bg-muted px-2 py-0.5 text-sm text-foreground"
        >
            <img
                v-if="optionFor(val)?.avatar"
                :src="optionFor(val).avatar"
                :alt="optionFor(val).label"
                class="size-4 rounded-full object-cover"
            />
            {{ optionFor(val)?.label ?? val }}
            <button
                type="button"
                class="ml-0.5 cursor-pointer rounded hover:text-red-500 focus:outline-none"
                @click="removeValue(val)"
            >
                <XIcon class="size-3" />
            </button>
        </span>
    </div>
</template>
