<script setup>
import { computed, ref } from 'vue'
import { Link, router, setLayoutProps } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import SearchInput from '@/Components/SearchInput.vue'
import SortDropdown from '@/Components/SortDropdown.vue'
import LabOrderCatalogModal from '@/Components/LabOrderCatalogModal.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'

defineOptions({ layout: DashboardLayout })

setLayoutProps({
    breadcrumbs: computed(() => [
        { label: trans('nav.lab_orders') },
    ]),
})

const props = defineProps({
    lab_orders: {
        type: Object,
        required: true,
    },
    search: {
        type: String,
        default: '',
    },
    sort_by: {
        type: String,
        default: 'name',
    },
    direction: {
        type: String,
        default: 'asc',
    },
})

const sort_options = computed(() => [
    { label: trans('lab_orders.catalog.index.sort.name'), value: 'name' },
    { label: trans('lab_orders.catalog.index.sort.performing_lab'), value: 'performing_lab' },
    { label: trans('lab_orders.catalog.index.sort.cpt_code'), value: 'cpt_code' },
])

const modal_open = ref(false)
const editing_lab_order = ref(null)

function openCreate() {
    editing_lab_order.value = null
    modal_open.value = true
}

function openEdit(lab_order) {
    editing_lab_order.value = lab_order
    modal_open.value = true
}

const confirm_open = ref(false)
const deleting_lab_order = ref(null)
const deleting = ref(false)

function askDelete(lab_order) {
    deleting_lab_order.value = lab_order
    confirm_open.value = true
}

function confirmDelete() {
    if (!deleting_lab_order.value) {
        return
    }

    deleting.value = true

    router.delete(route('lab-orders.destroy', deleting_lab_order.value.id), {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false
            confirm_open.value = false
            deleting_lab_order.value = null
        },
    })
}
</script>

<template>
    <div class="rounded border border-border bg-card shadow-sm">
        <div class="flex flex-col gap-3 border-b border-border px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <h2 class="font-bold text-foreground">{{ $t('lab_orders.catalog.index.heading') }}</h2>
                <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary">
                    {{ lab_orders.total }}
                </span>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <button
                    type="button"
                    @click="openCreate"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90"
                >
                    {{ $t('lab_orders.catalog.index.new') }}
                </button>
                <SortDropdown
                    :sort-by="props.sort_by"
                    :direction="props.direction"
                    :options="sort_options"
                    :params="{ search: props.search || undefined }"
                    route-name="lab-orders.index"
                />
                <SearchInput
                    :model-value="props.search"
                    :params="{ sort_by: props.sort_by, direction: props.direction }"
                    :placeholder="$t('lab_orders.catalog.index.search_placeholder')"
                    route-name="lab-orders.index"
                    class="w-full sm:w-72"
                />
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10 bg-card shadow-[0_1px_0_0_var(--color-border)]">
                    <tr class="text-left">
                        <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('lab_orders.catalog.index.column_name') }}</th>
                        <th class="hidden px-6 py-3 font-bold text-muted-foreground sm:table-cell">{{ $t('lab_orders.catalog.index.column_performing_lab') }}</th>
                        <th class="hidden px-6 py-3 font-bold text-muted-foreground sm:table-cell">{{ $t('lab_orders.catalog.index.column_cpt_code') }}</th>
                        <th class="px-6 py-3 font-bold text-muted-foreground"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="lab_orders.data.length === 0">
                        <td colspan="4" class="px-6 py-10 text-center text-sm text-muted-foreground">
                            {{ $t('lab_orders.catalog.index.empty') }}
                        </td>
                    </tr>
                    <tr
                        v-for="(lab_order, index) in lab_orders.data"
                        :key="lab_order.id"
                        class="border-l-2 border-transparent transition-colors hover:border-primary hover:bg-primary/5"
                        :class="index % 2 !== 0 ? 'bg-muted/20' : 'bg-card'"
                    >
                        <td class="px-6 py-4">
                            <button
                                type="button"
                                @click="openEdit(lab_order)"
                                class="text-left font-bold text-foreground hover:text-primary hover:underline"
                            >
                                {{ lab_order.name }}
                            </button>
                        </td>
                        <td class="hidden px-6 py-4 text-muted-foreground sm:table-cell">{{ lab_order.performing_lab }}</td>
                        <td class="hidden px-6 py-4 text-muted-foreground sm:table-cell">{{ lab_order.cpt_code }}</td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <Link
                                    :href="route('lab-orders.edit', lab_order.id)"
                                    class="rounded-lg border border-border px-3 py-1.5 text-xs font-bold text-foreground hover:bg-muted/40"
                                >
                                    {{ $t('lab_orders.catalog.index.ranges_action') }}
                                </Link>
                                <button
                                    type="button"
                                    @click="openEdit(lab_order)"
                                    class="rounded-lg border border-border px-3 py-1.5 text-xs font-bold text-foreground hover:bg-muted/40"
                                >
                                    {{ $t('common.actions.edit') }}
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg border border-border px-3 py-1.5 text-xs font-bold text-vibrant-coral-600 hover:bg-vibrant-coral-50"
                                    @click="askDelete(lab_order)"
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
                {{ $t('common.pagination.summary', { from: lab_orders.from, to: lab_orders.to, total: lab_orders.total, label: $t('lab_orders.catalog.index.record_label') }) }}
            </p>
            <div class="flex items-center gap-1">
                <Link
                    v-if="lab_orders.prev_page_url"
                    :href="lab_orders.prev_page_url"
                    class="rounded-lg border border-border px-3 py-1.5 text-sm font-bold text-foreground hover:bg-muted/40"
                >
                    ←
                </Link>
                <template v-for="link in lab_orders.links.slice(1, -1)" :key="link.label">
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
                    v-if="lab_orders.next_page_url"
                    :href="lab_orders.next_page_url"
                    class="rounded-lg border border-border px-3 py-1.5 text-sm font-bold text-foreground hover:bg-muted/40"
                >
                    →
                </Link>
            </div>
        </div>

        <LabOrderCatalogModal
            v-model:open="modal_open"
            :lab-order="editing_lab_order"
        />

        <ConfirmDialog
            v-model:open="confirm_open"
            :title="trans('common.actions.delete')"
            :description="trans('lab_orders.catalog.index.delete_confirm')"
            :confirm-label="trans('common.actions.delete')"
            :processing="deleting"
            @confirm="confirmDelete"
        />
    </div>
</template>
