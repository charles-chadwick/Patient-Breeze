<script setup>
import { computed } from 'vue'
import { setLayoutProps, useForm } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import PortalGuestLayout from '@/Layouts/PortalGuestLayout.vue'

defineOptions({ layout: PortalGuestLayout })

setLayoutProps({ title: computed(() => trans('two_factor.confirm_password_title')) })

const form = useForm({
    password: '',
})

function submit() {
    form.post(route('portal.password.confirm.store'), {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <div>
        <h1 class="mb-2 text-2xl font-bold text-slate-800">{{ $t('two_factor.confirm_password_heading') }}</h1>
        <p class="mb-8 text-sm text-slate-500">{{ $t('two_factor.confirm_password_instructions') }}</p>

        <form class="grid gap-5" @submit.prevent="submit">
            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">
                    {{ $t('two_factor.confirm_password_label') }}
                </label>
                <input
                    v-model="form.password"
                    type="password"
                    autocomplete="current-password"
                    autofocus
                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-cerulean-500/50"
                    :class="{ 'border-vibrant-coral-400': form.errors.password }"
                    :placeholder="$t('two_factor.confirm_password_placeholder')"
                />
                <p v-if="form.errors.password" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.password }}</p>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-xl bg-cerulean-600 px-4 py-3 text-sm font-bold text-white hover:bg-cerulean-700 disabled:opacity-50"
            >
                {{ $t('two_factor.confirm_password_submit') }}
            </button>
        </form>
    </div>
</template>
