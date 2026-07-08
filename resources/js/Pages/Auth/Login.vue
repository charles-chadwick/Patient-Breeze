<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { setLayoutProps } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import GuestLayout from '@/Layouts/GuestLayout.vue'

defineOptions({ layout: GuestLayout })

setLayoutProps({ title: computed(() => trans('login.title')) })

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

function submit() {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <div class="rounded-xl border border-border bg-white p-8 shadow-sm">
        <h1 class="mb-6 text-xl font-bold text-foreground">{{ $t('login.heading') }}</h1>

        <form class="grid gap-5" @submit.prevent="submit">
            <!-- Email -->
            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                    {{ $t('login.label_email') }} <span class="text-vibrant-coral-500">*</span>
                </label>
                <input
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                    autofocus
                    class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                    :class="{ 'border-vibrant-coral-400': form.errors.email }"
                    :placeholder="$t('login.placeholder_email')"
                />
                <p v-if="form.errors.email" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.email }}</p>
            </div>

            <!-- Password -->
            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                    {{ $t('login.label_password') }} <span class="text-vibrant-coral-500">*</span>
                </label>
                <input
                    v-model="form.password"
                    type="password"
                    autocomplete="current-password"
                    class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                    :class="{ 'border-vibrant-coral-400': form.errors.password }"
                    :placeholder="$t('login.placeholder_password')"
                />
                <p v-if="form.errors.password" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.password }}</p>
            </div>

            <!-- Remember me -->
            <div class="flex items-center gap-2">
                <input
                    id="remember"
                    v-model="form.remember"
                    type="checkbox"
                    class="rounded border-border text-primary focus:ring-primary/50"
                />
                <label for="remember" class="text-sm text-muted-foreground">{{ $t('login.remember_me') }}</label>
            </div>

            <!-- Submit -->
            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90 disabled:opacity-50"
            >
                {{ form.processing ? $t('login.submitting') : $t('login.submit') }}
            </button>
        </form>
    </div>
</template>
