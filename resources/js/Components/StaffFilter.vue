<script setup>
import { ref, watch, onUnmounted } from 'vue'
import { Search, X as XIcon } from 'lucide-vue-next'

const props = defineProps({
    // Currently applied staff ids (the filter value, mirrored from the URL).
    modelValue: {
        type: Array,
        default: () => [],
    },
    // Display data for the applied staff, resolved server-side: { id, first_name, last_name, avatar_url }.
    selected: {
        type: Array,
        default: () => [],
    },
    placeholder: {
        type: String,
        default: '',
    },
})

const emit = defineEmits(['update:modelValue'])

const search_value = ref('')
const results = ref([])
const searching = ref(false)
const open = ref(false)
let debounce_timer = null

watch(search_value, (value) => {
    clearTimeout(debounce_timer)

    const query = value.trim()

    if (query === '') {
        results.value = []
        open.value = false
        return
    }

    searching.value = true
    open.value = true

    debounce_timer = setTimeout(() => runSearch(query), 300)
})

async function runSearch(query) {
    try {
        const response = await fetch(route('appointments.staff.search', { search: query }), {
            headers: { Accept: 'application/json' },
        })
        const payload = await response.json()
        results.value = (payload.staff ?? []).filter((staff) => !props.modelValue.includes(staff.id))
    } catch {
        results.value = []
    } finally {
        searching.value = false
    }
}

function addStaff(staff) {
    if (props.modelValue.includes(staff.id)) {
        return
    }

    emit('update:modelValue', [...props.modelValue, staff.id])

    search_value.value = ''
    results.value = []
    open.value = false
}

function removeStaff(user_id) {
    emit('update:modelValue', props.modelValue.filter((id) => id !== user_id))
}

function fullName(staff) {
    return `${staff.last_name}, ${staff.first_name}`
}

onUnmounted(() => clearTimeout(debounce_timer))
</script>

<template>
    <div>
        <div class="relative">
            <div class="relative">
                <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                <input
                    v-model="search_value"
                    type="search"
                    :placeholder="placeholder"
                    class="h-10 w-full rounded-lg border border-border bg-background pl-9 pr-4 text-sm text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    @focus="search_value.trim() && (open = true)"
                />
            </div>

            <div
                v-if="open"
                class="absolute z-50 mt-1 w-full overflow-hidden rounded-xl border border-border bg-popover shadow-lg"
            >
                <p v-if="searching" class="px-3 py-2 text-sm text-muted-foreground">
                    {{ $t('appointments.index.filter_staff_searching') }}
                </p>
                <p v-else-if="results.length === 0" class="px-3 py-2 text-sm text-muted-foreground">
                    {{ $t('appointments.index.filter_staff_empty') }}
                </p>
                <ul v-else class="max-h-60 overflow-y-auto p-1">
                    <li v-for="staff in results" :key="staff.id">
                        <button
                            type="button"
                            @click="addStaff(staff)"
                            class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-foreground hover:bg-muted"
                        >
                            <img
                                v-if="staff.avatar_url"
                                :src="staff.avatar_url"
                                :alt="fullName(staff)"
                                class="size-6 rounded-full object-cover"
                            />
                            <span class="flex-1">{{ fullName(staff) }}</span>
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <div v-if="selected.length" class="mt-2 flex flex-wrap gap-1.5">
            <span
                v-for="staff in selected"
                :key="staff.id"
                class="inline-flex items-center gap-1.5 rounded-md bg-muted px-2 py-0.5 text-sm text-foreground"
            >
                <img
                    v-if="staff.avatar_url"
                    :src="staff.avatar_url"
                    :alt="fullName(staff)"
                    class="size-4 rounded-full object-cover"
                />
                {{ fullName(staff) }}
                <button
                    type="button"
                    class="ml-0.5 cursor-pointer rounded hover:text-vibrant-coral-500 focus:outline-none"
                    @click="removeStaff(staff.id)"
                >
                    <XIcon class="size-3" />
                </button>
            </span>
        </div>
    </div>
</template>
