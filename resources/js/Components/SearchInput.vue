<script setup>
import { Search } from 'lucide-vue-next'
import { ref, watch, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Search…',
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

const emit = defineEmits(['update:modelValue'])

const search_value = ref(props.modelValue)
let debounce_timer = null

watch(search_value, (value) => {
    emit('update:modelValue', value)

    clearTimeout(debounce_timer)
    debounce_timer = setTimeout(() => {
        router.get(
            route(props.routeName),
            { ...props.params, search: value || undefined },
            { preserveState: true, replace: true },
        )
    }, 300)
})

onUnmounted(() => clearTimeout(debounce_timer))
</script>

<template>
    <div class="relative">
        <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
        <input
            v-model="search_value"
            type="search"
            :placeholder="placeholder"
            class="w-full rounded-lg border border-border bg-white py-2 pl-9 pr-4 text-sm text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
        />
    </div>
</template>
