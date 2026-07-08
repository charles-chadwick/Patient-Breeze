<script setup>
import RoiBadge from '@/Components/RoiBadge.vue'
import ContactModal from '@/Components/ContactModal.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'
import { useContactManager } from '@/composables/useContactManager'

const props = defineProps({
    contacts: {
        type: Array,
        required: true,
    },
    contactableType: {
        type: String,
        required: true,
    },
    contactableId: {
        type: Number,
        required: true,
    },
    types: {
        type: Array,
        required: true,
    },
    reloadKey: {
        type: String,
        required: true,
    },
})

const {
    contact_modal_open,
    editing_contact,
    confirm_open,
    deleting_contact,
    deleting,
    openCreateContact,
    openEditContact,
    handleContactSaved,
    askDeleteContact,
    confirmDeleteContact,
} = useContactManager(props.reloadKey)
</script>

<template>
    <div class="flex items-center justify-between px-6 py-4">
        <h2 class="font-bold text-foreground">{{ $t('contacts.tab.heading') }}</h2>
        <button
            type="button"
            @click="openCreateContact"
            class="inline-flex h-10 items-center rounded-lg bg-primary px-4 text-sm font-bold text-white hover:bg-primary/90"
        >
            {{ $t('contacts.tab.new_contact') }}
        </button>
    </div>

    <div
        v-if="contacts.length === 0"
        class="px-6 py-8 text-center text-sm text-muted-foreground"
    >
        {{ $t('contacts.tab.empty') }}
    </div>

    <table v-else class="w-full text-sm">
        <thead>
            <tr class="border-b border-border text-left">
                <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('contacts.tab.column_name') }}</th>
                <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('contacts.tab.column_type') }}</th>
                <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('contacts.tab.column_phone') }}</th>
                <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('contacts.tab.column_address') }}</th>
                <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('contacts.tab.column_roi') }}</th>
                <th class="px-6 py-3 font-bold text-muted-foreground text-right">{{ $t('contacts.tab.column_actions') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-border">
            <tr
                v-for="contact in contacts"
                :key="contact.id"
                class="hover:bg-muted/40"
            >
                <td class="px-6 py-3 font-bold text-foreground">{{ contact.name }}</td>
                <td class="px-6 py-3">
                    <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary">
                        {{ $t('enums.contact_type.' + contact.type) }}
                    </span>
                </td>
                <td class="px-6 py-3 text-muted-foreground">{{ contact.phone || $t('common.placeholders.em_dash') }}</td>
                <td class="px-6 py-3 text-muted-foreground">{{ contact.street_address || $t('common.placeholders.em_dash') }}</td>
                <td class="px-6 py-3">
                    <RoiBadge :value="contact.roi" />
                </td>
                <td class="px-6 py-3 text-right">
                    <button
                        type="button"
                        @click="openEditContact(contact)"
                        class="rounded-lg border border-border px-3 py-1.5 text-xs font-bold text-foreground hover:bg-muted/40"
                    >
                        {{ $t('common.actions.edit') }}
                    </button>
                    <button
                        type="button"
                        @click="askDeleteContact(contact)"
                        class="ml-2 rounded-lg border border-vibrant-coral-200 px-3 py-1.5 text-xs font-bold text-vibrant-coral-600 hover:bg-vibrant-coral-50"
                    >
                        {{ $t('common.actions.delete') }}
                    </button>
                </td>
            </tr>
        </tbody>
    </table>

    <ContactModal
        v-model:open="contact_modal_open"
        :contact="editing_contact"
        :types="types"
        :contactable-type="contactableType"
        :contactable-id="contactableId"
        @saved="handleContactSaved"
    />

    <ConfirmDialog
        v-model:open="confirm_open"
        :title="$t('contacts.confirm.delete_title')"
        :description="deleting_contact ? $t('contacts.confirm.delete_description', { name: deleting_contact.name }) : ''"
        :confirm-label="$t('common.actions.delete')"
        :processing="deleting"
        @confirm="confirmDeleteContact"
    />
</template>
