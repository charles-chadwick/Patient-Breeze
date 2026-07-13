<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import { ShieldAlert } from 'lucide-vue-next'

const props = defineProps({
    status: { type: Number, required: true },
})

const title = computed(() => trans(`errors.status.${props.status}.title`))
const description = computed(() => trans(`errors.status.${props.status}.description`))
</script>

<template>
    <Head :title="title" />
    <div class="flex min-h-screen items-center justify-center bg-background px-4">
        <div class="w-full max-w-md text-center">
            <div class="mb-6 flex justify-center">
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-primary/10">
                    <ShieldAlert class="h-8 w-8 text-primary" />
                </div>
            </div>
            <p class="mb-2 text-sm font-bold uppercase tracking-wide text-accent">{{ status }}</p>
            <h1 class="mb-3 text-2xl font-bold text-foreground">{{ title }}</h1>
            <p class="mb-8 text-sm text-muted-foreground">{{ description }}</p>
            <Link
                href="/"
                class="inline-block rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90"
            >
                {{ $t('errors.back_home') }}
            </Link>
        </div>
    </div>
</template>
