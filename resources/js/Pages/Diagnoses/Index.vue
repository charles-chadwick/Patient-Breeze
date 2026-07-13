<script setup>
import { computed, ref } from 'vue'
import { Link, router, setLayoutProps } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import SearchInput from '@/Components/SearchInput.vue'
import SortDropdown from '@/Components/SortDropdown.vue'
import DiagnosisCatalogModal from '@/Components/DiagnosisCatalogModal.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'

defineOptions({ layout: DashboardLayout })

setLayoutProps({
    breadcrumbs: computed(() => [
        { label: trans('nav.diagnoses') },
    ]),
})

const props = defineProps({
    diagnoses: {
        type: Object,
        required: true,
    },
    search: {
        type: String,
        default: '',
    },
    sort_by: {
        type: String,
        default: 'diagnosis',
    },
    direction: {
        type: String,
        default: 'asc',
    },
})

const sort_options = computed(() => [
    { label: trans('diagnoses.catalog.index.sort.diagnosis'), value: 'diagnosis' },
    { label: trans('diagnoses.catalog.index.sort.icd10_code'), value: 'icd10_code' },
])

const modal_open = ref(false)
const editing_diagnosis = ref(null)

function openCreate() {
    editing_diagnosis.value = null
    modal_open.value = true
}

function openEdit(diagnosis) {
    editing_diagnosis.value = diagnosis
    modal_open.value = true
}

const confirm_open = ref(false)
const deleting_diagnosis = ref(null)
const deleting = ref(false)

function askDelete(diagnosis) {
    deleting_diagnosis.value = diagnosis
    confirm_open.value = true
}

function confirmDelete() {
    if (!deleting_diagnosis.value) {
        return
    }

    deleting.value = true

    router.delete(route('diagnoses.destroy', deleting_diagnosis.value.id), {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false
            confirm_open.value = false
            deleting_diagnosis.value = null
        },
    })
}
</script>

<template>
    <div class="rounded border border-border bg-card shadow-sm">
        <div class="flex flex-col gap-3 border-b border-border px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <h2 class="font-bold text-foreground">{{ $t('diagnoses.catalog.index.heading') }}</h2>
                <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary">
                    {{ diagnoses.total }}
                </span>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <button
                    type="button"
                    @click="openCreate"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90"
                >
                    {{ $t('diagnoses.catalog.index.new') }}
                </button>
                <SortDropdown
                    :sort-by="props.sort_by"
                    :direction="props.direction"
                    :options="sort_options"
                    :params="{ search: props.search || undefined }"
                    route-name="diagnoses.index"
                />
                <SearchInput
                    :model-value="props.search"
                    :params="{ sort_by: props.sort_by, direction: props.direction }"
                    :placeholder="$t('diagnoses.catalog.index.search_placeholder')"
                    route-name="diagnoses.index"
                    class="w-full sm:w-72"
                />
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10 bg-card shadow-[0_1px_0_0_var(--color-border)]">
                    <tr class="text-left">
                        <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('diagnoses.catalog.index.column_diagnosis') }}</th>
                        <th class="hidden px-6 py-3 font-bold text-muted-foreground sm:table-cell">{{ $t('diagnoses.catalog.index.column_icd10_code') }}</th>
                        <th class="px-6 py-3 font-bold text-muted-foreground"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="diagnoses.data.length === 0">
                        <td colspan="3" class="px-6 py-10 text-center text-sm text-muted-foreground">
                            {{ $t('diagnoses.catalog.index.empty') }}
                        </td>
                    </tr>
                    <tr
                        v-for="(diagnosis, index) in diagnoses.data"
                        :key="diagnosis.id"
                        class="border-l-2 border-transparent transition-colors hover:border-primary hover:bg-primary/5"
                        :class="index % 2 !== 0 ? 'bg-muted/20' : 'bg-card'"
                    >
                        <td class="px-6 py-4">
                            <button
                                type="button"
                                @click="openEdit(diagnosis)"
                                class="text-left font-bold text-foreground hover:text-primary hover:underline"
                            >
                                {{ diagnosis.diagnosis }}
                            </button>
                        </td>
                        <td class="hidden px-6 py-4 text-muted-foreground sm:table-cell">{{ diagnosis.icd10_code }}</td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    type="button"
                                    @click="openEdit(diagnosis)"
                                    class="rounded-lg border border-border px-3 py-1.5 text-xs font-bold text-foreground hover:bg-muted/40"
                                >
                                    {{ $t('common.actions.edit') }}
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg border border-border px-3 py-1.5 text-xs font-bold text-vibrant-coral-600 hover:bg-vibrant-coral-50"
                                    @click="askDelete(diagnosis)"
                                >
                                    {{ $t('common.actions.delete') }}
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between border-t border-border px-6 py-4">
            <p class="text-sm text-muted-foreground">
                {{ $t('common.pagination.summary', { from: diagnoses.from, to: diagnoses.to, total: diagnoses.total, label: $t('diagnoses.catalog.index.record_label') }) }}
            </p>
            <div class="flex items-center gap-1">
                <Link
                    v-if="diagnoses.prev_page_url"
                    :href="diagnoses.prev_page_url"
                    class="rounded-lg border border-border px-3 py-1.5 text-sm font-bold text-foreground hover:bg-muted/40"
                >
                    ←
                </Link>
                <template v-for="link in diagnoses.links.slice(1, -1)" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded-lg border px-3 py-1.5 text-sm font-bold"
                        :class="link.active
                            ? 'border-primary bg-primary text-white'
                            : 'border-border text-foreground hover:bg-muted/40'"
                    >
                        {{ link.label }}
                    </Link>
                    <span
                        v-else
                        class="px-2 py-1.5 text-sm text-muted-foreground"
                    >
                        {{ link.label }}
                    </span>
                </template>
                <Link
                    v-if="diagnoses.next_page_url"
                    :href="diagnoses.next_page_url"
                    class="rounded-lg border border-border px-3 py-1.5 text-sm font-bold text-foreground hover:bg-muted/40"
                >
                    →
                </Link>
            </div>
        </div>

        <DiagnosisCatalogModal
            v-model:open="modal_open"
            :diagnosis="editing_diagnosis"
        />

        <ConfirmDialog
            v-model:open="confirm_open"
            :title="trans('common.actions.delete')"
            :description="trans('diagnoses.catalog.index.delete_confirm')"
            :confirm-label="trans('common.actions.delete')"
            :processing="deleting"
            @confirm="confirmDelete"
        />
    </div>
</template>
