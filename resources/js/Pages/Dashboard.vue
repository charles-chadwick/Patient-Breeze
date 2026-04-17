<script setup>
import { setLayoutProps } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'

defineOptions({ layout: DashboardLayout })

setLayoutProps({ title: 'Dashboard' })

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
})

const stat_cards = [
    { label: 'Total Patients', key: 'total_patients' },
    { label: 'Appointments Today', key: 'appointments_today' },
    { label: 'Pending Appointments', key: 'pending_reviews' },
]
</script>

<template>
    <div class="grid gap-6">
        <!-- Stats row -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div
                v-for="card in stat_cards"
                :key="card.key"
                class="rounded-xl border border-border bg-white p-5 shadow-sm"
            >
                <p class="text-sm font-bold text-muted-foreground">{{ card.label }}</p>
                <p class="mt-1 text-3xl font-bold text-foreground">{{ props.stats[card.key] }}</p>
            </div>
        </div>

        <!-- Recent activity -->
        <div class="rounded-xl border border-border bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-base font-bold text-foreground">Recent Activity</h2>
            <p class="text-sm text-muted-foreground">No recent activity to display.</p>
        </div>
    </div>
</template>
