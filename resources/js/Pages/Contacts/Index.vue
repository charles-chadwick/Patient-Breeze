<script setup>
import { computed, ref } from 'vue'
import { router, setLayoutProps } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import ContactModal from '@/Components/ContactModal.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'
import RoiBadge from '@/Components/RoiBadge.vue'

defineOptions({ layout: DashboardLayout })

setLayoutProps({ title: computed(() => trans('contacts.index.title')) })

const props = defineProps({
    contacts: {
        type: Object,
        required: true,
    },
    types: {
        type: Array,
        required: true,
    },
})

const modal_open = ref(false)
const editing_contact = ref(null)

const confirm_open = ref(false)
const deleting_contact = ref(null)
const deleting = ref(false)

function openEdit(contact) {
    editing_contact.value = contact
    modal_open.value = true
}

function handleSaved() {
    router.reload({ only: ['contacts'] })
}

function askDelete(contact) {
    deleting_contact.value = contact
    confirm_open.value = true
}

function confirmDelete() {
    if (!deleting_contact.value) return
    deleting.value = true
    router.delete(route('contacts.destroy', deleting_contact.value.id), {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false
            confirm_open.value = false
            deleting_contact.value = null
        },
    })
}

function contactableLabel(contact) {
    if (!contact.contactable_type) return trans('common.placeholders.em_dash')
    const short = contact.contactable_type.split('\\').pop()
    return `${short} #${contact.contactable_id}`
}
</script>

<template>
    <div class="rounded border border-border bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-border px-6 py-4">
            <div class="flex items-center gap-3">
                <h2 class="font-bold text-foreground">{{ $t('contacts.index.heading') }}</h2>
                <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary">
                    {{ contacts.total }}
                </span>
            </div>
        </div>

        <div v-if="contacts.data.length === 0" class="px-6 py-8 text-center text-sm text-muted-foreground">
            {{ $t('contacts.index.empty') }}
        </div>

        <table v-else class="w-full text-sm">
            <thead>
                <tr class="border-b border-border text-left">
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('contacts.index.column_name') }}</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('contacts.index.column_type') }}</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('contacts.index.column_phone') }}</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('contacts.index.column_linked_to') }}</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('contacts.index.column_roi') }}</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground text-right">{{ $t('contacts.index.column_actions') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="contact in contacts.data"
                    :key="contact.id"
                    class="border-b border-border last:border-b-0"
                >
                    <td class="px-6 py-3 font-bold text-foreground">{{ contact.name }}</td>
                    <td class="px-6 py-3">
                        <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary">
                            {{ $t('enums.contact_type.' + contact.type) }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-muted-foreground">{{ contact.phone || $t('common.placeholders.em_dash') }}</td>
                    <td class="px-6 py-3 text-muted-foreground">{{ contactableLabel(contact) }}</td>
                    <td class="px-6 py-3">
                        <RoiBadge :value="contact.roi" />
                    </td>
                    <td class="px-6 py-3 text-right">
                        <button
                            type="button"
                            @click="openEdit(contact)"
                            class="rounded-lg border border-border px-3 py-1.5 text-xs font-bold text-foreground hover:bg-muted/40"
                        >
                            {{ $t('common.actions.edit') }}
                        </button>
                        <button
                            type="button"
                            @click="askDelete(contact)"
                            class="ml-2 rounded-lg border border-vibrant-coral-200 px-3 py-1.5 text-xs font-bold text-vibrant-coral-600 hover:bg-vibrant-coral-50"
                        >
                            {{ $t('common.actions.delete') }}
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>

        <ContactModal
            v-model:open="modal_open"
            :contact="editing_contact"
            :types="types"
            @saved="handleSaved"
        />

        <ConfirmDialog
            v-model:open="confirm_open"
            :title="$t('contacts.confirm.delete_title')"
            :description="deleting_contact ? $t('contacts.confirm.delete_description', { name: deleting_contact.name }) : ''"
            :confirm-label="$t('common.actions.delete')"
            :processing="deleting"
            @confirm="confirmDelete"
        />
    </div>
</template>
