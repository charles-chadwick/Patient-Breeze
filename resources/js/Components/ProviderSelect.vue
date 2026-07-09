<script setup>
import { ref, watch, onUnmounted } from 'vue'
import { Search, X as XIcon } from 'lucide-vue-next'

const props = defineProps({
    // The selected provider's id, or null when nothing is chosen.
    modelValue: {
        type: [Number, null],
        default: null,
    },
    placeholder: {
        type: String,
        default: '',
    },
    // Named route the debounced search hits. Must return { users: [...] }.
    searchRoute: {
        type: String,
        required: true,
    },
})

const emit = defineEmits(['update:modelValue'])

// The chosen provider is tracked as a full object so its chip keeps its name
// and avatar even after the search results backing it have changed.
const selected = ref(null)
const search_value = ref('')
const results = ref([])
const searching = ref(false)
const open = ref(false)
let debounce_timer = null

// Clear the chip when the bound value is reset externally (e.g. form.reset()).
watch(() => props.modelValue, (id) => {
    if (id === null) {
        selected.value = null
    }
})

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
        const response = await fetch(route(props.searchRoute, { search: query }), {
            headers: { Accept: 'application/json' },
        })
        const payload = await response.json()
        results.value = payload.users ?? []
    } catch {
        results.value = []
    } finally {
        searching.value = false
    }
}

function selectProvider(user) {
    selected.value = user
    emit('update:modelValue', user.id)

    search_value.value = ''
    results.value = []
    open.value = false
}

function clearProvider() {
    selected.value = null
    emit('update:modelValue', null)
}

function fullName(user) {
    return `${user.first_name} ${user.last_name}`
}

onUnmounted(() => clearTimeout(debounce_timer))
</script>

<template>
    <div class="relative">
        <div
            v-if="selected"
            class="mb-2 flex flex-wrap gap-1.5"
        >
            <span
                class="inline-flex items-center gap-1 rounded-md bg-muted px-2 py-0.5 text-xs font-medium text-foreground"
            >
                <img
                    v-if="selected.avatar_url"
                    :src="selected.avatar_url"
                    :alt="fullName(selected)"
                    class="size-4 rounded-full object-cover"
                />
                {{ fullName(selected) }}
                <button
                    type="button"
                    class="ml-0.5 rounded hover:text-vibrant-coral-500 focus:outline-none"
                    @click="clearProvider"
                >
                    <XIcon class="size-3" />
                </button>
            </span>
        </div>

        <div v-else class="relative">
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
            v-if="open && !selected"
            class="absolute z-50 mt-1 w-full overflow-hidden rounded-xl border border-border bg-popover shadow-lg"
        >
            <p v-if="searching" class="px-3 py-2 text-sm text-muted-foreground">
                {{ $t('discussions.create.participants_searching') }}
            </p>
            <p v-else-if="results.length === 0" class="px-3 py-2 text-sm text-muted-foreground">
                {{ $t('discussions.create.participants_empty') }}
            </p>
            <ul v-else class="max-h-60 overflow-y-auto p-1">
                <li v-for="user in results" :key="user.id">
                    <button
                        type="button"
                        @click="selectProvider(user)"
                        class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-foreground hover:bg-muted"
                    >
                        <img
                            v-if="user.avatar_url"
                            :src="user.avatar_url"
                            :alt="fullName(user)"
                            class="size-6 rounded-full object-cover"
                        />
                        <span class="flex-1">{{ fullName(user) }}</span>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</template>
