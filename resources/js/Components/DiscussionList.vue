<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import DiscussionCreateModal from '@/Components/DiscussionCreateModal.vue'
import DiscussionSlideOver from '@/Components/DiscussionSlideOver.vue'
import { formatDate, DATE_SHORT } from '@/lib/utils'

const props = defineProps({
    discussions: {
        type: Array,
        default: null,
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
    patient: {
        type: Object,
        default: null,
    },
})

const create_modal_open = ref(false)
const slide_over_open = ref(false)
const selected_discussion = ref(null)

watch(() => props.discussions, (updated) => {
    if (slide_over_open.value && selected_discussion.value && updated) {
        const refreshed = updated.find((d) => d.id === selected_discussion.value.id)
        if (refreshed) {
            selected_discussion.value = refreshed
        }
    }
})

function openDiscussion(discussion) {
    selected_discussion.value = discussion
    slide_over_open.value = true
}

function handleDiscussionCreated() {
    router.reload({ only: ['discussions'] })
}

function handleReplyPosted() {
    router.reload({ only: ['discussions'] })
}

function lastActivity(discussion) {
    const last_post = discussion.posts.at(-1)
    return last_post ? last_post.created_at : discussion.created_at
}
</script>

<template>
    <div>
        <div class="flex items-center justify-between border-b border-border px-6 py-4">
            <h2 class="font-bold text-foreground">Discussions</h2>
            <button
                type="button"
                @click="create_modal_open = true"
                class="inline-flex h-10 items-center rounded-lg bg-primary px-4 text-sm font-bold text-white hover:bg-primary/90"
            >
                + New Discussion
            </button>
        </div>

        <div v-if="discussions === null" class="divide-y divide-border">
            <div v-for="i in 3" :key="i" class="flex items-center gap-4 px-6 py-4">
                <div class="h-4 w-48 animate-pulse rounded bg-muted"></div>
                <div class="ml-auto h-4 w-24 animate-pulse rounded bg-muted"></div>
            </div>
        </div>

        <div v-else-if="discussions.length === 0" class="px-6 py-8 text-center text-sm text-muted-foreground">
            No discussions on record.
        </div>

        <table v-else class="w-full text-sm">
            <thead>
                <tr class="border-b border-border text-left">
                    <th class="px-6 py-3 font-bold text-muted-foreground">Title</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">Type</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">Participants</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">Posts</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">Last Activity</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                <tr
                    v-for="discussion in discussions"
                    :key="discussion.id"
                    class="cursor-pointer hover:bg-muted/40"
                    @click="openDiscussion(discussion)"
                >
                    <td class="px-6 py-3 font-bold text-foreground">{{ discussion.title }}</td>
                    <td class="px-6 py-3">
                        <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary">
                            {{ discussion.type }}
                        </span>
                    </td>
                    <td class="px-6 py-3">
                        <div class="flex -space-x-2">
                            <template v-for="participant in discussion.participants.slice(0, 5)" :key="participant.id">
                                <img
                                    v-if="participant.participantable"
                                    :src="participant.participantable.avatar_url"
                                    :alt="`${participant.participantable.first_name} ${participant.participantable.last_name}`"
                                    :title="`${participant.participantable.first_name} ${participant.participantable.last_name}`"
                                    class="size-7 rounded-full object-cover ring-2 ring-white"
                                />
                            </template>
                            <span
                                v-if="discussion.participants.length > 5"
                                class="flex size-7 items-center justify-center rounded-full bg-muted text-xs font-bold text-foreground ring-2 ring-white"
                            >
                                +{{ discussion.participants.length - 5 }}
                            </span>
                        </div>
                    </td>
                    <td class="px-6 py-3 text-muted-foreground">{{ discussion.posts.length }}</td>
                    <td class="px-6 py-3 text-muted-foreground">{{ formatDate(lastActivity(discussion), DATE_SHORT) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <DiscussionCreateModal
        v-model:open="create_modal_open"
        :discussionable-type="discussionableType"
        :discussionable-id="discussionableId"
        :users="users"
        :types="types"
        @saved="handleDiscussionCreated"
    />

    <DiscussionSlideOver
        v-model:open="slide_over_open"
        :discussion="selected_discussion"
        :patient="patient"
        @reply-posted="handleReplyPosted"
    />
</template>
