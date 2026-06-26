<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    flat: {
        type: Boolean,
        default: false,
    },
})

const full_name = computed(() =>
    [props.user.prefix, props.user.first_name, props.user.middle_name, props.user.last_name, props.user.suffix]
        .filter(Boolean).join(' ')
)

const user_initials = computed(() =>
    `${props.user.first_name[0]}${props.user.last_name[0]}`.toUpperCase()
)

const show_avatar_modal = ref(false)

const role_badge_classes = {
    'Super Admin': 'bg-purple-100 text-purple-700',
    'Doctor': 'bg-cerulean-100 text-cerulean-700',
    'Nurse': 'bg-cerulean-100 text-cerulean-700',
    'Medical Assistant': 'bg-tropical-teal-100 text-tropical-teal-700',
    'Staff': 'bg-gray-100 text-gray-600',
}
</script>

<template>
    <div :class="flat ? '' : 'rounded-xl border border-border bg-white shadow-sm'">
        <div :class="['flex items-center gap-5 px-6 py-5', !flat && 'border-b border-border']">
            <button
                type="button"
                class="shrink-0 cursor-zoom-in focus:outline-none"
                @click="show_avatar_modal = true"
            >
                <img
                    :src="user.avatar_url"
                    :alt="user_initials"
                    class="size-16 rounded-full object-cover ring-2 ring-primary/20"
                />
            </button>
            <div>
                <h2 class="text-lg font-bold text-foreground">{{ full_name }}</h2>
                <span
                    v-if="user.roles[0]"
                    class="mt-1 inline-block rounded-full px-2.5 py-0.5 text-xs font-bold"
                    :class="role_badge_classes[user.roles[0].name] ?? 'bg-gray-100 text-gray-600'"
                >
                    {{ user.roles[0].name }}
                </span>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-x-8 gap-y-4 px-6 py-5 sm:grid-cols-3 lg:grid-cols-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">Full Name</p>
                <p class="mt-1 text-sm font-bold text-foreground">{{ full_name }}</p>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">Email</p>
                <p class="mt-1 text-sm text-foreground">{{ user.email }}</p>
            </div>
            <div v-if="user.roles[0]">
                <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">Role</p>
                <p class="mt-1 text-sm text-foreground">{{ user.roles[0].name }}</p>
            </div>
        </div>
    </div>

    <Teleport to="body">
        <div
            v-if="show_avatar_modal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
            @click.self="show_avatar_modal = false"
        >
            <div class="relative w-full max-w-sm">
                <button
                    type="button"
                    class="absolute -right-3 -top-3 flex size-8 items-center justify-center rounded-full bg-white shadow-md text-muted-foreground hover:text-foreground focus:outline-none"
                    @click="show_avatar_modal = false"
                >
                    ✕
                </button>
                <img
                    :src="user.avatar_url"
                    :alt="user_initials"
                    class="w-full rounded-2xl object-cover shadow-xl ring-4 ring-white bg-white"
                />
            </div>
        </div>
    </Teleport>
</template>
