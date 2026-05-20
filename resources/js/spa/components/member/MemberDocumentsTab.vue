<template>
  <div class="space-y-4">
    <!-- Success / Error messages -->
    <div v-if="successMessage" class="rounded-xl border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 px-4 py-3 text-sm text-green-700 dark:text-green-200">
      {{ successMessage }}
    </div>
    <div v-if="errorMessage" class="rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
      {{ errorMessage }}
    </div>

    <!-- Uploaded Documents -->
    <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-700 shadow-sm overflow-hidden">
      <div class="px-5 py-3.5 border-b border-secondary-100 dark:border-secondary-800 flex items-center justify-between gap-2">
        <h2 class="text-xs font-semibold uppercase tracking-widest text-secondary-400 dark:text-secondary-500">
          Documents
        </h2>
        <div v-if="canManage" class="flex items-center gap-2">
          <button
            type="button"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg border border-secondary-300 dark:border-secondary-600 hover:bg-secondary-100 dark:hover:bg-secondary-800 text-secondary-700 dark:text-secondary-300 transition-colors"
            @click="openFormFillModal"
          >
            <svg
              class="w-3.5 h-3.5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            ><path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
            /></svg>
            Fill Form
          </button>
          <button
            type="button"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-primary-600 hover:bg-primary-700 text-white transition-colors"
            @click="openDocUpload"
          >
            <svg
              class="w-3.5 h-3.5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            ><path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2.5"
              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"
            /></svg>
            Upload
          </button>
        </div>
      </div>
      <div v-if="documentsLoading" class="px-5 py-6 text-center text-sm text-secondary-400">
        Loading...
      </div>
      <div v-else-if="documents.length === 0" class="px-5 py-10 text-center">
        <div class="mx-auto w-12 h-12 rounded-xl bg-secondary-100 dark:bg-secondary-800 flex items-center justify-center mb-3">
          <svg
            class="w-6 h-6 text-secondary-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          ><path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="1.5"
            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
          /></svg>
        </div>
        <p class="text-sm text-secondary-500 dark:text-secondary-400">
          No documents uploaded yet.
        </p>
        <button
          v-if="canManage"
          type="button"
          class="mt-3 text-xs text-primary-600 dark:text-primary-400 hover:underline"
          @click="openDocUpload"
        >
          Upload the first document
        </button>
      </div>
      <div v-else class="divide-y divide-secondary-100 dark:divide-secondary-800">
        <div v-for="doc in documents" :key="doc.id" class="flex items-start justify-between px-5 py-3.5 gap-3">
          <div class="min-w-0 flex items-start gap-3">
            <div class="shrink-0 w-9 h-9 rounded-lg bg-secondary-100 dark:bg-secondary-800 border border-secondary-200 dark:border-secondary-700 flex items-center justify-center mt-0.5">
              <svg
                class="w-4 h-4 text-secondary-500 dark:text-secondary-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              ><path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.5"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
              /></svg>
            </div>
            <div class="min-w-0">
              <p class="text-sm font-semibold text-secondary-900 dark:text-white truncate">
                {{ doc.name }}
              </p>
              <div class="flex flex-wrap items-center gap-1.5 mt-1">
                <span class="px-1.5 py-0.5 text-[10px] font-semibold rounded-full border" :class="docCategoryColor(doc.category)">{{ docCategoryLabel(doc.category) }}</span>
                <span class="text-[11px] text-secondary-400 dark:text-secondary-500">{{ formatFileSize(doc.file_size) }}</span>
                <span v-if="doc.original_filename" class="text-[11px] text-secondary-400 dark:text-secondary-500 truncate max-w-[160px]">{{ doc.original_filename }}</span>
              </div>
              <p class="text-[11px] text-secondary-400 dark:text-secondary-500 mt-0.5">
                {{ doc.created_at }}
                <span v-if="doc.uploaded_by"> &bull; by {{ doc.uploaded_by.name }}</span>
              </p>
              <p v-if="doc.notes" class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5 line-clamp-2">
                {{ doc.notes }}
              </p>
            </div>
          </div>
          <div class="shrink-0 flex items-center gap-1 mt-0.5">
            <button
              type="button"
              title="View"
              class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-secondary-200 dark:border-secondary-700 hover:bg-secondary-100 dark:hover:bg-secondary-800 text-secondary-500 dark:text-secondary-400 transition-colors"
              @click="viewDoc(doc)"
            >
              <svg
                class="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              ><path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
              /><path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
              /></svg>
            </button>
            <button
              v-if="canManage"
              type="button"
              title="Delete"
              class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-red-200 dark:border-red-800/50 hover:bg-red-50 dark:hover:bg-red-900/20 text-red-500 dark:text-red-400 transition-colors"
              @click="deleteDoc(doc)"
            >
              <svg
                class="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              ><path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
              /></svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Document Viewer Modal -->
    <div v-if="docViewOpen" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5">
      <div class="absolute inset-0 bg-black/70" @click="closeDocView" />
      <div class="relative z-10 flex flex-col w-full max-w-4xl max-h-[92vh] rounded-2xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 shadow-2xl overflow-hidden">
        <div class="flex items-start justify-between gap-3 px-5 py-4 border-b border-secondary-100 dark:border-secondary-800 shrink-0">
          <div class="min-w-0">
            <h3 class="text-base font-semibold text-secondary-900 dark:text-white truncate">
              {{ docViewDoc?.name }}
            </h3>
            <div class="flex flex-wrap items-center gap-2 mt-1">
              <span class="px-1.5 py-0.5 text-[10px] font-semibold rounded-full border" :class="docCategoryColor(docViewDoc?.category)">{{ docCategoryLabel(docViewDoc?.category) }}</span>
              <span class="text-[11px] text-secondary-400 dark:text-secondary-500">{{ formatFileSize(docViewDoc?.file_size) }}</span>
              <span v-if="docViewDoc?.original_filename" class="text-[11px] text-secondary-400 dark:text-secondary-500 truncate max-w-[200px]">{{ docViewDoc.original_filename }}</span>
            </div>
          </div>
          <div class="flex items-center gap-1.5 shrink-0">
            <button
              type="button"
              title="Download"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg border border-secondary-200 dark:border-secondary-600 hover:bg-secondary-100 dark:hover:bg-secondary-800 text-secondary-700 dark:text-secondary-200 transition-colors"
              :disabled="docViewLoading"
              @click="downloadDocView"
            >
              <svg
                class="w-3.5 h-3.5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              ><path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
              /></svg>
              Download
            </button>
            <button type="button" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-secondary-400 hover:text-secondary-700 dark:hover:text-secondary-200 hover:bg-secondary-100 dark:hover:bg-secondary-800 transition-colors" @click="closeDocView">
              ✕
            </button>
          </div>
        </div>
        <div class="flex-1 overflow-auto bg-secondary-50 dark:bg-secondary-950 min-h-0">
          <div v-if="docViewLoading" class="flex items-center justify-center h-64">
            <div class="text-sm text-secondary-400 dark:text-secondary-500">
              Loading...
            </div>
          </div>
          <div v-else-if="docViewError" class="flex items-center justify-center h-64">
            <p class="text-sm text-red-600 dark:text-red-400">
              {{ docViewError }}
            </p>
          </div>
          <div v-else-if="docViewType === 'image'" class="flex items-center justify-center p-4">
            <img
              :src="docViewUrl"
              :alt="docViewDoc?.name"
              class="max-w-full max-h-[70vh] rounded-lg shadow-lg object-contain"
              @error="docViewError = 'Failed to load image.'"
            />
          </div>
          <div v-else-if="docViewType === 'pdf'" class="w-full h-[70vh]">
            <iframe :src="docViewUrl" class="w-full h-full border-0" title="Document preview" />
          </div>
          <div v-else class="flex flex-col items-center justify-center gap-4 py-16 px-6">
            <div class="w-16 h-16 rounded-2xl bg-secondary-200 dark:bg-secondary-800 flex items-center justify-center">
              <svg
                class="w-8 h-8 text-secondary-500 dark:text-secondary-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              ><path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.5"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
              /></svg>
            </div>
            <div class="text-center">
              <p class="text-sm font-medium text-secondary-700 dark:text-secondary-300">
                Preview not available for this file type
              </p>
              <p class="text-xs text-secondary-400 dark:text-secondary-500 mt-1">
                {{ docViewDoc?.mime_type }}
              </p>
            </div>
            <button type="button" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg bg-primary-600 hover:bg-primary-700 text-white transition-colors" @click="downloadDocView">
              <svg
                class="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              ><path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
              /></svg>
              Download File
            </button>
          </div>
        </div>
        <div v-if="docViewDoc?.notes || docViewDoc?.uploaded_by" class="shrink-0 px-5 py-3 border-t border-secondary-100 dark:border-secondary-800 bg-white dark:bg-secondary-900">
          <p v-if="docViewDoc?.notes" class="text-xs text-secondary-600 dark:text-secondary-300 leading-relaxed">
            {{ docViewDoc.notes }}
          </p>
          <p class="text-[11px] text-secondary-400 dark:text-secondary-500 mt-1">
            Uploaded {{ docViewDoc?.created_at }}
            <span v-if="docViewDoc?.uploaded_by"> by {{ docViewDoc.uploaded_by.name }}</span>
          </p>
        </div>
      </div>
    </div>

    <!-- Document Upload Modal -->
    <div v-if="docUploadOpen" class="fixed inset-0 z-40 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/45" @click="closeDocUpload" />
      <div class="relative z-10 w-full max-w-md rounded-2xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 p-5 shadow-xl">
        <div class="flex items-start justify-between gap-3 mb-4">
          <div>
            <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">
              Upload Document
            </h3>
            <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-0.5">
              PDF, images, Word, Excel &bull; max 10 MB
            </p>
          </div>
          <button type="button" class="text-secondary-400 hover:text-secondary-700 dark:hover:text-secondary-200" @click="closeDocUpload">
            ✕
          </button>
        </div>
        <div v-if="docUploadError" class="mb-3 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-3 py-2 text-sm text-red-700 dark:text-red-200">
          {{ docUploadError }}
        </div>
        <form class="space-y-3" @submit.prevent="submitDocUpload">
          <div>
            <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">File <span class="text-red-500">*</span></label>
            <input
              ref="docFileInputRef"
              type="file"
              accept=".pdf,.jpg,.jpeg,.png,.webp,.gif,.doc,.docx,.xls,.xlsx,.txt"
              required
              class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 file:mr-3 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-primary-50 file:text-primary-600 dark:file:bg-primary-900/30 dark:file:text-primary-400"
              @change="onDocFileChange"
            />
          </div>
          <div>
            <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">Document Name <span class="text-red-500">*</span></label>
            <input
              v-model="docForm.name"
              type="text"
              maxlength="255"
              required
              placeholder="e.g. Blood Test Report"
              class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
            />
          </div>
          <div>
            <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">Category <span class="text-red-500">*</span></label>
            <select
              v-model="docForm.category"
              required
              class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
            >
              <option v-for="cat in docCategories" :key="cat.value" :value="cat.value">
                {{ cat.label }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">Notes <span class="text-xs text-secondary-400">(optional)</span></label>
            <textarea
              v-model="docForm.notes"
              rows="2"
              maxlength="1000"
              placeholder="Additional notes about this document..."
              class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 resize-none"
            />
          </div>
          <div class="flex items-center justify-end gap-2 pt-1">
            <button type="button" class="px-4 py-2 text-sm rounded-lg border border-secondary-300 dark:border-secondary-600 text-secondary-700 dark:text-secondary-200 hover:bg-secondary-100 dark:hover:bg-secondary-800" @click="closeDocUpload">
              Cancel
            </button>
            <button
              type="submit"
              class="px-4 py-2 text-sm font-semibold rounded-lg bg-primary-600 hover:bg-primary-700 text-white disabled:opacity-50"
              :disabled="docUploading || !docFile || !docForm.name.trim()"
            >
              {{ docUploading ? 'Uploading...' : 'Upload' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Fill Form Modal -->
    <div v-if="formFillModalOpen" class="fixed inset-0 z-40 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/45" @click="closeFormFillModal" />
      <div class="relative z-10 w-full max-w-xl rounded-2xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 shadow-xl flex flex-col max-h-[90vh]">
        <div class="flex items-start justify-between gap-3 px-5 py-4 border-b border-secondary-100 dark:border-secondary-800 shrink-0">
          <div>
            <h3 class="text-base font-semibold text-secondary-900 dark:text-white">
              {{ formFillStep === 'pick' ? 'Select Form Template' : (formFillTemplate?.title || 'Fill Form') }}
            </h3>
            <p v-if="formFillStep === 'fill'" class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">
              For: <strong>{{ member?.first_name }} {{ member?.last_name }}</strong>
            </p>
          </div>
          <button type="button" class="text-secondary-400 hover:text-secondary-700 dark:hover:text-secondary-200 mt-0.5" @click="closeFormFillModal">
            ✕
          </button>
        </div>
        <div class="overflow-y-auto flex-1 px-5 py-4 space-y-4" :dir="formFillDir">
          <!-- Step 1: Pick template -->
          <div v-if="formFillStep === 'pick'">
            <p class="text-sm text-secondary-600 dark:text-secondary-400 mb-3">
              Choose a form template to fill for this member:
            </p>
            <div v-if="formTemplatesLoading" class="text-sm text-secondary-400 text-center py-4">
              Loading…
            </div>
            <div v-else-if="activeFormTemplates.length === 0" class="text-sm text-secondary-500 dark:text-secondary-400 text-center py-4">
              No active form templates available.
            </div>
            <div v-else class="rounded-xl border border-secondary-200 dark:border-secondary-700 overflow-hidden">
              <button
                v-for="t in activeFormTemplates"
                :key="t.id"
                type="button"
                class="flex w-full items-start gap-3 px-4 py-3 text-sm hover:bg-secondary-50 dark:hover:bg-secondary-800 text-left border-b border-secondary-100 dark:border-secondary-800 last:border-0 transition-colors"
                @click="selectFormTemplate(t)"
              >
                <div class="min-w-0 flex-1">
                  <p class="font-semibold text-secondary-900 dark:text-white">
                    {{ t.title }}
                  </p>
                  <p v-if="t.description" class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5 line-clamp-2">
                    {{ t.description }}
                  </p>
                </div>
                <svg
                  class="w-4 h-4 text-secondary-400 shrink-0 mt-0.5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                ><path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 5l7 7-7 7"
                /></svg>
              </button>
            </div>
          </div>
          <!-- Step 2: Fill fields -->
          <template v-if="formFillStep === 'fill'">
            <div v-if="formFillError" class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-3 py-2 text-sm text-red-700 dark:text-red-200">
              {{ formFillError }}
            </div>
            <div v-if="availableFormLanguages.length > 1" class="flex items-center gap-2">
              <label class="text-xs font-medium text-secondary-500 dark:text-secondary-400 shrink-0">Language:</label>
              <div class="flex flex-wrap gap-1.5">
                <button
                  v-for="lang in availableFormLanguages"
                  :key="lang.code"
                  type="button"
                  class="px-2.5 py-1 rounded-md text-xs font-medium border transition-colors"
                  :class="formFillLanguage === lang.code
                    ? 'bg-primary-600 border-primary-600 text-white'
                    : 'border-secondary-300 dark:border-secondary-600 text-secondary-600 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-800'"
                  @click="formFillLanguage = lang.code"
                >
                  {{ lang.label }}
                </button>
              </div>
            </div>
            <template v-for="field in resolvedFormFields" :key="field.id">
              <div v-if="field.type === 'heading'">
                <h3 class="text-base font-bold text-secondary-900 dark:text-white border-b border-secondary-200 dark:border-secondary-700 pb-1">
                  {{ field.label }}
                </h3>
              </div>
              <div v-else-if="field.type === 'paragraph'">
                <pre class="text-sm text-secondary-500 dark:text-secondary-400 italic whitespace-pre-wrap font-sans">{{ field.label }}</pre>
              </div>
              <div v-else-if="field.type === 'checkbox'" class="flex items-start gap-2.5">
                <input
                  :id="`ff-${field.id}`"
                  v-model="formFillResponses[field.id]"
                  type="checkbox"
                  class="mt-0.5 rounded border-secondary-300 dark:border-secondary-600 text-primary-600 focus:ring-primary-500 shrink-0"
                />
                <label :for="`ff-${field.id}`" class="text-sm text-secondary-700 dark:text-secondary-300">{{ field.label }}<span v-if="field.required" class="text-red-500 ml-0.5">*</span></label>
              </div>
              <div v-else-if="field.type === 'radio'">
                <p class="text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">
                  {{ field.label }}<span v-if="field.required" class="text-red-500 ml-0.5">*</span>
                </p>
                <div class="space-y-1.5">
                  <div v-for="opt in field.options" :key="opt" class="flex items-center gap-2">
                    <input
                      :id="`ff-${field.id}_${opt}`"
                      v-model="formFillResponses[field.id]"
                      type="radio"
                      :name="`ff-${field.id}`"
                      :value="opt"
                      class="border-secondary-300 dark:border-secondary-600 text-primary-600 focus:ring-primary-500"
                    />
                    <label :for="`ff-${field.id}_${opt}`" class="text-sm text-secondary-700 dark:text-secondary-300">{{ opt }}</label>
                  </div>
                </div>
              </div>
              <div v-else-if="field.type === 'select'">
                <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">{{ field.label }}<span v-if="field.required" class="text-red-500 ml-0.5">*</span></label>
                <select v-model="formFillResponses[field.id]" class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                  <option value="">
                    {{ field.placeholder || 'Select…' }}
                  </option>
                  <option v-for="opt in field.options" :key="opt" :value="opt">
                    {{ opt }}
                  </option>
                </select>
              </div>
              <div v-else-if="field.type === 'textarea'">
                <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">{{ field.label }}<span v-if="field.required" class="text-red-500 ml-0.5">*</span></label>
                <textarea
                  v-model="formFillResponses[field.id]"
                  rows="3"
                  :placeholder="field.placeholder || ''"
                  class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 resize-none"
                />
              </div>
              <div v-else-if="field.type === 'date'">
                <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">{{ field.label }}<span v-if="field.required" class="text-red-500 ml-0.5">*</span></label>
                <input v-model="formFillResponses[field.id]" type="date" class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500" />
              </div>
              <div v-else-if="field.type === 'number'">
                <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">{{ field.label }}<span v-if="field.required" class="text-red-500 ml-0.5">*</span></label>
                <input
                  v-model="formFillResponses[field.id]"
                  type="number"
                  :placeholder="field.placeholder || ''"
                  class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                />
              </div>
              <div v-else-if="field.type === 'signature'">
                <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">{{ field.label }}<span v-if="field.required" class="text-red-500 ml-0.5">*</span></label>
                <AppSignaturePad v-model="formFillResponses[field.id]" :height="160" />
              </div>
              <div v-else>
                <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">{{ field.label }}<span v-if="field.required" class="text-red-500 ml-0.5">*</span></label>
                <input
                  v-model="formFillResponses[field.id]"
                  type="text"
                  :placeholder="field.placeholder || ''"
                  class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                />
              </div>
            </template>
          </template>
        </div>
        <div v-if="formFillStep === 'fill'" class="px-5 py-4 border-t border-secondary-100 dark:border-secondary-800 flex items-center justify-between gap-3 shrink-0">
          <button type="button" class="text-sm text-secondary-500 hover:text-secondary-700 dark:hover:text-secondary-300" @click="formFillStep = 'pick'">
            ← Back
          </button>
          <div class="flex items-center gap-2">
            <button type="button" class="px-4 py-2 text-sm rounded-lg border border-secondary-300 dark:border-secondary-600 text-secondary-700 dark:text-secondary-200 hover:bg-secondary-100 dark:hover:bg-secondary-800" @click="closeFormFillModal">
              Cancel
            </button>
            <button
              type="button"
              class="px-5 py-2 text-sm font-semibold rounded-lg bg-primary-600 hover:bg-primary-700 text-white disabled:opacity-50 transition-colors"
              :disabled="formFillSubmitting"
              @click="submitFormFill"
            >
              {{ formFillSubmitting ? 'Submitting…' : 'Submit & Generate PDF' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { apiRequest } from '../../composables/useApiClient';
import AppSignaturePad from '../AppSignaturePad.vue';

const props = defineProps({
    memberId: { type: [Number, String], required: true },
    member: { type: Object, required: true },
    canManage: { type: Boolean, default: false },
});

// Internal messages
const successMessage = ref('');
const errorMessage = ref('');

function showSuccess(msg, timeout = 4000) {
    successMessage.value = msg;
    setTimeout(() => { successMessage.value = ''; }, timeout);
}

function showError(msg) {
    errorMessage.value = msg;
}

// ── Documents ──
const documentsLoading = ref(false);
const documents = ref([]);
const docUploadOpen = ref(false);
const docUploading = ref(false);
const docUploadError = ref('');
const docFileInputRef = ref(null);
const docForm = ref({ name: '', category: 'other', notes: '' });
const docFile = ref(null);
const docCategories = [
    { value: 'medical', label: 'Medical' },
    { value: 'identification', label: 'Identification' },
    { value: 'contract', label: 'Contract' },
    { value: 'fitness', label: 'Fitness' },
    { value: 'other', label: 'Other' },
];

async function loadDocuments() {
    documentsLoading.value = true;
    try {
        const res = await apiRequest(`/api/members/${props.memberId}/documents`);
        documents.value = res.data || [];
    } catch { /* ignore */ } finally {
        documentsLoading.value = false;
    }
}

function openDocUpload() {
    docForm.value = { name: '', category: 'other', notes: '' };
    docFile.value = null;
    docUploadError.value = '';
    docUploadOpen.value = true;
}

function closeDocUpload() {
    docUploadOpen.value = false;
}

function onDocFileChange(event) {
    const file = event.target.files?.[0];
    if (!file) return;
    docFile.value = file;
    if (!docForm.value.name) {
        docForm.value.name = file.name.replace(/\.[^.]+$/, '');
    }
}

async function submitDocUpload() {
    if (!docFile.value) { docUploadError.value = 'Please select a file.'; return; }
    docUploading.value = true;
    docUploadError.value = '';
    try {
        const formData = new FormData();
        formData.append('file', docFile.value);
        formData.append('name', docForm.value.name);
        formData.append('category', docForm.value.category);
        if (docForm.value.notes) formData.append('notes', docForm.value.notes);
        const res = await apiRequest(`/api/members/${props.memberId}/documents`, {
            method: 'post',
            data: formData,
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        documents.value.unshift(res.data);
        closeDocUpload();
        showSuccess('Document uploaded successfully.');
    } catch (err) {
        docUploadError.value = err?.response?.data?.message || 'Failed to upload document.';
    } finally {
        docUploading.value = false;
    }
}

// ── Document Viewer ──
const docViewOpen = ref(false);
const docViewDoc = ref(null);
const docViewUrl = ref('');
const docViewType = ref('other');
const docViewLoading = ref(false);
const docViewError = ref('');

function resolveDocViewType(mimeType) {
    if (!mimeType) return 'other';
    if (mimeType.startsWith('image/')) return 'image';
    if (mimeType === 'application/pdf') return 'pdf';
    return 'other';
}

async function viewDoc(doc) {
    docViewDoc.value = doc;
    docViewUrl.value = '';
    docViewError.value = '';
    docViewType.value = resolveDocViewType(doc.mime_type);
    docViewOpen.value = true;
    docViewLoading.value = true;
    try {
        const res = await apiRequest(`/api/members/${props.memberId}/documents/${doc.id}/url`);
        docViewUrl.value = res.url;
    } catch {
        docViewError.value = 'Failed to load document URL.';
    } finally {
        docViewLoading.value = false;
    }
}

function closeDocView() {
    docViewOpen.value = false;
    docViewUrl.value = '';
    docViewDoc.value = null;
}

function downloadDocView() {
    if (docViewUrl.value) {
        window.open(docViewUrl.value, '_blank', 'noopener,noreferrer');
    }
}

async function deleteDoc(doc) {
    // eslint-disable-next-line no-alert
    if (!window.confirm(`Delete "${doc.name}"? This cannot be undone.`)) return;
    try {
        await apiRequest(`/api/members/${props.memberId}/documents/${doc.id}`, { method: 'delete' });
        documents.value = documents.value.filter(d => d.id !== doc.id);
        showSuccess('Document deleted.');
    } catch (err) {
        showError(err?.response?.data?.message || 'Failed to delete document.');
    }
}

function formatFileSize(bytes) {
    if (!bytes) return '—';
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

function docCategoryLabel(cat) {
    return docCategories.find(c => c.value === cat)?.label || cat;
}

function docCategoryColor(cat) {
    const map = {
        medical: 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800',
        identification: 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-800',
        contract: 'bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-400 border-violet-200 dark:border-violet-800',
        fitness: 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800',
        other: 'bg-secondary-100 dark:bg-secondary-700 text-secondary-600 dark:text-secondary-300 border-secondary-200 dark:border-secondary-600',
    };
    return map[cat] ?? map.other;
}

// ── Forms ──
const memberSubmissions = ref([]);
const activeFormTemplates = ref([]);
const formTemplatesLoading = ref(false);
const formFillModalOpen = ref(false);
const formFillStep = ref('pick');
const formFillTemplate = ref(null);
const formFillLanguage = ref('en');
const formFillResponses = ref({});
const formFillSubmitting = ref(false);
const formFillError = ref('');

const FORM_LANGUAGES = [
    { code: 'en', label: 'English' },
    { code: 'si', label: 'සිංහල' },
    { code: 'ta', label: 'தமிழ்' },
    { code: 'fr', label: 'Français' },
    { code: 'de', label: 'Deutsch' },
    { code: 'es', label: 'Español' },
    { code: 'pt', label: 'Português' },
    { code: 'zh', label: '中文' },
    { code: 'ja', label: '日本語' },
    { code: 'ar', label: 'العربية' },
];
const RTL_LANGUAGES = ['ar'];
const formFillDir = computed(() => RTL_LANGUAGES.includes(formFillLanguage.value) ? 'rtl' : 'ltr');

const availableFormLanguages = computed(() => {
    if (!formFillTemplate.value) return [FORM_LANGUAGES[0]];
    const trans = formFillTemplate.value.translations ?? {};
    return FORM_LANGUAGES.filter(l => l.code === 'en' || trans[l.code]);
});

const resolvedFormFields = computed(() => {
    if (!formFillTemplate.value) return [];
    const lang = formFillLanguage.value;
    const trans = formFillTemplate.value.translations?.[lang]?.fields ?? {};
    return (formFillTemplate.value.fields ?? []).map(field => {
        const t = trans[field.id] ?? {};
        return { ...field, label: t.label ?? field.label, options: t.options ?? field.options };
    });
});

watch(formFillLanguage, () => {
    if (!formFillTemplate.value) return;
    for (const field of (formFillTemplate.value.fields ?? [])) {
        if (field.type === 'radio' || field.type === 'select') {
            formFillResponses.value[field.id] = '';
        }
    }
});

async function loadMemberForms() {
    try {
        const res = await apiRequest(`/api/members/${props.memberId}/form-submissions`);
        memberSubmissions.value = res.data ?? [];
    } catch { /* ignore */ }
}

async function openFormFillModal() {
    formFillStep.value = 'pick';
    formFillTemplate.value = null;
    formFillResponses.value = {};
    formFillError.value = '';
    formFillModalOpen.value = true;
    if (activeFormTemplates.value.length === 0) {
        formTemplatesLoading.value = true;
        try {
            const res = await apiRequest('/api/forms/templates/active');
            activeFormTemplates.value = res.data ?? [];
        } catch { /* ignore */ } finally {
            formTemplatesLoading.value = false;
        }
    }
}

function closeFormFillModal() {
    formFillModalOpen.value = false;
}

function selectFormTemplate(t) {
    formFillTemplate.value = t;
    formFillLanguage.value = 'en';
    const responses = {};
    (t.fields ?? []).forEach(f => {
        if (!['heading', 'paragraph'].includes(f.type)) {
            responses[f.id] = f.type === 'checkbox' ? false : '';
        }
    });
    formFillResponses.value = responses;
    formFillStep.value = 'fill';
}

async function submitFormFill() {
    formFillError.value = '';
    for (const field of (formFillTemplate.value?.fields ?? [])) {
        if (['heading', 'paragraph'].includes(field.type)) continue;
        if (field.required) {
            const val = formFillResponses.value[field.id];
            if (val === '' || val === null || val === undefined || val === false) {
                formFillError.value = `"${field.label}" is required.`;
                return;
            }
        }
    }
    formFillSubmitting.value = true;
    try {
        const res = await apiRequest(
            `/api/forms/templates/${formFillTemplate.value.id}/members/${props.memberId}/submit`,
            { method: 'post', data: { responses: formFillResponses.value, language: formFillLanguage.value } },
        );
        memberSubmissions.value.unshift(res.data);
        closeFormFillModal();
        loadDocuments();
        showSuccess('Form submitted and PDF generated.');
    } catch (err) {
        formFillError.value = err?.response?.data?.message ?? 'Failed to submit form.';
    } finally {
        formFillSubmitting.value = false;
    }
}

defineExpose({ loadDocuments, loadMemberForms });
</script>
