<script setup>
import { computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog'
import MultiSelect from '@/Components/ui/MultiSelect.vue'

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
    users: {
        type: Array,
        required: true,
    },
    types: {
        type: Array,
        required: true,
    },
})

const emit = defineEmits(['update:open', 'saved'])

const page = usePage()
const current_user_id = computed(() => page.props.auth.user.id)

const user_options = computed(() =>
    props.users
        .filter((u) => u.id !== current_user_id.value)
        .map((u) => ({
            value: u.id,
            label: `${u.first_name} ${u.last_name}`,
            avatar: u.avatar_url,
        }))
)

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
                <DialogTitle>New Discussion</DialogTitle>
                <DialogDescription>Start a discussion thread about this patient.</DialogDescription>
            </DialogHeader>

            <form id="discussion-form" @submit.prevent="submit" class="grid gap-5">
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        Title <span class="text-vibrant-coral-500">*</span>
                    </label>
                    <input
                        v-model="form.title"
                        type="text"
                        class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-vibrant-coral-400': form.errors.title }"
                        placeholder="e.g. Follow-up re: medications"
                        autofocus
                    />
                    <p v-if="form.errors.title" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.title }}</p>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        Type <span class="text-vibrant-coral-500">*</span>
                    </label>
                    <select
                        v-model="form.type"
                        class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-vibrant-coral-400': form.errors.type }"
                    >
                        <option value="">Select…</option>
                        <option v-for="t in types" :key="t" :value="t">{{ t }}</option>
                    </select>
                    <p v-if="form.errors.type" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.type }}</p>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        Additional Participants
                    </label>
                    <MultiSelect
                        v-model="form.participant_ids"
                        :options="user_options"
                        placeholder="Add participants…"
                    />
                    <p v-if="form.errors.participant_ids" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.participant_ids }}</p>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        Initial Reply  <span class="text-vibrant-coral-500">*</span>
                    </label>
                    <textarea
                        v-model="form.initial_reply"
                        rows="3"
                        placeholder="Write your message"
                        class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
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
                    Cancel
                </button>
                <button
                    type="submit"
                    form="discussion-form"
                    :disabled="form.processing"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90 disabled:opacity-50"
                >
                    Create Discussion
                </button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
