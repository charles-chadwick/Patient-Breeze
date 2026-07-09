<script setup>
import { computed } from 'vue'
import { CalendarDate, DateFormatter, getLocalTimeZone, parseDate } from '@internationalized/date'
import { CalendarIcon, ChevronLeftIcon, ChevronRightIcon } from 'lucide-vue-next'
import {
    DatePickerRoot,
    DatePickerTrigger,
    DatePickerContent,
    DatePickerCalendar,
    DatePickerHeader,
    DatePickerPrev,
    DatePickerNext,
    DatePickerHeading,
    DatePickerGrid,
    DatePickerGridHead,
    DatePickerGridRow,
    DatePickerGridBody,
    DatePickerHeadCell,
    DatePickerCell,
    DatePickerCellTrigger,
} from 'reka-ui'
import { cn } from '@/lib/utils'

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Pick a date',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['update:modelValue'])

const formatter = new DateFormatter('en-US', { dateStyle: 'long' })

const dateValue = computed(() => {
    if (!props.modelValue) return undefined
    try {
        return parseDate(props.modelValue.substring(0, 10))
    } catch {
        return undefined
    }
})

const displayValue = computed(() =>
    dateValue.value
        ? formatter.format(dateValue.value.toDate(getLocalTimeZone()))
        : ''
)

function onUpdate(val) {
    if (!val) {
        emit('update:modelValue', '')
        return
    }
    const year = val.year
    const month = String(val.month).padStart(2, '0')
    const day = String(val.day).padStart(2, '0')
    emit('update:modelValue', `${year}-${month}-${day}`)
}
</script>

<template>
    <DatePickerRoot
        :model-value="dateValue"
        granularity="day"
        :disabled="disabled"
        @update:model-value="onUpdate"
    >
        <DatePickerTrigger
            :class="cn(
                'flex h-9 w-full items-center justify-between gap-2 rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50',
                !displayValue && 'text-muted-foreground',
                disabled && 'cursor-not-allowed opacity-50'
            )"
        >
            <span>{{ displayValue || placeholder }}</span>
            <CalendarIcon class="size-4 shrink-0 opacity-50" />
        </DatePickerTrigger>

        <DatePickerContent
            align="start"
            class="z-50 mt-1 rounded-xl border border-border bg-popover p-3 shadow-lg"
        >
            <DatePickerCalendar v-slot="{ weekDays, grid }">
                <DatePickerHeader class="mb-3 flex items-center justify-between">
                    <DatePickerPrev class="flex size-7 items-center justify-center rounded-md hover:bg-muted">
                        <ChevronLeftIcon class="size-4" />
                    </DatePickerPrev>
                    <DatePickerHeading class="text-sm font-semibold" />
                    <DatePickerNext class="flex size-7 items-center justify-center rounded-md hover:bg-muted">
                        <ChevronRightIcon class="size-4" />
                    </DatePickerNext>
                </DatePickerHeader>

                <DatePickerGrid v-for="month in grid" :key="month.value.toString()">
                    <DatePickerGridHead>
                        <DatePickerGridRow class="mb-1 flex">
                            <DatePickerHeadCell
                                v-for="day in weekDays"
                                :key="day"
                                class="w-9 text-center text-xs font-medium text-muted-foreground"
                            >
                                {{ day }}
                            </DatePickerHeadCell>
                        </DatePickerGridRow>
                    </DatePickerGridHead>

                    <DatePickerGridBody>
                        <DatePickerGridRow
                            v-for="(weekDates, idx) in month.rows"
                            :key="idx"
                            class="flex"
                        >
                            <DatePickerCell
                                v-for="date in weekDates"
                                :key="date.toString()"
                                :date="date"
                            >
                                <DatePickerCellTrigger
                                    :day="date"
                                    :month="month.value"
                                    :class="cn(
                                        'flex size-9 items-center justify-center rounded-md text-sm hover:bg-muted focus:outline-none focus:ring-2 focus:ring-primary/50',
                                        'data-[selected]:bg-primary data-[selected]:text-primary-foreground data-[selected]:hover:bg-primary',
                                        'data-[outside-month]:text-muted-foreground/40',
                                        'data-[disabled]:cursor-not-allowed data-[disabled]:opacity-40',
                                        'data-[today]:border data-[today]:border-border'
                                    )"
                                />
                            </DatePickerCell>
                        </DatePickerGridRow>
                    </DatePickerGridBody>
                </DatePickerGrid>
            </DatePickerCalendar>
        </DatePickerContent>
    </DatePickerRoot>
</template>
