<template>
  <section class="app-page-frame">
    <AppPageHeader title="Real Profit">
      <template #extra-slot>
        <div class="flex flex-wrap items-end gap-3">
          <form class="flex flex-wrap items-end gap-3" @submit.prevent="loadReport">
            <AppFormField label="Month" class="w-full sm:w-56">
              <AppFormInput v-model="selectedMonth" type="month" required />
            </AppFormField>

            <button
              type="submit"
              :disabled="loading"
              class="inline-flex h-12 items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-primary-500 to-primary-700 px-4 text-sm font-semibold text-white transition-all hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-60"
            >
              <RefreshCw class="h-4 w-4" :class="loading ? 'animate-spin' : ''" :stroke-width="2" />
              {{ loading ? 'Loading...' : 'Apply' }}
            </button>
          </form>

          <a
            :href="downloadUrl"
            class="inline-flex h-12 items-center justify-center gap-2 rounded-2xl app-surface-soft px-4 text-sm font-semibold text-secondary-700 transition-colors hover:brightness-105 dark:text-secondary-200"
          >
            <Download class="h-4 w-4" :stroke-width="2" />
            Download
          </a>

          <button
            type="button"
            :disabled="emailing"
            class="inline-flex h-12 items-center justify-center gap-2 rounded-2xl border border-primary-300 px-4 text-sm font-semibold text-primary-700 transition-colors hover:bg-primary-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-primary-700 dark:text-primary-300 dark:hover:bg-primary-900/20"
            @click="emailReport"
          >
            <Mail class="h-4 w-4" :stroke-width="2" />
            {{ emailing ? 'Sending...' : 'Email Admins' }}
          </button>
        </div>
      </template>
    </AppPageHeader>

    <div class="app-page-scroll">
      <div v-if="error" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-200">
        {{ error }}
      </div>

      <div v-if="emailMessage" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-200">
        {{ emailMessage }}
      </div>

      <div v-if="loading && !loaded" class="app-surface rounded-2xl p-5 text-sm text-secondary-500 dark:text-secondary-400">
        Loading report...
      </div>

      <template v-else>
        <p v-if="report.month_label" class="mb-4 text-sm text-secondary-500 dark:text-secondary-400">
          {{ report.month_label }} · {{ report.start_date }} to {{ report.end_date }}
        </p>

        <div class="mb-4 grid grid-cols-2 gap-3 xl:grid-cols-6">
          <article class="app-surface rounded-2xl p-4 ring-1 ring-primary-200 dark:ring-primary-900/40">
            <div class="flex items-center gap-2 text-xs text-secondary-500 dark:text-secondary-400">
              <TrendingUp class="h-4 w-4 text-primary-600 dark:text-primary-300" :stroke-width="2" />
              Real Profit
            </div>
            <p class="mt-2 text-xl font-semibold md:text-2xl" :class="profitClass(report.summary.real_profit)">
              {{ formatSignedMoney(report.summary.real_profit) }}
            </p>
          </article>

          <article class="app-surface rounded-2xl p-4">
            <div class="flex items-center gap-2 text-xs text-secondary-500 dark:text-secondary-400">
              <WalletCards class="h-4 w-4 text-emerald-600 dark:text-emerald-300" :stroke-width="2" />
              Membership Income
            </div>
            <p class="mt-2 text-lg font-semibold text-emerald-700 dark:text-emerald-400 md:text-xl">
              +{{ formatMoney(report.summary.membership_income) }}
            </p>
            <p class="mt-1 text-xs text-secondary-500 dark:text-secondary-400">
              {{ formatNumber(report.summary.membership_count) }} payments
            </p>
          </article>

          <article class="app-surface rounded-2xl p-4">
            <div class="flex items-center gap-2 text-xs text-secondary-500 dark:text-secondary-400">
              <Banknote class="h-4 w-4 text-teal-600 dark:text-teal-300" :stroke-width="2" />
              Other Payments
            </div>
            <p class="mt-2 text-lg font-semibold text-teal-700 dark:text-teal-400 md:text-xl">
              +{{ formatMoney(report.summary.other_payment_income) }}
            </p>
            <p class="mt-1 text-xs text-secondary-500 dark:text-secondary-400">
              {{ formatNumber(report.summary.other_payment_count) }} payments
            </p>
          </article>

          <article class="app-surface rounded-2xl p-4">
            <div class="flex items-center gap-2 text-xs text-secondary-500 dark:text-secondary-400">
              <ShoppingBag class="h-4 w-4 text-sky-600 dark:text-sky-300" :stroke-width="2" />
              Sales Profit
            </div>
            <p class="mt-2 text-lg font-semibold md:text-xl" :class="profitClass(report.summary.sales_profit)">
              {{ formatSignedMoney(report.summary.sales_profit) }}
            </p>
            <p class="mt-1 text-xs text-secondary-500 dark:text-secondary-400">
              {{ formatNumber(report.summary.sales_quantity) }} units
            </p>
          </article>

          <article class="app-surface rounded-2xl p-4">
            <div class="flex items-center gap-2 text-xs text-secondary-500 dark:text-secondary-400">
              <ReceiptText class="h-4 w-4 text-red-600 dark:text-red-300" :stroke-width="2" />
              Expenses
            </div>
            <p class="mt-2 text-lg font-semibold text-red-700 dark:text-red-400 md:text-xl">
              -{{ formatMoney(report.summary.expenses) }}
            </p>
            <p class="mt-1 text-xs text-secondary-500 dark:text-secondary-400">
              {{ formatNumber(report.summary.expense_count) }} entries
            </p>
          </article>

          <article class="app-surface rounded-2xl p-4">
            <div class="flex items-center gap-2 text-xs text-secondary-500 dark:text-secondary-400">
              <ReceiptText class="h-4 w-4 text-orange-600 dark:text-orange-300" :stroke-width="2" />
              Payment Fees
            </div>
            <p class="mt-2 text-lg font-semibold text-orange-700 dark:text-orange-400 md:text-xl">
              -{{ formatMoney(report.summary.payment_deductions) }}
            </p>
            <p class="mt-1 text-xs text-secondary-500 dark:text-secondary-400">
              {{ formatNumber(report.summary.payment_deduction_count) }} deductions
            </p>
          </article>
        </div>

        <article class="app-surface mb-4 rounded-2xl p-4">
          <p class="text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
            Formula
          </p>
          <div class="mt-2 flex flex-wrap items-center gap-2 text-sm font-semibold text-secondary-900 dark:text-white">
            <span>Membership {{ formatMoney(report.summary.membership_income) }}</span>
            <span class="text-secondary-400">+</span>
            <span>Other Payments {{ formatMoney(report.summary.other_payment_income) }}</span>
            <span class="text-secondary-400">+</span>
            <span>Sales Profit {{ formatMoney(report.summary.sales_profit) }}</span>
            <span class="text-secondary-400">-</span>
            <span>Expenses {{ formatMoney(report.summary.expenses) }}</span>
            <span class="text-secondary-400">-</span>
            <span>Payment Fees {{ formatMoney(report.summary.payment_deductions) }}</span>
            <span class="text-secondary-400">=</span>
            <span :class="profitClass(report.summary.real_profit)">{{ formatSignedMoney(report.summary.real_profit) }}</span>
          </div>
        </article>

        <div v-if="hasCostWarnings" class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
          <div class="flex gap-2">
            <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0" :stroke-width="2" />
            <p>
              {{ formatNumber(report.summary.estimated_cost_items) }} sale item costs are estimated and
              {{ formatNumber(report.summary.missing_cost_items) }} are missing cost data.
            </p>
          </div>
        </div>

        <div class="mb-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
          <article class="app-surface rounded-2xl p-4">
            <p class="text-xs text-secondary-500 dark:text-secondary-400">
              Sales Revenue
            </p>
            <p class="mt-1 text-lg font-semibold text-secondary-900 dark:text-white">
              {{ formatMoney(report.summary.sales_revenue) }}
            </p>
          </article>
          <article class="app-surface rounded-2xl p-4">
            <p class="text-xs text-secondary-500 dark:text-secondary-400">
              Actual Cost
            </p>
            <p class="mt-1 text-lg font-semibold text-secondary-900 dark:text-white">
              {{ formatMoney(report.summary.sales_cost) }}
            </p>
          </article>
          <article class="app-surface rounded-2xl p-4">
            <p class="text-xs text-secondary-500 dark:text-secondary-400">
              Sales Margin
            </p>
            <p class="mt-1 text-lg font-semibold" :class="profitClass(report.summary.sales_profit)">
              {{ formatPercent(salesMarginPercent) }}
            </p>
          </article>
        </div>

        <section class="grid grid-cols-1 gap-4 xl:grid-cols-2">
          <ReportPanel
            title="Sales Profit by Product"
            :empty="report.sales_by_product.length === 0"
            empty-text="No sales found for this month."
          >
            <template #mobile>
              <article v-for="item in report.sales_by_product" :key="item.product_id" class="space-y-2 px-4 py-3">
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-secondary-900 dark:text-white">
                      {{ item.product_name }}
                    </p>
                    <p class="text-xs text-secondary-500 dark:text-secondary-400">
                      {{ formatNumber(item.quantity) }} units · {{ formatPercent(item.margin_percent) }}
                    </p>
                  </div>
                  <p class="shrink-0 text-sm font-semibold" :class="profitClass(item.profit)">
                    {{ formatSignedMoney(item.profit) }}
                  </p>
                </div>
                <div class="grid grid-cols-2 gap-2 text-xs text-secondary-600 dark:text-secondary-300">
                  <p>Revenue: <span class="font-medium text-secondary-900 dark:text-white">{{ formatMoney(item.revenue) }}</span></p>
                  <p>Cost: <span class="font-medium text-secondary-900 dark:text-white">{{ formatMoney(item.cost) }}</span></p>
                </div>
              </article>
            </template>
            <template #desktop>
              <table class="w-full min-w-[640px]">
                <thead class="border-b border-secondary-200 bg-secondary-50 dark:border-secondary-700 dark:bg-background-dark">
                  <tr>
                    <th class="px-3 py-2 text-left text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                      Product
                    </th>
                    <th class="px-3 py-2 text-right text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                      Qty
                    </th>
                    <th class="px-3 py-2 text-right text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                      Revenue
                    </th>
                    <th class="px-3 py-2 text-right text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                      Cost
                    </th>
                    <th class="px-3 py-2 text-right text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                      Profit
                    </th>
                    <th class="px-3 py-2 text-right text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                      Margin
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                  <tr v-for="item in report.sales_by_product" :key="item.product_id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50">
                    <td class="px-3 py-2 text-sm font-medium text-secondary-900 dark:text-white">
                      {{ item.product_name }}
                    </td>
                    <td class="px-3 py-2 text-right text-sm text-secondary-700 dark:text-secondary-300">
                      {{ formatNumber(item.quantity) }}
                    </td>
                    <td class="px-3 py-2 text-right text-sm text-secondary-700 dark:text-secondary-300">
                      {{ formatMoney(item.revenue) }}
                    </td>
                    <td class="px-3 py-2 text-right text-sm text-secondary-700 dark:text-secondary-300">
                      {{ formatMoney(item.cost) }}
                    </td>
                    <td class="px-3 py-2 text-right text-sm font-semibold" :class="profitClass(item.profit)">
                      {{ formatSignedMoney(item.profit) }}
                    </td>
                    <td class="px-3 py-2 text-right text-sm text-secondary-700 dark:text-secondary-300">
                      {{ formatPercent(item.margin_percent) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </template>
          </ReportPanel>

          <ReportPanel
            title="Expense Categories"
            :empty="report.expenses_by_category.length === 0"
            empty-text="No expenses found for this month."
          >
            <template #mobile>
              <article v-for="item in report.expenses_by_category" :key="item.category" class="flex items-center justify-between gap-3 px-4 py-3">
                <div class="min-w-0">
                  <p class="truncate text-sm font-semibold text-secondary-900 dark:text-white">
                    {{ item.category }}
                  </p>
                  <p class="text-xs text-secondary-500 dark:text-secondary-400">
                    {{ formatNumber(item.count) }} entries
                  </p>
                </div>
                <p class="shrink-0 text-sm font-semibold text-red-700 dark:text-red-400">
                  -{{ formatMoney(item.amount) }}
                </p>
              </article>
            </template>
            <template #desktop>
              <table class="w-full min-w-[420px]">
                <thead class="border-b border-secondary-200 bg-secondary-50 dark:border-secondary-700 dark:bg-background-dark">
                  <tr>
                    <th class="px-3 py-2 text-left text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                      Category
                    </th>
                    <th class="px-3 py-2 text-right text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                      Entries
                    </th>
                    <th class="px-3 py-2 text-right text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                      Amount
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                  <tr v-for="item in report.expenses_by_category" :key="item.category" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50">
                    <td class="px-3 py-2 text-sm font-medium text-secondary-900 dark:text-white">
                      {{ item.category }}
                    </td>
                    <td class="px-3 py-2 text-right text-sm text-secondary-700 dark:text-secondary-300">
                      {{ formatNumber(item.count) }}
                    </td>
                    <td class="px-3 py-2 text-right text-sm font-semibold text-red-700 dark:text-red-400">
                      -{{ formatMoney(item.amount) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </template>
          </ReportPanel>
        </section>

        <ReportPanel
          class="mt-4"
          title="Membership Payments"
          :empty="report.membership_payments.length === 0"
          empty-text="No membership payments found for this month."
        >
          <template #mobile>
            <article v-for="payment in report.membership_payments" :key="payment.id" class="space-y-2 px-4 py-3">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="truncate text-sm font-semibold text-secondary-900 dark:text-white">
                    {{ payment.member_name }}
                  </p>
                  <p class="text-xs text-secondary-500 dark:text-secondary-400">
                    {{ payment.payment_date }} · {{ payment.payment_plan_name || 'Membership' }}
                  </p>
                </div>
                <p class="shrink-0 text-sm font-semibold text-emerald-700 dark:text-emerald-400">
                  +{{ formatMoney(payment.amount) }}
                </p>
              </div>
              <p class="text-xs text-secondary-600 dark:text-secondary-300">
                {{ paymentMethodLabel(payment.payment_method) }}<span v-if="payment.account_name"> · {{ payment.account_name }}</span>
              </p>
            </article>
          </template>
          <template #desktop>
            <table class="w-full min-w-[860px]">
              <thead class="border-b border-secondary-200 bg-secondary-50 dark:border-secondary-700 dark:bg-background-dark">
                <tr>
                  <th class="px-3 py-2 text-left text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                    Date
                  </th>
                  <th class="px-3 py-2 text-left text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                    Member
                  </th>
                  <th class="px-3 py-2 text-left text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                    Plan
                  </th>
                  <th class="px-3 py-2 text-left text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                    Method
                  </th>
                  <th class="px-3 py-2 text-left text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                    Period
                  </th>
                  <th class="px-3 py-2 text-right text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                    Amount
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                <tr v-for="payment in report.membership_payments" :key="payment.id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50">
                  <td class="px-3 py-2 text-sm text-secondary-700 dark:text-secondary-300">
                    {{ payment.payment_date }}
                  </td>
                  <td class="px-3 py-2 text-sm font-medium text-secondary-900 dark:text-white">
                    {{ payment.member_name }}
                  </td>
                  <td class="px-3 py-2 text-sm text-secondary-700 dark:text-secondary-300">
                    {{ payment.payment_plan_name || '-' }}
                  </td>
                  <td class="px-3 py-2 text-sm text-secondary-700 dark:text-secondary-300">
                    {{ paymentMethodLabel(payment.payment_method) }}
                  </td>
                  <td class="px-3 py-2 text-sm text-secondary-700 dark:text-secondary-300">
                    {{ payment.start_date || '-' }} to {{ payment.end_date || '-' }}
                  </td>
                  <td class="px-3 py-2 text-right text-sm font-semibold text-emerald-700 dark:text-emerald-400">
                    +{{ formatMoney(payment.amount) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </template>
        </ReportPanel>

        <ReportPanel
          class="mt-4"
          title="Other Payments"
          :empty="report.other_payments.length === 0"
          empty-text="No other payments found for this month."
        >
          <template #mobile>
            <article v-for="payment in report.other_payments" :key="payment.id" class="space-y-2 px-4 py-3">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="truncate text-sm font-semibold text-secondary-900 dark:text-white">
                    {{ payment.member_name }}
                  </p>
                  <p class="text-xs text-secondary-500 dark:text-secondary-400">
                    {{ payment.payment_date }}<span v-if="payment.reference_number"> · {{ payment.reference_number }}</span>
                  </p>
                </div>
                <p class="shrink-0 text-sm font-semibold text-teal-700 dark:text-teal-400">
                  +{{ formatMoney(payment.amount) }}
                </p>
              </div>
              <p class="text-xs text-secondary-600 dark:text-secondary-300">
                {{ paymentMethodLabel(payment.payment_method) }}<span v-if="payment.account_name"> · {{ payment.account_name }}</span>
              </p>
              <p v-if="payment.notes" class="text-xs text-secondary-500 dark:text-secondary-400">
                {{ payment.notes }}
              </p>
            </article>
          </template>
          <template #desktop>
            <table class="w-full min-w-[760px]">
              <thead class="border-b border-secondary-200 bg-secondary-50 dark:border-secondary-700 dark:bg-background-dark">
                <tr>
                  <th class="px-3 py-2 text-left text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                    Date
                  </th>
                  <th class="px-3 py-2 text-left text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                    Member
                  </th>
                  <th class="px-3 py-2 text-left text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                    Method
                  </th>
                  <th class="px-3 py-2 text-left text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                    Reference
                  </th>
                  <th class="px-3 py-2 text-left text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                    Notes
                  </th>
                  <th class="px-3 py-2 text-right text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                    Amount
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                <tr v-for="payment in report.other_payments" :key="payment.id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50">
                  <td class="px-3 py-2 text-sm text-secondary-700 dark:text-secondary-300">
                    {{ payment.payment_date }}
                  </td>
                  <td class="px-3 py-2 text-sm font-medium text-secondary-900 dark:text-white">
                    {{ payment.member_name }}
                  </td>
                  <td class="px-3 py-2 text-sm text-secondary-700 dark:text-secondary-300">
                    {{ paymentMethodLabel(payment.payment_method) }}
                  </td>
                  <td class="px-3 py-2 text-sm text-secondary-700 dark:text-secondary-300">
                    {{ payment.reference_number || '-' }}
                  </td>
                  <td class="px-3 py-2 text-sm text-secondary-700 dark:text-secondary-300">
                    {{ payment.notes || '-' }}
                  </td>
                  <td class="px-3 py-2 text-right text-sm font-semibold text-teal-700 dark:text-teal-400">
                    +{{ formatMoney(payment.amount) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </template>
        </ReportPanel>

        <ReportPanel
          class="mt-4"
          title="Sales Item Cost Detail"
          :empty="report.sales_items.length === 0"
          empty-text="No sale items found for this month."
        >
          <template #mobile>
            <article v-for="item in report.sales_items" :key="item.id" class="space-y-2 px-4 py-3">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="truncate text-sm font-semibold text-secondary-900 dark:text-white">
                    {{ item.product_name }}
                  </p>
                  <p class="text-xs text-secondary-500 dark:text-secondary-400">
                    #{{ item.sale_id }} · {{ item.sale_date }} · {{ item.customer_name }}
                  </p>
                </div>
                <p class="shrink-0 text-sm font-semibold" :class="profitClass(item.profit)">
                  {{ formatSignedMoney(item.profit) }}
                </p>
              </div>
              <div class="grid grid-cols-2 gap-2 text-xs text-secondary-600 dark:text-secondary-300">
                <p>Revenue: <span class="font-medium text-secondary-900 dark:text-white">{{ formatMoney(item.revenue) }}</span></p>
                <p>Cost: <span class="font-medium text-secondary-900 dark:text-white">{{ formatMoney(item.cost) }}</span></p>
                <p>Qty: <span class="font-medium text-secondary-900 dark:text-white">{{ formatNumber(item.quantity) }}</span></p>
                <p>Source: <span :class="costSourceClass(item.cost_source)">{{ costSourceLabel(item.cost_source) }}</span></p>
              </div>
            </article>
          </template>
          <template #desktop>
            <table class="w-full min-w-[980px]">
              <thead class="border-b border-secondary-200 bg-secondary-50 dark:border-secondary-700 dark:bg-background-dark">
                <tr>
                  <th class="px-3 py-2 text-left text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                    Sale
                  </th>
                  <th class="px-3 py-2 text-left text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                    Product
                  </th>
                  <th class="px-3 py-2 text-right text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                    Qty
                  </th>
                  <th class="px-3 py-2 text-right text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                    Sale Price
                  </th>
                  <th class="px-3 py-2 text-right text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                    Actual Price
                  </th>
                  <th class="px-3 py-2 text-right text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                    Revenue
                  </th>
                  <th class="px-3 py-2 text-right text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                    Cost
                  </th>
                  <th class="px-3 py-2 text-right text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                    Profit
                  </th>
                  <th class="px-3 py-2 text-left text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                    Cost
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                <tr v-for="item in report.sales_items" :key="item.id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50">
                  <td class="px-3 py-2 text-sm text-secondary-700 dark:text-secondary-300">
                    #{{ item.sale_id }}<br /><span class="text-xs text-secondary-500">{{ item.sale_date }}</span>
                  </td>
                  <td class="px-3 py-2 text-sm font-medium text-secondary-900 dark:text-white">
                    {{ item.product_name }}<br /><span class="text-xs font-normal text-secondary-500">{{ item.variation_name || '-' }}</span>
                  </td>
                  <td class="px-3 py-2 text-right text-sm text-secondary-700 dark:text-secondary-300">
                    {{ formatNumber(item.quantity) }}
                  </td>
                  <td class="px-3 py-2 text-right text-sm text-secondary-700 dark:text-secondary-300">
                    {{ formatMoney(item.unit_price) }}
                  </td>
                  <td class="px-3 py-2 text-right text-sm text-secondary-700 dark:text-secondary-300">
                    {{ formatMoney(item.unit_cost) }}
                  </td>
                  <td class="px-3 py-2 text-right text-sm text-secondary-700 dark:text-secondary-300">
                    {{ formatMoney(item.revenue) }}
                  </td>
                  <td class="px-3 py-2 text-right text-sm text-secondary-700 dark:text-secondary-300">
                    {{ formatMoney(item.cost) }}
                  </td>
                  <td class="px-3 py-2 text-right text-sm font-semibold" :class="profitClass(item.profit)">
                    {{ formatSignedMoney(item.profit) }}
                  </td>
                  <td class="px-3 py-2 text-sm">
                    <span :class="costSourceClass(item.cost_source)">{{ costSourceLabel(item.cost_source) }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </template>
        </ReportPanel>
      </template>
    </div>
  </section>
</template>

<script setup>
import { computed, defineComponent, h, onMounted, ref } from 'vue';
import { AlertTriangle, Banknote, Download, Mail, ReceiptText, RefreshCw, ShoppingBag, TrendingUp, WalletCards } from 'lucide-vue-next';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppFormField from '../components/forms/AppFormField.vue';
import AppFormInput from '../components/forms/AppFormInput.vue';
import { apiRequest } from '../composables/useApiClient';

const ReportPanel = defineComponent({
    props: {
        title: { type: String, required: true },
        empty: { type: Boolean, default: false },
        emptyText: { type: String, default: 'No data found.' },
    },
    setup(props, { slots, attrs }) {
        return () => h('article', { ...attrs, class: ['app-surface rounded-2xl overflow-hidden', attrs.class] }, [
            h('header', { class: 'border-b border-secondary-200 px-4 py-3 dark:border-secondary-700' }, [
                h('h3', { class: 'text-sm font-semibold text-secondary-900 dark:text-white' }, props.title),
            ]),
            props.empty
                ? h('div', { class: 'px-4 py-4 text-sm text-secondary-500 dark:text-secondary-400' }, props.emptyText)
                : [
                    h('div', { class: 'divide-y divide-secondary-200 dark:divide-secondary-700 md:hidden' }, slots.mobile?.()),
                    h('div', { class: 'hidden overflow-x-auto md:block' }, slots.desktop?.()),
                ],
        ]);
    },
});

const selectedMonth = ref(toMonthInputValue());
const loading = ref(false);
const loaded = ref(false);
const emailing = ref(false);
const error = ref('');
const emailMessage = ref('');
const report = ref(defaultReport());

const numberFormatter = new Intl.NumberFormat();
const moneyFormatter = new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const hasCostWarnings = computed(() => Number(report.value.summary.estimated_cost_items || 0) > 0 || Number(report.value.summary.missing_cost_items || 0) > 0);
const downloadUrl = computed(() => `/api/reports/real-profit/pdf?month=${encodeURIComponent(selectedMonth.value)}`);
const salesMarginPercent = computed(() => {
    const revenue = Number(report.value.summary.sales_revenue || 0);
    if (Math.abs(revenue) < 0.00001) return 0;

    return (Number(report.value.summary.sales_profit || 0) / revenue) * 100;
});

function defaultReport() {
    return {
        month: '',
        month_label: '',
        start_date: '',
        end_date: '',
        summary: {
            membership_income: 0,
            membership_count: 0,
            other_payment_income: 0,
            other_payment_count: 0,
            total_payment_income: 0,
            payment_count: 0,
            sales_revenue: 0,
            sales_cost: 0,
            sales_profit: 0,
            sales_transactions: 0,
            sales_quantity: 0,
            sales_item_lines: 0,
            expenses: 0,
            expense_count: 0,
            payment_deductions: 0,
            payment_deduction_count: 0,
            real_profit: 0,
            estimated_cost_items: 0,
            missing_cost_items: 0,
        },
        membership_payments: [],
        other_payments: [],
        sales_items: [],
        sales_by_product: [],
        expenses: [],
        expenses_by_category: [],
        payment_deductions: [],
    };
}

async function loadReport() {
    loading.value = true;
    error.value = '';
    emailMessage.value = '';

    try {
        const response = await apiRequest('/api/reports/real-profit', {
            params: { month: selectedMonth.value },
        });
        report.value = { ...defaultReport(), ...(response || {}) };
        selectedMonth.value = report.value.month || selectedMonth.value;
        loaded.value = true;
    } catch (err) {
        error.value = err?.response?.data?.message || 'Failed to load real profit report.';
    } finally {
        loading.value = false;
    }
}

async function emailReport() {
    emailing.value = true;
    error.value = '';
    emailMessage.value = '';

    try {
        const response = await apiRequest('/api/reports/real-profit/email', {
            method: 'post',
            data: { month: selectedMonth.value },
        });
        emailMessage.value = response?.message || 'Real profit report email queued for administrators.';
    } catch (err) {
        error.value = err?.response?.data?.message || 'Failed to email real profit report.';
    } finally {
        emailing.value = false;
    }
}

function toMonthInputValue(date = new Date()) {
    const month = String(date.getMonth() + 1).padStart(2, '0');
    return `${date.getFullYear()}-${month}`;
}

function formatNumber(value) {
    return numberFormatter.format(Number(value || 0));
}

function formatMoney(value) {
    return moneyFormatter.format(Number(value || 0));
}

function formatSignedMoney(value) {
    const amount = Number(value || 0);
    return `${amount >= 0 ? '+' : '-'}${formatMoney(Math.abs(amount))}`;
}

function formatPercent(value) {
    return `${Number(value || 0).toFixed(2)}%`;
}

function profitClass(value) {
    return Number(value || 0) >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-700 dark:text-red-400';
}

function paymentMethodLabel(value) {
    return { cash: 'Cash', bank: 'Bank', card: 'Card', member_wallet: 'Member Wallet' }[value] || 'Other';
}

function costSourceLabel(value) {
    return { exact: 'Exact', estimated: 'Estimated', missing: 'Missing' }[value] || 'Unknown';
}

function costSourceClass(value) {
    if (value === 'exact') return 'font-medium text-emerald-700 dark:text-emerald-400';
    if (value === 'estimated') return 'font-medium text-amber-700 dark:text-amber-300';
    return 'font-medium text-red-700 dark:text-red-400';
}

onMounted(() => {
    loadReport();
});
</script>
