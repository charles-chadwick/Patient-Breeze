<script setup>
import { computed, ref } from 'vue'
import { formatDate, DATE_LONG } from '@/lib/utils'

const props = defineProps({
    patient: {
        type: Object,
        required: true,
    },
})

const full_name = computed(() =>
    [props.patient.prefix, props.patient.first_name, props.patient.middle_name, props.patient.last_name, props.patient.suffix]
        .filter(Boolean).join(' ')
)

const patient_initials = computed(() =>
    `${props.patient.first_name[0]}${props.patient.last_name[0]}`.toUpperCase()
)

const show_avatar_modal = ref(false)
</script>

<template>
    <div class="rounded-xl border border-border bg-white shadow-sm">
        <div class="flex items-center gap-5 border-b border-border px-6 py-5">
            <button
                type="button"
                class="shrink-0 cursor-zoom-in focus:outline-none"
                @click="show_avatar_modal = true"
            >
                <img
                    :src="patient.avatar_url"
                    :alt="patient_initials"
                    class="size-16 rounded-full object-cover ring-2 ring-primary/20"
                />
            </button>
            <div>
                <h2 class="text-lg font-bold text-foreground">{{ full_name }}</h2>
                <p class="mt-0.5 font-mono text-sm text-muted-foreground">{{ patient.mrn }}</p>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-x-8 gap-y-4 px-6 py-5 sm:grid-cols-3 lg:grid-cols-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">Full Name</p>
                <p class="mt-1 text-sm font-bold text-foreground">{{ full_name }}</p>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">Date of Birth</p>
                <p class="mt-1 text-sm text-foreground">{{ formatDate(patient.date_of_birth, DATE_LONG) }}</p>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">Gender at Birth</p>
                <p class="mt-1 text-sm text-foreground">{{ patient.gender_at_birth }}</p>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">Gender Identity</p>
                <p class="mt-1 text-sm text-foreground">{{ patient.gender_identity }}</p>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">Blood Type</p>
                <p class="mt-1 text-sm text-foreground">{{ patient.blood_type ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">Email</p>
                <p class="mt-1 text-sm text-foreground">{{ patient.email }}</p>
            </div>
        </div>
    </div>

    <Teleport to="body">
        <div
            v-if="show_avatar_modal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
            @click.self="show_avatar_modal = false"
        >
            <div class="relative max-w-sm w-full">
                <button
                    type="button"
                    class="absolute -right-3 -top-3 flex size-8 items-center justify-center rounded-full bg-white shadow-md text-muted-foreground hover:text-foreground focus:outline-none"
                    @click="show_avatar_modal = false"
                >
                    ✕
                </button>
                <img
                    :src="patient.avatar_url"
                    :alt="patient_initials"
                    class="w-full rounded-2xl object-cover shadow-xl ring-4 ring-white bg-white"
                />
            </div>
        </div>
    </Teleport>
</template>
