<template>
  <article class="app-surface rounded-2xl overflow-hidden flex flex-col">
    <header class="px-4 py-3 border-b border-secondary-200 dark:border-secondary-700 flex items-center justify-between gap-3">
      <h3 class="text-sm font-semibold text-secondary-900 dark:text-white">
        {{ title }}
      </h3>
      <p class="text-base font-semibold" :class="toneText">
        {{ sign }}{{ formatMoney(total) }}
      </p>
    </header>

    <div v-if="loading" class="px-4 py-4 text-sm text-secondary-500 dark:text-secondary-400">
      Loading...
    </div>
    <div v-else-if="transactions.length === 0" class="px-4 py-4 text-sm text-secondary-500 dark:text-secondary-400">
      No {{ title.toLowerCase() }} recorded for this day.
    </div>
    <template v-else>
      <!-- Breakdown chips -->
      <div v-if="breakdown.length" class="px-4 pt-3 flex flex-wrap gap-2">
        <span
          v-for="item in breakdown"
          :key="item.key"
          class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium app-surface-soft text-secondary-700 dark:text-secondary-200"
        >
          {{ item.label }}
          <span class="font-semibold" :class="toneText">{{ formatMoney(item.amount) }}</span>
        </span>
      </div>

      <!-- Transactions -->
      <ul class="px-4 py-3 divide-y divide-secondary-200 dark:divide-secondary-700 overflow-y-auto max-h-80">
        <li v-for="tx in transactions" :key="tx.id" class="py-2.5 flex items-start justify-between gap-3">
          <div class="min-w-0">
            <p class="text-sm text-secondary-900 dark:text-white truncate">
              {{ tx.label }}
              <span class="text-secondary-400 dark:text-secondary-500">·</span>
              <span class="text-secondary-500 dark:text-secondary-400">{{ tx.account }}</span>
            </p>
            <p v-if="tx.notes || tx.reference" class="text-xs text-secondary-500 dark:text-secondary-400 truncate">
              {{ tx.notes || tx.reference }}
            </p>
          </div>
          <p class="text-sm font-semibold whitespace-nowrap" :class="toneText">
            {{ sign }}{{ formatMoney(tx.amount) }}
          </p>
        </li>
      </ul>
    </template>
  </article>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    tone: { type: String, default: 'income' }, // income | expense
    total: { type: [Number, String], default: 0 },
    breakdown: { type: Array, default: () => [] },
    transactions: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    formatMoney: { type: Function, required: true },
});

const sign = computed(() => (props.tone === 'expense' ? '-' : '+'));
const toneText = computed(() =>
    props.tone === 'expense'
        ? 'text-red-600 dark:text-red-400'
        : 'text-emerald-600 dark:text-emerald-400'
);
</script>
