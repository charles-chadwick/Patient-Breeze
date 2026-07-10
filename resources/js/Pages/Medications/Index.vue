<script setup>
import { computed } from 'vue'
import { Link, router, setLayoutProps } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import SearchInput from '@/Components/SearchInput.vue'
import SortDropdown from '@/Components/SortDropdown.vue'
import FilterDropdown from '@/Components/FilterDropdown.vue'

defineOptions({ layout: DashboardLayout })

setLayoutProps({
    breadcrumbs: computed(() => [
        { label: trans('nav.medications') },
    ]),
})

const props = defineProps({
    medications: {
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
    filters: {
        type: Object,
        default: () => ({ dose_form: [] }),
    },
    dose_form_options: {
        type: Array,
        default: () => [],
    },
})

const sort_options = computed(() => [
    { label: trans('medications.catalog.index.sort.name'), value: 'name' },
    { label: trans('medications.catalog.index.sort.type'), value: 'type' },
    { label: trans('medications.catalog.index.sort.dose_form'), value: 'dose_form' },
    { label: trans('medications.catalog.index.sort.ndc'), value: 'ndc' },
])

function destroy(medication) {
    if (window.confirm(trans('medications.catalog.index.delete_confirm'))) {
        router.delete(route('medications.destroy', medication.id), { preserveScroll: true })
    }
}
</script>

<template>
    <div class="rounded border border-border bg-card shadow-sm">
        <div class="flex flex-col gap-3 border-b border-border px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <h2 class="font-bold text-foreground">{{ $t('medications.catalog.index.heading') }}</h2>
                <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary">
                    {{ medications.total }}
                </span>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <Link
                    :href="route('medications.create')"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90"
                >
                    {{ $t('medications.catalog.index.new') }}
                </Link>
                <FilterDropdown
                    :label="$t('medications.catalog.index.filter_dose_form')"
                    param-name="dose_form"
                    :selected="props.filters.dose_form"
                    :options="props.dose_form_options"
                    :params="{ search: props.search || undefined, sort_by: props.sort_by, direction: props.direction }"
                    route-name="medications.index"
                />
                <SortDropdown
                    :sort-by="props.sort_by"
                    :direction="props.direction"
                    :options="sort_options"
                    :params="{ search: props.search || undefined, dose_form: props.filters.dose_form }"
                    route-name="medications.index"
                />
                <SearchInput
                    :model-value="props.search"
                    :params="{ sort_by: props.sort_by, direction: props.direction, dose_form: props.filters.dose_form }"
                    :placeholder="$t('medications.catalog.index.search_placeholder')"
                    route-name="medications.index"
                    class="w-full sm:w-72"
                />
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10 bg-card shadow-[0_1px_0_0_var(--color-border)]">
                    <tr class="text-left">
                        <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('medications.catalog.index.column_name') }}</th>
                        <th class="hidden px-6 py-3 font-bold text-muted-foreground sm:table-cell">{{ $t('medications.catalog.index.column_type') }}</th>
                        <th class="hidden px-6 py-3 font-bold text-muted-foreground md:table-cell">{{ $t('medications.catalog.index.column_dosage') }}</th>
                        <th class="hidden px-6 py-3 font-bold text-muted-foreground md:table-cell">{{ $t('medications.catalog.index.column_dose_form') }}</th>
                        <th class="hidden px-6 py-3 font-bold text-muted-foreground lg:table-cell">{{ $t('medications.catalog.index.column_ndc') }}</th>
                        <th class="px-6 py-3 font-bold text-muted-foreground"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="medications.data.length === 0">
                        <td colspan="6" class="px-6 py-10 text-center text-sm text-muted-foreground">
                            {{ $t('medications.catalog.index.empty') }}
                        </td>
                    </tr>
                    <tr
                        v-for="(medication, index) in medications.data"
                        :key="medication.id"
                        class="border-l-2 border-transparent transition-colors hover:border-primary hover:bg-primary/5"
                        :class="index % 2 !== 0 ? 'bg-muted/20' : 'bg-card'"
                    >
                        <td class="px-6 py-4">
                            <Link
                                :href="route('medications.edit', medication.id)"
                                class="font-bold text-foreground hover:text-primary hover:underline"
                            >
                                {{ medication.name }}
                            </Link>
                        </td>
                        <td class="hidden px-6 py-4 text-muted-foreground sm:table-cell">{{ medication.type }}</td>
                        <td class="hidden px-6 py-4 text-muted-foreground md:table-cell">{{ medication.dosage }}</td>
                        <td class="hidden px-6 py-4 text-muted-foreground md:table-cell">{{ medication.dose_form }}</td>
                        <td class="hidden px-6 py-4 text-muted-foreground lg:table-cell">{{ medication.ndc }}</td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <Link
                                    as="button"
                                    type="button"
                                    :href="route('medications.edit', medication.id)"
                                    class="rounded-lg border border-border px-3 py-1.5 text-xs font-bold text-foreground hover:bg-muted/40"
                                >
                                    {{ $t('common.actions.edit') }}
                                </Link>
                                <button
                                    type="button"
                                    class="rounded-lg border border-border px-3 py-1.5 text-xs font-bold text-vibrant-coral-600 hover:bg-vibrant-coral-50"
                                    @click="destroy(medication)"
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
                {{ $t('common.pagination.summary', { from: medications.from, to: medications.to, total: medications.total, label: $t('medications.catalog.index.record_label') }) }}
            </p>
            <div class="flex items-center gap-1">
                <Link
                    v-if="medications.prev_page_url"
                    :href="medications.prev_page_url"
                    class="rounded-lg border border-border px-3 py-1.5 text-sm font-bold text-foreground hover:bg-muted/40"
                >
                    ←
                </Link>
                <template v-for="link in medications.links.slice(1, -1)" :key="link.label">
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
                    v-if="medications.next_page_url"
                    :href="medications.next_page_url"
                    class="rounded-lg border border-border px-3 py-1.5 text-sm font-bold text-foreground hover:bg-muted/40"
                >
                    →
                </Link>
            </div>
        </div>
    </div>
</template>
