<template>
  <div class="space-y-4 pb-6">
    <!-- Header -->
    <div class="flex items-center justify-between pt-2 pb-1">
      <div>
        <h1 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">
          Payments &amp; Invoices
        </h1>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-medium">
          Memberships, bills, official receipts &amp; transaction records
        </p>
      </div>

      <div
        v-if="parseFloat(meta.total_outstanding || 0) > 0"
        class="px-3 py-1 rounded-full text-xs font-black bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 animate-pulse flex items-center gap-1.5"
      >
        <span class="w-1.5 h-1.5 rounded-full bg-red-500" />
        <span>Due: {{ meta.total_outstanding }}</span>
      </div>
      <div
        v-else
        class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 flex items-center gap-1.5"
      >
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500" />
        <span>All Settled</span>
      </div>
    </div>

    <!-- Outstanding Alert Card (when dues exist) -->
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
        v-if="activeTab !== 'outstanding'"
        type="button"
        class="px-3 py-1.5 text-xs font-bold rounded-xl bg-red-500 text-white shadow hover:bg-red-600 active:scale-95 transition-all cursor-pointer shrink-0"
        @click="activeTab = 'outstanding'"
      >
        View Dues
      </button>
    </div>

    <!-- Status Tabs Switcher -->
    <div class="flex gap-1.5 p-1 bg-gray-100 dark:bg-zinc-800/80 rounded-2xl">
      <button
        type="button"
        class="flex-1 py-2 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5 focus:outline-none cursor-pointer"
        :class="activeTab === 'outstanding'
          ? 'bg-white dark:bg-zinc-700 text-gray-900 dark:text-white shadow-sm'
          : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'"
        @click="activeTab = 'outstanding'"
      >
        <span>Outstanding</span>
        <span
          class="px-1.5 py-0.2 rounded-full text-[10px] font-black"
          :class="activeTab === 'outstanding'
            ? 'bg-red-500 text-white'
            : 'bg-gray-200 dark:bg-zinc-700 text-gray-600 dark:text-gray-400'"
        >
          {{ outstandingTransactions.length }}
        </span>
      </button>

      <button
        type="button"
        class="flex-1 py-2 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5 focus:outline-none cursor-pointer"
        :class="activeTab === 'paid'
          ? 'bg-white dark:bg-zinc-700 text-gray-900 dark:text-white shadow-sm'
          : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'"
        @click="activeTab = 'paid'"
      >
        <span>Paid</span>
        <span
          class="px-1.5 py-0.2 rounded-full text-[10px] font-black"
          :class="activeTab === 'paid'
            ? 'bg-emerald-500 text-white'
            : 'bg-gray-200 dark:bg-zinc-700 text-gray-600 dark:text-gray-400'"
        >
          {{ paidTransactions.length }}
        </span>
      </button>

      <button
        type="button"
        class="flex-1 py-2 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5 focus:outline-none cursor-pointer"
        :class="activeTab === 'all'
          ? 'bg-white dark:bg-zinc-700 text-gray-900 dark:text-white shadow-sm'
          : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'"
        @click="activeTab = 'all'"
      >
        <span>All</span>
        <span
          class="px-1.5 py-0.2 rounded-full text-[10px] font-black"
          :class="activeTab === 'all'
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
          <span
            v-if="getCategoryCount(cat.key) > 0"
            class="text-[10px] font-extrabold px-1 rounded-md"
            :class="selectedCategory === cat.key ? 'bg-white/20 text-white dark:bg-black/20 dark:text-zinc-900' : 'bg-gray-200 dark:bg-zinc-700 text-gray-500 dark:text-gray-400'"
          >
            {{ getCategoryCount(cat.key) }}
          </span>
        </button>
      </div>

      <!-- Quick Search Bar -->
      <div v-if="allTransactions.length > 5" class="relative">
        <Search class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" :stroke-width="2" />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search by plan, receipt #, invoice #, or ref..."
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

    <!-- Transactions List -->
    <div v-if="filteredTransactions.length" class="pp-glass-card rounded-3xl overflow-hidden divide-y divide-gray-100 dark:divide-zinc-800/60 shadow-sm">
      <button
        v-for="item in filteredTransactions"
        :key="item.id"
        type="button"
        class="w-full flex items-center gap-3.5 px-4 sm:px-5 py-4 hover:bg-gray-50 dark:hover:bg-zinc-800/40 active:bg-gray-100 dark:active:bg-zinc-800 transition-colors focus:outline-none text-left cursor-pointer group"
        @click="handleItemClick(item)"
      >
        <!-- Icon Avatar based on Category -->
        <div
          class="w-11 h-11 rounded-2xl flex items-center justify-center shrink-0 shadow-sm"
          :class="getIconWrapperClass(item)"
        >
          <Crown v-if="item.category === 'membership'" class="w-5 h-5" :stroke-width="2" />
          <FileText v-else-if="item.category === 'invoice'" class="w-5 h-5" :stroke-width="2" />
          <Receipt v-else class="w-5 h-5" :stroke-width="2" />
        </div>

        <!-- Details -->
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

          <p v-if="item.referenceNumber" class="text-[11px] font-mono text-gray-400 dark:text-gray-500 mt-0.5">
            Ref: {{ item.referenceNumber }}
          </p>
        </div>

        <!-- Amount & Status -->
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

    <!-- Empty State -->
    <div
      v-else
      class="pp-glass-card rounded-3xl p-12 flex flex-col items-center justify-center text-center gap-2.5 text-gray-400"
    >
      <div
        class="w-14 h-14 rounded-2xl flex items-center justify-center"
        :class="activeTab === 'outstanding'
          ? 'bg-emerald-500/10 text-emerald-500'
          : 'bg-gray-100 dark:bg-zinc-800 text-gray-400'"
      >
        <CheckCircle2 v-if="activeTab === 'outstanding'" class="w-7 h-7" :stroke-width="2" />
        <Receipt v-else class="w-7 h-7" :stroke-width="1.8" />
      </div>

      <p class="text-base font-bold text-gray-800 dark:text-gray-200">
        {{ getEmptyStateTitle() }}
      </p>

      <p class="text-xs text-gray-400 dark:text-gray-500 max-w-xs">
        {{ getEmptyStateDescription() }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import {
    FileText,
    Receipt,
    Crown,
    CheckCircle2,
    AlertCircle,
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
});

const emit = defineEmits(['open-sale', 'open-payment']);

const activeTab = ref('outstanding');
const selectedCategory = ref('all');
const searchQuery = ref('');

const categoryOptions = [
    { key: 'all',        label: 'All Types',   icon: LayoutGrid },
    { key: 'membership', label: 'Membership',  icon: Crown },
    { key: 'invoice',    label: 'Invoices',    icon: FileText },
    { key: 'other',      label: 'Other',       icon: Receipt },
];

// Unified list of all transaction records
const allTransactions = computed(() => {
    const list = [];

    // 1. POS Invoices / Sales
    for (const sale of props.salesData) {
        list.push({
            raw: sale,
            id: `sale_${sale.id}`,
            numericId: sale.id,
            category: 'invoice',
            categoryLabel: 'Invoice',
            title: `Invoice #${sale.id}`,
            subtitle: `${sale.created_at} · ${sale.items?.length || 0} item${sale.items?.length === 1 ? '' : 's'}`,
            details: sale.items?.map(it => it.product_name).filter(Boolean).join(', ') || null,
            date: sale.created_at,
            amount: sale.total_amount,
            paidAmount: sale.paid_amount,
            balance: sale.balance,
            isPaid: Boolean(sale.is_paid),
            paymentMethod: sale.payment_method,
            referenceNumber: sale.reference_number,
        });
    }

    // 2. Member Payments (Memberships & Other Payments)
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
            } else if (payment.end_date) {
                subtitle = `Valid until ${payment.end_date}`;
            }
        }

        list.push({
            raw: payment,
            id: `payment_${payment.id}`,
            numericId: payment.id,
            category: isMembership ? 'membership' : 'other',
            categoryLabel: isMembership ? 'Membership' : 'Other Payment',
            title: isMembership
                ? (payment.plan_name || 'Gym Membership')
                : (payment.notes ? `Payment: ${payment.notes}` : `Payment #${payment.id}`),
            subtitle,
            details: payment.notes || null,
            date: payment.payment_date || payment.created_at,
            amount: payment.amount,
            paidAmount: payment.paid_amount,
            balance: payment.balance,
            isPaid: Boolean(payment.is_paid),
            paymentMethod: payment.payment_method,
            referenceNumber: payment.reference_number,
        });
    }

    // Sort by date descending (newest first)
    return list.sort((a, b) => new Date(b.date || 0) - new Date(a.date || 0));
});

const outstandingTransactions = computed(() => allTransactions.value.filter(t => !t.isPaid));
const paidTransactions        = computed(() => allTransactions.value.filter(t => t.isPaid));

function getCategoryCount(categoryKey) {
    let pool = allTransactions.value;
    if (activeTab.value === 'outstanding') pool = outstandingTransactions.value;
    else if (activeTab.value === 'paid') pool = paidTransactions.value;

    if (categoryKey === 'all') return pool.length;
    return pool.filter(t => t.category === categoryKey).length;
}

const filteredTransactions = computed(() => {
    let result = allTransactions.value;

    // 1. Status Filter
    if (activeTab.value === 'outstanding') {
        result = outstandingTransactions.value;
    } else if (activeTab.value === 'paid') {
        result = paidTransactions.value;
    }

    // 2. Category Filter
    if (selectedCategory.value !== 'all') {
        result = result.filter(t => t.category === selectedCategory.value);
    }

    // 3. Search Query Filter
    const query = searchQuery.value.trim().toLowerCase();
    if (query) {
        result = result.filter(t =>
            t.title.toLowerCase().includes(query) ||
            t.subtitle.toLowerCase().includes(query) ||
            (t.details && t.details.toLowerCase().includes(query)) ||
            (t.referenceNumber && t.referenceNumber.toLowerCase().includes(query)) ||
            String(t.numericId).includes(query)
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
    if (!item.isPaid) {
        return 'bg-red-50 dark:bg-red-950/40 text-red-500';
    }
    if (item.category === 'membership') {
        return 'bg-red-50 dark:bg-red-950/40 text-red-500';
    }
    if (item.category === 'invoice') {
        return 'bg-sky-50 dark:bg-sky-950/40 text-sky-600 dark:text-sky-400';
    }
    return 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400';
}

function getEmptyStateTitle() {
    if (searchQuery.value) return 'No matching transactions';
    if (activeTab.value === 'outstanding') return 'No Pending Dues';
    if (activeTab.value === 'paid') return 'No Paid Records';
    return 'No Transactions Found';
}

function getEmptyStateDescription() {
    if (searchQuery.value) return 'Try adjusting your search query or switching categories.';
    if (activeTab.value === 'outstanding') return 'Your account is in good standing with zero outstanding balance.';
    if (activeTab.value === 'paid') return 'Completed membership and invoice payments will appear here.';
    return 'Official payment receipts and invoice bills will be listed here.';
}
</script>
