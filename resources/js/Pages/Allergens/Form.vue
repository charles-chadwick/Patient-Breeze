<script setup>
import { computed } from 'vue'
import { setLayoutProps } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import AllergenForm from '@/Pages/Allergens/Partials/Form.vue'

defineOptions({ layout: DashboardLayout })

const props = defineProps({
    allergen: {
        type: Object,
        default: null,
    },
    category_options: {
        type: Array,
        default: () => [],
    },
})

const isEditing = computed(() => props.allergen !== null)

setLayoutProps({
    breadcrumbs: computed(() => {
        if (isEditing.value) {
            return [
                { label: trans('nav.allergens'), href: route('allergens.index') },
                { label: trans('allergies.catalog.form.edit_title', { name: props.allergen.name }) },
            ]
        }
        return [
            { label: trans('nav.allergens'), href: route('allergens.index') },
            { label: trans('allergies.catalog.form.new_title') },
        ]
    }),
})

const formAction = computed(() =>
    isEditing.value ? route('allergens.update', props.allergen.id) : route('allergens.store')
)

const formMethod = computed(() => (isEditing.value ? 'put' : 'post'))
</script>

<template>
    <div class="rounded-xl border border-border bg-card shadow-sm">
        <div class="border-b border-border px-6 py-4">
            <h2 class="font-bold text-foreground">
                {{ isEditing ? $t('allergies.catalog.form.edit_title', { name: allergen.name }) : $t('allergies.catalog.form.new_title') }}
            </h2>
        </div>
        <div class="px-6 py-5">
            <AllergenForm
                :action="formAction"
                :method="formMethod"
                :allergen="allergen"
                :category-options="category_options"
                :cancel-href="route('allergens.index')"
            />
        </div>
    </div>
</template>
