<script setup>
import { ref, watch, onUnmounted } from 'vue'
import { Search, X as XIcon } from 'lucide-vue-next'

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
    placeholder: {
        type: String,
        default: '',
    },
    // Named route the debounced search hits. Must return { users: [...] }.
    searchRoute: {
        type: String,
        default: 'users.search',
    },
})

const emit = defineEmits(['update:modelValue'])

// Selected users are tracked as full objects so chips keep their name/avatar
// even after the search results (which back them) have changed.
const selected = ref([])
const search_value = ref('')
const results = ref([])
const searching = ref(false)
const open = ref(false)
let debounce_timer = null

// Keep the chip list in sync when the bound value is changed externally
// (e.g. the parent form calling reset() clears participant_ids to []).
watch(() => props.modelValue, (ids) => {
    selected.value = selected.value.filter((user) => ids.includes(user.id))
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
        const selected_ids = props.modelValue
        results.value = (payload.users ?? []).filter((user) => !selected_ids.includes(user.id))
    } catch {
        results.value = []
    } finally {
        searching.value = false
    }
}

function addParticipant(user) {
    if (props.modelValue.includes(user.id)) {
        return
    }

    selected.value = [...selected.value, user]
    emit('update:modelValue', [...props.modelValue, user.id])

    search_value.value = ''
    results.value = []
    open.value = false
}

function removeParticipant(user_id) {
    selected.value = selected.value.filter((user) => user.id !== user_id)
    emit('update:modelValue', props.modelValue.filter((id) => id !== user_id))
}

function fullName(user) {
    return `${user.first_name} ${user.last_name}`
}

onUnmounted(() => clearTimeout(debounce_timer))
</script>

<template>
    <div class="relative">
        <div
            v-if="selected.length"
            class="mb-2 flex flex-wrap gap-1.5"
        >
            <span
                v-for="user in selected"
                :key="user.id"
                class="inline-flex items-center gap-1 rounded-md bg-muted px-2 py-0.5 text-xs font-medium text-foreground"
            >
                <img
                    v-if="user.avatar_url"
                    :src="user.avatar_url"
                    :alt="fullName(user)"
                    class="size-4 rounded-full object-cover"
                />
                {{ fullName(user) }}
                <button
                    type="button"
                    class="ml-0.5 rounded hover:text-vibrant-coral-500 focus:outline-none"
                    @click="removeParticipant(user.id)"
                >
                    <XIcon class="size-3" />
                </button>
            </span>
        </div>

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
                {{ $t('discussions.create.participants_searching') }}
            </p>
            <p v-else-if="results.length === 0" class="px-3 py-2 text-sm text-muted-foreground">
                {{ $t('discussions.create.participants_empty') }}
            </p>
            <ul v-else class="max-h-60 overflow-y-auto p-1">
                <li v-for="user in results" :key="user.id">
                    <button
                        type="button"
                        @click="addParticipant(user)"
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
