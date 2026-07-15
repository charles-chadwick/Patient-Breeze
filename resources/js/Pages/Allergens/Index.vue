<script setup>
import { computed, ref } from 'vue'
import { Link, router, setLayoutProps } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import SearchInput from '@/Components/SearchInput.vue'
import SortDropdown from '@/Components/SortDropdown.vue'
import AllergenCatalogModal from '@/Components/AllergenCatalogModal.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'

defineOptions({ layout: DashboardLayout })

setLayoutProps({
    breadcrumbs: computed(() => [
        { label: trans('nav.allergens') },
    ]),
})

const props = defineProps({
    allergens: {
        type: Object,
        required: true,
    },
    category_options: {
        type: Array,
        default: () => [],
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
    { label: trans('allergies.catalog.index.sort.name'), value: 'name' },
    { label: trans('allergies.catalog.index.sort.category'), value: 'category' },
])

const modal_open = ref(false)
const editing_allergen = ref(null)

function openCreate() {
    editing_allergen.value = null
    modal_open.value = true
}

function openEdit(allergen) {
    editing_allergen.value = allergen
    modal_open.value = true
}

const confirm_open = ref(false)
const deleting_allergen = ref(null)
const deleting = ref(false)

function askDelete(allergen) {
    deleting_allergen.value = allergen
    confirm_open.value = true
}

function confirmDelete() {
    if (!deleting_allergen.value) {
        return
    }

    deleting.value = true

    router.delete(route('allergens.destroy', deleting_allergen.value.id), {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false
            confirm_open.value = false
            deleting_allergen.value = null
        },
    })
}
</script>

<template>
    <div class="rounded border border-border bg-card shadow-sm">
        <div class="flex flex-col gap-3 border-b border-border px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <h2 class="font-bold text-foreground">{{ $t('allergies.catalog.index.heading') }}</h2>
                <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary">
                    {{ allergens.total }}
                </span>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <button
                    type="button"
                    @click="openCreate"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90"
                >
                    {{ $t('allergies.catalog.index.new') }}
                </button>
                <SortDropdown
                    :sort-by="props.sort_by"
                    :direction="props.direction"
                    :options="sort_options"
                    :params="{ search: props.search || undefined }"
                    route-name="allergens.index"
                />
                <SearchInput
                    :model-value="props.search"
                    :params="{ sort_by: props.sort_by, direction: props.direction }"
                    :placeholder="$t('allergies.catalog.index.search_placeholder')"
                    route-name="allergens.index"
                    class="w-full sm:w-72"
                />
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10 bg-card shadow-[0_1px_0_0_var(--color-border)]">
                    <tr class="text-left">
                        <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('allergies.catalog.index.column_name') }}</th>
                        <th class="hidden px-6 py-3 font-bold text-muted-foreground sm:table-cell">{{ $t('allergies.catalog.index.column_category') }}</th>
                        <th class="px-6 py-3 font-bold text-muted-foreground"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="allergens.data.length === 0">
                        <td colspan="3" class="px-6 py-10 text-center text-sm text-muted-foreground">
                            {{ $t('allergies.catalog.index.empty') }}
                        </td>
                    </tr>
                    <tr
                        v-for="(allergen, index) in allergens.data"
                        :key="allergen.id"
                        class="border-l-2 border-transparent transition-colors hover:border-primary hover:bg-primary/5"
                        :class="index % 2 !== 0 ? 'bg-muted/20' : 'bg-card'"
                    >
                        <td class="px-6 py-4">
                            <button
                                type="button"
                                @click="openEdit(allergen)"
                                class="text-left font-bold text-foreground hover:text-primary hover:underline"
                            >
                                {{ allergen.name }}
                            </button>
                        </td>
                        <td class="hidden px-6 py-4 text-muted-foreground sm:table-cell">
                            {{ $t('enums.allergen_category.' + allergen.category) }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    type="button"
                                    @click="openEdit(allergen)"
                                    class="rounded-lg border border-border px-3 py-1.5 text-xs font-bold text-foreground hover:bg-muted/40"
                                >
                                    {{ $t('common.actions.edit') }}
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg border border-border px-3 py-1.5 text-xs font-bold text-vibrant-coral-600 hover:bg-vibrant-coral-50"
                                    @click="askDelete(allergen)"
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
                {{ $t('common.pagination.summary', { from: allergens.from, to: allergens.to, total: allergens.total, label: $t('allergies.catalog.index.record_label') }) }}
            </p>
            <div class="flex items-center gap-1">
                <Link
                    v-if="allergens.prev_page_url"
                    :href="allergens.prev_page_url"
                    class="rounded-lg border border-border px-3 py-1.5 text-sm font-bold text-foreground hover:bg-muted/40"
                >
                    ←
                </Link>
                <template v-for="link in allergens.links.slice(1, -1)" :key="link.label">
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
                    v-if="allergens.next_page_url"
                    :href="allergens.next_page_url"
                    class="rounded-lg border border-border px-3 py-1.5 text-sm font-bold text-foreground hover:bg-muted/40"
                >
                    →
                </Link>
            </div>
        </div>

        <AllergenCatalogModal
            v-model:open="modal_open"
            :allergen="editing_allergen"
            :category-options="category_options"
        />

        <ConfirmDialog
            v-model:open="confirm_open"
            :title="trans('common.actions.delete')"
            :description="trans('allergies.catalog.index.delete_confirm')"
            :confirm-label="trans('common.actions.delete')"
            :processing="deleting"
            @confirm="confirmDelete"
        />
    </div>
</template>
