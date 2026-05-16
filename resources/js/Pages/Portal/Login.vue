<script setup>
import PortalGuestLayout from '@/Layouts/PortalGuestLayout.vue'
import { setLayoutProps, useForm } from '@inertiajs/vue3'

defineOptions({ layout: PortalGuestLayout })

setLayoutProps({ title: 'Sign In – Patient Portal' })

const form = useForm({
    email: '',
    password: '',
})

function submit() {
    form.post(route('portal.login'), {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <div>
        <h1 class="mb-2 text-2xl font-bold text-slate-800">Sign In</h1>
        <p class="mb-8 text-sm text-slate-500">Access your health records and appointments.</p>

        <form class="grid gap-5" @submit.prevent="submit">
            <div>
                <label for="email" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">
                    Email <span class="text-red-500">*</span>
                </label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                    autofocus
                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/50"
                    :class="{ 'border-red-400': form.errors.email }"
                    placeholder="you@example.com"
                />
                <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
            </div>

            <div>
                <label for="password" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">
                    Password <span class="text-red-500">*</span>
                </label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    autocomplete="current-password"
                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/50"
                    :class="{ 'border-red-400': form.errors.password }"
                    placeholder="••••••••"
                />
                <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-xl bg-teal-600 px-4 py-3 text-sm font-bold text-white hover:bg-teal-700 disabled:opacity-50"
            >
                {{ form.processing ? 'Signing in…' : 'Sign In to Your Portal' }}
            </button>
        </form>
    </div>
</template>
