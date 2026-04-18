<script setup>
import { computed } from 'vue'
import { Link, setLayoutProps } from '@inertiajs/vue3'
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
    title: computed(() =>
        isEditing.value
            ? `Edit ${props.user.first_name} ${props.user.last_name}`
            : 'New User'
    ),
})

const backHref = computed(() => route('users.index'))

const formAction = computed(() =>
    isEditing.value ? route('users.update', props.user.id) : route('users.store')
)

const formMethod = computed(() => (isEditing.value ? 'put' : 'post'))
</script>

<template>
    <div class="grid gap-6">
        <div>
            <Link :href="backHref" class="text-sm font-bold text-primary hover:underline">
                ← Back to Users
            </Link>
        </div>

        <UserForm
            :action="formAction"
            :method="formMethod"
            :user="user"
            :cancel-href="backHref"
            :role_options="role_options"
        />
    </div>
</template>
