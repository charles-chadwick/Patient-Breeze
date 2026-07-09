<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'
import ParticipantSelect from '@/Components/ParticipantSelect.vue'
import { formatDate, DATE_SHORT } from '@/lib/utils'

defineOptions({ layout: PortalLayout })

const props = defineProps({
    threads: { type: Array, required: true },
})

const composer_open = ref(false)
const reply_target_id = ref(null)

const new_message_form = useForm({ title: '', content: '', recipient_ids: [] })
const reply_form = useForm({ content: '' })

function submitNew() {
    new_message_form.post(route('portal.messages.store'), {
        preserveScroll: true,
        onSuccess: () => {
            new_message_form.reset()
            composer_open.value = false
        },
    })
}

function submitReply(discussion_id) {
    reply_form.post(route('portal.messages.reply', discussion_id), {
        preserveScroll: true,
        onSuccess: () => {
            reply_form.reset()
            reply_target_id.value = null
        },
    })
}
</script>

<template>
    <Head :title="$t('portal.messages.title')" />
    <div class="grid gap-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-slate-800">{{ $t('portal.messages.heading') }}</h1>
            <button
                class="rounded-lg bg-cerulean-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cerulean-700"
                @click="composer_open = !composer_open"
            >
                {{ composer_open ? $t('common.actions.cancel') : $t('portal.messages.new_message') }}
            </button>
        </div>

        <form
            v-if="composer_open"
            class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm"
            @submit.prevent="submitNew"
        >
            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $t('portal.messages.label_subject') }}</label>
            <input
                v-model="new_message_form.title"
                type="text"
                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
                :placeholder="$t('portal.messages.placeholder_subject')"
            />
            <label class="mt-4 block text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $t('portal.messages.label_recipients') }}</label>
            <div class="mt-1">
                <ParticipantSelect
                    v-model="new_message_form.recipient_ids"
                    search-route="portal.messages.recipients.search"
                    :placeholder="$t('portal.messages.placeholder_recipients')"
                />
            </div>
            <p class="mt-1 text-xs text-slate-400">{{ $t('portal.messages.recipients_hint') }}</p>

            <label class="mt-4 block text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $t('portal.messages.label_message') }}</label>
            <textarea
                v-model="new_message_form.content"
                rows="4"
                required
                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
            />
            <p v-if="new_message_form.errors.content" class="mt-1 text-xs text-vibrant-coral-600">{{ new_message_form.errors.content }}</p>
            <button
                type="submit"
                :disabled="new_message_form.processing"
                class="mt-4 rounded-lg bg-cerulean-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cerulean-700 disabled:opacity-50"
            >
                {{ $t('portal.messages.send') }}
            </button>
        </form>

        <div v-if="threads.length === 0" class="rounded-2xl border border-slate-100 bg-white p-10 text-center text-sm text-slate-400">
            {{ $t('portal.messages.empty') }}
        </div>

        <div
            v-for="thread in threads"
            :key="thread.id"
            class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm"
        >
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-800">{{ thread.title ?? $t('portal.messages.no_subject') }}</h2>
                <span class="text-xs text-slate-400">{{ formatDate(thread.created_at, DATE_SHORT) }}</span>
            </div>

            <div class="mt-4 space-y-3">
                <div
                    v-for="post in thread.posts"
                    :key="post.id"
                    class="rounded-lg border border-slate-100 p-3"
                    :class="post.from_patient ? 'bg-cerulean-50/60' : 'bg-slate-50'"
                >
                    <p class="text-xs font-semibold text-slate-500">
                        {{ post.author_name }} · {{ formatDate(post.created_at, DATE_SHORT) }}
                    </p>
                    <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ post.content }}</p>
                </div>
            </div>

            <div class="mt-4">
                <button
                    v-if="reply_target_id !== thread.id"
                    class="text-sm font-semibold text-cerulean-600 hover:text-cerulean-700"
                    @click="reply_target_id = thread.id; reply_form.content = ''"
                >
                    {{ $t('portal.messages.reply') }}
                </button>
                <form v-else @submit.prevent="submitReply(thread.id)">
                    <textarea
                        v-model="reply_form.content"
                        rows="3"
                        required
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
                        :placeholder="$t('portal.messages.placeholder_reply')"
                    />
                    <div class="mt-2 flex gap-2">
                        <button
                            type="submit"
                            :disabled="reply_form.processing"
                            class="rounded-lg bg-cerulean-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cerulean-700 disabled:opacity-50"
                        >
                            {{ $t('portal.messages.send_reply') }}
                        </button>
                        <button
                            type="button"
                            class="rounded-lg px-4 py-2 text-sm text-slate-500 hover:bg-slate-100"
                            @click="reply_target_id = null"
                        >
                            {{ $t('common.actions.cancel') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
