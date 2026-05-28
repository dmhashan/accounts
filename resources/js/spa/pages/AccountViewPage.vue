<template>
  <section class="app-page-frame">
    <AppPageHeader show-back>
      <template #cta-slot>
        <RouterLink
          :to="`/accounts/${route.params.id}/edit`"
          class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-xl transition-colors"
        >
          Edit Account
        </RouterLink>
        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-50"
          :disabled="deleting"
          @click="deleteAccount"
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
        <h1 class="text-xl font-bold text-secondary-900 dark:text-white mb-4">
          {{ account.name }}
        </h1>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Opening Balance
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ money(account.opening_balance) }}
            </p>
          </div>
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Current Balance
            </p>
            <p class="font-bold text-lg text-secondary-900 dark:text-white">
              {{ money(account.current_balance) }}
            </p>
          </div>
        </div>

        <div v-if="account.description" class="mt-4">
          <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
            Description
          </p>
          <p class="text-sm text-secondary-800 dark:text-secondary-200 whitespace-pre-wrap">
            {{ account.description }}
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
const account = ref({});
const deleting = ref(false);

function money(value) {
    return Number(value || 0).toFixed(2);
}

async function deleteAccount() {
    if (!confirm(`Delete account "${account.value.name}"?`)) return;
    deleting.value = true;
    try {
        await apiRequest(`/api/accounts/${route.params.id}`, { method: 'DELETE' });
        router.push('/accounts');
    } catch (e) {
        alert(e?.response?.data?.message || 'Failed to delete account.');
    } finally {
        deleting.value = false;
    }
}

async function load() {
    loading.value = true;
    errorMessage.value = '';
    try {
        const response = await apiRequest(`/api/accounts/${route.params.id}`);
        account.value = response.data || response;
    } catch {
        errorMessage.value = 'Failed to load account.';
    } finally {
        loading.value = false;
    }
}

onMounted(() => load());
</script>
