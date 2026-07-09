<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import AvatarUpload from '@/Components/AvatarUpload.vue'

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
    avatar: null,
    remove_avatar: false,
})

function submit() {
    form[props.method](props.action)
}
</script>

<template>
    <form class="grid gap-6" @submit.prevent="submit">
        <!-- Avatar -->
        <div class="rounded-xl border border-border bg-card shadow-sm">
            <div class="border-b border-border px-6 py-4">
                <h2 class="font-bold text-foreground">{{ $t('users.form.section_avatar') }}</h2>
            </div>
            <div class="px-6 py-5">
                <AvatarUpload
                    v-model="form.avatar"
                    v-model:removed="form.remove_avatar"
                    :current-url="user?.avatar_url ?? null"
                    :error="form.errors.avatar"
                />
            </div>
        </div>

        <!-- Identity -->
        <div class="rounded-xl border border-border bg-card shadow-sm">
            <div class="border-b border-border px-6 py-4">
                <h2 class="font-bold text-foreground">{{ $t('users.form.section_identity') }}</h2>
            </div>
            <div class="grid gap-5 px-6 py-5 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Prefix -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('users.form.label_prefix') }}
                    </label>
                    <input
                        v-model="form.prefix"
                        type="text"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :placeholder="$t('users.form.placeholder_prefix')"
                    />
                    <p v-if="form.errors.prefix" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.prefix }}</p>
                </div>

                <!-- First Name -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('users.form.label_first_name') }} <span class="text-vibrant-coral-500">*</span>
                    </label>
                    <input
                        v-model="form.first_name"
                        type="text"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-vibrant-coral-400': form.errors.first_name }"
                        :placeholder="$t('users.form.placeholder_first_name')"
                    />
                    <p v-if="form.errors.first_name" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.first_name }}</p>
                </div>

                <!-- Middle Name -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('users.form.label_middle_name') }}
                    </label>
                    <input
                        v-model="form.middle_name"
                        type="text"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :placeholder="$t('users.form.placeholder_middle_name')"
                    />
                </div>

                <!-- Last Name -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('users.form.label_last_name') }} <span class="text-vibrant-coral-500">*</span>
                    </label>
                    <input
                        v-model="form.last_name"
                        type="text"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-vibrant-coral-400': form.errors.last_name }"
                        :placeholder="$t('users.form.placeholder_last_name')"
                    />
                    <p v-if="form.errors.last_name" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.last_name }}</p>
                </div>

                <!-- Suffix -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('users.form.label_suffix') }}
                    </label>
                    <input
                        v-model="form.suffix"
                        type="text"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :placeholder="$t('users.form.placeholder_suffix')"
                    />
                </div>

                <!-- Email -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('users.form.label_email') }} <span class="text-vibrant-coral-500">*</span>
                    </label>
                    <input
                        v-model="form.email"
                        type="email"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-vibrant-coral-400': form.errors.email }"
                        :placeholder="$t('users.form.placeholder_email')"
                    />
                    <p v-if="form.errors.email" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.email }}</p>
                </div>
            </div>
        </div>

        <!-- Access -->
        <div class="rounded-xl border border-border bg-card shadow-sm">
            <div class="border-b border-border px-6 py-4">
                <h2 class="font-bold text-foreground">{{ $t('users.form.section_access') }}</h2>
            </div>
            <div class="grid gap-5 px-6 py-5 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Role -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('users.form.label_role') }} <span class="text-vibrant-coral-500">*</span>
                    </label>
                    <select
                        v-model="form.role"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-vibrant-coral-400': form.errors.role }"
                    >
                        <option value="">{{ $t('common.actions.select_placeholder') }}</option>
                        <option v-for="opt in role_options" :key="opt" :value="opt">
                            {{ $t('enums.user_role.' + opt) }}
                        </option>
                    </select>
                    <p v-if="form.errors.role" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.role }}</p>
                </div>
            </div>
        </div>

        <!-- Password -->
        <div class="rounded-xl border border-border bg-card shadow-sm">
            <div class="border-b border-border px-6 py-4">
                <h2 class="font-bold text-foreground">
                    {{ user ? $t('users.form.section_change_password') : $t('users.form.section_password') }}
                </h2>
                <p v-if="user" class="mt-0.5 text-xs text-muted-foreground">{{ $t('users.form.password_hint') }}</p>
            </div>
            <div class="grid gap-5 px-6 py-5 sm:grid-cols-2">
                <!-- Password -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ user ? $t('users.form.label_new_password') : $t('users.form.label_password') }} <span v-if="!user" class="text-vibrant-coral-500">*</span>
                    </label>
                    <input
                        v-model="form.password"
                        type="password"
                        autocomplete="new-password"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-vibrant-coral-400': form.errors.password }"
                        :placeholder="$t('users.form.placeholder_password')"
                    />
                    <p v-if="form.errors.password" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.password }}</p>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('users.form.label_confirm_password') }} <span v-if="!user" class="text-vibrant-coral-500">*</span>
                    </label>
                    <input
                        v-model="form.password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :placeholder="$t('users.form.placeholder_confirm_password')"
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
                {{ $t('common.actions.cancel') }}
            </Link>
            <button
                type="submit"
                :disabled="form.processing"
                class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90 disabled:opacity-50"
            >
                {{ form.processing ? $t('users.form.submitting') : $t('users.form.submit') }}
            </button>
        </div>
    </form>
</template>
