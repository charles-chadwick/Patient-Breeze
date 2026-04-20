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
    <div class="flex min-h-screen items-center justify-center bg-background px-4">
        <div class="w-full max-w-sm">
            <div class="mb-8 text-center">
                <span class="text-2xl font-bold text-primary">PB Health</span>
            </div>
            <slot />
        </div>
    </div>
</template>
