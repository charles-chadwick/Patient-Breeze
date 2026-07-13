<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import Quill from 'quill'
import 'quill/dist/quill.snow.css'

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: '',
    },
})

const emit = defineEmits(['update:modelValue'])

const editor_element = ref(null)
let quill = null
let is_internal_change = false

const toolbar_options = [
    ['bold', 'italic', 'underline'],
    [{ list: 'ordered' }, { list: 'bullet' }],
    ['link'],
    ['clean'],
]

onMounted(() => {
    quill = new Quill(editor_element.value, {
        theme: 'snow',
        placeholder: props.placeholder,
        modules: { toolbar: toolbar_options },
    })

    if (props.modelValue) {
        quill.clipboard.dangerouslyPasteHTML(props.modelValue)
    }

    quill.on('text-change', () => {
        is_internal_change = true
        const html = quill.getLength() > 1 ? quill.root.innerHTML : ''
        emit('update:modelValue', html)
    })
})

watch(() => props.modelValue, (value) => {
    if (is_internal_change) {
        is_internal_change = false
        return
    }
    if (quill && value !== quill.root.innerHTML) {
        quill.clipboard.dangerouslyPasteHTML(value || '')
    }
})

onBeforeUnmount(() => {
    quill = null
})
</script>

<template>
    <div class="flex h-full flex-col rounded-lg border border-border bg-background">
        <div ref="editor_element"></div>
    </div>
</template>

<style scoped>
:deep(.ql-container) {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
}

:deep(.ql-editor) {
    min-height: 12rem;
}
</style>
