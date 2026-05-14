<script setup>
import { Link, setLayoutProps } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import SearchInput from '@/Components/SearchInput.vue'
import SortDropdown from '@/Components/SortDropdown.vue'

defineOptions({ layout: DashboardLayout })

setLayoutProps({
    breadcrumbs: [
        { label: 'Users' },
    ],
})

const props = defineProps({
    users: {
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

const sort_options = [
    { label: 'Last Name', value: 'last_name' },
    { label: 'First Name', value: 'first_name' },
    { label: 'Email', value: 'email' },
]

function userInitials(user) {
    return `${user.first_name[0]}${user.last_name[0]}`.toUpperCase()
}

const role_badge_classes = {
    'Super Admin': 'bg-purple-100 text-purple-700',
    'Doctor': 'bg-blue-100 text-blue-700',
    'Nurse': 'bg-teal-100 text-teal-700',
    'Medical Assistant': 'bg-cyan-100 text-cyan-700',
    'Staff': 'bg-gray-100 text-gray-600',
    'Patient': 'bg-green-100 text-green-700',
}
</script>

<template>
    <div class="rounded border border-border bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-border px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <h2 class="font-bold text-foreground">All Users</h2>
                <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary">
                    {{ users.total }}
                </span>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <Link
                    :href="route('users.create')"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90"
                >
                    + New User
                </Link>
                <SortDropdown
                    :sort-by="props.sort_by"
                    :direction="props.direction"
                    :options="sort_options"
                    :params="{ search: props.search || undefined }"
                    route-name="users.index"
                />
                <SearchInput
                    :model-value="props.search"
                    :params="{ sort_by: props.sort_by, direction: props.direction }"
                    placeholder="Search by name or email…"
                    route-name="users.index"
                    class="w-full sm:w-72"
                />
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10 bg-white shadow-[0_1px_0_0_var(--color-border)]">
                    <tr class="text-left">
                        <th class="px-6 py-3 font-bold text-muted-foreground">Name</th>
                        <th class="hidden px-6 py-3 font-bold text-muted-foreground sm:table-cell">Email</th>
                        <th class="hidden px-6 py-3 font-bold text-muted-foreground md:table-cell">Role</th>
                        <th class="px-6 py-3 font-bold text-muted-foreground"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="users.data.length === 0">
                        <td colspan="4" class="px-6 py-10 text-center text-sm text-muted-foreground">
                            No users found.
                        </td>
                    </tr>
                    <tr
                        v-for="(user, index) in users.data"
                        :key="user.id"
                        class="border-l-2 border-transparent transition-colors hover:border-primary hover:bg-primary/5"
                        :class="index % 2 !== 0 ? 'bg-muted/20' : 'bg-white'"
                    >
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <img
                                    :src="user.avatar_url"
                                    :alt="userInitials(user)"
                                    class="size-8 shrink-0 rounded-full object-cover"
                                />
                                <div>
                                    <Link
                                        :href="route('users.show', user.id)"
                                        class="font-bold text-foreground hover:text-primary hover:underline"
                                    >
                                        {{ [user.prefix, user.first_name, user.middle_name, user.last_name, user.suffix].filter(Boolean).join(' ') }}
                                    </Link>
                                    <p class="mt-0.5 text-xs text-muted-foreground sm:hidden">{{ user.email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="hidden px-6 py-4 text-muted-foreground sm:table-cell">{{ user.email }}</td>
                        <td class="hidden px-6 py-4 md:table-cell">
                            <span
                                v-if="user.roles[0]"
                                class="rounded-full px-2.5 py-0.5 text-xs font-bold"
                                :class="role_badge_classes[user.roles[0].name] ?? 'bg-gray-100 text-gray-600'"
                            >
                                {{ user.roles[0].name }}
                            </span>
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <Link
                                :href="route('users.edit', user.id)"
                                class="text-sm font-bold text-primary hover:underline"
                            >
                                Edit
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between border-t border-border px-6 py-4">
            <p class="text-sm text-muted-foreground">
                Showing {{ users.from }}–{{ users.to }} of {{ users.total }} users
            </p>
            <div class="flex items-center gap-1">
                <Link
                    v-if="users.prev_page_url"
                    :href="users.prev_page_url"
                    class="rounded-lg border border-border px-3 py-1.5 text-sm font-bold text-foreground hover:bg-muted/40"
                >
                    ←
                </Link>
                <template v-for="link in users.links.slice(1, -1)" :key="link.label">
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
                    v-if="users.next_page_url"
                    :href="users.next_page_url"
                    class="rounded-lg border border-border px-3 py-1.5 text-sm font-bold text-foreground hover:bg-muted/40"
                >
                    →
                </Link>
            </div>
        </div>
    </div>
</template>
