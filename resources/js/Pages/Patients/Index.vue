<script setup>
import { computed } from 'vue'
import { Link, setLayoutProps } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import SearchInput from '@/Components/SearchInput.vue'
import SortDropdown from '@/Components/SortDropdown.vue'
import { formatDate, DATE_SHORT } from '@/lib/utils'

defineOptions({ layout: DashboardLayout })

setLayoutProps({
    breadcrumbs: computed(() => [
        { label: trans('nav.patients') },
    ]),
})

const props = defineProps({
    patients: {
        type: Object,
        required: true,
    },
    search: {
        type: String,
        default: '',
    },
    sort_by: {
        type: String,
        default: 'last_name',
    },
    direction: {
        type: String,
        default: 'asc',
    },
})

const sort_options = computed(() => [
    { label: trans('patients.sort.last_name'), value: 'last_name' },
    { label: trans('patients.sort.first_name'), value: 'first_name' },
    { label: trans('patients.sort.date_of_birth'), value: 'date_of_birth' },
    { label: trans('patients.sort.blood_type'), value: 'blood_type' },
])

function patientInitials(patient) {
    return `${patient.first_name[0]}${patient.last_name[0]}`.toUpperCase()
}

function calculateAge(dateOfBirth) {
    const today = new Date()
    const dob = new Date(dateOfBirth)
    let age = today.getFullYear() - dob.getFullYear()
    const m = today.getMonth() - dob.getMonth()
    if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
        age--
    }
    return age
}

function bloodTypeBadgeClass(bloodType) {
    if (!bloodType) return 'bg-muted text-muted-foreground'
    return bloodType.includes('+')
        ? 'bg-accent/20 text-accent-foreground'
        : 'bg-primary/10 text-primary'
}

function genderBadgeClass(gender) {
    const map = {
        Male: 'bg-primary/10 text-primary',
        Female: 'bg-accent/20 text-accent-foreground',
        Unknown: 'bg-muted text-muted-foreground',
    }
    return map[gender] ?? 'bg-muted text-muted-foreground'
}
</script>

<template>
    <div class="rounded border border-border bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-border px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <h2 class="font-bold text-foreground">{{ $t('patients.index.heading') }}</h2>
                <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary">
                    {{ patients.total }}
                </span>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <Link
                    :href="route('patients.create')"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90"
                >
                    {{ $t('patients.index.new_patient') }}
                </Link>
                <SortDropdown
                    :sort-by="props.sort_by"
                    :direction="props.direction"
                    :options="sort_options"
                    :params="{ search: props.search || undefined }"
                    route-name="patients.index"
                />
                <SearchInput
                    :model-value="props.search"
                    :params="{ sort_by: props.sort_by, direction: props.direction }"
                    :placeholder="$t('patients.index.search_placeholder')"
                    route-name="patients.index"
                    class="w-full sm:w-72"
                />
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10 bg-white shadow-[0_1px_0_0_var(--color-border)]">
                    <tr class="text-left">
                        <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('patients.index.column_name') }}</th>
                        <th class="hidden px-6 py-3 font-bold text-muted-foreground sm:table-cell">{{ $t('patients.index.column_mrn') }}</th>
                        <th class="hidden px-6 py-3 font-bold text-muted-foreground md:table-cell">{{ $t('patients.index.column_date_of_birth') }}</th>
                        <th class="hidden px-6 py-3 font-bold text-muted-foreground md:table-cell">{{ $t('patients.index.column_gender') }}</th>
                        <th class="hidden px-6 py-3 font-bold text-muted-foreground lg:table-cell">{{ $t('patients.index.column_blood_type') }}</th>
                        <th class="hidden px-6 py-3 font-bold text-muted-foreground lg:table-cell">{{ $t('patients.index.column_email') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="patients.data.length === 0">
                        <td colspan="6" class="px-6 py-10 text-center text-sm text-muted-foreground">
                            {{ $t('patients.index.empty') }}
                        </td>
                    </tr>
                    <tr
                        v-for="(patient, index) in patients.data"
                        :key="patient.id"
                        class="border-l-2 border-transparent transition-colors hover:border-primary hover:bg-primary/5"
                        :class="index % 2 !== 0 ? 'bg-muted/20' : 'bg-white'"
                    >
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <img
                                    :src="patient.avatar_url"
                                    :alt="patientInitials(patient)"
                                    class="size-8 shrink-0 rounded-full object-cover"
                                />
                                <div>
                                    <Link
                                        :href="route('patients.show', patient.id)"
                                        class="font-bold text-primary hover:underline"
                                    >
                                        {{ patient.prefix }} {{ patient.first_name }} {{ patient.last_name }}{{ patient.suffix ? `, ${patient.suffix}` : '' }}
                                    </Link>
                                    <p class="mt-0.5 font-mono text-xs text-muted-foreground sm:hidden">{{ patient.mrn }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="hidden px-6 py-4 font-mono text-sm text-muted-foreground sm:table-cell">{{ patient.mrn }}</td>
                        <td class="hidden px-6 py-4 text-foreground md:table-cell">
                            {{ formatDate(patient.date_of_birth, DATE_SHORT) }}
                            <span class="ml-1 text-xs text-muted-foreground">· {{ calculateAge(patient.date_of_birth) }}y</span>
                        </td>
                        <td class="hidden px-6 py-4 md:table-cell">
                            <span
                                class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                                :class="genderBadgeClass(patient.gender_at_birth)"
                            >
                                {{ $t('enums.gender_at_birth.' + patient.gender_at_birth) }}
                            </span>
                        </td>
                        <td class="hidden px-6 py-4 lg:table-cell">
                            <span
                                v-if="patient.blood_type"
                                class="rounded-full px-2.5 py-0.5 text-xs font-bold"
                                :class="bloodTypeBadgeClass(patient.blood_type)"
                            >
                                {{ $t('enums.blood_type.' + patient.blood_type) }}
                            </span>
                            <span v-else class="text-muted-foreground">{{ $t('common.placeholders.em_dash') }}</span>
                        </td>
                        <td class="hidden px-6 py-4 text-muted-foreground lg:table-cell">{{ patient.email }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between border-t border-border px-6 py-4">
            <p class="text-sm text-muted-foreground">
                {{ $t('common.pagination.summary', { from: patients.from, to: patients.to, total: patients.total, label: $t('patients.index.record_label') }) }}
            </p>
            <div class="flex items-center gap-1">
                <Link
                    v-if="patients.prev_page_url"
                    :href="patients.prev_page_url"
                    class="rounded-lg border border-border px-3 py-1.5 text-sm font-bold text-foreground hover:bg-muted/40"
                >
                    ←
                </Link>
                <template v-for="link in patients.links.slice(1, -1)" :key="link.label">
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
                    v-if="patients.next_page_url"
                    :href="patients.next_page_url"
                    class="rounded-lg border border-border px-3 py-1.5 text-sm font-bold text-foreground hover:bg-muted/40"
                >
                    →
                </Link>
            </div>
        </div>
    </div>
</template>
