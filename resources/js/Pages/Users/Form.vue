<script setup>
import { computed } from 'vue'
import { setLayoutProps } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import UserForm from '@/Pages/Users/Partials/Form.vue'

defineOptions({ layout: DashboardLayout })

const props = defineProps({
    user: {
        type: Object,
        default: null,
    },
    role_options: {
        type: Array,
        required: true,
    },
})

const isEditing = computed(() => props.user !== null)

setLayoutProps({
    breadcrumbs: computed(() => {
        if (isEditing.value) {
            return [
                { label: 'Users', href: route('users.index') },
                { label: `${props.user.first_name} ${props.user.last_name}`, href: route('users.show', props.user.id) },
                { label: `Edit ${props.user.first_name} ${props.user.last_name}` },
            ]
        }
        return [
            { label: 'Users', href: route('users.index') },
            { label: 'New User' },
        ]
    }),
})

const backHref = computed(() => route('users.index'))

const formAction = computed(() =>
    isEditing.value ? route('users.update', props.user.id) : route('users.store')
)

const formMethod = computed(() => (isEditing.value ? 'put' : 'post'))
</script>

<template>
    <UserForm
        :action="formAction"
        :method="formMethod"
        :user="user"
        :cancel-href="backHref"
        :role_options="role_options"
    />
</template>
