<template>
  <div class="rounded-xl border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 focus-within:ring-2 focus-within:ring-primary-500 focus-within:border-primary-500 transition-all overflow-hidden flex flex-col">
    <!-- Toolbar -->
    <div class="flex flex-wrap items-center gap-0.5 p-1.5 bg-secondary-50 dark:bg-secondary-900/60 border-b border-secondary-200 dark:border-secondary-700 text-secondary-700 dark:text-secondary-300 select-none">
      <!-- Heading Select -->
      <select
        class="text-xs font-semibold px-2 py-1 bg-white dark:bg-secondary-800 rounded border border-secondary-200 dark:border-secondary-700 text-secondary-800 dark:text-secondary-200 focus:outline-none cursor-pointer mr-1"
        :value="currentBlockFormat"
        @change="setBlockFormat($event.target.value)"
      >
        <option value="p">
          Paragraph
        </option>
        <option value="h2">
          Heading 1
        </option>
        <option value="h3">
          Heading 2
        </option>
        <option value="h4">
          Heading 3
        </option>
        <option value="pre">
          Code / Monospace
        </option>
      </select>

      <div class="w-px h-5 bg-secondary-200 dark:border-secondary-700 mx-1" />

      <!-- Text styles -->
      <button
        type="button"
        class="p-1.5 rounded hover:bg-secondary-200 dark:hover:bg-secondary-700 text-secondary-700 dark:text-secondary-200 transition-colors"
        title="Bold (Ctrl+B)"
        @click="execCmd('bold')"
      >
        <Bold class="w-3.5 h-3.5" />
      </button>
      <button
        type="button"
        class="p-1.5 rounded hover:bg-secondary-200 dark:hover:bg-secondary-700 text-secondary-700 dark:text-secondary-200 transition-colors"
        title="Italic (Ctrl+I)"
        @click="execCmd('italic')"
      >
        <Italic class="w-3.5 h-3.5" />
      </button>
      <button
        type="button"
        class="p-1.5 rounded hover:bg-secondary-200 dark:hover:bg-secondary-700 text-secondary-700 dark:text-secondary-200 transition-colors"
        title="Underline (Ctrl+U)"
        @click="execCmd('underline')"
      >
        <Underline class="w-3.5 h-3.5" />
      </button>
      <button
        type="button"
        class="p-1.5 rounded hover:bg-secondary-200 dark:hover:bg-secondary-700 text-secondary-700 dark:text-secondary-200 transition-colors"
        title="Strikethrough"
        @click="execCmd('strikeThrough')"
      >
        <Strikethrough class="w-3.5 h-3.5" />
      </button>

      <div class="w-px h-5 bg-secondary-200 dark:border-secondary-700 mx-1" />

      <!-- Lists -->
      <button
        type="button"
        class="p-1.5 rounded hover:bg-secondary-200 dark:hover:bg-secondary-700 text-secondary-700 dark:text-secondary-200 transition-colors"
        title="Bullet List"
        @click="execCmd('insertUnorderedList')"
      >
        <List class="w-3.5 h-3.5" />
      </button>
      <button
        type="button"
        class="p-1.5 rounded hover:bg-secondary-200 dark:hover:bg-secondary-700 text-secondary-700 dark:text-secondary-200 transition-colors"
        title="Numbered List"
        @click="execCmd('insertOrderedList')"
      >
        <ListOrdered class="w-3.5 h-3.5" />
      </button>

      <div class="w-px h-5 bg-secondary-200 dark:border-secondary-700 mx-1" />

      <!-- Table / Divider / Quote -->
      <button
        type="button"
        class="p-1.5 rounded hover:bg-secondary-200 dark:hover:bg-secondary-700 text-secondary-700 dark:text-secondary-200 transition-colors"
        title="Blockquote"
        @click="setBlockFormat('blockquote')"
      >
        <Quote class="w-3.5 h-3.5" />
      </button>
      <button
        type="button"
        class="p-1.5 rounded hover:bg-secondary-200 dark:hover:bg-secondary-700 text-secondary-700 dark:text-secondary-200 transition-colors"
        title="Insert Routine Table"
        @click="insertWorkoutTable"
      >
        <Table class="w-3.5 h-3.5" />
      </button>
      <button
        type="button"
        class="p-1.5 rounded hover:bg-secondary-200 dark:hover:bg-secondary-700 text-secondary-700 dark:text-secondary-200 transition-colors"
        title="Horizontal Line"
        @click="execCmd('insertHorizontalRule')"
      >
        <Minus class="w-3.5 h-3.5" />
      </button>

      <!-- Clear formatting -->
      <div class="flex-1" />
      <button
        type="button"
        class="p-1.5 rounded hover:bg-secondary-200 dark:hover:bg-secondary-700 text-secondary-500 hover:text-secondary-800 dark:hover:text-white transition-colors text-xs flex items-center gap-1"
        title="Clear formatting"
        @click="execCmd('removeFormat')"
      >
        <Eraser class="w-3.5 h-3.5" />
        <span class="hidden sm:inline text-[11px]">Clear</span>
      </button>
    </div>

    <!-- Editable Canvas -->
    <div
      ref="editorRef"
      contenteditable="true"
      :data-placeholder="placeholder"
      class="app-rich-editor-content p-4 min-h-[160px] max-h-[360px] overflow-y-auto text-sm text-secondary-900 dark:text-white focus:outline-none leading-relaxed"
      @input="onInput"
      @blur="onBlur"
    />

    <!-- Footer Stats -->
    <div class="px-3 py-1 bg-secondary-50/70 dark:bg-secondary-900/40 border-t border-secondary-100 dark:border-secondary-800 text-[11px] text-secondary-400 flex items-center justify-between">
      <span>{{ wordCount }} words &bull; {{ charCount }} characters</span>
      <span class="text-[10px] text-secondary-400 opacity-80">Supports rich formatting &amp; tables</span>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import {
    Bold,
    Italic,
    Underline,
    Strikethrough,
    List,
    ListOrdered,
    Quote,
    Table,
    Minus,
    Eraser,
} from 'lucide-vue-next';

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Write formatted workout routine, exercises, sets, reps, or guidelines...' },
});

const emit = defineEmits(['update:modelValue']);

const editorRef = ref(null);
const currentBlockFormat = ref('p');

function execCmd(command, value = null) {
    editorRef.value?.focus();
    document.execCommand(command, false, value);
    emitUpdate();
}

function setBlockFormat(tag) {
    editorRef.value?.focus();
    document.execCommand('formatBlock', false, `<${tag}>`);
    currentBlockFormat.value = tag;
    emitUpdate();
}

function insertWorkoutTable() {
    editorRef.value?.focus();
    const tableHtml = `
      <table style="width:100%; border-collapse:collapse; margin: 8px 0; border:1px solid #cbd5e1;">
        <thead>
          <tr style="background:#f1f5f9; border-bottom:1px solid #cbd5e1;">
            <th style="padding:6px 8px; border:1px solid #cbd5e1; text-align:left; font-size:12px;">Exercise</th>
            <th style="padding:6px 8px; border:1px solid #cbd5e1; text-align:center; font-size:12px;">Sets</th>
            <th style="padding:6px 8px; border:1px solid #cbd5e1; text-align:center; font-size:12px;">Reps</th>
            <th style="padding:6px 8px; border:1px solid #cbd5e1; text-align:center; font-size:12px;">Rest</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td style="padding:6px 8px; border:1px solid #cbd5e1;">Bench Press</td>
            <td style="padding:6px 8px; border:1px solid #cbd5e1; text-align:center;">3</td>
            <td style="padding:6px 8px; border:1px solid #cbd5e1; text-align:center;">10-12</td>
            <td style="padding:6px 8px; border:1px solid #cbd5e1; text-align:center;">60s</td>
          </tr>
          <tr>
            <td style="padding:6px 8px; border:1px solid #cbd5e1;">Incline Dumbbell Press</td>
            <td style="padding:6px 8px; border:1px solid #cbd5e1; text-align:center;">3</td>
            <td style="padding:6px 8px; border:1px solid #cbd5e1; text-align:center;">12</td>
            <td style="padding:6px 8px; border:1px solid #cbd5e1; text-align:center;">60s</td>
          </tr>
        </tbody>
      </table>
      <p><br></p>
    `;
    document.execCommand('insertHTML', false, tableHtml);
    emitUpdate();
}

function emitUpdate() {
    if (!editorRef.value) return;
    const html = editorRef.value.innerHTML;
    emit('update:modelValue', html === '<p><br></p>' || html === '<br>' ? '' : html);
}

function onInput() {
    emitUpdate();
}

function onBlur() {
    emitUpdate();
}

const textOnly = computed(() => {
    const raw = props.modelValue || '';
    return raw.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
});

const wordCount = computed(() => {
    if (!textOnly.value) return 0;
    return textOnly.value.split(/\s+/).filter(Boolean).length;
});

const charCount = computed(() => textOnly.value.length);

watch(
    () => props.modelValue,
    (newVal) => {
        if (editorRef.value && editorRef.value.innerHTML !== newVal) {
            editorRef.value.innerHTML = newVal || '';
        }
    }
);

onMounted(() => {
    if (editorRef.value) {
        editorRef.value.innerHTML = props.modelValue || '';
    }
});
</script>

<style>
.app-rich-editor-content:empty:before {
    content: attr(data-placeholder);
    color: #94a3b8;
    pointer-events: none;
    display: block;
}

.app-rich-editor-content h2 {
    font-size: 1.25rem;
    font-weight: 700;
    margin-top: 0.75rem;
    margin-bottom: 0.35rem;
}

.app-rich-editor-content h3 {
    font-size: 1.1rem;
    font-weight: 600;
    margin-top: 0.5rem;
    margin-bottom: 0.25rem;
}

.app-rich-editor-content h4 {
    font-size: 0.95rem;
    font-weight: 600;
    margin-top: 0.5rem;
    margin-bottom: 0.25rem;
}

.app-rich-editor-content ul {
    list-style-type: disc;
    padding-left: 1.25rem;
    margin: 0.4rem 0;
}

.app-rich-editor-content ol {
    list-style-type: decimal;
    padding-left: 1.25rem;
    margin: 0.4rem 0;
}

.app-rich-editor-content blockquote {
    border-left: 3px solid #e11d48;
    padding-left: 0.75rem;
    margin: 0.5rem 0;
    font-style: italic;
    color: #64748b;
}

.dark .app-rich-editor-content blockquote {
    color: #94a3b8;
}

.app-rich-editor-content table {
    width: 100%;
    border-collapse: collapse;
    margin: 0.5rem 0;
}

.app-rich-editor-content th,
.app-rich-editor-content td {
    border: 1px solid #cbd5e1;
    padding: 0.35rem 0.5rem;
}

.dark .app-rich-editor-content th,
.dark .app-rich-editor-content td {
    border-color: #334155;
}
</style>
