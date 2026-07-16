<script setup>
import { ref, watch, onUnmounted } from 'vue'
import { Search } from 'lucide-vue-next'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog'
import InsuranceForm from '@/Components/InsuranceForm.vue'

const props = defineProps({
    open: {
        type: Boolean,
        required: true,
    },
    action: {
        type: String,
        required: true,
    },
    planTypeOptions: {
        type: Array,
        default: () => [],
    },
    priorityOptions: {
        type: Array,
        default: () => [],
    },
    relationshipOptions: {
        type: Array,
        default: () => [],
    },
})

const emit = defineEmits(['update:open', 'saved'])

const step = ref('search')
const selected = ref(null)
const search_value = ref('')
const results = ref([])
const searching = ref(false)
const searched = ref(false)
let debounce_timer = null

watch(search_value, (value) => {
    clearTimeout(debounce_timer)

    const query = value.trim()

    if (query === '') {
        results.value = []
        searched.value = false
        return
    }

    searching.value = true

    debounce_timer = setTimeout(() => runSearch(query), 300)
})

async function runSearch(query) {
    try {
        const response = await fetch(route('insurance-companies.search', { search: query }), {
            headers: { Accept: 'application/json' },
        })
        const payload = await response.json()
        results.value = payload.insurance_companies ?? []
    } catch {
        results.value = []
    } finally {
        searching.value = false
        searched.value = true
    }
}

function selectCompany(company) {
    selected.value = company
    step.value = 'form'
}

function backToSearch() {
    step.value = 'search'
    selected.value = null
}

function handleSuccess() {
    emit('saved')
    emit('update:open', false)
}

function handleOpenUpdate(value) {
    emit('update:open', value)
}

// Reset to a clean search state whenever the modal is opened.
watch(() => props.open, (is_open) => {
    if (is_open) {
        step.value = 'search'
        selected.value = null
        search_value.value = ''
        results.value = []
        searched.value = false
    }
})

onUnmounted(() => clearTimeout(debounce_timer))
</script>

<template>
    <Dialog :open="open" @update:open="handleOpenUpdate">
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>
                    {{ step === 'search' ? $t('insurances.search.heading') : $t('insurances.form.heading') }}
                </DialogTitle>
                <DialogDescription>
                    {{ step === 'search' ? $t('insurances.search.hint') : $t('insurances.form.hint') }}
                </DialogDescription>
            </DialogHeader>

            <div v-if="step === 'search'" class="grid gap-4">
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <input
                        v-model="search_value"
                        type="search"
                        data-testid="insurance-search-input"
                        :placeholder="$t('insurances.search.placeholder')"
                        class="h-10 w-full rounded-lg border border-border bg-background pl-9 pr-4 text-sm text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    />
                </div>

                <p v-if="searching" class="px-1 py-6 text-center text-sm text-muted-foreground">
                    {{ $t('insurances.search.searching') }}
                </p>
                <p v-else-if="!searched" class="px-1 py-6 text-center text-sm text-muted-foreground">
                    {{ $t('insurances.search.prompt') }}
                </p>
                <p v-else-if="results.length === 0" class="px-1 py-6 text-center text-sm text-muted-foreground">
                    {{ $t('insurances.search.empty') }}
                </p>

                <ul v-else class="max-h-72 divide-y divide-border overflow-y-auto rounded-lg border border-border">
                    <li v-for="company in results" :key="company.id">
                        <button
                            type="button"
                            data-testid="insurance-search-result"
                            @click="selectCompany(company)"
                            class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left hover:bg-muted/40"
                        >
                            <span class="min-w-0">
                                <span class="block truncate font-bold text-foreground">{{ company.name }}</span>
                                <span v-if="company.address" class="block truncate text-xs text-muted-foreground">{{ company.address }}</span>
                            </span>
                            <span v-if="company.payer_id" class="shrink-0 font-mono text-xs font-bold text-muted-foreground">{{ company.payer_id }}</span>
                        </button>
                    </li>
                </ul>
            </div>

            <InsuranceForm
                v-else
                :key="selected?.id"
                :action="action"
                :company="selected"
                :plan-type-options="planTypeOptions"
                :priority-options="priorityOptions"
                :relationship-options="relationshipOptions"
                form-id="patient-insurance-form"
                @success="handleSuccess"
            />

            <DialogFooter>
                <button
                    v-if="step === 'form'"
                    type="button"
                    @click="backToSearch"
                    class="mr-auto rounded-lg border border-border px-4 py-2 text-sm font-bold text-foreground hover:bg-muted/40"
                >
                    {{ $t('insurances.form.back') }}
                </button>
                <button
                    type="button"
                    @click="handleOpenUpdate(false)"
                    class="rounded-lg border border-border px-4 py-2 text-sm font-bold text-foreground hover:bg-muted/40"
                >
                    {{ $t('insurances.form.cancel') }}
                </button>
                <button
                    v-if="step === 'form'"
                    type="submit"
                    form="patient-insurance-form"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90"
                >
                    {{ $t('insurances.form.submit') }}
                </button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
