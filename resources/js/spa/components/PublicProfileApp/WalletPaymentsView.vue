<template>
  <div class="space-y-5 pb-6">
    <!-- Header -->
    <div class="pt-2 pb-1 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">
          Wallet &amp; Payments
        </h1>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-medium">
          Prepaid balance, membership bills, invoices &amp; transaction records
        </p>
      </div>

      <div
        v-if="parseFloat(meta.total_outstanding || 0) > 0"
        class="px-3 py-1 rounded-full text-xs font-black bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 animate-pulse flex items-center gap-1.5 shrink-0"
      >
        <span class="w-1.5 h-1.5 rounded-full bg-red-500" />
        <span>Due: {{ meta.total_outstanding }}</span>
      </div>
      <div
        v-else
        class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 flex items-center gap-1.5 shrink-0"
      >
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500" />
        <span>All Settled</span>
      </div>
    </div>

    <!-- Main Navigation Sub-Tabs Switcher (Wallet vs Payments) -->
    <div class="flex gap-1.5 p-1 bg-gray-100 dark:bg-zinc-800/80 rounded-2xl">
      <button
        type="button"
        class="flex-1 py-2 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-2 focus:outline-none cursor-pointer"
        :class="activeSection === 'wallet'
          ? 'bg-white dark:bg-zinc-700 text-gray-900 dark:text-white shadow-sm'
          : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'"
        @click="activeSection = 'wallet'"
      >
        <CreditCard class="w-4 h-4 text-emerald-500" :stroke-width="2" />
        <span>Prepaid Wallet</span>
        <span class="px-1.5 py-0.2 rounded-full text-[10px] font-black bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
          {{ formatMoney(meta.current_balance ?? 0) }}
        </span>
      </button>

      <button
        type="button"
        class="flex-1 py-2 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-2 focus:outline-none cursor-pointer"
        :class="activeSection === 'payments'
          ? 'bg-white dark:bg-zinc-700 text-gray-900 dark:text-white shadow-sm'
          : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'"
        @click="activeSection = 'payments'"
      >
        <Receipt class="w-4 h-4 text-blue-500" :stroke-width="2" />
        <span>Invoices &amp; Bills</span>
        <span
          v-if="parseFloat(meta.total_outstanding || 0) > 0"
          class="px-1.5 py-0.2 rounded-full text-[10px] font-black bg-red-500 text-white animate-pulse"
        >
          {{ outstandingTransactions.length }}
        </span>
      </button>
    </div>

    <!-- ── SECTION 1: PREPAID WALLET ──────────────────────── -->
    <div v-if="activeSection === 'wallet'" class="space-y-5">
      <!-- Digital Wallet Card -->
      <div class="pp-wallet-card p-6 relative overflow-hidden select-none shadow-xl">
        <div class="absolute -top-12 -right-12 w-48 h-48 rounded-full bg-white/10 pointer-events-none" />
        <div class="absolute -bottom-10 -left-10 w-36 h-36 rounded-full bg-black/10 pointer-events-none" />

        <div class="relative flex items-center justify-between mb-4">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-xl bg-white/20 p-1.5 backdrop-blur-sm border border-white/20 flex items-center justify-center">
              <CreditCard class="w-full h-full text-white" :stroke-width="2" />
            </div>
            <div>
              <p class="text-xs font-black tracking-wider uppercase text-white/90">
                {{ meta.tenant_name || 'Member Wallet' }}
              </p>
              <p class="text-[9px] font-semibold text-emerald-200 uppercase tracking-widest leading-none">
                Prepaid Member Account
              </p>
            </div>
          </div>
          <Wifi class="w-5 h-5 text-emerald-200 rotate-90" :stroke-width="2" />
        </div>

        <div class="relative py-1">
          <p class="text-[11px] font-bold text-emerald-200 uppercase tracking-wider mb-1">
            Available Balance
          </p>
          <p class="text-4xl sm:text-5xl font-black text-white tracking-tight leading-none">
            {{ formatMoney(meta.current_balance ?? 0) }}
          </p>
        </div>

        <div class="relative mt-6 pt-3 border-t border-white/15 flex items-center justify-between text-xs">
          <div>
            <p class="font-bold text-white uppercase tracking-tight truncate">
              {{ meta.name }}
            </p>
            <p class="text-[10px] text-emerald-200 font-mono mt-0.5">
              {{ meta.member_code || meta.member_id || '#MEMBER' }}
            </p>
          </div>
          <div class="text-right">
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-white/20 backdrop-blur-sm text-white">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-300 animate-pulse" />
              Active
            </span>
          </div>
        </div>
      </div>

      <!-- Wallet Summary Stats -->
      <div class="grid grid-cols-2 gap-3">
        <div class="pp-glass-card rounded-2xl p-4 flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
            <ArrowDownLeft class="w-5 h-5" :stroke-width="2.2" />
          </div>
          <div class="min-w-0">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
              Total Top-ups
            </p>
            <p class="text-base font-black text-gray-900 dark:text-white truncate mt-0.5">
              +{{ formatMoney(totalCredits) }}
            </p>
          </div>
        </div>

        <div class="pp-glass-card rounded-2xl p-4 flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-rose-50 dark:bg-rose-950/50 text-rose-500 dark:text-rose-400 flex items-center justify-center shrink-0">
            <ArrowUpRight class="w-5 h-5" :stroke-width="2.2" />
          </div>
          <div class="min-w-0">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
              Total Spent
            </p>
            <p class="text-base font-black text-gray-900 dark:text-white truncate mt-0.5">
              -{{ formatMoney(totalDebits) }}
            </p>
          </div>
        </div>
      </div>

      <!-- Transaction Activity -->
      <section class="space-y-3">
        <div class="flex items-center justify-between">
          <h2 class="text-base font-extrabold text-gray-900 dark:text-white tracking-tight">
            Wallet Activity Log
          </h2>
          <span v-if="txMeta.total > 0" class="text-xs font-semibold text-gray-400 dark:text-gray-500">
            {{ filteredWalletTx.length }} of {{ txMeta.total }}
          </span>
        </div>

        <!-- Filter Tabs -->
        <div class="flex gap-1.5 p-1 bg-gray-100 dark:bg-zinc-800/80 rounded-2xl">
          <button
            v-for="filter in ['all', 'credit', 'debit']"
            :key="filter"
            type="button"
            class="flex-1 py-1.5 text-xs font-bold rounded-xl transition-all capitalize focus:outline-none cursor-pointer"
            :class="walletFilter === filter
              ? 'bg-white dark:bg-zinc-700 text-gray-900 dark:text-white shadow-sm'
              : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'"
            @click="walletFilter = filter"
          >
            {{ filter === 'all' ? 'All' : (filter === 'credit' ? 'Top-ups (+)' : 'Spent (-)') }}
          </button>
        </div>

        <!-- Wallet Tx List -->
        <div v-if="filteredWalletTx.length" class="pp-glass-card rounded-3xl overflow-hidden divide-y divide-gray-100 dark:divide-zinc-800/60 shadow-sm">
          <div
            v-for="tx in filteredWalletTx"
            :key="tx.id"
            class="flex items-center gap-3.5 px-4 sm:px-5 py-4 hover:bg-gray-50/50 dark:hover:bg-zinc-800/30 transition-colors"
          >
            <div
              class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0"
              :class="tx.direction === 'credit'
                ? 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400'
                : 'bg-rose-50 dark:bg-rose-950/50 text-rose-500 dark:text-rose-400'"
            >
              <ArrowDownLeft v-if="tx.direction === 'credit'" class="w-5 h-5" :stroke-width="2.2" />
              <ShoppingBag v-else class="w-5 h-5" :stroke-width="2" />
            </div>

            <div class="flex-1 min-w-0">
              <p class="text-sm font-bold text-gray-900 dark:text-white truncate">
                {{ tx.label || (tx.direction === 'credit' ? 'Wallet Top-up' : 'Account Purchase') }}
              </p>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 truncate">
                {{ tx.date }}
                <span v-if="tx.reference" class="opacity-80">&middot; {{ tx.reference }}</span>
              </p>
            </div>

            <div class="text-right shrink-0">
              <p
                class="text-sm font-black"
                :class="tx.direction === 'credit' ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-900 dark:text-white'"
              >
                {{ tx.direction === 'credit' ? '+' : '-' }}{{ formatMoney(tx.amount) }}
              </p>
              <span
                class="inline-block text-[10px] font-extrabold px-2 py-0.5 rounded-full mt-0.5"
                :class="tx.direction === 'credit'
                  ? 'text-emerald-700 bg-emerald-50 dark:bg-emerald-950/60 dark:text-emerald-400'
                  : 'text-gray-600 bg-gray-100 dark:bg-zinc-800 dark:text-gray-300'"
              >
                {{ tx.direction === 'credit' ? 'Credit' : 'Debit' }}
              </span>
            </div>
          </div>
        </div>

        <div v-else class="pp-glass-card rounded-3xl p-8 text-center text-gray-400">
          <Receipt class="w-8 h-8 mx-auto text-gray-300 dark:text-zinc-600 mb-2" :stroke-width="1.5" />
          <p class="text-sm font-bold text-gray-700 dark:text-gray-300">
            No wallet transactions
          </p>
          <p class="text-xs text-gray-400">
            Top-ups and prepaid payments will be listed here.
          </p>
        </div>

        <!-- Load More Button -->
        <button
          v-if="txMeta.current_page < txMeta.last_page"
          type="button"
          :disabled="loadingTx"
          class="w-full py-3 rounded-2xl text-xs font-bold bg-gray-900 dark:bg-white text-white dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-100 active:scale-98 transition-all disabled:opacity-50 cursor-pointer shadow-md"
          @click="loadMoreWalletTx"
        >
          <span v-if="loadingTx" class="flex items-center justify-center gap-2">
            <span class="w-3.5 h-3.5 border-2 border-white dark:border-gray-900 border-t-transparent rounded-full animate-spin" />
            Loading more&hellip;
          </span>
          <span v-else>Load more transactions</span>
        </button>
      </section>
    </div>

    <!-- ── SECTION 2: INVOICES & PAYMENTS ───────────────────── -->
    <div v-else class="space-y-4">
      <!-- Outstanding Alert Card -->
      <div
        v-if="parseFloat(meta.total_outstanding || 0) > 0"
        class="p-4 rounded-3xl bg-gradient-to-r from-red-500/10 via-rose-500/10 to-amber-500/10 dark:from-red-950/40 dark:via-rose-950/30 dark:to-zinc-900 border border-red-500/20 flex items-center justify-between gap-3 shadow-sm"
      >
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-red-500 text-white flex items-center justify-center shrink-0 shadow-md shadow-red-500/20">
            <AlertCircle class="w-5 h-5" :stroke-width="2.2" />
          </div>
          <div>
            <p class="text-xs font-black uppercase tracking-wider text-red-600 dark:text-red-400">
              Pending Dues Breakdown
            </p>
            <p class="text-lg font-black text-gray-900 dark:text-white leading-tight mt-0.5">
              {{ meta.total_outstanding }}
            </p>
            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
              {{ outstandingTransactions.length }} payment{{ outstandingTransactions.length === 1 ? '' : 's' }} awaiting settlement
            </p>
          </div>
        </div>

        <button
          v-if="paymentsStatusTab !== 'outstanding'"
          type="button"
          class="px-3 py-1.5 text-xs font-bold rounded-xl bg-red-500 text-white shadow hover:bg-red-600 active:scale-95 transition-all cursor-pointer shrink-0"
          @click="paymentsStatusTab = 'outstanding'"
        >
          View Dues
        </button>
      </div>

      <!-- Status Tabs Switcher -->
      <div class="flex gap-1.5 p-1 bg-gray-100 dark:bg-zinc-800/80 rounded-2xl">
        <button
          type="button"
          class="flex-1 py-2 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5 focus:outline-none cursor-pointer"
          :class="paymentsStatusTab === 'outstanding'
            ? 'bg-white dark:bg-zinc-700 text-gray-900 dark:text-white shadow-sm'
            : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'"
          @click="paymentsStatusTab = 'outstanding'"
        >
          <span>Outstanding</span>
          <span
            class="px-1.5 py-0.2 rounded-full text-[10px] font-black"
            :class="paymentsStatusTab === 'outstanding'
              ? 'bg-red-500 text-white'
              : 'bg-gray-200 dark:bg-zinc-700 text-gray-600 dark:text-gray-400'"
          >
            {{ outstandingTransactions.length }}
          </span>
        </button>

        <button
          type="button"
          class="flex-1 py-2 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5 focus:outline-none cursor-pointer"
          :class="paymentsStatusTab === 'paid'
            ? 'bg-white dark:bg-zinc-700 text-gray-900 dark:text-white shadow-sm'
            : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'"
          @click="paymentsStatusTab = 'paid'"
        >
          <span>Paid</span>
          <span
            class="px-1.5 py-0.2 rounded-full text-[10px] font-black"
            :class="paymentsStatusTab === 'paid'
              ? 'bg-emerald-500 text-white'
              : 'bg-gray-200 dark:bg-zinc-700 text-gray-600 dark:text-gray-400'"
          >
            {{ paidTransactions.length }}
          </span>
        </button>

        <button
          type="button"
          class="flex-1 py-2 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5 focus:outline-none cursor-pointer"
          :class="paymentsStatusTab === 'all'
            ? 'bg-white dark:bg-zinc-700 text-gray-900 dark:text-white shadow-sm'
            : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'"
          @click="paymentsStatusTab = 'all'"
        >
          <span>All</span>
          <span
            class="px-1.5 py-0.2 rounded-full text-[10px] font-black"
            :class="paymentsStatusTab === 'all'
              ? 'bg-zinc-800 dark:bg-zinc-100 text-white dark:text-zinc-900'
              : 'bg-gray-200 dark:bg-zinc-700 text-gray-600 dark:text-gray-400'"
          >
            {{ allTransactions.length }}
          </span>
        </button>
      </div>

      <!-- Category Filter Pills & Search -->
      <div class="space-y-2">
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 no-scrollbar text-xs">
          <button
            v-for="cat in categoryOptions"
            :key="cat.key"
            type="button"
            class="px-3 py-1.5 rounded-xl font-bold whitespace-nowrap transition-all focus:outline-none cursor-pointer flex items-center gap-1.5"
            :class="selectedCategory === cat.key
              ? 'bg-gray-900 dark:bg-white text-white dark:text-zinc-900 shadow-sm'
              : 'bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-zinc-700'"
            @click="selectedCategory = cat.key"
          >
            <component :is="cat.icon" class="w-3.5 h-3.5" :stroke-width="2" />
            <span>{{ cat.label }}</span>
          </button>
        </div>

        <div v-if="allTransactions.length > 3" class="relative">
          <Search class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" :stroke-width="2" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search by plan, invoice #, ref..."
            class="w-full pl-9.5 pr-8 py-2 text-xs rounded-xl bg-gray-100 dark:bg-zinc-800/80 border border-transparent focus:border-red-500/50 focus:bg-white dark:focus:bg-zinc-800 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none transition-all"
          />
          <button
            v-if="searchQuery"
            type="button"
            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-0.5"
            @click="searchQuery = ''"
          >
            <X class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>

      <!-- Invoices & Payments List -->
      <div v-if="filteredPaymentsList.length" class="pp-glass-card rounded-3xl overflow-hidden divide-y divide-gray-100 dark:divide-zinc-800/60 shadow-sm">
        <button
          v-for="item in filteredPaymentsList"
          :key="item.id"
          type="button"
          class="w-full flex items-center gap-3.5 px-4 sm:px-5 py-4 hover:bg-gray-50 dark:hover:bg-zinc-800/40 active:bg-gray-100 dark:active:bg-zinc-800 transition-colors focus:outline-none text-left cursor-pointer group"
          @click="handleItemClick(item)"
        >
          <div
            class="w-11 h-11 rounded-2xl flex items-center justify-center shrink-0 shadow-sm"
            :class="getIconWrapperClass(item)"
          >
            <Crown v-if="item.category === 'membership'" class="w-5 h-5" :stroke-width="2" />
            <FileText v-else-if="item.category === 'invoice'" class="w-5 h-5" :stroke-width="2" />
            <Receipt v-else class="w-5 h-5" :stroke-width="2" />
          </div>

          <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2 flex-wrap">
              <p class="text-sm font-bold text-gray-900 dark:text-white group-hover:text-red-500 transition-colors truncate">
                {{ item.title }}
              </p>
              <span
                class="text-[10px] font-extrabold px-2 py-0.5 rounded-full uppercase tracking-wider"
                :class="item.isPaid
                  ? 'text-emerald-700 bg-emerald-50 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-500/20'
                  : 'text-red-700 bg-red-50 dark:bg-red-950/60 dark:text-red-400 border border-red-500/20 animate-pulse'"
              >
                {{ item.isPaid ? 'Paid' : 'Due' }}
              </span>
            </div>

            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 truncate flex items-center gap-1.5">
              <span>{{ item.subtitle }}</span>
              <span v-if="item.paymentMethod" class="inline-block opacity-60">&bull; {{ item.paymentMethod }}</span>
            </p>
          </div>

          <div class="text-right shrink-0">
            <p class="text-base font-black text-gray-900 dark:text-white">
              {{ item.amount }}
            </p>
            <p
              v-if="!item.isPaid && parseFloat(item.balance || 0) > 0"
              class="text-[11px] font-bold text-red-500 mt-0.5"
            >
              Bal: {{ item.balance }}
            </p>
            <ChevronRight
              v-else
              class="w-4 h-4 text-gray-400 ml-auto mt-1 group-hover:translate-x-0.5 transition-transform"
              :stroke-width="2"
            />
          </div>
        </button>
      </div>

      <div v-else class="pp-glass-card rounded-3xl p-10 flex flex-col items-center justify-center text-center gap-2 text-gray-400">
        <CheckCircle2 v-if="paymentsStatusTab === 'outstanding'" class="w-10 h-10 text-emerald-500" :stroke-width="1.8" />
        <Receipt v-else class="w-10 h-10 text-gray-300 dark:text-zinc-600" :stroke-width="1.5" />
        <p class="text-base font-bold text-gray-800 dark:text-gray-200">
          {{ paymentsStatusTab === 'outstanding' ? 'No Pending Dues' : 'No records found' }}
        </p>
        <p class="text-xs text-gray-400">
          {{ paymentsStatusTab === 'outstanding' ? 'Your account is in good standing.' : 'Invoices and receipts will appear here.' }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import {
    CreditCard,
    Receipt,
    ArrowDownLeft,
    ArrowUpRight,
    ShoppingBag,
    Wifi,
    AlertCircle,
    CheckCircle2,
    Crown,
    FileText,
    ChevronRight,
    Search,
    X,
    LayoutGrid,
} from 'lucide-vue-next';

const props = defineProps({
    meta:               { type: Object, default: () => ({}) },
    salesData:          { type: Array,  default: () => [] },
    paymentsData:       { type: Array,  default: () => [] },
    membershipPayments: { type: Array,  default: () => [] },
    otherPayments:      { type: Array,  default: () => [] },
    walletTransactions: { type: Array,  default: () => [] },
    walletTxMeta:       { type: Object, default: () => ({ current_page: 1, last_page: 1, total: 0, per_page: 10 }) },
});

const emit = defineEmits(['open-sale', 'open-payment']);

const MEMBER_KEY = 'public_profile_member_id';

// Section Tab: 'wallet' or 'payments'
const activeSection = ref('wallet');

// Wallet state
const walletTransactionsList = ref([...props.walletTransactions]);
const txMeta                 = ref({ ...props.walletTxMeta });
const loadingTx              = ref(false);
const walletFilter           = ref('all'); // 'all' | 'credit' | 'debit'

// Payments state
const paymentsStatusTab = ref('outstanding'); // 'outstanding' | 'paid' | 'all'
const selectedCategory  = ref('all'); // 'all' | 'membership' | 'invoice' | 'other'
const searchQuery       = ref('');

const categoryOptions = [
    { key: 'all',        label: 'All Types',  icon: LayoutGrid },
    { key: 'membership', label: 'Membership', icon: Crown },
    { key: 'invoice',    label: 'Invoices',   icon: FileText },
    { key: 'other',      label: 'Other',      icon: Receipt },
];

function getCsrfToken() {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
    return match ? decodeURIComponent(match[1]) : '';
}

// Wallet computed
const totalCredits = computed(() =>
    walletTransactionsList.value.filter(t => t.direction === 'credit').reduce((s, t) => s + (parseFloat(t.amount) || 0), 0)
);

const totalDebits = computed(() =>
    walletTransactionsList.value.filter(t => t.direction === 'debit').reduce((s, t) => s + (parseFloat(t.amount) || 0), 0)
);

const filteredWalletTx = computed(() => {
    if (walletFilter.value === 'all') return walletTransactionsList.value;
    return walletTransactionsList.value.filter(t => t.direction === walletFilter.value);
});

async function loadMoreWalletTx() {
    if (loadingTx.value) return;
    loadingTx.value = true;
    try {
        const token = localStorage.getItem(MEMBER_KEY);
        const nextPage = txMeta.value.current_page + 1;
        const res = await fetch(`/api/public/wallet/transactions?page=${nextPage}&per_page=15`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN':     getCsrfToken(),
                'X-PP-Token':       token || '',
            },
        });
        if (!res.ok) return;
        const data = await res.json();
        walletTransactionsList.value.push(...(data.data || []));
        txMeta.value = data.meta || txMeta.value;
    } catch {
        /* silent */
    } finally {
        loadingTx.value = false;
    }
}

// Unified Invoices / Payments List
const allTransactions = computed(() => {
    const list = [];

    for (const sale of props.salesData) {
        list.push({
            raw: sale,
            id: `sale_${sale.id}`,
            numericId: sale.id,
            category: 'invoice',
            title: `Invoice #${sale.id}`,
            subtitle: `${sale.created_at} · ${sale.items?.length || 0} item${sale.items?.length === 1 ? '' : 's'}`,
            date: sale.created_at,
            amount: sale.total_amount,
            paidAmount: sale.paid_amount,
            balance: sale.balance,
            isPaid: Boolean(sale.is_paid),
            paymentMethod: sale.payment_method,
        });
    }

    const sourcePayments = props.paymentsData.length
        ? props.paymentsData
        : [...props.membershipPayments, ...props.otherPayments];

    for (const payment of sourcePayments) {
        const isMembership = payment.type === 'membership' || Boolean(payment.plan_name);
        let subtitle = `Paid on ${payment.payment_date || payment.created_at}`;

        if (isMembership) {
            if (payment.start_date && payment.end_date) {
                subtitle = `Valid: ${payment.start_date} → ${payment.end_date}`;
            } else if (payment.start_date) {
                subtitle = `Valid from ${payment.start_date}`;
            }
        }

        list.push({
            raw: payment,
            id: `payment_${payment.id}`,
            numericId: payment.id,
            category: isMembership ? 'membership' : 'other',
            title: isMembership
                ? (payment.plan_name || 'Gym Membership')
                : (payment.notes ? `Payment: ${payment.notes}` : `Payment #${payment.id}`),
            subtitle,
            date: payment.payment_date || payment.created_at,
            amount: payment.amount,
            paidAmount: payment.paid_amount,
            balance: payment.balance,
            isPaid: Boolean(payment.is_paid),
            paymentMethod: payment.payment_method,
        });
    }

    return list.sort((a, b) => new Date(b.date || 0) - new Date(a.date || 0));
});

const outstandingTransactions = computed(() => allTransactions.value.filter(t => !t.isPaid));
const paidTransactions        = computed(() => allTransactions.value.filter(t => t.isPaid));

const filteredPaymentsList = computed(() => {
    let result = allTransactions.value;

    if (paymentsStatusTab.value === 'outstanding') {
        result = outstandingTransactions.value;
    } else if (paymentsStatusTab.value === 'paid') {
        result = paidTransactions.value;
    }

    if (selectedCategory.value !== 'all') {
        result = result.filter(t => t.category === selectedCategory.value);
    }

    const q = searchQuery.value.trim().toLowerCase();
    if (q) {
        result = result.filter(t =>
            t.title.toLowerCase().includes(q) ||
            t.subtitle.toLowerCase().includes(q) ||
            String(t.numericId).includes(q)
        );
    }

    return result;
});

function handleItemClick(item) {
    if (item.category === 'invoice') {
        emit('open-sale', item.raw);
    } else {
        emit('open-payment', item.raw);
    }
}

function getIconWrapperClass(item) {
    if (!item.isPaid) return 'bg-red-50 dark:bg-red-950/40 text-red-500';
    if (item.category === 'membership') return 'bg-red-50 dark:bg-red-950/40 text-red-500';
    if (item.category === 'invoice') return 'bg-sky-50 dark:bg-sky-950/40 text-sky-600 dark:text-sky-400';
    return 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400';
}

function formatMoney(val) {
    const n = parseFloat(val ?? 0);
    return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>
