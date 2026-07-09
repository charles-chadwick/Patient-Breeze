<script setup>
import { ref, watch, onUnmounted } from 'vue'
import { Search, X as XIcon } from 'lucide-vue-next'

const props = defineProps({
    // Array of { user_id, role } — the value submitted with the form.
    modelValue: {
        type: Array,
        default: () => [],
    },
    // Display data for staff already assigned (edit mode): { id, first_name, last_name, avatar_url, role }.
    initialStaff: {
        type: Array,
        default: () => [],
    },
    roleOptions: {
        type: Array,
        required: true,
    },
    defaultRole: {
        type: String,
        default: 'Assistant',
    },
    // Role assigned to the first staff member added.
    primaryRole: {
        type: String,
        default: 'Primary',
    },
    placeholder: {
        type: String,
        default: '',
    },
})

const emit = defineEmits(['update:modelValue'])

// Selected staff tracked as full display objects (id, name, avatar, role) so
// their rows render without a preloaded option list.
const selected = ref(props.initialStaff.map((staff) => ({ ...staff })))
const search_value = ref('')
const results = ref([])
const searching = ref(false)
const open = ref(false)
let debounce_timer = null

function emitModel() {
    emit('update:modelValue', selected.value.map((staff) => ({ user_id: staff.id, role: staff.role })))
}

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
        const selected_ids = selected.value.map((staff) => staff.id)
        results.value = (payload.staff ?? []).filter((staff) => !selected_ids.includes(staff.id))
    } catch {
        results.value = []
    } finally {
        searching.value = false
    }
}

function addStaff(staff) {
    if (selected.value.some((entry) => entry.id === staff.id)) {
        return
    }

    const assigned_role = selected.value.length === 0 ? props.primaryRole : props.defaultRole
    selected.value = [...selected.value, { ...staff, role: assigned_role }]
    emitModel()

    search_value.value = ''
    results.value = []
    open.value = false
}

function removeStaff(user_id) {
    selected.value = selected.value.filter((staff) => staff.id !== user_id)
    emitModel()
}

function updateRole(user_id, role) {
    const entry = selected.value.find((staff) => staff.id === user_id)
    if (entry) {
        entry.role = role
        emitModel()
    }
}

function fullName(staff) {
    return `${staff.first_name} ${staff.last_name}`
}

onUnmounted(() => clearTimeout(debounce_timer))
</script>

<template>
    <div class="grid gap-4">
        <div class="relative">
            <div class="relative">
                <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                <input
                    v-model="search_value"
                    type="search"
                    :placeholder="placeholder"
                    class="h-10 w-full rounded-lg border border-border bg-white pl-9 pr-4 text-sm text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    @focus="search_value.trim() && (open = true)"
                />
            </div>

            <div
                v-if="open"
                class="absolute z-50 mt-1 w-full overflow-hidden rounded-xl border border-border bg-white shadow-lg"
            >
                <p v-if="searching" class="px-3 py-2 text-sm text-muted-foreground">
                    {{ $t('appointments.form.staff_searching') }}
                </p>
                <p v-else-if="results.length === 0" class="px-3 py-2 text-sm text-muted-foreground">
                    {{ $t('appointments.form.staff_empty') }}
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

        <div v-if="selected.length" class="grid gap-2">
            <div
                v-for="staff in selected"
                :key="staff.id"
                class="flex items-center gap-3"
            >
                <img
                    v-if="staff.avatar_url"
                    :src="staff.avatar_url"
                    :alt="fullName(staff)"
                    class="size-7 rounded-full object-cover ring-1 ring-border"
                />
                <span class="flex-1 text-sm text-foreground">{{ fullName(staff) }}</span>
                <select
                    :value="staff.role"
                    @change="updateRole(staff.id, $event.target.value)"
                    class="w-36 rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                >
                    <option v-for="role in roleOptions" :key="role" :value="role">{{ $t('enums.appointment_role.' + role) }}</option>
                </select>
                <button
                    type="button"
                    class="rounded p-1 text-muted-foreground hover:text-vibrant-coral-500 focus:outline-none"
                    @click="removeStaff(staff.id)"
                >
                    <XIcon class="size-4" />
                </button>
            </div>
        </div>
    </div>
</template>
