<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import {
    HoverCardContent,
    HoverCardPortal,
    HoverCardRoot,
    HoverCardTrigger,
} from 'reka-ui'

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
})

const full_name = computed(() =>
    [props.user.prefix, props.user.first_name, props.user.middle_name, props.user.last_name, props.user.suffix]
        .filter(Boolean).join(' ')
)

const user_initials = computed(() =>
    `${props.user.first_name?.[0] ?? ''}${props.user.last_name?.[0] ?? ''}`.toUpperCase()
)

const role_name = computed(() => props.user.roles?.[0]?.name ?? null)

const role_badge_classes = {
    'Super Admin': 'bg-purple-100 text-purple-700',
    'Doctor': 'bg-cerulean-100 text-cerulean-700',
    'Nurse': 'bg-cerulean-100 text-cerulean-700',
    'Medical Assistant': 'bg-tropical-teal-100 text-tropical-teal-700',
    'Staff': 'bg-muted text-muted-foreground',
}

const translated_role = computed(() =>
    role_name.value ? trans(`enums.user_role.${role_name.value}`) : null
)
</script>

<template>
    <HoverCardRoot :open-delay="150" :close-delay="100">
        <HoverCardTrigger as-child>
            <slot>
                <span class="cursor-default underline decoration-dotted underline-offset-2">
                    {{ user.first_name }} {{ user.last_name }}
                </span>
            </slot>
        </HoverCardTrigger>

        <HoverCardPortal>
            <HoverCardContent
                side="top"
                align="center"
                :side-offset="8"
                :collision-padding="16"
                class="z-50 w-64 rounded-xl border border-border bg-popover p-4 shadow-xl focus:outline-none"
            >
                <div class="flex items-center gap-3">
                    <img
                        :src="user.avatar_url"
                        :alt="user_initials"
                        class="size-12 shrink-0 rounded-full object-cover ring-2 ring-primary/20"
                    />
                    <div class="min-w-0">
                        <p class="truncate font-bold text-foreground">{{ full_name }}</p>
                        <span
                            v-if="translated_role"
                            class="mt-1 inline-block rounded-full px-2.5 py-0.5 text-xs font-bold"
                            :class="role_badge_classes[role_name] ?? 'bg-muted text-muted-foreground'"
                        >
                            {{ translated_role }}
                        </span>
                    </div>
                </div>

                <Link
                    :href="route('users.show', user.id)"
                    class="mt-4 flex w-full items-center justify-center rounded-lg border border-border px-3 py-1.5 text-xs font-bold text-foreground hover:bg-muted/40"
                >
                    {{ $t('users.popover.view_profile') }}
                </Link>
            </HoverCardContent>
        </HoverCardPortal>
    </HoverCardRoot>
</template>
