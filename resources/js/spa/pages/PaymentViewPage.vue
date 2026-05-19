<template>
  <section class="app-page-frame">
    <AppPageHeader show-back>
      <template #cta-slot>
        <RouterLink
          v-if="canManage"
          :to="`/payments/${route.params.id}/edit`"
          class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-xl transition-colors"
        >
          Edit Payment
        </RouterLink>
        <button
          v-if="canManage"
          type="button"
          class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-50"
          :disabled="deleting"
          @click="deletePayment"
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
              {{ payment.member_name || 'Walk-in / Unspecified' }}
            </h1>
            <p v-if="payment.member_phone" class="mt-1 text-sm text-secondary-500 dark:text-secondary-400">
              {{ payment.member_phone }}
            </p>
          </div>
          <span class="self-start px-3 py-1 text-lg font-bold text-primary-600 dark:text-primary-400">
            {{ money(payment.amount) }}
          </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Payment Date
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ payment.payment_date || '—' }}
            </p>
          </div>
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Account
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ payment.account_name || '—' }}
            </p>
          </div>
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Reference
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ payment.reference_number || '—' }}
            </p>
          </div>
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Payment Plan
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ payment.payment_plan_name || '—' }}
            </p>
          </div>
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Membership Start
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ payment.start_date || '—' }}
            </p>
          </div>
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Membership End
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ payment.end_date || '—' }}
            </p>
          </div>
        </div>

        <div v-if="payment.notes" class="mt-4">
          <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
            Notes
          </p>
          <p class="text-sm text-secondary-800 dark:text-secondary-200 whitespace-pre-wrap">
            {{ payment.notes }}
          </p>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiRequest } from '../composables/useApiClient';
import { useAppContext } from '../composables/useAppContext';
import AppPageHeader from '../components/AppPageHeader.vue';

const route = useRoute();
const router = useRouter();
const context = useAppContext();

const loading = ref(false);
const errorMessage = ref('');
const payment = ref({});
const deleting = ref(false);

const canManage = computed(() => Boolean(context.permissions?.paymentsManage));

function money(value) {
    return Number(value || 0).toFixed(2);
}

async function deletePayment() {
    if (!confirm('Delete this payment record?')) return;
    deleting.value = true;
    try {
        await apiRequest(`/api/payments/${route.params.id}`, { method: 'DELETE' });
        router.push('/payments');
    } catch (e) {
        alert(e?.response?.data?.message || 'Failed to delete payment.');
    } finally {
        deleting.value = false;
    }
}

async function load() {
    loading.value = true;
    errorMessage.value = '';
    try {
        const response = await apiRequest(`/api/payments/${route.params.id}`);
        payment.value = response.data || response;
    } catch {
        errorMessage.value = 'Failed to load payment.';
    } finally {
        loading.value = false;
    }
}

onMounted(() => load());
</script>
