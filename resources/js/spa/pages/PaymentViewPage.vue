<template>
  <section class="app-page-frame">
    <AppPageHeader show-back>
      <template #cta-slot>
        <button
          v-if="canManage && !payment.is_paid"
          type="button"
          class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition-colors"
          @click="openPayModal"
        >
          Pay Now
        </button>
        <RouterLink
          v-if="canManage"
          :to="`/accounting/payments/${route.params.id}/edit`"
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
            <div class="flex flex-wrap items-center gap-2">
              <h1 class="text-xl font-bold text-secondary-900 dark:text-white">
                <RouterLink
                  v-if="payment.member_id"
                  :to="`/members/${payment.member_id}`"
                  class="hover:text-primary-600 dark:hover:text-primary-400 hover:underline transition-colors"
                >
                  {{ payment.member_name || 'Member' }}
                </RouterLink>
                <span v-else>
                  {{ payment.member_name || 'Walk-in / Unspecified' }}
                </span>
              </h1>
              <span
                v-if="!payment.is_paid"
                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold border bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800"
              >
                Outstanding
              </span>
              <span
                v-else
                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold border bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800"
              >
                Paid
              </span>
            </div>
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
              Payment Method
            </p>
            <div class="mt-0.5">
              <span
                v-if="payment.is_paid && payment.payment_method_name"
                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border"
                :class="`${getColorClasses(payment.payment_method_color).bg} ${getColorClasses(payment.payment_method_color).text} ${getColorClasses(payment.payment_method_color).border}`"
              >
                <component :is="getIconComponent(payment.payment_method_icon)" class="w-3.5 h-3.5 shrink-0" />
                <span>{{ payment.payment_method_name }}</span>
              </span>
              <span
                v-else-if="!payment.is_paid"
                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800"
              >
                Outstanding
              </span>
              <p v-else class="font-medium text-secondary-800 dark:text-secondary-200">
                {{ payment.account_name || '—' }}
              </p>
            </div>
            <p v-if="payment.is_paid && payment.account_name" class="mt-0.5 text-xs text-secondary-500 dark:text-secondary-400">
              {{ payment.account_name }}
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
              payment Valid from
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ payment.start_date || '—' }}
            </p>
          </div>
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              payment Valid to
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ payment.end_date || '—' }}
            </p>
          </div>
          <div v-if="payment.settlement_status">
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Settlement
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ payment.settlement_status }}
            </p>
          </div>
          <div v-if="payment.settlement_deduction_amount !== null && payment.settlement_deduction_amount !== undefined">
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Fee / Net
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ money(payment.settlement_deduction_amount) }} / {{ money(payment.settlement_net_amount) }}
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

    <!-- Pay Now Modal -->
    <div v-if="payModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/45" @click="closePayModal" />
      <div class="relative z-10 w-full max-w-md rounded-xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 p-4 md:p-5 shadow-xl">
        <div class="flex items-start justify-between gap-3">
          <div>
            <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">
              Settle Outstanding Payment
            </h3>
            <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-1">
              Select a payment method to settle this payment of {{ money(payment.amount) }}.
            </p>
          </div>
          <button type="button" class="text-secondary-500 hover:text-secondary-700 dark:hover:text-secondary-200" @click="closePayModal">
            ✕
          </button>
        </div>

        <div class="mt-4">
          <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Payment Method</label>
          <AppPaymentMethodSelect
            v-model="selectedAccount"
            :methods="paymentMethods"
            :member-id="payment.member_id ?? undefined"
            :amount="parseFloat(payment.amount) || 0"
          />
        </div>

        <div class="mt-5 flex items-center justify-end gap-2">
          <button type="button" class="px-4 py-2 text-sm rounded-lg border border-secondary-300 dark:border-secondary-600 text-secondary-700 dark:text-secondary-100 hover:bg-secondary-100 dark:hover:bg-secondary-800" @click="closePayModal">
            Cancel
          </button>
          <button
            type="button"
            class="px-4 py-2 text-sm font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white disabled:opacity-50"
            :disabled="payModalSaving || !selectedAccount"
            @click="confirmPayment"
          >
            {{ payModalSaving ? 'Processing...' : 'Settle Payment' }}
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import { apiRequest } from '../composables/useApiClient';
import { useAppContext } from '../composables/useAppContext';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppPaymentMethodSelect from '../components/forms/AppPaymentMethodSelect.vue';
import { getColorClasses, getIconComponent } from '../utils/paymentMethodHelper';

const route = useRoute();
const router = useRouter();
const context = useAppContext();

const loading = ref(false);
const errorMessage = ref('');
const payment = ref({});
const deleting = ref(false);

const paymentMethods = ref([]);
const accounts = ref([]);
const payModalOpen = ref(false);
const payModalSaving = ref(false);
const selectedAccount = ref(null);

const canManage = computed(() => Boolean(context.permissions?.paymentsManage));

function money(value) {
    return Number(value || 0).toFixed(2);
}

async function openPayModal() {
    if (paymentMethods.value.length === 0) {
        try {
            const response = await apiRequest('/api/payments/meta');
            paymentMethods.value = response.payment_methods || [];
            accounts.value = response.accounts || [];
            if (paymentMethods.value.length > 0) {
                selectedAccount.value = paymentMethods.value[0].id;
            }
        } catch { /* ignore */ }
    }
    payModalOpen.value = true;
}

function closePayModal() {
    payModalOpen.value = false;
}

async function confirmPayment() {
    if (!selectedAccount.value) return;
    payModalSaving.value = true;
    try {
        const isWallet = selectedAccount.value === 'member_wallet';
        const payload = {
            payment_method_id: isWallet ? null : selectedAccount.value,
            payment_method: isWallet ? 'member_wallet' : null,
        };
        await apiRequest(`/api/payments/${route.params.id}/mark-as-paid`, {
            method: 'POST',
            data: payload,
        });
        payModalOpen.value = false;
        await load(); // Reload page content
    } catch (e) {
        alert(e?.response?.data?.message || 'Failed to submit payment.');
    } finally {
        payModalSaving.value = false;
    }
}

async function deletePayment() {
    if (!confirm('Delete this payment record?')) return;
    deleting.value = true;
    try {
        await apiRequest(`/api/payments/${route.params.id}`, { method: 'DELETE' });
        router.push('/accounting/payments');
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
