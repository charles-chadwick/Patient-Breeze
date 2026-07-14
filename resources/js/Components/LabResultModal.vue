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
import LabResultForm from '@/Components/LabResultForm.vue'

const props = defineProps({
    open: {
        type: Boolean,
        required: true,
    },
    action: {
        type: String,
        required: true,
    },
    patientId: {
        type: Number,
        required: true,
    },
})

const emit = defineEmits(['update:open', 'saved'])

const step = ref('search')
const selected = ref(null)
const reference_range = ref(null)
const patient_context = ref({})
const loading_range = ref(false)
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
        const response = await fetch(route('lab-orders.search', { search: query }), {
            headers: { Accept: 'application/json' },
        })
        const payload = await response.json()
        results.value = payload.lab_orders ?? []
    } catch {
        results.value = []
    } finally {
        searching.value = false
        searched.value = true
    }
}

async function selectLabOrder(lab_order) {
    selected.value = lab_order
    reference_range.value = null
    patient_context.value = {}
    loading_range.value = true
    step.value = 'form'

    try {
        const response = await fetch(
            route('patients.lab-results.reference-range', { patient: props.patientId, lab_order_id: lab_order.id }),
            { headers: { Accept: 'application/json' } },
        )
        const payload = await response.json()
        reference_range.value = payload.reference_range
        patient_context.value = { gender: payload.gender, age: payload.age }
    } catch {
        reference_range.value = null
    } finally {
        loading_range.value = false
    }
}

function backToSearch() {
    step.value = 'search'
    selected.value = null
    reference_range.value = null
}

function handleSuccess() {
    emit('saved')
    emit('update:open', false)
}

function handleOpenUpdate(value) {
    emit('update:open', value)
}

// Reset the modal to a clean search state whenever it is opened.
watch(() => props.open, (is_open) => {
    if (is_open) {
        step.value = 'search'
        selected.value = null
        reference_range.value = null
        patient_context.value = {}
        search_value.value = ''
        results.value = []
        searched.value = false
    }
})

onUnmounted(() => clearTimeout(debounce_timer))
</script>

<template>
    <Dialog :open="open" @update:open="handleOpenUpdate">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>
                    {{ step === 'search' ? $t('lab_results.search.heading') : $t('lab_results.form.heading') }}
                </DialogTitle>
                <DialogDescription>
                    {{ step === 'search' ? $t('lab_results.search.hint') : $t('lab_results.form.hint') }}
                </DialogDescription>
            </DialogHeader>

            <div v-if="step === 'search'" class="grid gap-4">
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <input
                        v-model="search_value"
                        type="search"
                        :placeholder="$t('lab_results.search.placeholder')"
                        class="h-10 w-full rounded-lg border border-border bg-background pl-9 pr-4 text-sm text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    />
                </div>

                <p v-if="searching" class="px-1 py-6 text-center text-sm text-muted-foreground">
                    {{ $t('lab_results.search.searching') }}
                </p>
                <p v-else-if="!searched" class="px-1 py-6 text-center text-sm text-muted-foreground">
                    {{ $t('lab_results.search.prompt') }}
                </p>
                <p v-else-if="results.length === 0" class="px-1 py-6 text-center text-sm text-muted-foreground">
                    {{ $t('lab_results.search.empty') }}
                </p>

                <ul v-else class="max-h-72 divide-y divide-border overflow-y-auto rounded-lg border border-border">
                    <li v-for="lab_order in results" :key="lab_order.id">
                        <button
                            type="button"
                            @click="selectLabOrder(lab_order)"
                            class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left hover:bg-muted/40"
                        >
                            <span class="min-w-0">
                                <span class="block truncate font-bold text-foreground">{{ lab_order.name }}</span>
                                <span class="block truncate text-xs text-muted-foreground">
                                    {{ lab_order.performing_lab }}
                                </span>
                            </span>
                            <span class="shrink-0 text-xs text-muted-foreground">{{ lab_order.cpt_code }}</span>
                        </button>
                    </li>
                </ul>
            </div>

            <LabResultForm
                v-else
                :key="selected?.id"
                :action="action"
                :initial="selected"
                :reference-range="reference_range"
                :patient-context="patient_context"
                form-id="lab-result-form"
                @success="handleSuccess"
            />

            <DialogFooter>
                <button
                    v-if="step === 'form'"
                    type="button"
                    @click="backToSearch"
                    class="mr-auto rounded-lg border border-border px-4 py-2 text-sm font-bold text-foreground hover:bg-muted/40"
                >
                    {{ $t('lab_results.form.back') }}
                </button>
                <button
                    type="button"
                    @click="handleOpenUpdate(false)"
                    class="rounded-lg border border-border px-4 py-2 text-sm font-bold text-foreground hover:bg-muted/40"
                >
                    {{ $t('lab_results.form.cancel') }}
                </button>
                <button
                    v-if="step === 'form'"
                    type="submit"
                    form="lab-result-form"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90"
                >
                    {{ $t('lab_results.form.submit') }}
                </button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
