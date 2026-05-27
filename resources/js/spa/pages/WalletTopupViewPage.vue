<template>
  <section class="app-page-frame">
    <AppPageHeader show-back />

    <div v-if="loading" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">
      Loading...
    </div>

    <div v-else-if="errorMessage" class="p-6 text-sm text-red-600 dark:text-red-400">
      {{ errorMessage }}
    </div>

    <div v-else class="app-page-scroll space-y-5">
      <!-- Member card -->
      <div class="app-surface rounded-2xl p-4 md:p-6">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-4">
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Member
            </p>
            <h1 class="text-xl font-bold text-secondary-900 dark:text-white">
              {{ topup.member?.name || '—' }}
            </h1>
            <p v-if="topup.member?.biometric_member_id" class="mt-0.5 text-sm text-secondary-500 dark:text-secondary-400">
              {{ topup.member.biometric_member_id }}
            </p>
          </div>
          <RouterLink
            v-if="topup.member?.id"
            :to="`/members/${topup.member.id}`"
            class="self-start inline-flex items-center gap-1.5 px-3 py-1.5 bg-secondary-100 hover:bg-secondary-200 dark:bg-secondary-800 dark:hover:bg-secondary-700 text-secondary-700 dark:text-secondary-200 text-sm font-medium rounded-lg transition-colors"
          >
            View Profile
          </RouterLink>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
          <div v-if="topup.member?.phone">
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Phone
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ topup.member.phone }}
            </p>
          </div>
          <div v-if="topup.member?.email">
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Email
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ topup.member.email }}
            </p>
          </div>
        </div>
      </div>

      <!-- Topup details card -->
      <div class="app-surface rounded-2xl p-4 md:p-6">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-5">
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Wallet Top-up
            </p>
            <h2 class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
              + {{ money(topup.amount) }}
            </h2>
          </div>
          <span class="self-start px-3 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 uppercase tracking-wide">
            Topup
          </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Topup Date
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ topup.topup_date || '—' }}
            </p>
          </div>
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Company Account
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ topup.account_name || '—' }}
            </p>
          </div>
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Reference
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ topup.reference_number || '—' }}
            </p>
          </div>
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Recorded By
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ topup.created_by_name || '—' }}
            </p>
          </div>
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Recorded At
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ topup.created_at || '—' }}
            </p>
          </div>
        </div>

        <div v-if="topup.notes" class="mt-5">
          <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
            Notes
          </p>
          <p class="text-sm text-secondary-800 dark:text-secondary-200 whitespace-pre-wrap">
            {{ topup.notes }}
          </p>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { apiRequest } from '../composables/useApiClient';
import AppPageHeader from '../components/AppPageHeader.vue';

const route = useRoute();

const loading = ref(false);
const errorMessage = ref('');
const topup = ref({});

function money(value) {
    return Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

async function load() {
    loading.value = true;
    errorMessage.value = '';
    try {
        topup.value = await apiRequest(`/api/wallet-topups/${route.params.id}`);
    } catch (err) {
        errorMessage.value = err?.response?.data?.message || 'Failed to load wallet top-up record.';
    } finally {
        loading.value = false;
    }
}

onMounted(load);
</script>
