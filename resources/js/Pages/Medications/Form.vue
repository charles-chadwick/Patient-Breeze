<script setup>
import { computed } from 'vue'
import { setLayoutProps } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import MedicationForm from '@/Pages/Medications/Partials/Form.vue'

defineOptions({ layout: DashboardLayout })

const props = defineProps({
    medication: {
        type: Object,
        default: null,
    },
    dose_form_options: {
        type: Array,
        required: true,
    },
})

const isEditing = computed(() => props.medication !== null)

setLayoutProps({
    breadcrumbs: computed(() => {
        if (isEditing.value) {
            return [
                { label: trans('nav.medications'), href: route('medications.index') },
                { label: trans('medications.catalog.form.edit_title', { name: props.medication.name }) },
            ]
        }
        return [
            { label: trans('nav.medications'), href: route('medications.index') },
            { label: trans('medications.catalog.form.new_title') },
        ]
    }),
})

const formAction = computed(() =>
    isEditing.value ? route('medications.update', props.medication.id) : route('medications.store')
)

const formMethod = computed(() => (isEditing.value ? 'put' : 'post'))
</script>

<template>
    <div class="rounded-xl border border-border bg-card shadow-sm">
        <div class="border-b border-border px-6 py-4">
            <h2 class="font-bold text-foreground">
                {{ isEditing ? $t('medications.catalog.form.edit_title', { name: medication.name }) : $t('medications.catalog.form.new_title') }}
            </h2>
        </div>
        <div class="px-6 py-5">
            <MedicationForm
                :action="formAction"
                :method="formMethod"
                :medication="medication"
                :cancel-href="route('medications.index')"
                :dose_form_options="dose_form_options"
            />
        </div>
    </div>
</template>
