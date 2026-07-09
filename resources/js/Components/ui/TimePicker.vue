<script setup>
import { computed, nextTick, ref, watch } from 'vue'
import { ClockIcon } from 'lucide-vue-next'
import { PopoverRoot, PopoverTrigger, PopoverPortal, PopoverContent } from 'reka-ui'
import { cn } from '@/lib/utils'

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Pick a time',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    step: {
        type: Number,
        default: 15,
    },
})

const emit = defineEmits(['update:modelValue'])

const is_open = ref(false)
const list_ref = ref(null)

function formatLabel(hhmm) {
    const [hour, minute] = hhmm.split(':').map(Number)
    if (Number.isNaN(hour) || Number.isNaN(minute)) {
        return ''
    }
    const period = hour < 12 ? 'AM' : 'PM'
    const hour_12 = hour % 12 === 0 ? 12 : hour % 12
    return `${hour_12}:${String(minute).padStart(2, '0')} ${period}`
}

const time_options = computed(() => {
    const options = []
    for (let minutes = 0; minutes < 24 * 60; minutes += props.step) {
        const hour = Math.floor(minutes / 60)
        const minute = minutes % 60
        const value = `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`
        options.push({ value, label: formatLabel(value) })
    }
    return options
})

const selected_value = computed(() => (props.modelValue ? props.modelValue.substring(0, 5) : ''))

const displayValue = computed(() => (selected_value.value ? formatLabel(selected_value.value) : ''))

function selectTime(value) {
    emit('update:modelValue', value)
    is_open.value = false
}

watch(is_open, (open) => {
    if (!open) {
        return
    }
    nextTick(() => {
        const selected_node = list_ref.value?.querySelector('[data-selected="true"]')
        if (selected_node) {
            selected_node.scrollIntoView({ block: 'center' })
        }
    })
})
</script>

<template>
    <PopoverRoot v-model:open="is_open">
        <PopoverTrigger
            :disabled="disabled"
            :class="cn(
                'flex h-9 w-full items-center justify-between gap-2 rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50',
                !displayValue && 'text-muted-foreground',
                disabled && 'cursor-not-allowed opacity-50'
            )"
        >
            <span>{{ displayValue || placeholder }}</span>
            <ClockIcon class="size-4 shrink-0 opacity-50" />
        </PopoverTrigger>

        <PopoverPortal>
            <PopoverContent
                align="start"
                :side-offset="4"
                class="z-50 w-[var(--reka-popover-trigger-width)] rounded-xl border border-border bg-white p-1 shadow-lg"
            >
                <div ref="list_ref" class="max-h-60 overflow-y-auto">
                    <button
                        v-for="option in time_options"
                        :key="option.value"
                        type="button"
                        :data-selected="option.value === selected_value"
                        :class="cn(
                            'flex w-full items-center rounded-md px-3 py-1.5 text-left text-sm hover:bg-muted focus:outline-none focus:ring-2 focus:ring-primary/50',
                            option.value === selected_value && 'bg-primary text-primary-foreground hover:bg-primary'
                        )"
                        @click="selectTime(option.value)"
                    >
                        {{ option.label }}
                    </button>
                </div>
            </PopoverContent>
        </PopoverPortal>
    </PopoverRoot>
</template>
