<template>
  <section class="app-page-frame">
    <AppPageHeader show-back>
      <template #cta-slot>
        <RouterLink
          :to="`/expenses/${route.params.id}/edit`"
          class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-xl transition-colors"
        >
          Edit Expense
        </RouterLink>
        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-50"
          :disabled="deleting"
          @click="deleteExpense"
        >
          Delete
        </button>
      </template>
    </AppPageHeader>

    <div v-if="loading" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">
      Loading...
    </div>

    <div v-else-if="errorMessage" class="p-6 text-sm text-red-600 dark:text-red-400">
      {{ errorMessage }}
    </div>

    <div v-else class="app-page-scroll space-y-5">
      <div class="app-surface rounded-2xl p-4 md:p-6">
        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3 mb-4">
          <div>
            <h1 class="text-xl font-bold text-secondary-900 dark:text-white">
              {{ expense.category || '—' }}
            </h1>
            <p class="mt-1 text-sm text-secondary-500 dark:text-secondary-400">
              {{ expense.account_name || '—' }}
            </p>
          </div>
          <span class="self-start text-lg font-bold text-red-600 dark:text-red-400">
            -{{ money(expense.amount) }}
          </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Date
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ expense.expense_date || '—' }}
            </p>
          </div>
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Account
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ expense.account_name || '—' }}
            </p>
          </div>
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Reference
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ expense.reference_number || '—' }}
            </p>
          </div>
        </div>

        <div v-if="expense.notes" class="mt-4">
          <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
            Notes
          </p>
          <p class="text-sm text-secondary-800 dark:text-secondary-200 whitespace-pre-wrap">
            {{ expense.notes }}
          </p>
        </div>
      </div>

      <div class="app-surface rounded-2xl p-4 md:p-6">
        <div class="mb-4 flex items-center justify-between gap-3">
          <div>
            <h2 class="text-base font-semibold text-secondary-900 dark:text-white">
              Documents
            </h2>
          </div>
          <RouterLink
            :to="`/expenses/${route.params.id}/edit`"
            class="inline-flex items-center gap-1.5 rounded-lg border border-secondary-300 dark:border-secondary-700 px-3 py-2 text-sm font-medium text-secondary-700 dark:text-secondary-200 hover:bg-secondary-100 dark:hover:bg-secondary-800"
          >
            Add
          </RouterLink>
        </div>

        <div v-if="documents.length === 0" class="rounded-xl border border-dashed border-secondary-300 dark:border-secondary-700 px-4 py-8 text-center text-sm text-secondary-500 dark:text-secondary-400">
          No documents uploaded yet.
        </div>

        <div v-else class="divide-y divide-secondary-200 dark:divide-secondary-700 overflow-hidden rounded-xl border border-secondary-200 dark:border-secondary-700">
          <div
            v-for="doc in documents"
            :key="doc.id"
            class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
          >
            <div class="min-w-0">
              <p class="truncate text-sm font-medium text-secondary-900 dark:text-white">
                {{ doc.original_filename }}
              </p>
              <p class="mt-1 text-xs text-secondary-500 dark:text-secondary-400">
                {{ formatFileSize(doc.file_size) }}
                <span v-if="doc.uploaded_by"> · by {{ doc.uploaded_by.name }}</span>
                <span v-if="doc.created_at"> · {{ doc.created_at }}</span>
              </p>
            </div>

            <div class="flex shrink-0 items-center gap-2">
              <button
                type="button"
                class="inline-flex items-center gap-1.5 rounded-lg border border-secondary-300 dark:border-secondary-700 px-3 py-2 text-sm font-medium text-secondary-700 dark:text-secondary-200 hover:bg-secondary-100 dark:hover:bg-secondary-800 disabled:opacity-50"
                :disabled="openingDocumentId === doc.id"
                @click="openDocument(doc)"
              >
                Open
              </button>
              <button
                type="button"
                class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 dark:border-red-800 px-3 py-2 text-sm font-medium text-red-600 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 disabled:opacity-50"
                :disabled="deletingDocumentId === doc.id"
                @click="deleteDocument(doc)"
              >
                Delete
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiRequest } from '../composables/useApiClient';
import AppPageHeader from '../components/AppPageHeader.vue';

const route = useRoute();
const router = useRouter();

const loading = ref(false);
const errorMessage = ref('');
const expense = ref({});
const deleting = ref(false);
const openingDocumentId = ref(null);
const deletingDocumentId = ref(null);

const documents = ref([]);

function money(value) {
    return Number(value || 0).toFixed(2);
}

async function deleteExpense() {
    if (!confirm(`Delete expense "${expense.value.category}"?`)) return;
    deleting.value = true;
    try {
        await apiRequest(`/api/accounts/expenses/${route.params.id}`, { method: 'DELETE' });
        router.push('/expenses');
    } catch (e) {
        alert(e?.response?.data?.message || 'Failed to delete expense.');
    } finally {
        deleting.value = false;
    }
}

async function openDocument(doc) {
    openingDocumentId.value = doc.id;
    try {
        const response = await apiRequest(`/api/accounts/expenses/${route.params.id}/documents/${doc.id}/url`);
        if (response.url) {
            window.open(response.url, '_blank', 'noopener');
        }
    } catch (e) {
        alert(e?.response?.data?.message || 'Failed to open document.');
    } finally {
        openingDocumentId.value = null;
    }
}

async function deleteDocument(doc) {
    if (!confirm(`Delete document "${doc.original_filename}"?`)) return;

    deletingDocumentId.value = doc.id;
    try {
        await apiRequest(`/api/accounts/expenses/${route.params.id}/documents/${doc.id}`, { method: 'DELETE' });
        documents.value = documents.value.filter((item) => item.id !== doc.id);
    } catch (e) {
        alert(e?.response?.data?.message || 'Failed to delete document.');
    } finally {
        deletingDocumentId.value = null;
    }
}

function formatFileSize(bytes) {
    if (!bytes) return '0 B';
    const units = ['B', 'KB', 'MB', 'GB'];
    let size = Number(bytes);
    let index = 0;
    while (size >= 1024 && index < units.length - 1) {
        size /= 1024;
        index += 1;
    }
    return `${size.toFixed(index === 0 ? 0 : 1)} ${units[index]}`;
}

async function load() {
    loading.value = true;
    errorMessage.value = '';
    try {
        const response = await apiRequest(`/api/accounts/expenses/${route.params.id}`);
        expense.value = response.data || response;
        documents.value = expense.value.documents || [];
    } catch {
        errorMessage.value = 'Failed to load expense.';
    } finally {
        loading.value = false;
    }
}

onMounted(() => load());
</script>
