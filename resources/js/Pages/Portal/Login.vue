<script setup>
import { computed } from 'vue'
import PortalGuestLayout from '@/Layouts/PortalGuestLayout.vue'
import { setLayoutProps, useForm } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'

defineOptions({ layout: PortalGuestLayout })

setLayoutProps({ title: computed(() => trans('portal.login.title')) })

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
        <h1 class="mb-2 text-2xl font-bold text-slate-800">{{ $t('portal.login.heading') }}</h1>
        <p class="mb-8 text-sm text-slate-500">{{ $t('portal.login.subtitle') }}</p>

        <form class="grid gap-5" @submit.prevent="submit">
            <div>
                <label for="email" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">
                    {{ $t('portal.login.label_email') }} <span class="text-vibrant-coral-500">*</span>
                </label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                    autofocus
                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-cerulean-500/50"
                    :class="{ 'border-vibrant-coral-400': form.errors.email }"
                    :placeholder="$t('portal.login.placeholder_email')"
                />
                <p v-if="form.errors.email" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.email }}</p>
            </div>

            <div>
                <label for="password" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">
                    {{ $t('portal.login.label_password') }} <span class="text-vibrant-coral-500">*</span>
                </label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    autocomplete="current-password"
                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-cerulean-500/50"
                    :class="{ 'border-vibrant-coral-400': form.errors.password }"
                    :placeholder="$t('portal.login.placeholder_password')"
                />
                <p v-if="form.errors.password" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.password }}</p>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-xl bg-cerulean-600 px-4 py-3 text-sm font-bold text-white hover:bg-cerulean-700 disabled:opacity-50"
            >
                {{ form.processing ? $t('portal.login.submitting') : $t('portal.login.submit') }}
            </button>
        </form>
    </div>
</template>
