<template>
  <div class="space-y-4">
    <!-- Transaction History -->
    <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-700 shadow-sm overflow-hidden">
      <div class="px-5 py-3.5 border-b border-secondary-100 dark:border-secondary-800">
        <h2 class="text-xs font-semibold uppercase tracking-widest text-secondary-400 dark:text-secondary-500">
          Transaction History
        </h2>
      </div>
      <div v-if="walletLoading" class="px-5 py-6 text-center text-sm text-secondary-400">
        Loading...
      </div>
      <div v-else-if="walletTransactions.length === 0" class="px-5 py-6 text-center text-sm text-secondary-400 dark:text-secondary-500">
        No wallet transactions yet.
      </div>
      <div v-else class="divide-y divide-secondary-100 dark:divide-secondary-800">
        <div v-for="tx in walletTransactions" :key="tx.id" class="flex items-center justify-between px-5 py-3 gap-3">
          <div class="min-w-0">
            <p class="text-sm font-semibold text-secondary-900 dark:text-white">
              {{ tx.label }}
            </p>
            <p class="text-xs text-secondary-500 dark:text-secondary-400">
              {{ formatDate(tx.date) }}
              <span v-if="tx.reference"> &bull; Ref: {{ tx.reference }}</span>
            </p>
            <p v-if="tx.notes" class="text-xs text-secondary-400 dark:text-secondary-500 mt-0.5 truncate">
              {{ tx.notes }}
            </p>
          </div>
          <div class="shrink-0 text-right">
            <p
              class="text-sm font-bold"
              :class="tx.direction === 'credit' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'"
            >
              {{ tx.direction === 'credit' ? '+' : '-' }}{{ formatMoney(tx.amount) }}
            </p>
            <span
              class="text-[10px] font-semibold uppercase tracking-wide px-1.5 py-0.5 rounded-full"
              :class="tx.direction === 'credit' ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400' : 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400'"
            >
              {{ tx.direction }}
            </span>
          </div>
        </div>
      </div>
      <div v-if="txMeta.last_page > 1" class="px-5 py-3 border-t border-secondary-100 dark:border-secondary-800 flex items-center justify-between gap-2">
        <p class="text-xs text-secondary-500 dark:text-secondary-400">
          Page {{ txMeta.current_page }} of {{ txMeta.last_page }}
        </p>
        <div class="flex gap-1">
          <button
            type="button"
            class="px-2 py-1 text-xs rounded border border-secondary-200 dark:border-secondary-700 disabled:opacity-40"
            :disabled="txMeta.current_page <= 1"
            @click="loadTransactions(txMeta.current_page - 1)"
          >
            Prev
          </button>
          <button
            type="button"
            class="px-2 py-1 text-xs rounded border border-secondary-200 dark:border-secondary-700 disabled:opacity-40"
            :disabled="txMeta.current_page >= txMeta.last_page"
            @click="loadTransactions(txMeta.current_page + 1)"
          >
            Next
          </button>
        </div>
      </div>
    </div>

    <!-- Voucher Redemption History -->
    <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-700 shadow-sm overflow-hidden">
      <div class="px-5 py-3.5 border-b border-secondary-100 dark:border-secondary-800 flex items-center gap-2">
        <svg
          class="w-4 h-4 text-violet-500"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        ><path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"
        /></svg>
        <h2 class="text-xs font-semibold uppercase tracking-widest text-secondary-400 dark:text-secondary-500">
          Voucher Redemptions
        </h2>
      </div>
      <div v-if="walletLoading" class="px-5 py-6 text-center text-sm text-secondary-400">
        Loading...
      </div>
      <div v-else-if="voucherRedemptions.length === 0" class="px-5 py-6 text-center text-sm text-secondary-400 dark:text-secondary-500">
        No vouchers redeemed yet.
      </div>
      <div v-else class="divide-y divide-secondary-100 dark:divide-secondary-800">
        <div v-for="r in voucherRedemptions" :key="r.id" class="flex items-start justify-between px-5 py-3 gap-3">
          <div class="min-w-0">
            <p class="text-sm font-semibold text-secondary-900 dark:text-white">
              {{ r.voucher?.name || 'Voucher' }}
            </p>
            <p class="text-xs font-mono text-secondary-400 dark:text-secondary-500 break-all">
              {{ r.voucher?.uuid }}
            </p>
            <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">
              {{ formatDate(r.redeemed_at) }}
              <span v-if="r.redeemed_by"> &bull; by {{ r.redeemed_by.name }}</span>
            </p>
            <p v-if="r.notes" class="text-xs text-secondary-400 dark:text-secondary-500 mt-0.5 truncate">
              {{ r.notes }}
            </p>
          </div>
          <div class="shrink-0 text-right">
            <p class="text-sm font-bold text-violet-600 dark:text-violet-400">
              +{{ formatMoney(r.voucher?.amount ?? 0) }}
            </p>
            <span class="text-[10px] font-semibold uppercase tracking-wide px-1.5 py-0.5 rounded-full bg-violet-50 dark:bg-violet-900/20 text-violet-600 dark:text-violet-400">Voucher</span>
          </div>
        </div>
      </div>
      <div v-if="voucherRedemptionMeta.last_page > 1" class="px-5 py-3 border-t border-secondary-100 dark:border-secondary-800 flex items-center justify-between gap-2">
        <p class="text-xs text-secondary-500 dark:text-secondary-400">
          Page {{ voucherRedemptionMeta.current_page }} of {{ voucherRedemptionMeta.last_page }}
        </p>
        <div class="flex gap-1">
          <button
            type="button"
            class="px-2 py-1 text-xs rounded border border-secondary-200 dark:border-secondary-700 disabled:opacity-40"
            :disabled="voucherRedemptionMeta.current_page <= 1"
            @click="loadVoucherRedemptions(voucherRedemptionMeta.current_page - 1)"
          >
            Prev
          </button>
          <button
            type="button"
            class="px-2 py-1 text-xs rounded border border-secondary-200 dark:border-secondary-700 disabled:opacity-40"
            :disabled="voucherRedemptionMeta.current_page >= voucherRedemptionMeta.last_page"
            @click="loadVoucherRedemptions(voucherRedemptionMeta.current_page + 1)"
          >
            Next
          </button>
        </div>
      </div>
    </div>

    <!-- Top-up Modal -->
    <div v-if="topupModalOpen" class="fixed inset-0 z-40 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/45" @click="closeTopupModal" />
      <div class="relative z-10 w-full max-w-md rounded-2xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 p-5 shadow-xl">
        <div class="flex items-start justify-between gap-3 mb-4">
          <div>
            <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">
              Top Up Wallet
            </h3>
            <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-0.5">
              Current balance: <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ formatMoney(currentBalance) }}</span>
            </p>
          </div>
          <button type="button" class="text-secondary-400 hover:text-secondary-700 dark:hover:text-secondary-200" @click="closeTopupModal">
            ✕
          </button>
        </div>

        <div v-if="topupError" class="mb-3 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-3 py-2 text-sm text-red-700 dark:text-red-200">
          {{ topupError }}
        </div>

        <form class="space-y-3" @submit.prevent="submitTopup">
          <div>
            <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">Amount <span class="text-red-500">*</span></label>
            <input
              v-model="topupForm.amount"
              type="number"
              min="0.01"
              step="0.01"
              required
              class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
              placeholder="0.00"
            />
          </div>
          <div>
            <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">Company Account <span class="text-red-500">*</span></label>
            <select
              v-model.number="topupForm.company_account_id"
              required
              class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
            >
              <option :value="null">
                Select account...
              </option>
              <option v-for="acc in walletAccounts" :key="acc.id" :value="acc.id">
                {{ acc.name }} — {{ formatMoney(acc.current_balance ?? 0) }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">Date <span class="text-red-500">*</span></label>
            <input
              v-model="topupForm.topup_date"
              type="date"
              required
              class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
            />
          </div>
          <div>
            <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">Reference <span class="text-xs text-secondary-400">(optional)</span></label>
            <input
              v-model="topupForm.reference_number"
              type="text"
              maxlength="255"
              class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
              placeholder="Receipt or reference number"
            />
          </div>
          <div>
            <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">Notes <span class="text-xs text-secondary-400">(optional)</span></label>
            <textarea
              v-model="topupForm.notes"
              rows="2"
              maxlength="1000"
              class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 resize-none"
            />
          </div>
          <div class="flex items-center justify-end gap-2 pt-1">
            <button type="button" class="px-4 py-2 text-sm rounded-lg border border-secondary-300 dark:border-secondary-600 text-secondary-700 dark:text-secondary-200 hover:bg-secondary-100 dark:hover:bg-secondary-800" @click="closeTopupModal">
              Cancel
            </button>
            <button
              type="submit"
              class="px-4 py-2 text-sm font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white disabled:opacity-50"
              :disabled="topupSubmitting || !topupForm.amount || !topupForm.company_account_id"
            >
              {{ topupSubmitting ? 'Processing...' : 'Top Up' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Redeem Voucher Modal -->
    <div v-if="redeemModalOpen" class="fixed inset-0 z-40 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/45" @click="closeRedeemModal" />
      <div class="relative z-10 w-full max-w-md rounded-2xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 p-5 shadow-xl">
        <div class="flex items-start justify-between gap-3 mb-4">
          <div>
            <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">
              Redeem Voucher
            </h3>
            <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-0.5">
              Current balance: <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ formatMoney(currentBalance) }}</span>
            </p>
          </div>
          <button type="button" class="text-secondary-400 hover:text-secondary-700 dark:hover:text-secondary-200" @click="closeRedeemModal">
            ✕
          </button>
        </div>

        <div v-if="redeemError" class="mb-3 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-3 py-2 text-sm text-red-700 dark:text-red-200">
          {{ redeemError }}
        </div>

        <form class="space-y-3" @submit.prevent="submitRedeem">
          <canvas ref="qrCanvasRef" class="hidden" />
          <input
            ref="qrFileInputRef"
            type="file"
            accept="image/*"
            class="hidden"
            @change="onQrFileChange"
          />
          <div>
            <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">Voucher UUID <span class="text-red-500">*</span></label>
            <input
              v-model="redeemForm.uuid"
              type="text"
              required
              maxlength="36"
              class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm font-mono text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500"
              placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
              autocomplete="off"
              spellcheck="false"
            />
            <div class="flex gap-2 mt-2">
              <button
                type="button"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors"
                :class="qrScanMode === 'camera'
                  ? 'border-violet-400 bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-300'
                  : 'border-secondary-300 dark:border-secondary-600 text-secondary-600 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-800'"
                @click="qrScanMode === 'camera' ? stopCameraScan() : startCameraScan()"
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
                  d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"
                /><path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"
                /></svg>
                {{ qrScanMode === 'camera' ? 'Stop Camera' : 'Scan QR' }}
              </button>
              <button
                type="button"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-secondary-300 dark:border-secondary-600 text-secondary-600 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-800 transition-colors"
                @click="triggerFileInput"
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
                  d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                /></svg>
                Upload Image
              </button>
            </div>
            <p v-if="qrError" class="mt-1.5 text-xs text-red-600 dark:text-red-400">
              {{ qrError }}
            </p>
            <div v-if="qrScanMode === 'camera'" class="mt-3 rounded-xl overflow-hidden bg-black relative">
              <video
                ref="qrVideoRef"
                autoplay
                playsinline
                muted
                class="w-full max-h-52 object-cover"
              />
              <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                <div class="relative w-40 h-40">
                  <span class="absolute top-0 left-0 w-6 h-6 border-t-2 border-l-2 border-violet-400 rounded-tl-sm" />
                  <span class="absolute top-0 right-0 w-6 h-6 border-t-2 border-r-2 border-violet-400 rounded-tr-sm" />
                  <span class="absolute bottom-0 left-0 w-6 h-6 border-b-2 border-l-2 border-violet-400 rounded-bl-sm" />
                  <span class="absolute bottom-0 right-0 w-6 h-6 border-b-2 border-r-2 border-violet-400 rounded-br-sm" />
                </div>
              </div>
              <p class="absolute bottom-2 inset-x-0 text-center text-[11px] text-white/70">
                Point camera at the voucher QR code
              </p>
            </div>
            <p v-if="qrScanMode === 'off' && !qrError" class="mt-1 text-xs text-secondary-400 dark:text-secondary-500">
              Enter the UUID manually or scan the voucher QR code.
            </p>
          </div>
          <div>
            <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">Notes <span class="text-xs text-secondary-400">(optional)</span></label>
            <textarea
              v-model="redeemForm.notes"
              rows="2"
              maxlength="1000"
              class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500 resize-none"
            />
          </div>
          <div class="flex items-center justify-end gap-2 pt-1">
            <button type="button" class="px-4 py-2 text-sm rounded-lg border border-secondary-300 dark:border-secondary-600 text-secondary-700 dark:text-secondary-200 hover:bg-secondary-100 dark:hover:bg-secondary-800" @click="closeRedeemModal">
              Cancel
            </button>
            <button
              type="submit"
              class="px-4 py-2 text-sm font-semibold rounded-lg bg-violet-600 hover:bg-violet-700 text-white disabled:opacity-50"
              :disabled="redeemSubmitting || !redeemForm.uuid.trim()"
            >
              {{ redeemSubmitting ? 'Redeeming...' : 'Redeem Voucher' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { nextTick, onUnmounted, ref } from 'vue';
import jsQR from 'jsqr';
import { apiRequest } from '../../composables/useApiClient';
import { useMemberFormatters } from '../../composables/useMemberFormatters';

const props = defineProps({
    memberId: { type: [Number, String], required: true },
    currentBalance: { type: Number, default: 0 },
    canManage: { type: Boolean, default: false },
});

const emit = defineEmits(['balance-updated']);

const { formatDate, formatMoney } = useMemberFormatters();

// ── Wallet state ──
const walletLoading = ref(false);
const walletTransactions = ref([]);
const txMeta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
const walletAccounts = ref([]);

// Voucher redemption history
const voucherRedemptions = ref([]);
const voucherRedemptionMeta = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 });

// Top-up modal
const topupModalOpen = ref(false);
const topupSubmitting = ref(false);
const topupError = ref('');
const topupForm = ref({ amount: '', company_account_id: null, topup_date: todayString(), reference_number: '', notes: '' });

// Redeem modal
const redeemModalOpen = ref(false);
const redeemSubmitting = ref(false);
const redeemError = ref('');
const redeemForm = ref({ uuid: '', notes: '' });

// QR scanner
const qrScanMode = ref('off');
const qrError = ref('');
const qrVideoRef = ref(null);
const qrCanvasRef = ref(null);
const qrFileInputRef = ref(null);
let qrStream = null;
let qrAnimFrame = null;

function todayString() {
    return new Date().toISOString().slice(0, 10);
}

async function loadWalletMeta() {
    try {
        const res = await apiRequest('/api/wallet/meta');
        walletAccounts.value = res.accounts || [];
    } catch { /* ignore */ }
}

async function loadTransactions(page = 1) {
    try {
        const res = await apiRequest(`/api/members/${props.memberId}/wallet/transactions?page=${page}&per_page=15`);
        walletTransactions.value = res.data || [];
        txMeta.value = res.meta || txMeta.value;
    } catch { /* ignore */ }
}

async function loadVoucherRedemptions(page = 1) {
    try {
        const res = await apiRequest(`/api/members/${props.memberId}/wallet/voucher-redemptions?page=${page}&per_page=10`);
        voucherRedemptions.value = res.data || [];
        voucherRedemptionMeta.value = res.meta || voucherRedemptionMeta.value;
    } catch { /* ignore */ }
}

async function loadWalletData() {
    walletLoading.value = true;
    try {
        await Promise.all([loadTransactions(1), loadVoucherRedemptions(1)]);
        if (walletAccounts.value.length === 0) loadWalletMeta();
    } finally {
        walletLoading.value = false;
    }
}

function openTopupModal() {
    topupForm.value = { amount: '', company_account_id: null, topup_date: todayString(), reference_number: '', notes: '' };
    topupError.value = '';
    topupModalOpen.value = true;
    loadWalletMeta();
}

function closeTopupModal() {
    topupModalOpen.value = false;
}

async function submitTopup() {
    topupSubmitting.value = true;
    topupError.value = '';
    try {
        const res = await apiRequest(`/api/members/${props.memberId}/wallet/topup`, {
            method: 'post',
            data: topupForm.value,
        });
        emit('balance-updated', res.current_balance);
        closeTopupModal();
        await Promise.all([loadTransactions(1), loadWalletMeta()]);
    } catch (err) {
        topupError.value = err?.response?.data?.message || 'Failed to process top-up.';
    } finally {
        topupSubmitting.value = false;
    }
}

function openRedeemModal() {
    redeemForm.value = { uuid: '', notes: '' };
    redeemError.value = '';
    qrScanMode.value = 'off';
    qrError.value = '';
    redeemModalOpen.value = true;
}

function closeRedeemModal() {
    stopCameraScan();
    redeemModalOpen.value = false;
}

async function submitRedeem() {
    redeemSubmitting.value = true;
    redeemError.value = '';
    try {
        const res = await apiRequest(`/api/members/${props.memberId}/wallet/redeem-voucher`, {
            method: 'post',
            data: { uuid: redeemForm.value.uuid.trim(), notes: redeemForm.value.notes || null },
        });
        emit('balance-updated', res.current_balance);
        closeRedeemModal();
        await Promise.all([loadVoucherRedemptions(1), loadTransactions(1)]);
    } catch (err) {
        redeemError.value = err?.response?.data?.message || 'Failed to redeem voucher.';
    } finally {
        redeemSubmitting.value = false;
    }
}

// ── QR Scanner ──
async function startCameraScan() {
    qrError.value = '';
    qrScanMode.value = 'camera';
    await nextTick();
    try {
        qrStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
        qrVideoRef.value.srcObject = qrStream;
        qrVideoRef.value.onloadedmetadata = () => scanVideoFrame();
    } catch {
        qrError.value = 'Camera access denied. Please allow camera permission or upload an image.';
        qrScanMode.value = 'off';
    }
}

function stopCameraScan() {
    if (qrStream) {
        qrStream.getTracks().forEach(t => t.stop());
        qrStream = null;
    }
    cancelAnimationFrame(qrAnimFrame);
    qrAnimFrame = null;
    qrScanMode.value = 'off';
}

function scanVideoFrame() {
    if (qrScanMode.value !== 'camera' || !qrVideoRef.value || !qrCanvasRef.value) return;
    const video = qrVideoRef.value;
    if (video.readyState === video.HAVE_ENOUGH_DATA) {
        const canvas = qrCanvasRef.value;
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0);
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const code = jsQR(imageData.data, imageData.width, imageData.height);
        if (code?.data) { onQrDetected(code.data); return; }
    }
    qrAnimFrame = requestAnimationFrame(scanVideoFrame);
}

function triggerFileInput() {
    qrFileInputRef.value?.click();
}

function onQrFileChange(event) {
    const file = event.target.files?.[0];
    if (!file) return;
    qrError.value = '';
    const img = new Image();
    const url = URL.createObjectURL(file);
    img.onload = () => {
        const canvas = qrCanvasRef.value;
        canvas.width = img.naturalWidth;
        canvas.height = img.naturalHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0);
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const code = jsQR(imageData.data, imageData.width, imageData.height);
        URL.revokeObjectURL(url);
        if (code?.data) {
            onQrDetected(code.data);
        } else {
            qrError.value = 'No QR code found in image. Try a clearer image.';
        }
        event.target.value = '';
    };
    img.src = url;
}

function onQrDetected(data) {
    const match = data.match(/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i);
    if (match) {
        redeemForm.value.uuid = match[0];
        stopCameraScan();
        qrError.value = '';
    } else {
        qrError.value = 'QR code does not contain a valid UUID.';
    }
}

onUnmounted(() => stopCameraScan());

// Exposed for parent to trigger modals
defineExpose({ openTopupModal, openRedeemModal, loadWalletData });
</script>
