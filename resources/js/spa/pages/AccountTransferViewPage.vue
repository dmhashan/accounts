<template>
  <section class="app-page-frame">
    <AppPageHeader show-back>
      <template #cta-slot>
        <RouterLink
          :to="`/accounting/transfers/${route.params.id}/edit`"
          class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-xl transition-colors"
        >
          Edit Transfer
        </RouterLink>
        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-50"
          :disabled="deleting"
          @click="deleteTransfer"
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
          <h1 class="text-xl font-bold text-secondary-900 dark:text-white">
            {{ transfer.source_account_name }} → {{ transfer.destination_account_name }}
          </h1>
          <span class="self-start text-lg font-bold text-secondary-900 dark:text-white">
            {{ money(transfer.amount) }}
          </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Date
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ transfer.transfer_date || '—' }}
            </p>
          </div>
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              From
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ transfer.source_account_name || '—' }}
            </p>
          </div>
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              To
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ transfer.destination_account_name || '—' }}
            </p>
          </div>
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Reference
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ transfer.reference_number || '—' }}
            </p>
          </div>
        </div>

        <div v-if="transfer.notes" class="mt-4">
          <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
            Notes
          </p>
          <p class="text-sm text-secondary-800 dark:text-secondary-200 whitespace-pre-wrap">
            {{ transfer.notes }}
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
const transfer = ref({});
const deleting = ref(false);

function money(value) {
    return Number(value || 0).toFixed(2);
}

async function deleteTransfer() {
    if (!confirm('Delete this transfer?')) return;
    deleting.value = true;
    try {
        await apiRequest(`/api/accounts/transfers/${route.params.id}`, { method: 'DELETE' });
        router.push('/accounting/transfers');
    } catch (e) {
        alert(e?.response?.data?.message || 'Failed to delete transfer.');
    } finally {
        deleting.value = false;
    }
}

async function load() {
    loading.value = true;
    errorMessage.value = '';
    try {
        const response = await apiRequest(`/api/accounts/transfers/${route.params.id}`);
        transfer.value = response.data || response;
    } catch {
        errorMessage.value = 'Failed to load transfer.';
    } finally {
        loading.value = false;
    }
}

onMounted(() => load());
</script>
