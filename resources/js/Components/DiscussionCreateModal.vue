<script setup>
import { useForm } from '@inertiajs/vue3'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog'
import ParticipantSelect from '@/Components/ParticipantSelect.vue'

const props = defineProps({
    open: {
        type: Boolean,
        required: true,
    },
    discussionableType: {
        type: String,
        required: true,
    },
    discussionableId: {
        type: Number,
        required: true,
    },
    types: {
        type: Array,
        required: true,
    },
})

const emit = defineEmits(['update:open', 'saved'])

const form = useForm({
    title: '',
    type: '',
    discussionable_type: props.discussionableType,
    discussionable_id: props.discussionableId,
    participant_ids: [],
    initial_reply: '',
})

function handleOpenUpdate(value) {
    emit('update:open', value)
    if (!value) {
        form.reset()
    }
}

function submit() {
    form.post(route('discussions.store'), {
        preserveScroll: true,
        onSuccess: () => {
            handleOpenUpdate(false)
            emit('saved')
        },
    })
}
</script>

<template>
    <Dialog :open="open" @update:open="handleOpenUpdate">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>{{ $t('discussions.create.title') }}</DialogTitle>
                <DialogDescription>{{ $t('discussions.create.description') }}</DialogDescription>
            </DialogHeader>

            <form id="discussion-form" @submit.prevent="submit" class="grid gap-5">
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('discussions.create.label_title') }} <span class="text-vibrant-coral-500">*</span>
                    </label>
                    <input
                        v-model="form.title"
                        type="text"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-vibrant-coral-400': form.errors.title }"
                        :placeholder="$t('discussions.create.placeholder_title')"
                        autofocus
                    />
                    <p v-if="form.errors.title" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.title }}</p>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('discussions.create.label_type') }} <span class="text-vibrant-coral-500">*</span>
                    </label>
                    <select
                        v-model="form.type"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-vibrant-coral-400': form.errors.type }"
                    >
                        <option value="">{{ $t('common.actions.select_placeholder') }}</option>
                        <option v-for="type_option in types" :key="type_option" :value="type_option">{{ $t('enums.discussion_type.' + type_option) }}</option>
                    </select>
                    <p v-if="form.errors.type" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.type }}</p>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('discussions.create.label_participants') }}
                    </label>
                    <ParticipantSelect
                        v-model="form.participant_ids"
                        :placeholder="$t('discussions.create.placeholder_participants')"
                    />
                    <p v-if="form.errors.participant_ids" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.participant_ids }}</p>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('discussions.create.label_initial_reply') }}  <span class="text-vibrant-coral-500">*</span>
                    </label>
                    <textarea
                        v-model="form.initial_reply"
                        rows="3"
                        :placeholder="$t('discussions.create.placeholder_initial_reply')"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-vibrant-coral-400': form.errors.initial_reply }"
                    ></textarea>
                    <p v-if="form.errors.initial_reply" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.initial_reply }}</p>
                </div>
            </form>

            <DialogFooter>
                <button
                    type="button"
                    @click="handleOpenUpdate(false)"
                    class="rounded-lg border border-border px-4 py-2 text-sm font-bold text-foreground hover:bg-muted/40"
                >
                    {{ $t('common.actions.cancel') }}
                </button>
                <button
                    type="submit"
                    form="discussion-form"
                    :disabled="form.processing"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90 disabled:opacity-50"
                >
                    {{ $t('discussions.create.submit') }}
                </button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
