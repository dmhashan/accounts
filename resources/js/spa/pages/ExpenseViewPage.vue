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

async function load() {
    loading.value = true;
    errorMessage.value = '';
    try {
        const response = await apiRequest(`/api/accounts/expenses/${route.params.id}`);
        expense.value = response.data || response;
    } catch {
        errorMessage.value = 'Failed to load expense.';
    } finally {
        loading.value = false;
    }
}

onMounted(() => load());
</script>
