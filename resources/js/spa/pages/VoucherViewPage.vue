<template>
  <section class="app-page-frame">
    <AppPageHeader show-back>
      <template v-if="voucher && canManage && voucher.status !== 'redeemed'" #cta-slot>
        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-4 py-2 border border-secondary-300 dark:border-secondary-700 text-secondary-700 dark:text-secondary-200 text-sm font-medium rounded-xl hover:bg-secondary-100 dark:hover:bg-secondary-800 transition-colors"
          @click="openEditModal"
        >
          Edit
        </button>
        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-50"
          :disabled="deleting"
          @click="deleteVoucher"
        >
          {{ deleting ? 'Deleting…' : 'Delete' }}
        </button>
      </template>
    </AppPageHeader>

    <div v-if="loading" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">
      Loading...
    </div>

    <div v-else-if="errorMessage" class="p-6">
      <div class="app-alert app-alert-error">
        {{ errorMessage }}
      </div>
    </div>

    <div v-else-if="voucher" class="app-page-scroll space-y-5">
      <!-- Main detail card -->
      <div class="app-surface rounded-2xl p-4 md:p-6">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-5">
          <div class="min-w-0">
            <h1 class="text-xl font-bold text-secondary-900 dark:text-white">
              {{ voucher.name }}
            </h1>
            <p class="mt-1 font-mono text-xs text-secondary-400 dark:text-secondary-500 select-all break-all">
              {{ voucher.uuid }}
            </p>
          </div>
          <span class="self-start px-3 py-1 text-xs font-semibold rounded-full shrink-0" :class="statusClass(voucher.status)">
            {{ capitalize(voucher.status) }}
          </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Amount
            </p>
            <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400">
              {{ formatMoney(voucher.amount) }}
            </p>
          </div>
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Valid From
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ voucher.valid_from || '—' }}
            </p>
          </div>
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Valid Until
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ voucher.valid_until || '—' }}
            </p>
          </div>
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Created By
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ voucher.created_by?.name || '—' }}
            </p>
          </div>
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Created At
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ formatDate(voucher.created_at) }}
            </p>
          </div>
        </div>
      </div>

      <!-- Redemption card -->
      <div v-if="voucher.redemption" class="app-surface rounded-2xl p-4 md:p-6">
        <h2 class="text-sm font-semibold text-secondary-900 dark:text-white mb-4 flex items-center gap-2">
          <span class="h-2 w-2 rounded-full bg-blue-500 shrink-0" />
          Redemption Record
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Member
            </p>
            <RouterLink
              v-if="voucher.redemption.member"
              :to="`/members/${voucher.redemption.member.id}`"
              class="font-medium text-primary-600 dark:text-primary-400 hover:underline"
            >
              {{ voucher.redemption.member.name }}
            </RouterLink>
            <p v-else class="font-medium text-secondary-800 dark:text-secondary-200">
              —
            </p>
            <p v-if="voucher.redemption.member?.biometric_member_id" class="text-xs text-secondary-400 mt-0.5">
              {{ voucher.redemption.member.biometric_member_id }}
            </p>
          </div>
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Processed By
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ voucher.redemption.redeemed_by?.name || '—' }}
            </p>
          </div>
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Redeemed At
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ formatDate(voucher.redemption.redeemed_at) }}
            </p>
          </div>
        </div>

        <div v-if="voucher.redemption.notes" class="mt-4">
          <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
            Notes
          </p>
          <p class="text-sm text-secondary-800 dark:text-secondary-200 whitespace-pre-wrap">
            {{ voucher.redemption.notes }}
          </p>
        </div>
      </div>
    </div>

    <!-- ── Edit Modal ── -->
    <div v-if="editModalOpen" class="fixed inset-0 z-40 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/45" @click="closeEditModal" />
      <div class="relative z-10 w-full max-w-md rounded-2xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 p-5 shadow-xl">
        <div class="flex items-start justify-between gap-3 mb-4">
          <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">
            Edit Voucher
          </h3>
          <button type="button" class="text-secondary-400 hover:text-secondary-700 dark:hover:text-secondary-200" @click="closeEditModal">
            ✕
          </button>
        </div>

        <div v-if="formError" class="mb-3 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-3 py-2 text-sm text-red-700 dark:text-red-200">
          {{ formError }}
        </div>

        <form class="space-y-3" @submit.prevent="submitEdit">
          <div>
            <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">Voucher Name <span class="text-red-500">*</span></label>
            <input
              v-model="form.name"
              type="text"
              required
              maxlength="255"
              class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
              placeholder="e.g. Welcome Gift"
            />
          </div>
          <div>
            <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">Amount <span class="text-red-500">*</span></label>
            <input
              v-model="form.amount"
              type="number"
              min="0.01"
              step="0.01"
              required
              class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
              placeholder="0.00"
            />
          </div>
          <div>
            <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">Status <span class="text-red-500">*</span></label>
            <select
              v-model="form.status"
              required
              class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
            >
              <option value="active">
                Active
              </option>
              <option value="inactive">
                Inactive
              </option>
            </select>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">Valid From <span class="text-xs text-secondary-400">(optional)</span></label>
              <AppFormDateInput
                v-model="form.valid_from"
                input-class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">Valid Until <span class="text-xs text-secondary-400">(optional)</span></label>
              <AppFormDateInput
                v-model="form.valid_until"
                input-class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
              />
            </div>
          </div>
          <div class="flex items-center justify-end gap-2 pt-1">
            <button type="button" class="px-4 py-2 text-sm rounded-lg border border-secondary-300 dark:border-secondary-600 text-secondary-700 dark:text-secondary-200 hover:bg-secondary-100 dark:hover:bg-secondary-800" @click="closeEditModal">
              Cancel
            </button>
            <button type="submit" class="px-4 py-2 text-sm font-semibold rounded-lg bg-primary-600 hover:bg-primary-700 text-white disabled:opacity-50" :disabled="formSubmitting">
              {{ formSubmitting ? 'Saving…' : 'Save Changes' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { apiRequest } from '../composables/useApiClient';
import { useDateTimeFormat } from '../composables/useDateTimeFormat';
import { useAppContext } from '../composables/useAppContext';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppFormDateInput from '../components/forms/AppFormDateInput.vue';

const route = useRoute();
const router = useRouter();
const context = useAppContext();

const loading = ref(false);
const errorMessage = ref('');
const voucher = ref(null);
const deleting = ref(false);

const canManage = computed(() => Boolean(context.permissions?.vouchersManage));

// ── Helpers ───────────────────────────────────────────────────────────
const moneyFormatter = new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

function formatMoney(v) {
    return moneyFormatter.format(Number(v) || 0);
}

const { formatDate } = useDateTimeFormat();

function capitalize(s) {
    return s ? s.charAt(0).toUpperCase() + s.slice(1) : '';
}

function statusClass(status) {
    if (status === 'active')   return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300';
    if (status === 'inactive') return 'bg-secondary-100 text-secondary-600 dark:bg-secondary-800 dark:text-secondary-400';
    if (status === 'redeemed') return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300';
    return '';
}

// ── Load ──────────────────────────────────────────────────────────────
async function load() {
    loading.value = true;
    errorMessage.value = '';
    try {
        voucher.value = await apiRequest(`/api/vouchers/${route.params.id}`);
    } catch {
        errorMessage.value = 'Failed to load voucher.';
    } finally {
        loading.value = false;
    }
}

// ── Delete ────────────────────────────────────────────────────────────
async function deleteVoucher() {
    if (!confirm(`Delete voucher "${voucher.value?.name}"? This cannot be undone.`)) return;
    deleting.value = true;
    errorMessage.value = '';
    try {
        await apiRequest(`/api/vouchers/${route.params.id}`, { method: 'delete' });
        router.push('/vouchers');
    } catch (err) {
        errorMessage.value = err?.response?.data?.message || 'Failed to delete voucher.';
        deleting.value = false;
    }
}

// ── Edit Modal ────────────────────────────────────────────────────────
const editModalOpen = ref(false);
const formSubmitting = ref(false);
const formError = ref('');
const form = ref({ name: '', amount: '', status: 'active', valid_from: '', valid_until: '' });

function openEditModal() {
    form.value = {
        name: voucher.value.name,
        amount: voucher.value.amount,
        status: voucher.value.status,
        valid_from: voucher.value.valid_from || '',
        valid_until: voucher.value.valid_until || '',
    };
    formError.value = '';
    editModalOpen.value = true;
}

function closeEditModal() {
    editModalOpen.value = false;
}

async function submitEdit() {
    formSubmitting.value = true;
    formError.value = '';
    try {
        await apiRequest(`/api/vouchers/${route.params.id}`, {
            method: 'put',
            data: {
                name: form.value.name,
                amount: form.value.amount,
                status: form.value.status,
                valid_from: form.value.valid_from || null,
                valid_until: form.value.valid_until || null,
            },
        });
        closeEditModal();
        await load();
    } catch (err) {
        formError.value = err?.response?.data?.message || 'Failed to save voucher.';
    } finally {
        formSubmitting.value = false;
    }
}

onMounted(() => load());
</script>
