<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { formatDate, DATE_SHORT } from '@/lib/utils'

const props = defineProps({
    open: {
        type: Boolean,
        required: true,
    },
    discussion: {
        type: Object,
        default: null,
    },
    patient: {
        type: Object,
        default: null,
    },
})

const initiator = computed(() =>
    props.discussion?.participants?.find((p) => p.is_initiator)?.participantable ?? null
)

const isPortalMessage = computed(() => props.discussion?.type === 'Portal Message')

const emit = defineEmits(['update:open', 'reply-posted'])

const form = useForm({ content: '' })

function close() {
    emit('update:open', false)
}

function submitReply() {
    form.post(route('discussions.posts.store', props.discussion.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset()
            emit('reply-posted')
        },
    })
}
</script>

<template>
    <Teleport to="body">
        <div v-if="open && discussion" class="fixed inset-0 z-50 flex justify-end">
            <div class="absolute inset-0 bg-black/40" @click="close"></div>

            <div class="relative z-10 flex h-full w-full max-w-lg flex-col bg-white shadow-xl">
                <div class="flex items-start justify-between border-b border-border px-6 py-4">
                    <div>
                        <h2 class="text-base font-bold text-foreground">{{ discussion.title }}</h2>
                        <div class="mt-1 flex items-center gap-2">
                            <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary">
                                {{ discussion.type }}
                            </span>
                            <span class="text-xs text-muted-foreground">{{ formatDate(discussion.created_at, DATE_SHORT) }}</span>
                        </div>
                        <div class="mt-2 flex flex-wrap items-center gap-3">
                            <div v-if="initiator" class="flex items-center gap-1.5">
                                <img
                                    :src="initiator.avatar_url"
                                    :alt="`${initiator.first_name} ${initiator.last_name}`"
                                    class="size-5 rounded-full object-cover ring-1 ring-border"
                                />
                                <span class="text-xs text-muted-foreground">{{ initiator.first_name }} {{ initiator.last_name }}</span>
                            </div>
                            <div v-if="isPortalMessage && patient" class="flex items-center gap-1.5">
                                <span class="text-xs text-muted-foreground">→</span>
                                <img
                                    :src="patient.avatar_url"
                                    :alt="`${patient.first_name} ${patient.last_name}`"
                                    class="size-5 rounded-full object-cover ring-1 ring-border"
                                />
                                <span class="text-xs text-muted-foreground">{{ patient.first_name }} {{ patient.last_name }}</span>
                            </div>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="close"
                        class="ml-4 rounded-lg p-1.5 text-muted-foreground hover:bg-muted/40 hover:text-foreground"
                    >
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="border-b border-border px-6 py-3">
                    <p class="mb-2 text-xs font-bold uppercase tracking-wide text-muted-foreground">Participants</p>
                    <div class="flex flex-wrap gap-2">
                        <div
                            v-for="participant in discussion.participants"
                            :key="participant.id"
                            class="flex items-center gap-1.5 rounded-full bg-muted px-2.5 py-1"
                        >
                            <img
                                v-if="participant.participantable"
                                :src="participant.participantable.avatar_url"
                                :alt="`${participant.participantable.first_name} ${participant.participantable.last_name}`"
                                class="size-5 rounded-full object-cover"
                            />
                            <span class="text-xs font-medium text-foreground">
                                {{ participant.participantable
                                    ? `${participant.participantable.first_name} ${participant.participantable.last_name}`
                                    : 'Unknown' }}
                            </span>
                            <span v-if="participant.is_initiator" class="text-xs text-muted-foreground">(initiator)</span>
                        </div>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-4">
                    <div v-if="discussion.posts.length === 0" class="py-8 text-center text-sm text-muted-foreground">
                        No messages yet. Start the conversation below.
                    </div>
                    <div v-else class="space-y-4">
                        <div
                            v-for="post in discussion.posts"
                            :key="post.id"
                            class="flex gap-3"
                        >
                            <img
                                v-if="post.user"
                                :src="post.user.avatar_url"
                                :alt="`${post.user.first_name} ${post.user.last_name}`"
                                class="size-8 shrink-0 rounded-full object-cover ring-1 ring-border"
                            />
                            <div class="min-w-0 flex-1">
                                <div class="flex items-baseline gap-2">
                                    <span class="text-sm font-bold text-foreground">
                                        {{ post.user ? `${post.user.first_name} ${post.user.last_name}` : 'Unknown' }}
                                    </span>
                                    <span class="text-xs text-muted-foreground">{{ formatDate(post.created_at, DATE_SHORT) }}</span>
                                </div>
                                <p class="mt-1 text-sm text-foreground">{{ post.content }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-border px-6 py-4">
                    <form @submit.prevent="submitReply" class="flex gap-3">
                        <textarea
                            v-model="form.content"
                            rows="2"
                            placeholder="Write a reply…"
                            class="flex-1 resize-none rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                            :class="{ 'border-red-400': form.errors.content }"
                        ></textarea>
                        <button
                            type="submit"
                            :disabled="form.processing || !form.content.trim()"
                            class="self-end rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90 disabled:opacity-50"
                        >
                            Reply
                        </button>
                    </form>
                    <p v-if="form.errors.content" class="mt-1 text-xs text-red-600">{{ form.errors.content }}</p>
                </div>
            </div>
        </div>
    </Teleport>
</template>
