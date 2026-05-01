<script setup>
import { computed } from 'vue'
import {
    ComboboxRoot,
    ComboboxAnchor,
    ComboboxContent,
    ComboboxEmpty,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxViewport,
} from 'reka-ui'
import { CheckIcon, ChevronDownIcon, XIcon } from 'lucide-vue-next'
import { cn } from '@/lib/utils'

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
    options: {
        type: Array,
        default: () => [],
    },
    placeholder: {
        type: String,
        default: 'Select…',
    },
})

const emit = defineEmits(['update:modelValue'])

const selectedOptions = computed({
    get: () => props.options.filter((o) => props.modelValue.includes(o.value)),
    set: (objects) => emit('update:modelValue', objects.map((o) => o.value)),
})

function removeOption(value) {
    emit('update:modelValue', props.modelValue.filter((v) => v !== value))
}

function labelFor(value) {
    return props.options.find((o) => o.value === value)?.label ?? value
}
</script>

<template>
    <ComboboxRoot
        v-model="selectedOptions"
        multiple
        by="value"
        :reset-search-term-on-select="false"
        class="relative"
    >
        <ComboboxAnchor
            :class="cn(
                'flex min-h-9 w-full flex-wrap items-center gap-1.5 rounded-lg border border-border bg-white px-2.5 py-1.5 text-sm focus-within:ring-2 focus-within:ring-primary/50',
            )"
        >
            <span
                v-for="val in modelValue"
                :key="val"
                class="inline-flex items-center gap-1 rounded-md bg-muted px-2 py-0.5 text-xs font-medium text-foreground"
            >
                {{ labelFor(val) }}
                <button
                    type="button"
                    class="ml-0.5 rounded hover:text-red-500 focus:outline-none"
                    @click.stop="removeOption(val)"
                >
                    <XIcon class="size-3" />
                </button>
            </span>

            <ComboboxInput
                :placeholder="modelValue.length ? '' : placeholder"
                class="min-w-[80px] flex-1 bg-transparent text-sm text-foreground placeholder:text-muted-foreground focus:outline-none"
            />

            <ChevronDownIcon class="size-4 shrink-0 text-muted-foreground" />
        </ComboboxAnchor>

        <ComboboxContent
            class="z-50 mt-1 w-full rounded-xl border border-border bg-white shadow-lg"
        >
            <ComboboxViewport class="max-h-60 overflow-y-auto p-1">
                <ComboboxEmpty class="px-3 py-2 text-sm text-muted-foreground">
                    No results found.
                </ComboboxEmpty>

                <ComboboxItem
                    v-for="option in options"
                    :key="option.value"
                    :value="option"
                    class="flex cursor-pointer items-center gap-2 rounded-lg px-3 py-2 text-sm text-foreground data-[highlighted]:bg-muted data-[state=checked]:font-medium"
                >
                    <ComboboxItemIndicator>
                        <CheckIcon class="size-4 text-primary" />
                    </ComboboxItemIndicator>
                    <span class="flex-1">{{ option.label }}</span>
                </ComboboxItem>
            </ComboboxViewport>
        </ComboboxContent>
    </ComboboxRoot>
</template>
