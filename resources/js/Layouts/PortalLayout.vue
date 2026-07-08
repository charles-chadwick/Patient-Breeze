<script setup>
import { computed } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { LogOut } from 'lucide-vue-next'

const page = usePage()
const patient = computed(() => page.props.auth?.portal_patient)

function logout() {
    router.post(route('portal.logout'))
}
</script>

<template>
    <Head :title="$t('common.brand.portal_label')" />
    <div class="min-h-screen bg-slate-50">
        <!-- Top navigation bar -->
        <header class="border-b border-slate-100 bg-white shadow-sm">
            <div class="mx-auto flex h-16 max-w-5xl items-center justify-between px-6">
                <div class="flex items-center gap-6">
                    <span class="text-lg font-bold text-cerulean-600">{{ $t('common.brand.portal_name') }}</span>
                    <nav class="flex items-center gap-4 text-sm font-medium text-slate-600">
                        <Link :href="route('portal.dashboard')" class="hover:text-cerulean-600">{{ $t('nav.portal.dashboard') }}</Link>
                        <Link :href="route('portal.messages.index')" class="hover:text-cerulean-600">{{ $t('nav.portal.messages') }}</Link>
                        <Link :href="route('portal.settings.index')" class="hover:text-cerulean-600">{{ $t('nav.portal.settings') }}</Link>
                    </nav>
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-cerulean-100 text-sm font-bold text-cerulean-700">
                            {{ patient?.first_name?.[0] ?? 'P' }}
                        </div>
                        <span class="text-sm font-medium text-slate-700">
                            {{ patient?.first_name }} {{ patient?.last_name }}
                        </span>
                    </div>
                    <button
                        class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm text-slate-500 hover:bg-slate-100 hover:text-slate-700"
                        @click="logout"
                    >
                        <LogOut class="size-4" />
                        {{ $t('common.actions.sign_out') }}
                    </button>
                </div>
            </div>
        </header>
        <!-- Page content -->
        <main class="mx-auto max-w-5xl px-6 py-8">
            <slot />
        </main>
    </div>
</template>
