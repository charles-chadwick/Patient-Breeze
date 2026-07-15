<script setup>
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    action: {
        type: String,
        required: true,
    },
    categoryOptions: {
        type: Array,
        required: true,
    },
    reactionOptions: {
        type: Array,
        required: true,
    },
    severityOptions: {
        type: Array,
        required: true,
    },
    statusOptions: {
        type: Array,
        required: true,
    },
    initial: {
        type: Object,
        default: () => ({}),
    },
    formId: {
        type: String,
        default: 'allergy-form',
    },
})

const emit = defineEmits(['success'])

const today = new Date().toISOString().slice(0, 10)

const form = useForm({
    allergen: props.initial.name ?? '',
    category: props.initial.category ?? props.categoryOptions[0] ?? '',
    reactions: [],
    severity: props.severityOptions[0] ?? '',
    status: props.statusOptions[0] ?? '',
    onset_on: today,
    notes: '',
})

function toggleReaction(reaction) {
    const index = form.reactions.indexOf(reaction)

    if (index === -1) {
        form.reactions.push(reaction)
    } else {
        form.reactions.splice(index, 1)
    }
}

function submit() {
    form.post(props.action, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset()
            emit('success')
        },
    })
}
</script>

<template>
    <form :id="formId" action="#" method="post" @submit.prevent="submit" class="grid gap-5">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                    {{ $t('allergies.form.label_allergen') }} <span class="text-vibrant-coral-500">*</span>
                </label>
                <input
                    v-model="form.allergen"
                    type="text"
                    class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                    :class="{ 'border-vibrant-coral-400': form.errors.allergen }"
                />
                <p v-if="form.errors.allergen" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.allergen }}</p>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                    {{ $t('allergies.form.label_category') }} <span class="text-vibrant-coral-500">*</span>
                </label>
                <select
                    v-model="form.category"
                    class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                    :class="{ 'border-vibrant-coral-400': form.errors.category }"
                >
                    <option v-for="option in categoryOptions" :key="option" :value="option">
                        {{ $t('enums.allergen_category.' + option) }}
                    </option>
                </select>
                <p v-if="form.errors.category" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.category }}</p>
            </div>
        </div>

        <!-- Reactions: multi-select, since a single exposure often causes several. -->
        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('allergies.form.label_reactions') }} <span class="text-vibrant-coral-500">*</span>
            </label>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="option in reactionOptions"
                    :key="option"
                    type="button"
                    :data-testid="`allergy-reaction-${option}`"
                    :aria-pressed="form.reactions.includes(option)"
                    @click="toggleReaction(option)"
                    class="rounded-full border px-3 py-1.5 text-xs font-bold transition-colors"
                    :class="form.reactions.includes(option)
                        ? 'border-primary bg-primary text-white'
                        : 'border-border bg-background text-foreground hover:bg-muted/40'"
                >
                    {{ $t('enums.allergy_reaction.' + option) }}
                </button>
            </div>
            <p class="mt-1.5 text-xs text-muted-foreground">{{ $t('allergies.form.reactions_hint') }}</p>
            <p v-if="form.errors.reactions" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.reactions }}</p>
        </div>

        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                    {{ $t('allergies.form.label_severity') }} <span class="text-vibrant-coral-500">*</span>
                </label>
                <select
                    v-model="form.severity"
                    class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                    :class="{ 'border-vibrant-coral-400': form.errors.severity }"
                >
                    <option v-for="option in severityOptions" :key="option" :value="option">
                        {{ $t('enums.allergy_severity.' + option) }}
                    </option>
                </select>
                <p v-if="form.errors.severity" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.severity }}</p>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                    {{ $t('allergies.form.label_status') }} <span class="text-vibrant-coral-500">*</span>
                </label>
                <select
                    v-model="form.status"
                    class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                    :class="{ 'border-vibrant-coral-400': form.errors.status }"
                >
                    <option v-for="option in statusOptions" :key="option" :value="option">
                        {{ $t('enums.allergy_status.' + option) }}
                    </option>
                </select>
                <p v-if="form.errors.status" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.status }}</p>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                    {{ $t('allergies.form.label_onset_on') }}
                </label>
                <input
                    v-model="form.onset_on"
                    type="date"
                    class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                    :class="{ 'border-vibrant-coral-400': form.errors.onset_on }"
                />
                <p v-if="form.errors.onset_on" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.onset_on }}</p>
            </div>
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('allergies.form.label_notes') }}
            </label>
            <textarea
                v-model="form.notes"
                rows="2"
                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                :class="{ 'border-vibrant-coral-400': form.errors.notes }"
            ></textarea>
            <p v-if="form.errors.notes" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.notes }}</p>
        </div>
    </form>
</template>
