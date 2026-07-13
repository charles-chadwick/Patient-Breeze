<script setup>
import { computed } from 'vue'
import { setLayoutProps } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import DiagnosisForm from '@/Pages/Diagnoses/Partials/Form.vue'

defineOptions({ layout: DashboardLayout })

const props = defineProps({
    diagnosis: {
        type: Object,
        default: null,
    },
})

const isEditing = computed(() => props.diagnosis !== null)

setLayoutProps({
    breadcrumbs: computed(() => {
        if (isEditing.value) {
            return [
                { label: trans('nav.diagnoses'), href: route('diagnoses.index') },
                { label: trans('diagnoses.catalog.form.edit_title', { name: props.diagnosis.diagnosis }) },
            ]
        }
        return [
            { label: trans('nav.diagnoses'), href: route('diagnoses.index') },
            { label: trans('diagnoses.catalog.form.new_title') },
        ]
    }),
})

const formAction = computed(() =>
    isEditing.value ? route('diagnoses.update', props.diagnosis.id) : route('diagnoses.store')
)

const formMethod = computed(() => (isEditing.value ? 'put' : 'post'))
</script>

<template>
    <div class="rounded-xl border border-border bg-card shadow-sm">
        <div class="border-b border-border px-6 py-4">
            <h2 class="font-bold text-foreground">
                {{ isEditing ? $t('diagnoses.catalog.form.edit_title', { name: diagnosis.diagnosis }) : $t('diagnoses.catalog.form.new_title') }}
            </h2>
        </div>
        <div class="px-6 py-5">
            <DiagnosisForm
                :action="formAction"
                :method="formMethod"
                :diagnosis="diagnosis"
                :cancel-href="route('diagnoses.index')"
            />
        </div>
    </div>
</template>
