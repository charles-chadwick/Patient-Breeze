<script setup>
import { computed, nextTick, ref } from 'vue'
import { setLayoutProps, useForm } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import GuestLayout from '@/Layouts/GuestLayout.vue'

defineOptions({ layout: GuestLayout })

setLayoutProps({ title: computed(() => trans('two_factor.challenge_title')) })

const using_recovery_code = ref(false)

const form = useForm({
    code: '',
    recovery_code: '',
})

function toggle_recovery_code() {
    using_recovery_code.value = !using_recovery_code.value

    nextTick(() => {
        if (using_recovery_code.value) {
            form.reset('code')
        } else {
            form.reset('recovery_code')
        }
    })
}

function submit() {
    form.post(route('two-factor.login.store'), {
        onFinish: () => form.reset('code', 'recovery_code'),
    })
}
</script>

<template>
    <div class="rounded-xl border border-border bg-white p-8 shadow-sm">
        <h1 class="mb-2 text-xl font-bold text-foreground">{{ $t('two_factor.challenge_heading') }}</h1>
        <p class="mb-6 text-sm text-muted-foreground">
            {{ using_recovery_code ? $t('two_factor.challenge_recovery_instructions') : $t('two_factor.challenge_code_instructions') }}
        </p>

        <form class="grid gap-5" @submit.prevent="submit">
            <!-- Authentication code -->
            <div v-if="!using_recovery_code">
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                    {{ $t('two_factor.code_label') }}
                </label>
                <input
                    v-model="form.code"
                    type="text"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    autofocus
                    class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                    :class="{ 'border-vibrant-coral-400': form.errors.code }"
                    :placeholder="$t('two_factor.code_placeholder')"
                />
                <p v-if="form.errors.code" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.code }}</p>
            </div>

            <!-- Recovery code -->
            <div v-else>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                    {{ $t('two_factor.recovery_code_label') }}
                </label>
                <input
                    v-model="form.recovery_code"
                    type="text"
                    autocomplete="one-time-code"
                    autofocus
                    class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                    :class="{ 'border-vibrant-coral-400': form.errors.recovery_code }"
                    :placeholder="$t('two_factor.recovery_code_placeholder')"
                />
                <p v-if="form.errors.recovery_code" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.recovery_code }}</p>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90 disabled:opacity-50"
            >
                {{ form.processing ? $t('two_factor.submitting') : $t('two_factor.submit') }}
            </button>

            <button
                type="button"
                class="text-sm font-bold text-primary hover:underline"
                @click="toggle_recovery_code"
            >
                {{ using_recovery_code ? $t('two_factor.use_authentication_code') : $t('two_factor.use_recovery_code') }}
            </button>
        </form>
    </div>
</template>
