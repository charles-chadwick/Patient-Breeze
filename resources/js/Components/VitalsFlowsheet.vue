<script setup>
import { Trash2 } from 'lucide-vue-next'
import { formatDate, DATE_SHORT } from '@/lib/utils'

const props = defineProps({
    vitals: {
        type: Array,
        default: () => [],
    },
    vitalTypes: {
        type: Array,
        default: () => [],
    },
    deletable: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['delete'])

// A cell is highlighted when the set flagged this vital type as out of range.
function isAbnormal(set, type) {
    return Array.isArray(set.abnormal_flags) && set.abnormal_flags.includes(type.value)
}

function reading(set, type) {
    const value = set[type.column]

    return value === null || value === undefined || value === '' ? null : value
}
</script>

<template>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[36rem] border-collapse text-sm">
            <thead>
                <tr class="border-b border-border text-left">
                    <th class="sticky left-0 z-10 bg-card px-4 py-3 font-bold text-muted-foreground">
                        {{ $t('vitals.flowsheet.measurement') }}
                    </th>
                    <th
                        v-for="set in vitals"
                        :key="set.id"
                        class="px-4 py-3 text-right font-bold text-muted-foreground whitespace-nowrap"
                    >
                        <span class="inline-flex items-center gap-1.5">
                            {{ formatDate(set.measured_on, DATE_SHORT) }}
                            <button
                                v-if="deletable"
                                type="button"
                                data-testid="vitals-delete-button"
                                :aria-label="$t('vitals.delete')"
                                @click="emit('delete', set)"
                                class="rounded p-0.5 text-muted-foreground hover:bg-vibrant-coral-50 hover:text-vibrant-coral-600"
                            >
                                <Trash2 class="size-3.5" />
                            </button>
                        </span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                <tr v-for="type in vitalTypes" :key="type.value" class="hover:bg-muted/30">
                    <th class="sticky left-0 z-10 bg-card px-4 py-2.5 text-left font-medium text-foreground">
                        {{ type.label }}
                        <span v-if="type.unit" class="ml-1 text-xs font-normal text-muted-foreground">{{ type.unit }}</span>
                    </th>
                    <td
                        v-for="set in vitals"
                        :key="set.id"
                        class="px-4 py-2.5 text-right tabular-nums"
                    >
                        <span
                            v-if="reading(set, type) !== null"
                            :class="isAbnormal(set, type)
                                ? 'rounded-md bg-vibrant-coral-50 px-1.5 py-0.5 font-bold text-vibrant-coral-600'
                                : 'text-foreground'"
                            :title="isAbnormal(set, type) ? $t('vitals.abnormal') : null"
                        >
                            {{ reading(set, type) }}
                        </span>
                        <span v-else class="text-muted-foreground">{{ $t('vitals.latest.no_reading') }}</span>
                    </td>
                </tr>

                <!-- BMI is derived, shown after the raw measurements. -->
                <tr class="hover:bg-muted/30">
                    <th class="sticky left-0 z-10 bg-card px-4 py-2.5 text-left font-medium text-foreground">
                        {{ $t('vitals.latest.bmi') }}
                    </th>
                    <td v-for="set in vitals" :key="set.id" class="px-4 py-2.5 text-right tabular-nums text-foreground">
                        {{ set.bmi ?? $t('vitals.latest.no_reading') }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
