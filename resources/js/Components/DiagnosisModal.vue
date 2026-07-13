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
import DiagnosisForm from '@/Components/DiagnosisForm.vue'

const props = defineProps({
    open: {
        type: Boolean,
        required: true,
    },
    action: {
        type: String,
        required: true,
    },
    statusOptions: {
        type: Array,
        required: true,
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
        const response = await fetch(route('diagnoses.search', { search: query }), {
            headers: { Accept: 'application/json' },
        })
        const payload = await response.json()
        results.value = payload.diagnoses ?? []
    } catch {
        results.value = []
    } finally {
        searching.value = false
        searched.value = true
    }
}

function selectDiagnosis(diagnosis) {
    selected.value = diagnosis
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

// Reset the modal to a clean search state whenever it is opened.
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
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>
                    {{ step === 'search' ? $t('diagnoses.search.heading') : $t('diagnoses.form.heading') }}
                </DialogTitle>
                <DialogDescription>
                    {{ step === 'search' ? $t('diagnoses.search.hint') : $t('diagnoses.form.hint') }}
                </DialogDescription>
            </DialogHeader>

            <div v-if="step === 'search'" class="grid gap-4">
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <input
                        v-model="search_value"
                        type="search"
                        :placeholder="$t('diagnoses.search.placeholder')"
                        class="h-10 w-full rounded-lg border border-border bg-background pl-9 pr-4 text-sm text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    />
                </div>

                <p v-if="searching" class="px-1 py-6 text-center text-sm text-muted-foreground">
                    {{ $t('diagnoses.search.searching') }}
                </p>
                <p v-else-if="!searched" class="px-1 py-6 text-center text-sm text-muted-foreground">
                    {{ $t('diagnoses.search.prompt') }}
                </p>
                <p v-else-if="results.length === 0" class="px-1 py-6 text-center text-sm text-muted-foreground">
                    {{ $t('diagnoses.search.empty') }}
                </p>

                <ul v-else class="max-h-72 divide-y divide-border overflow-y-auto rounded-lg border border-border">
                    <li v-for="diagnosis in results" :key="diagnosis.id">
                        <button
                            type="button"
                            @click="selectDiagnosis(diagnosis)"
                            class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left hover:bg-muted/40"
                        >
                            <span class="min-w-0">
                                <span class="block truncate font-bold text-foreground">{{ diagnosis.diagnosis }}</span>
                            </span>
                            <span class="shrink-0 text-xs font-bold text-muted-foreground">{{ diagnosis.icd10_code }}</span>
                        </button>
                    </li>
                </ul>
            </div>

            <DiagnosisForm
                v-else
                :key="selected?.id"
                :action="action"
                :status-options="statusOptions"
                :initial="selected"
                form-id="diagnosis-form"
                @success="handleSuccess"
            />

            <DialogFooter>
                <button
                    v-if="step === 'form'"
                    type="button"
                    @click="backToSearch"
                    class="mr-auto rounded-lg border border-border px-4 py-2 text-sm font-bold text-foreground hover:bg-muted/40"
                >
                    {{ $t('diagnoses.form.back') }}
                </button>
                <button
                    type="button"
                    @click="handleOpenUpdate(false)"
                    class="rounded-lg border border-border px-4 py-2 text-sm font-bold text-foreground hover:bg-muted/40"
                >
                    {{ $t('diagnoses.form.cancel') }}
                </button>
                <button
                    v-if="step === 'form'"
                    type="submit"
                    form="diagnosis-form"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90"
                >
                    {{ $t('diagnoses.form.submit') }}
                </button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
