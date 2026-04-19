<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
    currentUrl: {
        type: String,
        default: null,
    },
    modelValue: {
        default: null,
    },
    removed: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: null,
    },
})

const emit = defineEmits(['update:modelValue', 'update:removed'])

const fileInput = ref(null)
const previewUrl = ref(null)

const displayUrl = computed(() => {
    if (previewUrl.value) {
        return previewUrl.value
    }
    if (props.removed) {
        return null
    }
    return props.currentUrl
})

function onFileChange(e) {
    const file = e.target.files[0]
    if (!file) {
        return
    }
    previewUrl.value = URL.createObjectURL(file)
    emit('update:modelValue', file)
    emit('update:removed', false)
}

function remove() {
    previewUrl.value = null
    emit('update:modelValue', null)
    emit('update:removed', true)
    if (fileInput.value) {
        fileInput.value.value = ''
    }
}

function trigger() {
    fileInput.value?.click()
}
</script>

<template>
    <div class="flex items-center gap-5">
        <button
            type="button"
            class="shrink-0 cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/50 rounded-full"
            :class="{ 'ring-2 ring-red-400': error }"
            @click="trigger"
            title="Click to upload avatar"
        >
            <img
                v-if="displayUrl"
                :src="displayUrl"
                alt="Avatar"
                class="size-20 rounded-full object-cover ring-2 ring-border"
            />
            <div
                v-else
                class="size-20 rounded-full bg-muted flex items-center justify-center ring-2 ring-border"
            >
                <svg class="size-8 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
                </svg>
            </div>
        </button>

        <div class="flex flex-col gap-2">
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="rounded-lg border border-border px-3 py-1.5 text-xs font-bold text-foreground hover:bg-muted/40"
                    @click="trigger"
                >
                    {{ displayUrl ? 'Change Photo' : 'Upload Photo' }}
                </button>
                <button
                    v-if="displayUrl"
                    type="button"
                    class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-bold text-red-600 hover:bg-red-50"
                    @click="remove"
                >
                    Remove
                </button>
            </div>
            <p class="text-xs text-muted-foreground">JPG, PNG or GIF · Max 2 MB</p>
            <p v-if="error" class="text-xs text-red-600">{{ error }}</p>
        </div>

        <input
            ref="fileInput"
            type="file"
            accept="image/*"
            class="hidden"
            @change="onFileChange"
        />
    </div>
</template>
