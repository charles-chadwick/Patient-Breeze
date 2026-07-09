<script setup>
import { computed } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'

defineOptions({ layout: DashboardLayout })

const props = defineProps({
    two_factor_enabled: {
        type: Boolean,
        default: false,
    },
    two_factor_pending: {
        type: Boolean,
        default: false,
    },
    password_confirmed: {
        type: Boolean,
        default: false,
    },
    qr_code_svg: {
        type: String,
        default: null,
    },
    recovery_codes: {
        type: Array,
        default: () => [],
    },
    preferences: {
        type: Object,
        default: () => ({}),
    },
    preference_options: {
        type: Object,
        default: () => ({}),
    },
})

const confirm_form = useForm({ code: '' })

const preferences_form = useForm({ settings: { ...props.preferences } })

function save_preferences() {
    preferences_form.put(route('settings.update'), { preserveScroll: true })
}

const show_setup = computed(() => props.two_factor_pending)
const show_recovery_codes = computed(() => props.two_factor_pending || props.two_factor_enabled)

function enable() {
    router.post(route('two-factor.enable'), {}, { preserveScroll: true })
}

function confirm() {
    confirm_form.post(route('two-factor.confirm'), {
        preserveScroll: true,
        onSuccess: () => confirm_form.reset('code'),
    })
}

function disable() {
    router.delete(route('two-factor.disable'), { preserveScroll: true })
}

function regenerate_recovery_codes() {
    router.post(route('two-factor.recovery-codes'), {}, { preserveScroll: true })
}
</script>

<template>
    <div class="mx-auto max-w-2xl space-y-6">
        <div class="rounded-xl border border-border bg-card p-8 shadow-sm">
            <h1 class="text-xl font-bold text-foreground">{{ $t('two_factor.settings_heading') }}</h1>
            <p class="mt-2 text-sm text-muted-foreground">{{ $t('two_factor.settings_description') }}</p>

            <!-- Status -->
            <div class="mt-6 flex items-center gap-2">
                <span
                    class="inline-flex h-2.5 w-2.5 rounded-full"
                    :class="two_factor_enabled ? 'bg-primary' : 'bg-muted-foreground/40'"
                />
                <span class="text-sm font-bold text-foreground">
                    {{ two_factor_enabled ? $t('two_factor.status_enabled') : $t('two_factor.status_disabled') }}
                </span>
            </div>

            <!-- Enable button (disabled state) -->
            <div v-if="!two_factor_enabled && !two_factor_pending" class="mt-6">
                <button
                    type="button"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90"
                    @click="enable"
                >
                    {{ $t('two_factor.enable') }}
                </button>
            </div>

            <!-- Setup / confirm (pending state) -->
            <div v-if="show_setup" class="mt-6 border-t border-border pt-6">
                <h2 class="text-sm font-bold text-foreground">{{ $t('two_factor.setup_heading') }}</h2>
                <p class="mt-1 text-sm text-muted-foreground">{{ $t('two_factor.setup_instructions') }}</p>

                <div class="mt-4 inline-block rounded-lg border border-border bg-white p-3" v-html="qr_code_svg" />

                <form class="mt-4 flex max-w-xs items-start gap-2" @submit.prevent="confirm">
                    <div class="flex-1">
                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                            {{ $t('two_factor.confirm_label') }}
                        </label>
                        <input
                            v-model="confirm_form.code"
                            type="text"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            autofocus
                            class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                            :class="{ 'border-vibrant-coral-400': confirm_form.errors.code }"
                            :placeholder="$t('two_factor.confirm_placeholder')"
                        />
                        <p v-if="confirm_form.errors.code" class="mt-1 text-xs text-vibrant-coral-600">{{ confirm_form.errors.code }}</p>
                    </div>
                    <button
                        type="submit"
                        :disabled="confirm_form.processing"
                        class="mt-6 rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90 disabled:opacity-50"
                    >
                        {{ $t('two_factor.confirm') }}
                    </button>
                </form>
            </div>

            <!-- Recovery codes -->
            <div v-if="show_recovery_codes" class="mt-6 border-t border-border pt-6">
                <h2 class="text-sm font-bold text-foreground">{{ $t('two_factor.recovery_codes_heading') }}</h2>
                <p class="mt-1 text-sm text-muted-foreground">{{ $t('two_factor.recovery_codes_description') }}</p>

                <!-- Password confirmation required to reveal codes -->
                <Link
                    v-if="!password_confirmed"
                    :href="route('password.confirm')"
                    class="mt-4 inline-block text-sm font-bold text-primary hover:underline"
                >
                    {{ $t('two_factor.reveal_recovery_codes') }}
                </Link>

                <template v-else>
                    <div class="mt-4 grid grid-cols-2 gap-2 rounded-lg bg-background p-4 font-mono text-sm text-foreground">
                        <span v-for="recovery_code in recovery_codes" :key="recovery_code">{{ recovery_code }}</span>
                    </div>

                    <button
                        type="button"
                        class="mt-4 text-sm font-bold text-primary hover:underline"
                        @click="regenerate_recovery_codes"
                    >
                        {{ $t('two_factor.regenerate_recovery_codes') }}
                    </button>
                </template>
            </div>

            <!-- Disable -->
            <div v-if="two_factor_enabled" class="mt-6 border-t border-border pt-6">
                <button
                    type="button"
                    class="rounded-lg border border-vibrant-coral-400 px-4 py-2 text-sm font-bold text-vibrant-coral-600 hover:bg-vibrant-coral-50"
                    @click="disable"
                >
                    {{ $t('two_factor.disable') }}
                </button>
            </div>
        </div>

        <!-- Preferences -->
        <div class="rounded-xl border border-border bg-card p-8 shadow-sm">
            <h2 class="text-xl font-bold text-foreground">{{ $t('settings.preferences_heading') }}</h2>
            <p class="mt-2 text-sm text-muted-foreground">{{ $t('settings.preferences_description') }}</p>

            <form class="mt-6 space-y-5" @submit.prevent="save_preferences">
                <div v-for="(options, setting_key) in preference_options" :key="setting_key">
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('enums.setting_key.' + setting_key) }}
                    </label>
                    <select
                        v-model="preferences_form.settings[setting_key]"
                        class="w-full max-w-xs rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                    >
                        <option v-for="option in options" :key="option" :value="option">{{ option }}</option>
                    </select>
                    <p v-if="preferences_form.errors['settings.' + setting_key]" class="mt-1 text-xs text-vibrant-coral-600">
                        {{ preferences_form.errors['settings.' + setting_key] }}
                    </p>
                </div>

                <button
                    type="submit"
                    :disabled="preferences_form.processing"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90 disabled:opacity-50"
                >
                    {{ $t('settings.save') }}
                </button>
            </form>
        </div>
    </div>
</template>
