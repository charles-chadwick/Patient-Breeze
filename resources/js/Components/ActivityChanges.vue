<script setup>
defineProps({
    changes: {
        type: Array,
        default: () => [],
    },
})

function humanizeField(field) {
    return field
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (c) => c.toUpperCase())
}

function displayValue(value) {
    if (value === null || value === undefined || value === '') {
        return null
    }

    if (typeof value === 'object') {
        return JSON.stringify(value)
    }

    return String(value)
}
</script>

<template>
    <div v-if="changes.length" class="space-y-2">
        <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">{{ $t('audit.changes_heading') }}</p>
        <div class="overflow-hidden rounded-lg border border-border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-border bg-muted/30 text-left">
                        <th class="px-3 py-2 font-bold text-muted-foreground">{{ $t('common.labels.field') }}</th>
                        <th class="px-3 py-2 font-bold text-muted-foreground">{{ $t('audit.old_value') }}</th>
                        <th class="px-3 py-2 font-bold text-muted-foreground">{{ $t('audit.new_value') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-for="change in changes" :key="change.field">
                        <td class="px-3 py-2 font-medium text-foreground">{{ humanizeField(change.field) }}</td>
                        <td class="px-3 py-2 text-muted-foreground">
                            <span v-if="displayValue(change.old) !== null">{{ displayValue(change.old) }}</span>
                            <span v-else class="text-muted-foreground/60">{{ $t('audit.empty_value') }}</span>
                        </td>
                        <td class="px-3 py-2 text-foreground">
                            <span v-if="displayValue(change.new) !== null">{{ displayValue(change.new) }}</span>
                            <span v-else class="text-muted-foreground/60">{{ $t('audit.empty_value') }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <p v-else class="text-xs text-muted-foreground">{{ $t('audit.no_changes') }}</p>
</template>
