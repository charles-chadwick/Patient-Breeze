<script setup>
import { Head } from '@inertiajs/vue3'

defineProps({
    title: {
        type: String,
        default: '',
    },
})
</script>

<template>
    <Head :title="title" />
    <div class="flex min-h-screen">
        <!-- Left panel: gradient branding -->
        <div class="hidden flex-col items-center justify-center bg-gradient-to-br from-cerulean-500 to-cerulean-500 px-12 text-white lg:flex lg:w-5/12">
            <div class="max-w-xs">
                <p class="mb-1 text-2xl font-bold">PB Health</p>
                <p class="mb-4 text-lg font-semibold">Patient Portal</p>
                <p class="text-base text-white/80">Your health, at your fingertips.</p>
            </div>
        </div>
        <!-- Right panel: login form -->
        <div class="flex flex-1 items-center justify-center bg-white px-6 py-12">
            <div class="w-full max-w-sm">
                <div class="mb-8 lg:hidden">
                    <p class="text-xl font-bold text-cerulean-600">PB Health</p>
                    <p class="text-sm text-slate-500">Patient Portal</p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
