<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import { formatDate, DATE_SHORT } from '@/lib/utils'
import DocumentModal from '@/Components/DocumentModal.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'

const props = defineProps({
    patientId: {
        type: Number,
        required: true,
    },
    documents: {
        type: Array,
        default: () => [],
    },
    types: {
        type: Array,
        default: () => [],
    },
})

const upload_open = ref(false)
const confirm_open = ref(false)
const deleting_document = ref(null)
const deleting = ref(false)

function askDelete(document) {
    deleting_document.value = document
    confirm_open.value = true
}

function confirmDelete() {
    if (!deleting_document.value) {
        return
    }

    deleting.value = true

    router.delete(route('patients.documents.destroy', [props.patientId, deleting_document.value.id]), {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false
            confirm_open.value = false
            deleting_document.value = null
        },
    })
}
</script>

<template>
    <div class="rounded-xl border border-border bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-border px-6 py-4">
            <h2 class="font-bold text-foreground">{{ $t('documents.heading') }}</h2>
            <button
                type="button"
                @click="upload_open = true"
                class="inline-flex h-10 items-center rounded-lg bg-primary px-4 text-sm font-bold text-white hover:bg-primary/90"
            >
                {{ $t('documents.upload') }}
            </button>
        </div>

        <div v-if="documents.length === 0" class="px-6 py-8 text-center text-sm text-muted-foreground">
            {{ $t('documents.empty') }}
        </div>

        <table v-else class="w-full text-sm">
            <thead>
                <tr class="border-b border-border text-left">
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('documents.column_date') }}</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('documents.column_type') }}</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('documents.column_name') }}</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('documents.column_notes') }}</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('documents.column_uploaded_by') }}</th>
                    <th class="px-6 py-3 text-right font-bold text-muted-foreground">{{ $t('documents.column_actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                <tr
                    v-for="document in documents"
                    :key="document.id"
                    class="hover:bg-muted/40"
                >
                    <td class="px-6 py-3 text-foreground">
                        {{ document.document_date ? formatDate(document.document_date, DATE_SHORT) : formatDate(document.created_at, DATE_SHORT) }}
                    </td>
                    <td class="px-6 py-3">
                        <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary">
                            {{ document.type_label }}
                        </span>
                    </td>
                    <td class="px-6 py-3 font-bold text-foreground">{{ document.name }}</td>
                    <td class="px-6 py-3 text-muted-foreground">{{ document.notes ?? $t('common.placeholders.em_dash') }}</td>
                    <td class="px-6 py-3 text-muted-foreground">{{ document.uploaded_by ?? $t('common.placeholders.em_dash') }}</td>
                    <td class="px-6 py-3 text-right">
                        <a
                            :href="document.download_url"
                            class="rounded-lg border border-border px-3 py-1.5 text-xs font-bold text-foreground hover:bg-muted/40"
                        >
                            {{ $t('documents.download') }}
                        </a>
                        <button
                            type="button"
                            @click="askDelete(document)"
                            class="ml-2 rounded-lg border border-vibrant-coral-200 px-3 py-1.5 text-xs font-bold text-vibrant-coral-600 hover:bg-vibrant-coral-50"
                        >
                            {{ $t('documents.delete') }}
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>

        <DocumentModal
            v-model:open="upload_open"
            :action="route('patients.documents.store', patientId)"
            :types="types"
        />

        <ConfirmDialog
            v-model:open="confirm_open"
            :title="trans('documents.delete')"
            :description="deleting_document ? trans('documents.delete_confirm') : ''"
            :confirm-label="trans('documents.delete')"
            :processing="deleting"
            @confirm="confirmDelete"
        />
    </div>
</template>
