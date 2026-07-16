<script setup>
import { computed } from 'vue'
import { setLayoutProps } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import InsuranceCompanyForm from '@/Pages/InsuranceCompanies/Partials/Form.vue'

defineOptions({ layout: DashboardLayout })

const props = defineProps({
    insurance_company: {
        type: Object,
        default: null,
    },
})

const isEditing = computed(() => props.insurance_company !== null)

setLayoutProps({
    breadcrumbs: computed(() => {
        if (isEditing.value) {
            return [
                { label: trans('nav.insurance_companies'), href: route('insurance-companies.index') },
                { label: trans('insurance_companies.form.edit_title', { name: props.insurance_company.name }) },
            ]
        }
        return [
            { label: trans('nav.insurance_companies'), href: route('insurance-companies.index') },
            { label: trans('insurance_companies.form.new_title') },
        ]
    }),
})

const formAction = computed(() =>
    isEditing.value ? route('insurance-companies.update', props.insurance_company.id) : route('insurance-companies.store')
)

const formMethod = computed(() => (isEditing.value ? 'put' : 'post'))
</script>

<template>
    <div class="rounded-xl border border-border bg-card shadow-sm">
        <div class="border-b border-border px-6 py-4">
            <h2 class="font-bold text-foreground">
                {{ isEditing ? $t('insurance_companies.form.edit_title', { name: insurance_company.name }) : $t('insurance_companies.form.new_title') }}
            </h2>
        </div>
        <div class="px-6 py-5">
            <InsuranceCompanyForm
                :action="formAction"
                :method="formMethod"
                :company="insurance_company"
                :cancel-href="route('insurance-companies.index')"
            />
        </div>
    </div>
</template>
