<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import { TriangleAlert, ShieldCheck, CircleHelp } from 'lucide-vue-next'
import { formatDate, DATE_SHORT } from '@/lib/utils'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'

const props = defineProps({
    patientId: {
        type: Number,
        required: true,
    },
    banner: {
        type: Object,
        required: true,
    },
})

const has_allergies = computed(() => props.banner.allergies.length > 0)
const is_critical = computed(() => props.banner.is_critical)
const no_known_allergies = computed(() => props.banner.no_known_allergies)
const not_reviewed = computed(() => !has_allergies.value && !no_known_allergies.value)

/**
 * Three states, three treatments: a red alert when anything severe is on file,
 * a softer alert for milder allergies or an unreviewed list, and a quiet
 * confirmation once staff have recorded "no known allergies".
 */
const tone = computed(() => {
    if (is_critical.value) {
        return 'border-vibrant-coral-300 bg-vibrant-coral-50'
    }

    if (has_allergies.value || not_reviewed.value) {
        return 'border-soft-apricot-300 bg-soft-apricot-50'
    }

    return 'border-border bg-muted/30'
})

const icon = computed(() => {
    if (has_allergies.value) {
        return TriangleAlert
    }

    return not_reviewed.value ? CircleHelp : ShieldCheck
})

const icon_tone = computed(() => {
    if (is_critical.value) {
        return 'text-vibrant-coral-600'
    }

    if (has_allergies.value || not_reviewed.value) {
        return 'text-soft-apricot-700'
    }

    return 'text-muted-foreground'
})

const review_note = computed(() => {
    if (props.banner.reviewed_at === null) {
        return null
    }

    const date = formatDate(props.banner.reviewed_at, DATE_SHORT)
    const key = no_known_allergies.value ? 'confirmed' : 'reviewed'

    return props.banner.reviewed_by
        ? trans(`allergies.banner.${key}_by`, { name: props.banner.reviewed_by, date })
        : trans(`allergies.banner.${key}_on`, { date })
})

const confirm_open = ref(false)
const reviewing = ref(false)

function confirmReview() {
    reviewing.value = true

    router.post(route('patients.allergies.review', props.patientId), {}, {
        preserveScroll: true,
        onFinish: () => {
            reviewing.value = false
            confirm_open.value = false
        },
    })
}
</script>

<template>
    <div
        data-testid="allergy-banner"
        class="flex flex-wrap items-center gap-x-4 gap-y-2 rounded-xl border px-6 py-3"
        :class="tone"
    >
        <div class="flex shrink-0 items-center gap-2">
            <component :is="icon" class="size-5" :class="icon_tone" />
            <span class="text-xs font-bold uppercase tracking-wide" :class="icon_tone">
                {{ $t('allergies.banner.label') }}
            </span>
        </div>

        <div v-if="has_allergies" class="flex min-w-0 flex-wrap items-center gap-2">
            <span
                v-for="allergy in banner.allergies"
                :key="allergy.id"
                data-testid="allergy-banner-item"
                class="rounded-full px-2.5 py-0.5 text-xs font-bold"
                :class="allergy.is_critical
                    ? 'bg-vibrant-coral-600 text-white'
                    : 'bg-soft-apricot-200 text-soft-apricot-900'"
            >
                {{ allergy.allergen }} ({{ allergy.severity_label }})
            </span>
        </div>

        <p v-else-if="no_known_allergies" class="text-sm font-bold text-foreground">
            {{ $t('allergies.banner.no_known_allergies') }}
        </p>

        <p v-else class="text-sm font-bold text-soft-apricot-800">
            {{ $t('allergies.banner.not_reviewed') }}
        </p>

        <p v-if="review_note" class="text-xs text-muted-foreground">{{ review_note }}</p>

        <button
            v-if="!has_allergies"
            type="button"
            data-testid="allergy-mark-reviewed"
            @click="confirm_open = true"
            class="ml-auto shrink-0 rounded-lg border border-border bg-background px-3 py-1.5 text-xs font-bold text-foreground hover:bg-muted/40"
        >
            {{ no_known_allergies ? $t('allergies.banner.mark_reviewed') : $t('allergies.banner.mark_no_known') }}
        </button>

        <ConfirmDialog
            v-model:open="confirm_open"
            :title="trans('allergies.banner.confirm_no_known_title')"
            :description="no_known_allergies
                ? trans('allergies.banner.confirm_reviewed_description')
                : trans('allergies.banner.confirm_no_known_description')"
            :confirm-label="no_known_allergies
                ? trans('allergies.banner.mark_reviewed')
                : trans('allergies.banner.mark_no_known')"
            :destructive="false"
            :processing="reviewing"
            @confirm="confirmReview"
        />
    </div>
</template>
