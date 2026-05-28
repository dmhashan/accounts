<template>
  <div class="bg-white dark:bg-secondary-900 rounded-2xl shadow-2xl shadow-slate-900/10 overflow-hidden w-full">
    <!-- Header -->
    <div class="border-b border-secondary-200 dark:border-secondary-700 px-6 py-5 md:px-8">
      <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
          <p class="text-xs text-secondary-500 dark:text-secondary-400">
            Invoice
          </p>
          <h2 class="text-2xl md:text-3xl font-bold tracking-tight text-secondary-900 dark:text-white">
            #{{ sale.id }}
          </h2>
          <p class="mt-1 text-sm text-secondary-500 dark:text-secondary-400">
            {{ sale.created_at }}
          </p>
          <p v-if="sale.customer_name" class="mt-0.5 text-sm text-secondary-600 dark:text-secondary-300">
            Customer: <span class="font-medium">{{ sale.customer_name }}</span>
            <span v-if="sale.customer_type" class="ml-1 capitalize text-secondary-400 dark:text-secondary-500">({{ sale.customer_type }})</span>
          </p>
        </div>
        <div class="flex flex-col gap-2 items-start sm:items-end shrink-0">
          <span
            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold"
            :class="sale.is_paid
              ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400'
              : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400'"
          >{{ sale.is_paid ? 'Paid' : 'Outstanding' }}</span>
          <div v-if="sale.payment_method" class="flex items-center gap-1.5 text-xs text-secondary-500 dark:text-secondary-400">
            <CreditCard class="w-3.5 h-3.5 shrink-0" />
            <span class="capitalize">{{ sale.payment_method }}</span>
          </div>
          <div v-if="sale.reference_number" class="text-xs text-secondary-400 dark:text-secondary-500">
            Ref: {{ sale.reference_number }}
          </div>
        </div>
      </div>
    </div>

    <!-- Items Table -->
    <div class="px-4 py-5 md:px-8 space-y-5">
      <div class="overflow-x-auto rounded-xl border border-secondary-200 dark:border-secondary-700">
        <table class="w-full border-collapse text-sm">
          <thead>
            <tr class="bg-secondary-50 dark:bg-secondary-800 text-secondary-600 dark:text-secondary-400">
              <th class="border border-secondary-200 dark:border-secondary-700 px-3 py-2 text-left font-semibold">
                Item
              </th>
              <th class="border border-secondary-200 dark:border-secondary-700 px-3 py-2 text-right font-semibold">
                Qty
              </th>
              <th class="border border-secondary-200 dark:border-secondary-700 px-3 py-2 text-right font-semibold whitespace-nowrap">
                Unit Price
              </th>
              <th class="border border-secondary-200 dark:border-secondary-700 px-3 py-2 text-right font-semibold">
                Subtotal
              </th>
            </tr>
          </thead>
          <tbody class="text-secondary-800 dark:text-secondary-200">
            <tr v-for="(item, i) in sale.items" :key="i" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/40">
              <td class="border border-secondary-200 dark:border-secondary-700 px-3 py-2 align-top">
                {{ item.product_name }}
                <span v-if="item.variation_name" class="text-secondary-500 dark:text-secondary-400"> – {{ item.variation_name }}</span>
              </td>
              <td class="border border-secondary-200 dark:border-secondary-700 px-3 py-2 align-top text-right">
                {{ item.quantity }}
              </td>
              <td class="border border-secondary-200 dark:border-secondary-700 px-3 py-2 align-top text-right">
                {{ money(item.unit_price) }}
              </td>
              <td class="border border-secondary-200 dark:border-secondary-700 px-3 py-2 align-top text-right font-medium">
                {{ money(item.subtotal) }}
              </td>
            </tr>
            <tr v-if="!sale.items || sale.items.length === 0">
              <td colspan="4" class="border border-secondary-200 dark:border-secondary-700 px-3 py-4 text-center text-secondary-500">
                No items on record.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Payment Summary -->
      <div class="grid grid-cols-3 gap-3 text-center">
        <div class="bg-secondary-50 dark:bg-secondary-800 rounded-xl px-3 py-3">
          <p class="text-xs text-secondary-500 dark:text-secondary-400 mb-0.5">
            Total
          </p>
          <p class="font-bold text-secondary-900 dark:text-white">
            {{ money(sale.total_amount) }}
          </p>
        </div>
        <div class="bg-green-50 dark:bg-green-900/20 rounded-xl px-3 py-3">
          <p class="text-xs text-green-600 dark:text-green-400 mb-0.5">
            Paid
          </p>
          <p class="font-bold text-green-700 dark:text-green-400">
            {{ money(sale.paid_amount) }}
          </p>
        </div>
        <div
          class="rounded-xl px-3 py-3"
          :class="!sale.is_paid ? 'bg-red-50 dark:bg-red-900/20' : 'bg-secondary-50 dark:bg-secondary-800'"
        >
          <p
            class="text-xs mb-0.5"
            :class="!sale.is_paid ? 'text-red-500 dark:text-red-400' : 'text-secondary-500 dark:text-secondary-400'"
          >
            Outstanding
          </p>
          <p
            class="font-bold"
            :class="!sale.is_paid ? 'text-red-600 dark:text-red-400' : 'text-secondary-900 dark:text-white'"
          >
            {{ money(sale.balance) }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { CreditCard } from 'lucide-vue-next';
defineProps({
    /**
     * Sale object:
     * {
     *   id: number,
     *   customer_name: string|null,
     *   customer_type: string,
     *   payment_method: string|null,
     *   reference_number: string|null,
     *   total_amount: number,
     *   paid_amount: number,
     *   balance: number,
     *   is_paid: boolean,
     *   created_at: string,
     *   items: [{ product_name, variation_name, quantity, unit_price, subtotal }]
     * }
     */
    sale: { type: Object, required: true },
});

const moneyFormatter = new Intl.NumberFormat(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

function money(value) {
    return moneyFormatter.format(Number(value || 0));
}
</script>
