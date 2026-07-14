<script setup>
import { computed } from 'vue'
import { setLayoutProps } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import LabOrderForm from '@/Pages/LabOrders/Partials/Form.vue'
import LabReferenceRangesManager from '@/Components/LabReferenceRangesManager.vue'

defineOptions({ layout: DashboardLayout })

const props = defineProps({
    lab_order: {
        type: Object,
        default: null,
    },
    reference_ranges: {
        type: Array,
        default: () => [],
    },
    gender_at_birth_options: {
        type: Array,
        default: () => [],
    },
})

const isEditing = computed(() => props.lab_order !== null)

setLayoutProps({
    breadcrumbs: computed(() => {
        if (isEditing.value) {
            return [
                { label: trans('nav.lab_orders'), href: route('lab-orders.index') },
                { label: trans('lab_orders.catalog.form.edit_title', { name: props.lab_order.name }) },
            ]
        }
        return [
            { label: trans('nav.lab_orders'), href: route('lab-orders.index') },
            { label: trans('lab_orders.catalog.form.new_title') },
        ]
    }),
})

const formAction = computed(() =>
    isEditing.value ? route('lab-orders.update', props.lab_order.id) : route('lab-orders.store')
)

const formMethod = computed(() => (isEditing.value ? 'put' : 'post'))
</script>

<template>
    <div class="space-y-6">
        <div class="rounded-xl border border-border bg-card shadow-sm">
            <div class="border-b border-border px-6 py-4">
                <h2 class="font-bold text-foreground">
                    {{ isEditing ? $t('lab_orders.catalog.form.edit_title', { name: lab_order.name }) : $t('lab_orders.catalog.form.new_title') }}
                </h2>
            </div>
            <div class="px-6 py-5">
                <LabOrderForm
                    :action="formAction"
                    :method="formMethod"
                    :lab-order="lab_order"
                    :cancel-href="route('lab-orders.index')"
                />
            </div>
        </div>

        <LabReferenceRangesManager
            v-if="isEditing"
            :lab-order-id="lab_order.id"
            :ranges="reference_ranges"
            :gender-options="gender_at_birth_options"
        />
    </div>
</template>
