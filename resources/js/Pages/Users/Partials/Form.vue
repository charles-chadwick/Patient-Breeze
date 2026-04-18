<script setup>
import { Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
    action: {
        type: String,
        required: true,
    },
    method: {
        type: String,
        required: true,
    },
    user: {
        type: Object,
        default: null,
    },
    cancelHref: {
        type: String,
        required: true,
    },
    role_options: {
        type: Array,
        required: true,
    },
})

const form = useForm({
    prefix: props.user?.prefix ?? '',
    first_name: props.user?.first_name ?? '',
    middle_name: props.user?.middle_name ?? '',
    last_name: props.user?.last_name ?? '',
    suffix: props.user?.suffix ?? '',
    email: props.user?.email ?? '',
    role: props.user?.roles?.[0]?.name ?? '',
    password: '',
    password_confirmation: '',
})

function submit() {
    form[props.method](props.action)
}
</script>

<template>
    <form class="grid gap-6" @submit.prevent="submit">
        <!-- Identity -->
        <div class="rounded-xl border border-border bg-white shadow-sm">
            <div class="border-b border-border px-6 py-4">
                <h2 class="font-bold text-foreground">Identity</h2>
            </div>
            <div class="grid gap-5 px-6 py-5 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Prefix -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        Prefix
                    </label>
                    <input
                        v-model="form.prefix"
                        type="text"
                        class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        placeholder="Dr., Mr., Ms.…"
                    />
                    <p v-if="form.errors.prefix" class="mt-1 text-xs text-red-600">{{ form.errors.prefix }}</p>
                </div>

                <!-- First Name -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        First Name <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.first_name"
                        type="text"
                        class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-red-400': form.errors.first_name }"
                        placeholder="First name"
                    />
                    <p v-if="form.errors.first_name" class="mt-1 text-xs text-red-600">{{ form.errors.first_name }}</p>
                </div>

                <!-- Middle Name -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        Middle Name
                    </label>
                    <input
                        v-model="form.middle_name"
                        type="text"
                        class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        placeholder="Middle name"
                    />
                </div>

                <!-- Last Name -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        Last Name <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.last_name"
                        type="text"
                        class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-red-400': form.errors.last_name }"
                        placeholder="Last name"
                    />
                    <p v-if="form.errors.last_name" class="mt-1 text-xs text-red-600">{{ form.errors.last_name }}</p>
                </div>

                <!-- Suffix -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        Suffix
                    </label>
                    <input
                        v-model="form.suffix"
                        type="text"
                        class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        placeholder="MD, DO, Jr.…"
                    />
                </div>

                <!-- Email -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        Email <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.email"
                        type="email"
                        class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-red-400': form.errors.email }"
                        placeholder="email@example.com"
                    />
                    <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
                </div>
            </div>
        </div>

        <!-- Access -->
        <div class="rounded-xl border border-border bg-white shadow-sm">
            <div class="border-b border-border px-6 py-4">
                <h2 class="font-bold text-foreground">Access</h2>
            </div>
            <div class="grid gap-5 px-6 py-5 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Role -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        Role <span class="text-red-500">*</span>
                    </label>
                    <select
                        v-model="form.role"
                        class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-red-400': form.errors.role }"
                    >
                        <option value="">Select…</option>
                        <option v-for="opt in role_options" :key="opt" :value="opt">
                            {{ opt }}
                        </option>
                    </select>
                    <p v-if="form.errors.role" class="mt-1 text-xs text-red-600">{{ form.errors.role }}</p>
                </div>
            </div>
        </div>

        <!-- Password -->
        <div class="rounded-xl border border-border bg-white shadow-sm">
            <div class="border-b border-border px-6 py-4">
                <h2 class="font-bold text-foreground">
                    {{ user ? 'Change Password' : 'Password' }}
                </h2>
                <p v-if="user" class="mt-0.5 text-xs text-muted-foreground">Leave blank to keep the current password.</p>
            </div>
            <div class="grid gap-5 px-6 py-5 sm:grid-cols-2">
                <!-- Password -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ user ? 'New Password' : 'Password' }} <span v-if="!user" class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.password"
                        type="password"
                        autocomplete="new-password"
                        class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-red-400': form.errors.password }"
                        placeholder="Min. 8 characters"
                    />
                    <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        Confirm Password <span v-if="!user" class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        placeholder="Repeat password"
                    />
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3">
            <Link
                :href="cancelHref"
                class="rounded-lg border border-border px-4 py-2 text-sm font-bold text-foreground hover:bg-muted/40"
            >
                Cancel
            </Link>
            <button
                type="submit"
                :disabled="form.processing"
                class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90 disabled:opacity-50"
            >
                {{ form.processing ? 'Saving…' : 'Save User' }}
            </button>
        </div>
    </form>
</template>
