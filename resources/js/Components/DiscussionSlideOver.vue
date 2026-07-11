<script setup>
import { computed, ref, watch, onUnmounted } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
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

function postAuthor(post) {
    const author = post.user ?? post.patient ?? null

    return {
        name: author
            ? `${author.first_name} ${author.last_name}`
            : null,
        avatar_url: author?.avatar_url ?? null,
    }
}

const emit = defineEmits(['update:open', 'reply-posted'])

const page = usePage()
const current_user_id = computed(() => page.props.auth?.user?.id ?? null)
const permissions = computed(() => page.props.auth?.permissions ?? [])
const can_delete_discussion = computed(() => permissions.value.includes('delete_discussions'))

function isOwnPost(post) {
    return current_user_id.value !== null && post.user_id === current_user_id.value
}

function canEditPost(post) {
    return isOwnPost(post) && permissions.value.includes('update_discussions')
}

function canDeletePost(post) {
    return isOwnPost(post) && can_delete_discussion.value
}

const form = useForm({ content: '' })

// Inline post editing.
const editing_post_id = ref(null)
const edit_form = useForm({ content: '' })

function startEdit(post) {
    editing_post_id.value = post.id
    edit_form.clearErrors()
    edit_form.content = post.content
}

function cancelEdit() {
    editing_post_id.value = null
    edit_form.reset()
}

function saveEdit(post) {
    edit_form.put(route('discussions.posts.update', [props.discussion.id, post.id]), {
        preserveScroll: true,
        onSuccess: () => {
            editing_post_id.value = null
            edit_form.reset()
            emit('reply-posted')
        },
    })
}

function deletePost(post) {
    if (window.confirm(trans('discussions.slide_over.delete_post_confirm'))) {
        router.delete(route('discussions.posts.destroy', [props.discussion.id, post.id]), {
            preserveScroll: true,
            onSuccess: () => emit('reply-posted'),
        })
    }
}

function deleteDiscussion() {
    if (window.confirm(trans('discussions.slide_over.delete_thread_confirm'))) {
        router.delete(route('discussions.destroy', props.discussion.id), {
            preserveScroll: true,
            onSuccess: () => {
                emit('reply-posted')
                emit('update:open', false)
            },
        })
    }
}

let subscribed_channel = null

function leaveChannel() {
    if (subscribed_channel !== null && window.Echo) {
        window.Echo.leave(`discussion.${subscribed_channel}`)
        subscribed_channel = null
    }
}

watch(
    () => props.discussion?.id,
    (discussion_id) => {
        leaveChannel()

        if (!discussion_id || !window.Echo) {
            return
        }

        subscribed_channel = discussion_id
        window.Echo.private(`discussion.${discussion_id}`)
            .listen('.DiscussionPostCreated', () => {
                emit('reply-posted')
            })
    },
    { immediate: true },
)

onUnmounted(leaveChannel)

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

            <div class="relative z-10 flex h-full w-full max-w-lg flex-col bg-card shadow-xl">
                <div class="flex items-start justify-between border-b border-border px-6 py-4">
                    <div>
                        <h2 class="text-base font-bold text-foreground">{{ discussion.title }}</h2>
                        <div class="mt-1 flex items-center gap-2">
                            <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary">
                                {{ $t('enums.discussion_type.' + discussion.type) }}
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
                    <div class="ml-4 flex shrink-0 items-center gap-1">
                        <button
                            v-if="can_delete_discussion"
                            type="button"
                            @click="deleteDiscussion"
                            class="rounded-lg px-2 py-1.5 text-xs font-bold text-vibrant-coral-600 hover:bg-vibrant-coral-50"
                        >
                            {{ $t('discussions.slide_over.delete_thread') }}
                        </button>
                        <button
                            type="button"
                            @click="close"
                            class="rounded-lg p-1.5 text-muted-foreground hover:bg-muted/40 hover:text-foreground"
                        >
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="border-b border-border px-6 py-3">
                    <p class="mb-2 text-xs font-bold uppercase tracking-wide text-muted-foreground">{{ $t('discussions.slide_over.participants_heading') }}</p>
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
                                    : $t('discussions.slide_over.unknown_participant') }}
                            </span>
                            <span v-if="participant.is_initiator" class="text-xs text-muted-foreground">{{ $t('discussions.slide_over.initiator_marker') }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-4">
                    <div v-if="discussion.posts.length === 0" class="py-8 text-center text-sm text-muted-foreground">
                        {{ $t('discussions.slide_over.empty_posts') }}
                    </div>
                    <div v-else class="space-y-4">
                        <div
                            v-for="post in discussion.posts"
                            :key="post.id"
                            class="flex gap-3"
                        >
                            <img
                                v-if="postAuthor(post).avatar_url"
                                :src="postAuthor(post).avatar_url"
                                :alt="postAuthor(post).name"
                                class="size-8 shrink-0 rounded-full object-cover ring-1 ring-border"
                            />
                            <div class="group min-w-0 flex-1">
                                <div class="flex items-baseline gap-2">
                                    <span class="text-sm font-bold text-foreground">
                                        {{ postAuthor(post).name ?? $t('discussions.slide_over.unknown_participant') }}
                                    </span>
                                    <span class="text-xs text-muted-foreground">{{ formatDate(post.created_at, DATE_SHORT) }}</span>
                                    <div
                                        v-if="editing_post_id !== post.id && (canEditPost(post) || canDeletePost(post))"
                                        class="ml-auto flex items-center gap-2 opacity-0 transition-opacity group-hover:opacity-100"
                                    >
                                        <button
                                            v-if="canEditPost(post)"
                                            type="button"
                                            @click="startEdit(post)"
                                            class="text-xs font-bold text-muted-foreground hover:text-foreground"
                                        >
                                            {{ $t('discussions.slide_over.edit_post') }}
                                        </button>
                                        <button
                                            v-if="canDeletePost(post)"
                                            type="button"
                                            @click="deletePost(post)"
                                            class="text-xs font-bold text-vibrant-coral-600 hover:text-vibrant-coral-700"
                                        >
                                            {{ $t('discussions.slide_over.delete_post') }}
                                        </button>
                                    </div>
                                </div>
                                <template v-if="editing_post_id === post.id">
                                    <textarea
                                        v-model="edit_form.content"
                                        rows="2"
                                        class="mt-1 w-full resize-none rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                                        :class="{ 'border-vibrant-coral-400': edit_form.errors.content }"
                                    ></textarea>
                                    <p v-if="edit_form.errors.content" class="mt-1 text-xs text-vibrant-coral-600">{{ edit_form.errors.content }}</p>
                                    <div class="mt-2 flex items-center gap-2">
                                        <button
                                            type="button"
                                            @click="saveEdit(post)"
                                            :disabled="edit_form.processing || !edit_form.content.trim()"
                                            class="rounded-lg bg-primary px-3 py-1.5 text-xs font-bold text-white hover:bg-primary/90 disabled:opacity-50"
                                        >
                                            {{ $t('discussions.slide_over.save_edit') }}
                                        </button>
                                        <button
                                            type="button"
                                            @click="cancelEdit"
                                            class="rounded-lg border border-border px-3 py-1.5 text-xs font-bold text-foreground hover:bg-muted/40"
                                        >
                                            {{ $t('discussions.slide_over.cancel_edit') }}
                                        </button>
                                    </div>
                                </template>
                                <p v-else class="mt-1 text-sm text-foreground">{{ post.content }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-border px-6 py-4">
                    <form @submit.prevent="submitReply" class="flex gap-3">
                        <textarea
                            v-model="form.content"
                            rows="2"
                            :placeholder="$t('discussions.slide_over.placeholder_reply')"
                            class="flex-1 resize-none rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                            :class="{ 'border-vibrant-coral-400': form.errors.content }"
                        ></textarea>
                        <button
                            type="submit"
                            :disabled="form.processing || !form.content.trim()"
                            class="self-end rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90 disabled:opacity-50"
                        >
                            {{ $t('discussions.slide_over.reply') }}
                        </button>
                    </form>
                    <p v-if="form.errors.content" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.content }}</p>
                </div>
            </div>
        </div>
    </Teleport>
</template>
