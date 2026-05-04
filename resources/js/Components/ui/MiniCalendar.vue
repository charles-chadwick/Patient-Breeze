<script setup>
import { computed } from 'vue'
import { getDayOfWeek, getLocalTimeZone, parseDate, today } from '@internationalized/date'
import { ChevronLeftIcon, ChevronRightIcon } from 'lucide-vue-next'
import {
    CalendarCell,
    CalendarCellTrigger,
    CalendarGrid,
    CalendarGridBody,
    CalendarGridHead,
    CalendarGridRow,
    CalendarHeadCell,
    CalendarHeader,
    CalendarHeading,
    CalendarNext,
    CalendarPrev,
    CalendarRoot,
} from 'reka-ui'
import { cn } from '@/lib/utils'

const props = defineProps({
    modelValue: {
        type: String,
        required: true,
    },
    view: {
        type: String,
        default: 'week',
    },
})

const emit = defineEmits(['update:modelValue'])

const todayDate = today(getLocalTimeZone())

const calendarValue = computed(() => {
    try {
        return parseDate(props.modelValue)
    } catch {
        return todayDate
    }
})

const selectedWeekKeys = computed(() => {
    if (props.view !== 'week') return new Set()
    const dow = getDayOfWeek(calendarValue.value, 'en-US') // 0=Sun, 1=Mon…6=Sat
    const monday = calendarValue.value.subtract({ days: (dow + 6) % 7 })
    const keys = new Set()
    for (let i = 0; i < 7; i++) keys.add(monday.add({ days: i }).toString())
    return keys
})

function onSelect(val) {
    if (!val) return
    emit('update:modelValue', `${val.year}-${String(val.month).padStart(2, '0')}-${String(val.day).padStart(2, '0')}`)
}
</script>

<template>
    <CalendarRoot
        :model-value="calendarValue"
        :week-starts-on="1"
        granularity="day"
        @update:model-value="onSelect"
    >
        <template #default="{ weekDays, grid }">
            <CalendarHeader class="mb-3 flex items-center justify-between">
                <CalendarPrev
                    class="flex size-7 items-center justify-center rounded-md text-muted-foreground hover:bg-muted focus:outline-none"
                >
                    <ChevronLeftIcon class="size-4" />
                </CalendarPrev>
                <CalendarHeading class="text-sm font-semibold text-foreground" />
                <CalendarNext
                    class="flex size-7 items-center justify-center rounded-md text-muted-foreground hover:bg-muted focus:outline-none"
                >
                    <ChevronRightIcon class="size-4" />
                </CalendarNext>
            </CalendarHeader>

            <CalendarGrid v-for="month in grid" :key="month.value.toString()" class="w-full">
                <CalendarGridHead>
                    <CalendarGridRow class="mb-1 flex w-full">
                        <CalendarHeadCell
                            v-for="day in weekDays"
                            :key="day"
                            class="flex-1 text-center text-xs font-medium text-muted-foreground"
                        >
                            {{ day }}
                        </CalendarHeadCell>
                    </CalendarGridRow>
                </CalendarGridHead>

                <CalendarGridBody>
                    <CalendarGridRow
                        v-for="(weekDates, idx) in month.rows"
                        :key="idx"
                        class="flex w-full"
                    >
                        <CalendarCell
                            v-for="date in weekDates"
                            :key="date.toString()"
                            :date="date"
                            :class="cn(
                                'relative flex flex-1 p-0',
                                view === 'week' && selectedWeekKeys.has(date.toString()) && 'bg-primary/10 first:rounded-l-md last:rounded-r-md',
                            )"
                        >
                            <CalendarCellTrigger
                                :day="date"
                                :month="month.value"
                                :class="cn(
                                    'aspect-square w-full items-center justify-center rounded-md text-sm focus:outline-none flex',
                                    'data-[outside-month]:text-muted-foreground/40',
                                    'data-[disabled]:cursor-not-allowed data-[disabled]:opacity-40',
                                    date.compare(todayDate) === 0 && 'border border-border',
                                    view === 'day' && [
                                        'text-foreground hover:bg-muted',
                                        'data-[selected]:bg-primary data-[selected]:text-primary-foreground data-[selected]:hover:bg-primary',
                                    ],
                                    view === 'week' && selectedWeekKeys.has(date.toString()) && 'hover:bg-primary/20',
                                    view === 'week' && !selectedWeekKeys.has(date.toString()) && 'text-foreground hover:bg-muted',
                                    view === 'week' && calendarValue.compare(date) === 0 && 'bg-primary text-primary-foreground hover:bg-primary',
                                )"
                            />
                        </CalendarCell>
                    </CalendarGridRow>
                </CalendarGridBody>
            </CalendarGrid>
        </template>
    </CalendarRoot>
</template>
