<template>
  <article v-if="summary.can_view" class="app-surface flex h-full min-h-0 flex-col rounded-xl border border-secondary-200/70 p-3.5 dark:border-secondary-700/70 sm:p-4 xl:p-5">
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--text-muted)">
          Cash Flow
        </p>
        <p class="mt-1 truncate text-xs sm:text-sm" style="color: var(--text-muted)">
          {{ summary.range_label || 'Selected period' }}
        </p>
      </div>
      <div class="flex items-center gap-2">
        <!-- Multi Select Dropdown Filter -->
        <div v-if="summary.accounts && summary.accounts.length > 0" class="relative">
          <button
            ref="triggerRef"
            type="button"
            :disabled="loading"
            class="inline-flex h-10 items-center gap-1.5 rounded-lg border border-secondary-300 bg-white px-3 text-xs font-semibold text-secondary-700 transition-colors hover:bg-secondary-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-secondary-700 dark:bg-secondary-900 dark:text-secondary-300 dark:hover:bg-secondary-800"
            @click="toggleDropdown"
          >
            <Filter class="h-3.5 w-3.5 text-secondary-500" />
            <span>{{ triggerLabel }}</span>
            <ChevronDown class="h-3 w-3 text-secondary-400 transition-transform duration-200" :class="dropdownOpen ? 'rotate-180' : ''" />
          </button>

          <Teleport to="body">
            <Transition
              enter-active-class="transition duration-100 ease-out"
              enter-from-class="opacity-0 scale-95"
              enter-to-class="opacity-100 scale-100"
              leave-active-class="transition duration-75 ease-in"
              leave-from-class="opacity-100 scale-100"
              leave-to-class="opacity-0 scale-95"
            >
              <div
                v-if="dropdownOpen"
                ref="panelRef"
                class="app-overlay-panel fixed z-[9999] flex flex-col overflow-hidden rounded-xl border border-secondary-200/80 bg-white/90 p-1 shadow-lg backdrop-blur-md dark:border-secondary-800/80 dark:bg-secondary-900/90"
                :style="panelStyle"
              >
                <button
                  type="button"
                  class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-xs font-semibold transition-colors hover:bg-secondary-100/50 dark:hover:bg-secondary-800/50"
                  :class="selectedAccountIds.length === 0 ? 'text-primary-600 dark:text-primary-400 font-bold' : 'text-secondary-700 dark:text-secondary-300'"
                  @click="emit('change-filter', [])"
                >
                  <span>All Accounts</span>
                  <Check v-if="selectedAccountIds.length === 0" class="h-3.5 w-3.5" />
                </button>
                <div class="my-1 h-px bg-secondary-200/60 dark:bg-secondary-800/60" />
                <div class="flex-1 overflow-y-auto overscroll-contain">
                  <button
                    v-for="account in summary.accounts"
                    :key="account.id"
                    type="button"
                    class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-xs transition-colors hover:bg-secondary-100/50 dark:hover:bg-secondary-800/50"
                    :class="selectedAccountIds.includes(account.id) ? 'font-semibold text-primary-600 dark:text-primary-400 font-bold' : 'text-secondary-600 dark:text-secondary-400'"
                    @click="selectAccount(account.id)"
                  >
                    <span class="truncate">{{ account.name }}</span>
                    <Check v-if="selectedAccountIds.includes(account.id)" class="h-3.5 w-3.5 text-primary-600 dark:text-primary-400" />
                  </button>
                </div>
              </div>
            </Transition>
          </Teleport>
        </div>

        <RouterLink
          :to="statsLink"
          title="View all transactions"
          aria-label="View all transactions"
          class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-secondary-100 transition-colors hover:bg-secondary-200 dark:bg-secondary-800 dark:hover:bg-secondary-700"
        >
          <ChartNoAxesCombined class="h-5 w-5 text-secondary-700 dark:text-secondary-300" :stroke-width="2" />
        </RouterLink>
      </div>
    </div>

    <div v-if="loading" class="mt-4 grid grid-cols-2 gap-2">
      <div v-for="i in 4" :key="i" class="app-skeleton h-16 rounded-xl" />
    </div>

    <div v-else class="mt-3 grid grid-cols-2 gap-2 sm:mt-4">
      <div class="min-w-0 rounded-lg border border-green-200 bg-green-50/70 p-2.5 dark:border-green-900/50 dark:bg-green-900/20 sm:p-3">
        <p class="text-[11px] font-medium text-green-700 dark:text-green-400">
          Income
        </p>
        <p class="mt-1 truncate text-base font-bold text-green-700 dark:text-green-400">
          {{ money(summary.income) }}
        </p>
        <p class="mt-1 text-[10px] text-green-600/80 dark:text-green-400/80">
          {{ formatCount(summary.income_count) }} entries
        </p>
      </div>

      <div class="min-w-0 rounded-lg border border-red-200 bg-red-50/70 p-2.5 dark:border-red-900/50 dark:bg-red-900/20 sm:p-3">
        <p class="text-[11px] font-medium text-red-700 dark:text-red-400">
          Expenses
        </p>
        <p class="mt-1 truncate text-base font-bold text-red-700 dark:text-red-400">
          {{ money(summary.expense) }}
        </p>
        <p class="mt-1 text-[10px] text-red-600/80 dark:text-red-400/80">
          {{ formatCount(summary.expense_count) }} entries
        </p>
      </div>

      <div class="col-span-2 flex min-h-14 items-center justify-between rounded-lg p-2.5 sm:p-3" style="background: var(--surface-muted); border: 1px solid var(--surface-border)">
        <div>
          <p class="text-[11px] font-medium" style="color: var(--text-muted)">
            Net Movement
          </p>
          <p class="mt-1 text-lg font-bold" :class="netAmountClass">
            {{ signedMoney(summary.net_movement) }}
          </p>
        </div>
        <RouterLink :to="statsLink" class="text-xs font-semibold text-primary-600 hover:underline dark:text-primary-400">
          All transactions
        </RouterLink>
      </div>
    </div>

    <div class="mt-3 min-h-0 overflow-hidden rounded-lg sm:mt-4" style="border: 1px solid var(--surface-border)">
      <div class="px-3 py-2" style="background: var(--surface-muted)">
        <p class="text-[11px] font-semibold uppercase tracking-[0.06em]" style="color: var(--text-muted)">
          Transactions
        </p>
      </div>

      <div v-if="loading" class="divide-y divide-secondary-200 dark:divide-secondary-700">
        <div v-for="i in 3" :key="i" class="flex items-center gap-3 px-3 py-2.5">
          <div class="app-skeleton h-8 w-8 rounded-lg" />
          <div class="min-w-0 flex-1 space-y-1">
            <div class="app-skeleton h-3 w-24 rounded" />
            <div class="app-skeleton h-2.5 w-16 rounded" />
          </div>
          <div class="app-skeleton h-3 w-14 rounded" />
        </div>
      </div>

      <AppEmptyState
        v-else-if="summary.transactions.length === 0"
        :icon="ReceiptText"
        title="No cash flow"
        description="No income or expense transactions were recorded in this period."
      />

      <ul v-else class="m-0 max-h-[360px] divide-y divide-secondary-200 overflow-auto p-0 dark:divide-secondary-700">
        <li
          v-for="transaction in summary.transactions"
          :key="transaction.id"
        >
          <div
            class="flex min-h-12 items-center gap-2.5 px-3 py-2 transition-colors hover:bg-secondary-50 dark:hover:bg-secondary-800/50 sm:py-2.5"
          >
            <div
              class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
              :class="transaction.amount >= 0
                ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'"
            >
              <ArrowDownLeft v-if="transaction.amount >= 0" class="h-4 w-4" />
              <ArrowUpRight v-else class="h-4 w-4" />
            </div>
            <div class="min-w-0 flex-1">
              <RouterLink
                v-if="transaction.source_path"
                :to="transaction.source_path"
                class="block truncate text-xs font-semibold text-primary-600 hover:underline dark:text-primary-400"
              >
                {{ transaction.source_label }}
              </RouterLink>
              <p v-else class="truncate text-xs font-semibold" style="color: var(--text-strong)">
                {{ transaction.source_label }}
              </p>
              <p class="truncate text-[10px]" style="color: var(--text-muted)">
                {{ transaction.account_name }} · {{ transaction.transaction_date }}
              </p>
            </div>
            <p
              class="shrink-0 text-xs font-bold tabular-nums"
              :class="transaction.amount >= 0 ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400'"
            >
              {{ signedMoney(transaction.amount) }}
            </p>
          </div>
        </li>
      </ul>
    </div>
  </article>
</template>

<script setup>
import { computed, ref, onMounted, onBeforeUnmount } from 'vue';
import { RouterLink } from 'vue-router';
import { ArrowDownLeft, ArrowUpRight, ChartNoAxesCombined, ReceiptText, ChevronDown, Check, Filter } from 'lucide-vue-next';
import AppEmptyState from '../AppEmptyState.vue';

const props = defineProps({
  loading: { type: Boolean, default: false },
  selectedAccountIds: { type: Array, default: () => [] },
  summary: {
    type: Object,
    default: () => ({
      can_view: true,
      range_label: '',
      income: 0,
      expense: 0,
      net_movement: 0,
      income_count: 0,
      expense_count: 0,
      start_date: '',
      end_date: '',
      transactions: [],
      accounts: [],
    }),
  },
});

const emit = defineEmits(['change-filter']);

const dropdownOpen = ref(false);
const triggerRef = ref(null);
const panelRef = ref(null);
const panelStyle = ref({});

const triggerLabel = computed(() => {
  const selectedIds = props.selectedAccountIds;
  const accounts = props.summary.accounts || [];
  if (!selectedIds || selectedIds.length === 0 || selectedIds.length === accounts.length) {
    return 'All Accounts';
  }
  if (selectedIds.length === 1) {
    const found = accounts.find(a => a.id === selectedIds[0]);
    return found ? found.name : '1 Account';
  }
  return `${selectedIds.length} Accounts`;
});

function computePanelStyle() {
  if (!triggerRef.value) return;
  const rect = triggerRef.value.getBoundingClientRect();
  const viewportHeight = window.innerHeight;
  const spaceBelow = viewportHeight - rect.bottom - 12;
  const spaceAbove = rect.top - 12;
  const openBelow = spaceBelow >= 160 || spaceBelow >= spaceAbove;
  const maxHeight = Math.min(openBelow ? spaceBelow : spaceAbove, 240);
  panelStyle.value = {
    top: openBelow ? `${rect.bottom + 6}px` : undefined,
    bottom: !openBelow ? `${viewportHeight - rect.top + 6}px` : undefined,
    left: `${rect.left + rect.width - 180}px`,
    width: '180px',
    maxHeight: `${maxHeight}px`,
  };
}

function toggleDropdown() {
  if (props.loading) return;
  dropdownOpen.value = !dropdownOpen.value;
  if (dropdownOpen.value) {
    computePanelStyle();
  }
}

function selectAccount(accountId) {
  let newSelected = [...props.selectedAccountIds];
  if (newSelected.includes(accountId)) {
    newSelected = newSelected.filter(id => id !== accountId);
  } else {
    newSelected.push(accountId);
  }
  emit('change-filter', newSelected);
}

function handleOutsideClick(e) {
  if (!dropdownOpen.value) return;
  if (triggerRef.value?.contains(e.target)) return;
  if (panelRef.value?.contains(e.target)) return;
  dropdownOpen.value = false;
}

function handleScrollOrResize() {
  if (dropdownOpen.value) computePanelStyle();
}

onMounted(() => {
  document.addEventListener('click', handleOutsideClick, true);
  window.addEventListener('scroll', handleScrollOrResize, true);
  window.addEventListener('resize', handleScrollOrResize);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleOutsideClick, true);
  window.removeEventListener('scroll', handleScrollOrResize, true);
  window.removeEventListener('resize', handleScrollOrResize);
});

const moneyFormatter = new Intl.NumberFormat(undefined, {
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
});
const numberFormatter = new Intl.NumberFormat();

const netAmountClass = computed(() => Number(props.summary.net_movement || 0) >= 0
  ? 'text-green-700 dark:text-green-400'
  : 'text-red-700 dark:text-red-400');

const statsLink = computed(() => ({
  path: '/stats',
  query: {
    tab: 'stats',
    focus: 'cash_flow',
    range_type: 'date_range',
    start_date: props.summary.start_date,
    end_date: props.summary.end_date,
  },
}));

function money(value) {
  return moneyFormatter.format(Math.abs(Number(value || 0)));
}

function signedMoney(value) {
  const amount = Number(value || 0);
  return `${amount >= 0 ? '+' : '-'}${money(amount)}`;
}

function formatCount(value) {
  return numberFormatter.format(Number(value || 0));
}
</script>
