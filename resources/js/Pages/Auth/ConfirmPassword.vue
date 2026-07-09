<script setup>
import { computed } from 'vue'
import { setLayoutProps, useForm } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import GuestLayout from '@/Layouts/GuestLayout.vue'

defineOptions({ layout: GuestLayout })

setLayoutProps({ title: computed(() => trans('two_factor.confirm_password_title')) })

const form = useForm({
    password: '',
})

function submit() {
    form.post(route('password.confirm.store'), {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <div class="rounded-xl border border-border bg-card p-8 shadow-sm">
        <h1 class="mb-2 text-xl font-bold text-foreground">{{ $t('two_factor.confirm_password_heading') }}</h1>
        <p class="mb-6 text-sm text-muted-foreground">{{ $t('two_factor.confirm_password_instructions') }}</p>

        <form class="grid gap-5" @submit.prevent="submit">
            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                    {{ $t('two_factor.confirm_password_label') }}
                </label>
                <input
                    v-model="form.password"
                    type="password"
                    autocomplete="current-password"
                    autofocus
                    class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                    :class="{ 'border-vibrant-coral-400': form.errors.password }"
                    :placeholder="$t('two_factor.confirm_password_placeholder')"
                />
                <p v-if="form.errors.password" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.password }}</p>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90 disabled:opacity-50"
            >
                {{ $t('two_factor.confirm_password_submit') }}
            </button>
        </form>
    </div>
</template>
