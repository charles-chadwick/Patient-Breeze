<script setup>
import { computed } from 'vue'
import { setLayoutProps } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import VaccineForm from '@/Pages/Vaccines/Partials/Form.vue'

defineOptions({ layout: DashboardLayout })

const props = defineProps({
    vaccine: {
        type: Object,
        default: null,
    },
})

const isEditing = computed(() => props.vaccine !== null)

setLayoutProps({
    breadcrumbs: computed(() => {
        if (isEditing.value) {
            return [
                { label: trans('nav.vaccines'), href: route('vaccines.index') },
                { label: trans('vaccines.catalog.form.edit_title', { name: props.vaccine.name }) },
            ]
        }
        return [
            { label: trans('nav.vaccines'), href: route('vaccines.index') },
            { label: trans('vaccines.catalog.form.new_title') },
        ]
    }),
})

const formAction = computed(() =>
    isEditing.value ? route('vaccines.update', props.vaccine.id) : route('vaccines.store')
)

const formMethod = computed(() => (isEditing.value ? 'put' : 'post'))
</script>

<template>
    <div class="rounded-xl border border-border bg-card shadow-sm">
        <div class="border-b border-border px-6 py-4">
            <h2 class="font-bold text-foreground">
                {{ isEditing ? $t('vaccines.catalog.form.edit_title', { name: vaccine.name }) : $t('vaccines.catalog.form.new_title') }}
            </h2>
        </div>
        <div class="px-6 py-5">
            <VaccineForm
                :action="formAction"
                :method="formMethod"
                :vaccine="vaccine"
                :cancel-href="route('vaccines.index')"
            />
        </div>
    </div>
</template>
