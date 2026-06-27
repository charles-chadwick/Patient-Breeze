<script setup>
import { ChevronDown, Filter, Check } from 'lucide-vue-next'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    label: {
        type: String,
        required: true,
    },
    paramName: {
        type: String,
        required: true,
    },
    selected: {
        type: Array,
        default: () => [],
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

const selected_count = computed(() => props.selected.length)

function isSelected(value) {
    return props.selected.includes(value)
}

function toggle(value) {
    const next = isSelected(value)
        ? props.selected.filter((selected_value) => selected_value !== value)
        : [...props.selected, value]

    router.get(
        route(props.routeName),
        { ...props.params, [props.paramName]: next },
        { preserveState: true, replace: true },
    )
}

function clearAll() {
    router.get(
        route(props.routeName),
        { ...props.params, [props.paramName]: undefined },
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
            <Filter class="size-4 text-muted-foreground" />
            <span>{{ label }}</span>
            <span
                v-if="selected_count > 0"
                class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-bold text-primary"
            >
                {{ selected_count }}
            </span>
            <ChevronDown
                class="size-4 text-muted-foreground transition-transform"
                :class="{ 'rotate-180': open }"
            />
        </button>

        <div
            v-if="open"
            class="absolute right-0 top-full z-20 mt-1 w-56 rounded-lg border border-border bg-white py-1 shadow-md"
        >
            <button
                v-for="option in options"
                :key="option"
                type="button"
                class="flex w-full items-center gap-2 px-3 py-2 text-sm text-foreground hover:bg-muted/40"
                :class="{ 'font-bold text-primary': isSelected(option) }"
                @click="toggle(option)"
            >
                <span
                    class="flex size-4 shrink-0 items-center justify-center rounded border"
                    :class="isSelected(option) ? 'border-primary bg-primary text-white' : 'border-border'"
                >
                    <Check v-if="isSelected(option)" class="size-3" />
                </span>
                {{ option }}
            </button>

            <div v-if="selected_count > 0" class="mt-1 border-t border-border pt-1">
                <button
                    type="button"
                    class="w-full px-3 py-2 text-left text-sm text-muted-foreground hover:bg-muted/40"
                    @click="clearAll"
                >
                    Clear filter
                </button>
            </div>
        </div>
    </div>
</template>
