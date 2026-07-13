<script setup>
import { watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { CircleCheck, CircleAlert, Info, X } from 'lucide-vue-next'
import { useToast, ToastType } from '@/composables/useToast'

const page = usePage()
const { state, pushToast, dismissToast } = useToast()

// Icon and accent styling per toast type.
const type_styles = {
    [ToastType.Success]: { icon: CircleCheck, accent: 'text-primary', ring: 'border-primary/30' },
    [ToastType.Error]: { icon: CircleAlert, accent: 'text-vibrant-coral-600', ring: 'border-vibrant-coral-300' },
    [ToastType.Info]: { icon: Info, accent: 'text-cerulean-600', ring: 'border-cerulean-300' },
}

// Surface server flash messages as toasts. Inertia hands back a fresh `flash`
// object on every response, so this fires even when the same message repeats.
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            pushToast(flash.success, { type: ToastType.Success })
        }

        if (flash?.error) {
            pushToast(flash.error, { type: ToastType.Error })
        }
    },
    { immediate: true },
)
</script>

<template>
    <Teleport to="body">
        <div class="pointer-events-none fixed inset-x-0 bottom-0 z-50 flex flex-col items-center gap-2 p-4 sm:items-end sm:p-6">
            <TransitionGroup
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="translate-y-2 opacity-0 sm:translate-x-4 sm:translate-y-0"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
                move-class="transition duration-200 ease-out"
            >
                <div
                    v-for="toast in state.toasts"
                    :key="toast.id"
                    role="status"
                    aria-live="polite"
                    class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl border bg-card p-4 shadow-lg"
                    :class="type_styles[toast.type].ring"
                >
                    <component
                        :is="type_styles[toast.type].icon"
                        class="mt-0.5 size-5 shrink-0"
                        :class="type_styles[toast.type].accent"
                    />
                    <p class="flex-1 text-sm font-bold text-foreground">{{ toast.message }}</p>
                    <button
                        type="button"
                        class="shrink-0 rounded p-0.5 text-muted-foreground transition-colors hover:text-foreground"
                        :aria-label="$t('common.a11y.dismiss')"
                        @click="dismissToast(toast.id)"
                    >
                        <X class="size-4" />
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>
