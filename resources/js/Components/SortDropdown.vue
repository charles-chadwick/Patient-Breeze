<script setup>
import { ChevronDown, ArrowUpDown, ArrowUp, ArrowDown, Check } from 'lucide-vue-next'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    sortBy: {
        type: String,
        default: null,
    },
    direction: {
        type: String,
        default: 'asc',
    },
    options: {
        type: Array,
        required: true,
    },
    routeName: {
        type: String,
        required: true,
    },
    params: {
        type: Object,
        default: () => ({}),
    },
})

const open = ref(false)
const container = ref(null)

const current_option = computed(() => props.options.find((o) => o.value === props.sortBy))

const sort_icon = computed(() => {
    if (!current_option.value) return ArrowUpDown
    return props.direction === 'asc' ? ArrowUp : ArrowDown
})

function select(value) {
    const new_direction = value === props.sortBy && props.direction === 'asc' ? 'desc' : 'asc'

    router.get(
        route(props.routeName),
        { ...props.params, sort_by: value, direction: new_direction },
        { preserveState: true, replace: true },
    )

    open.value = false
}

function handleClickOutside(event) {
    if (!container.value?.contains(event.target)) {
        open.value = false
    }
}

onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))
</script>

<template>
    <div ref="container" class="relative">
        <button
            type="button"
            class="flex items-center gap-2 rounded-lg border border-border bg-white px-3 py-2 text-sm font-bold text-foreground hover:bg-muted/40 focus:outline-none focus:ring-2 focus:ring-primary/20"
            @click="open = !open"
        >
            <component :is="sort_icon" class="size-4 text-muted-foreground" />
            <span>{{ current_option?.label ?? 'Sort' }}</span>
            <ChevronDown
                class="size-4 text-muted-foreground transition-transform"
                :class="{ 'rotate-180': open }"
            />
        </button>

        <div
            v-if="open"
            class="absolute right-0 top-full z-20 mt-1 w-48 rounded-lg border border-border bg-white py-1 shadow-md"
        >
            <button
                v-for="option in options"
                :key="option.value"
                type="button"
                class="flex w-full items-center gap-2 px-3 py-2 text-sm text-foreground hover:bg-muted/40"
                :class="{ 'font-bold text-primary': option.value === sortBy }"
                @click="select(option.value)"
            >
                <Check v-if="option.value === sortBy" class="size-4 shrink-0 text-primary" />
                <span v-else class="size-4 shrink-0" />
                {{ option.label }}
                <span v-if="option.value === sortBy" class="ml-auto text-xs text-muted-foreground">
                    {{ direction === 'asc' ? '↑' : '↓' }}
                </span>
            </button>
        </div>
    </div>
</template>
