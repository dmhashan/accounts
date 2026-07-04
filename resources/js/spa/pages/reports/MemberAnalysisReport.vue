<template>
  <section class="app-page-frame">
    <AppPageHeader title="Member Analysis">
      <template #cta-slot>
        <a
          :href="exportUrl"
          class="inline-flex h-10 items-center gap-2 rounded-xl border border-secondary-300 px-3 text-sm font-semibold text-secondary-700 transition hover:bg-secondary-50 dark:border-secondary-700 dark:text-secondary-200 dark:hover:bg-secondary-800"
        >
          <Download class="h-4 w-4" :stroke-width="2" />
          Export
        </a>
        <button
          type="button"
          :disabled="loading"
          class="inline-flex h-10 items-center gap-2 rounded-xl bg-primary-600 px-3 text-sm font-semibold text-white transition hover:bg-primary-700 disabled:opacity-60"
          @click="loadReport"
        >
          <RefreshCw class="h-4 w-4" :class="loading ? 'animate-spin' : ''" :stroke-width="2" />
          Refresh
        </button>
      </template>

      <template #extra-slot>
        <form class="flex flex-col gap-3 md:flex-row md:items-end" @submit.prevent="applyFilters">
          <AppFormField label="Search" class="flex-1">
            <div class="relative">
              <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-secondary-400" :stroke-width="2" />
              <AppFormInput v-model.trim="filters.search" class="pl-9" placeholder="Name, phone, email, ID" />
            </div>
          </AppFormField>
          <div class="flex gap-2">
            <button
              type="button"
              :disabled="loading"
              class="inline-flex h-12 flex-1 items-center justify-center gap-2 rounded-2xl bg-secondary-900 px-4 text-sm font-semibold text-white transition hover:bg-secondary-800 disabled:opacity-60 dark:bg-white dark:text-secondary-900 md:flex-none"
              @click="openFilters"
            >
              <Funnel class="h-4 w-4" :stroke-width="2" />
              Filter
              <span v-if="activeFilterCount > 0" class="inline-flex min-w-5 items-center justify-center rounded-full bg-white px-1.5 py-0.5 text-xs font-bold text-secondary-900 dark:bg-secondary-900 dark:text-white">
                {{ activeFilterCount }}
              </span>
            </button>
            <button
              type="button"
              class="inline-flex h-12 w-12 items-center justify-center rounded-2xl border border-secondary-300 text-secondary-600 transition hover:bg-secondary-50 dark:border-secondary-700 dark:text-secondary-300 dark:hover:bg-secondary-800"
              title="Reset filters"
              @click="resetFilters"
            >
              <RotateCcw class="h-4 w-4" :stroke-width="2" />
            </button>
          </div>
        </form>
        <div class="mt-3 flex flex-wrap gap-2">
          <button
            v-for="tag in quickFilterTags"
            :key="tag.key"
            type="button"
            class="inline-flex h-9 items-center rounded-full border px-3 text-xs font-semibold transition"
            :class="quickFilterIsActive(tag)
              ? 'border-primary-600 bg-primary-600 text-white'
              : 'border-secondary-300 text-secondary-600 hover:bg-secondary-50 dark:border-secondary-700 dark:text-secondary-300 dark:hover:bg-secondary-800'"
            @click="toggleQuickFilter(tag)"
          >
            {{ tag.label }}
          </button>
        </div>
      </template>
    </AppPageHeader>

    <div class="app-page-scroll">
      <div v-if="error" class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-200">
        {{ error }}
      </div>
      <div v-if="bulkError" class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-200">
        {{ bulkError }}
      </div>
      <div v-if="bulkMessage" class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-200">
        {{ bulkMessage }}
      </div>

      <div class="mb-4 grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
        <article
          v-for="card in summaryCards"
          :key="card.key"
          class="app-surface rounded-2xl p-4"
        >
          <div class="flex items-center justify-between gap-2">
            <p class="text-xs font-medium text-secondary-500 dark:text-secondary-400">
              {{ card.label }}
            </p>
            <component
              :is="card.icon"
              class="h-4 w-4"
              :class="card.iconClass"
              :stroke-width="2"
            />
          </div>
          <p class="mt-1 text-lg font-semibold text-secondary-900 dark:text-white">
            {{ loading ? '-' : card.format(summary[card.key]) }}
          </p>
        </article>
      </div>

      <article class="app-surface overflow-hidden rounded-2xl">
        <header class="flex flex-col gap-2 border-b border-secondary-200 px-4 py-3 dark:border-secondary-700 md:flex-row md:items-center md:justify-between">
          <div>
            <h3 class="text-sm font-semibold text-secondary-900 dark:text-white">
              Members
            </h3>
            <p class="mt-0.5 text-xs text-secondary-500 dark:text-secondary-400">
              {{ asOfLabel }}
            </p>
          </div>
          <div v-if="canBulkUpdateStatus" class="max-w-full overflow-x-auto">
            <div class="flex flex-nowrap items-center gap-2 whitespace-nowrap">
              <span class="inline-flex h-10 shrink-0 items-center rounded-xl border border-secondary-200 px-3 text-sm font-semibold text-secondary-600 dark:border-secondary-700 dark:text-secondary-300">
                {{ selectedCount }} selected
              </span>
              <div class="w-36 shrink-0">
                <AppFormSelect v-model="bulkAction" class="h-10">
                  <option value="">
                    Actions
                  </option>
                  <option value="active">
                    Active
                  </option>
                  <option value="inactive">
                    Inactive
                  </option>
                </AppFormSelect>
              </div>
              <button
                type="button"
                :disabled="bulkActionDisabled"
                class="inline-flex h-10 shrink-0 items-center gap-2 rounded-xl bg-secondary-900 px-3 text-sm font-semibold text-white transition hover:bg-secondary-800 disabled:opacity-60 dark:bg-white dark:text-secondary-900"
                @click="requestBulkStatusUpdate"
              >
                <CheckCircle2 class="h-4 w-4" :stroke-width="2" />
                Apply
              </button>
              <button
                v-if="selectedCount > 0"
                type="button"
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-secondary-300 text-secondary-600 transition hover:bg-secondary-50 dark:border-secondary-700 dark:text-secondary-300 dark:hover:bg-secondary-800"
                title="Clear selection"
                @click="clearSelectedMembers"
              >
                <X class="h-4 w-4" :stroke-width="2" />
              </button>
            </div>
          </div>
        </header>

        <div v-if="loading" class="px-4 py-6 text-sm text-secondary-500 dark:text-secondary-400">
          Loading member analysis...
        </div>
        <div v-else-if="members.length === 0" class="px-4 py-6 text-sm text-secondary-500 dark:text-secondary-400">
          No members match the selected filters.
        </div>
        <template v-else>
          <div class="divide-y divide-secondary-200 dark:divide-secondary-700 md:hidden">
            <article v-for="member in members" :key="member.member_id" class="px-4 py-4">
              <div class="flex items-start gap-3">
                <input
                  v-if="canBulkUpdateStatus"
                  type="checkbox"
                  class="mt-1 h-4 w-4 rounded border-secondary-300 text-primary-600 focus:ring-primary-500"
                  :checked="memberIsSelected(member.member_id)"
                  :aria-label="`Select ${member.name}`"
                  @change="setMemberSelected(member.member_id, $event.target.checked)"
                />
                <div class="min-w-0 flex-1">
                  <RouterLink :to="`/members/${member.member_id}`" class="text-sm font-semibold text-secondary-900 hover:text-primary-600 dark:text-white">
                    {{ member.name }}
                  </RouterLink>
                  <p class="mt-0.5 text-xs text-secondary-500 dark:text-secondary-400">
                    {{ member.member_code || '-' }} · {{ member.phone || '-' }}
                  </p>
                </div>
              </div>
              <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-secondary-600 dark:text-secondary-300">
                <p>Plan: <span class="font-medium text-secondary-900 dark:text-white">{{ member.plan_name || '-' }}</span></p>
                <p>Payment Expiry: <span class="font-medium text-secondary-900 dark:text-white">{{ formatDate(member.membership_expiry_date, '-') }}</span></p>
                <p>Expiry Days: <span :class="paymentExpiryDaysClass(member.days_until_payment_expiry)">{{ paymentExpiryDaysLabel(member.days_until_payment_expiry) }}</span></p>
                <p>Last Visit: <span class="font-medium text-secondary-900 dark:text-white">{{ formatDate(member.last_attendance_date, '-') }}</span></p>
                <p>Attendance Days: <span :class="attendanceDaysClass(member.days_since_last_attendance)">{{ attendanceDaysLabel(member.days_since_last_attendance) }}</span></p>
                <p>Attendance: <span class="font-medium text-secondary-900 dark:text-white">{{ formatNumber(member.attendance_count) }}</span></p>
                <p>Outstanding: <span class="font-medium text-secondary-900 dark:text-white">{{ formatMoney(member.total_outstanding_amount) }}</span></p>
                <p>Biometric: <span :class="biometricSyncClass(member)">{{ biometricSyncLabel(member) }}</span></p>
              </div>
              <div class="mt-3 flex gap-2">
                <RouterLink :to="`/members/${member.member_id}`" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-secondary-300 px-3 text-xs font-semibold text-secondary-700 dark:border-secondary-700 dark:text-secondary-200">
                  <Eye class="h-3.5 w-3.5" :stroke-width="2" />
                  View
                </RouterLink>
                <RouterLink :to="`/accounting/payments/new?member_id=${member.member_id}`" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-secondary-300 px-3 text-xs font-semibold text-secondary-700 dark:border-secondary-700 dark:text-secondary-200">
                  <CreditCard class="h-3.5 w-3.5" :stroke-width="2" />
                  Payment
                </RouterLink>
              </div>
            </article>
          </div>

          <div class="hidden overflow-x-auto md:block">
            <table class="w-full min-w-[1180px]">
              <thead class="border-b border-secondary-200 bg-secondary-50 dark:border-secondary-700 dark:bg-background-dark">
                <tr>
                  <th v-if="canBulkUpdateStatus" class="w-10 px-3 py-2 text-left">
                    <input
                      type="checkbox"
                      class="h-4 w-4 rounded border-secondary-300 text-primary-600 focus:ring-primary-500"
                      :checked="allVisibleSelected"
                      :indeterminate="someVisibleSelected && !allVisibleSelected"
                      aria-label="Select visible members"
                      @change="toggleAllVisibleMembers($event.target.checked)"
                    />
                  </th>
                  <th class="px-3 py-2 text-left text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                    Member
                  </th>
                  <th class="px-3 py-2 text-left text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                    Phone
                  </th>
                  <th class="px-3 py-2 text-left text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                    Plan
                  </th>
                  <th class="px-3 py-2 text-left text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                    Payment Expiry
                  </th>
                  <th class="px-3 py-2 text-left text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                    Expiry Days
                  </th>
                  <th class="px-3 py-2 text-left text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                    Last Attendance
                  </th>
                  <th class="px-3 py-2 text-left text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                    Attendance Days
                  </th>
                  <th class="px-3 py-2 text-right text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                    Count
                  </th>
                  <th class="px-3 py-2 text-left text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                    Biometric
                  </th>
                  <th class="px-3 py-2 text-right text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                    Outstanding
                  </th>
                  <th class="px-3 py-2 text-right text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                <tr
                  v-for="member in members"
                  :key="member.member_id"
                  class="hover:bg-secondary-50/70 dark:hover:bg-secondary-800/40"
                  :class="memberIsSelected(member.member_id) ? 'bg-primary-50/50 dark:bg-primary-900/10' : ''"
                >
                  <td v-if="canBulkUpdateStatus" class="px-3 py-3">
                    <input
                      type="checkbox"
                      class="h-4 w-4 rounded border-secondary-300 text-primary-600 focus:ring-primary-500"
                      :checked="memberIsSelected(member.member_id)"
                      :aria-label="`Select ${member.name}`"
                      @change="setMemberSelected(member.member_id, $event.target.checked)"
                    />
                  </td>
                  <td class="px-3 py-3">
                    <RouterLink :to="`/members/${member.member_id}`" class="block max-w-44 truncate text-sm font-semibold text-secondary-900 hover:text-primary-600 dark:text-white">
                      {{ member.name }}
                    </RouterLink>
                    <p class="text-xs text-secondary-500 dark:text-secondary-400">
                      {{ member.member_code || '-' }}
                    </p>
                  </td>
                  <td class="px-3 py-3 text-sm text-secondary-700 dark:text-secondary-300">
                    {{ member.phone || '-' }}
                  </td>
                  <td class="px-3 py-3 text-sm text-secondary-700 dark:text-secondary-300">
                    {{ member.plan_name || '-' }}
                  </td>
                  <td class="px-3 py-3 text-sm text-secondary-700 dark:text-secondary-300">
                    {{ formatDate(member.membership_expiry_date, '-') }}
                  </td>
                  <td class="px-3 py-3 text-sm text-secondary-700 dark:text-secondary-300">
                    <span :class="paymentExpiryDaysClass(member.days_until_payment_expiry)">
                      {{ paymentExpiryDaysLabel(member.days_until_payment_expiry) }}
                    </span>
                  </td>
                  <td class="px-3 py-3 text-sm text-secondary-700 dark:text-secondary-300">
                    {{ formatDate(member.last_attendance_date, '-') }}
                  </td>
                  <td class="px-3 py-3 text-sm text-secondary-700 dark:text-secondary-300">
                    <span :class="attendanceDaysClass(member.days_since_last_attendance)">
                      {{ attendanceDaysLabel(member.days_since_last_attendance) }}
                    </span>
                  </td>
                  <td class="px-3 py-3 text-right text-sm font-medium text-secondary-900 dark:text-white">
                    {{ formatNumber(member.attendance_count) }}
                  </td>
                  <td class="px-3 py-3">
                    <span :class="biometricSyncClass(member)">
                      {{ biometricSyncLabel(member) }}
                    </span>
                  </td>
                  <td class="px-3 py-3 text-right text-sm font-semibold text-secondary-900 dark:text-white">
                    {{ formatMoney(member.total_outstanding_amount) }}
                  </td>
                  <td class="px-3 py-3">
                    <div class="flex justify-end gap-1.5">
                      <RouterLink :to="`/members/${member.member_id}`" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-secondary-300 text-secondary-600 transition hover:bg-secondary-100 dark:border-secondary-700 dark:text-secondary-300 dark:hover:bg-secondary-800" title="View member">
                        <Eye class="h-4 w-4" :stroke-width="2" />
                      </RouterLink>
                      <RouterLink :to="`/accounting/payments/new?member_id=${member.member_id}`" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-secondary-300 text-secondary-600 transition hover:bg-secondary-100 dark:border-secondary-700 dark:text-secondary-300 dark:hover:bg-secondary-800" title="Record payment">
                        <CreditCard class="h-4 w-4" :stroke-width="2" />
                      </RouterLink>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>
      </article>

      <div class="mt-4">
        <AppPagination
          :current-page="meta.current_page"
          :last-page="meta.last_page"
          :per-page="meta.per_page"
          :total="meta.total"
          :disabled="loading"
          @page-change="loadMembers"
          @limit-change="changeLimit"
        />
      </div>
    </div>

    <div
      v-if="filtersOpen"
      class="fixed inset-0 z-50 flex items-end justify-center bg-secondary-950/50 p-3 sm:items-center"
      @click.self="closeFilters"
    >
      <article class="app-surface flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl">
        <header class="flex items-center justify-between gap-3 border-b border-secondary-200 px-5 py-4 dark:border-secondary-700">
          <div class="flex min-w-0 items-center gap-3">
            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">
              <Funnel class="h-5 w-5" :stroke-width="2" />
            </span>
            <div class="min-w-0">
              <h3 class="text-base font-semibold text-secondary-900 dark:text-white">
                Filters
              </h3>
              <p class="mt-0.5 text-xs text-secondary-500 dark:text-secondary-400">
                {{ activeDraftFilterCount }} selected
              </p>
            </div>
          </div>
          <button
            type="button"
            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-secondary-300 text-secondary-600 transition hover:bg-secondary-50 dark:border-secondary-700 dark:text-secondary-300 dark:hover:bg-secondary-800"
            title="Close filters"
            @click="closeFilters"
          >
            <X class="h-4 w-4" :stroke-width="2" />
          </button>
        </header>

        <div class="flex-1 overflow-y-auto p-4">
          <div v-if="filterDraftRules.length === 0" class="rounded-2xl border border-dashed border-secondary-300 px-4 py-6 text-sm text-secondary-500 dark:border-secondary-700 dark:text-secondary-400">
            No filters selected.
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="(rule, index) in filterDraftRules"
              :key="rule.id"
              class="grid grid-cols-1 gap-2 rounded-2xl border border-secondary-200 p-3 dark:border-secondary-700 lg:grid-cols-[200px_1fr_auto]"
            >
              <AppFormSelect :model-value="rule.field" class="h-10" @update:model-value="updateRuleField(rule, $event)">
                <option v-for="field in filterFields" :key="field.key" :value="field.key">
                  {{ field.label }}
                </option>
              </AppFormSelect>

              <div v-if="fieldDefinition(rule.field)?.type === 'multi'" class="flex flex-wrap gap-2">
                <label
                  v-for="option in filterOptionsFor(rule.field)"
                  :key="option.value"
                  class="inline-flex h-10 items-center gap-2 rounded-xl border border-secondary-200 px-3 text-sm font-medium text-secondary-700 dark:border-secondary-700 dark:text-secondary-200"
                >
                  <input
                    type="checkbox"
                    class="h-4 w-4 rounded border-secondary-300 text-primary-600 focus:ring-primary-500"
                    :checked="ruleValueIncludes(rule, option.value)"
                    @change="setRuleValueSelected(rule, option.value, $event.target.checked)"
                  />
                  <span>{{ option.label }}</span>
                </label>
              </div>

              <div v-else class="grid grid-cols-1 gap-2 sm:grid-cols-[180px_1fr]">
                <AppFormSelect v-model="rule.operator" class="h-10">
                  <option value="lt">
                    Less than
                  </option>
                  <option value="lte">
                    Less than or equal
                  </option>
                  <option value="eq">
                    Equal
                  </option>
                  <option value="gte">
                    Greater than or equal
                  </option>
                  <option value="gt">
                    Greater than
                  </option>
                </AppFormSelect>
                <AppFormInput
                  v-model="rule.value"
                  class="h-10"
                  :type="fieldDefinition(rule.field)?.type === 'date' ? 'date' : 'number'"
                  :step="fieldDefinition(rule.field)?.type === 'number' ? '1' : undefined"
                />
              </div>

              <button
                type="button"
                class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-secondary-300 text-secondary-600 transition hover:bg-secondary-50 dark:border-secondary-700 dark:text-secondary-300 dark:hover:bg-secondary-800"
                title="Remove filter"
                @click="removeDraftFilterRule(index)"
              >
                <Trash2 class="h-4 w-4" :stroke-width="2" />
              </button>
            </div>
          </div>

          <button
            type="button"
            class="mt-3 inline-flex h-10 items-center gap-2 rounded-xl border border-secondary-300 px-3 text-sm font-semibold text-secondary-700 transition hover:bg-secondary-50 dark:border-secondary-700 dark:text-secondary-200 dark:hover:bg-secondary-800"
            @click="addDraftFilterRule"
          >
            <Plus class="h-4 w-4" :stroke-width="2" />
            Add Filter
          </button>
        </div>

        <footer class="flex flex-col-reverse gap-2 border-t border-secondary-200 px-5 py-4 dark:border-secondary-700 sm:flex-row sm:items-center sm:justify-between">
          <button
            type="button"
            class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-secondary-300 px-3 text-sm font-semibold text-secondary-700 transition hover:bg-secondary-50 dark:border-secondary-700 dark:text-secondary-200 dark:hover:bg-secondary-800"
            @click="clearDraftFilterRules"
          >
            <RotateCcw class="h-4 w-4" :stroke-width="2" />
            Clear
          </button>
          <div class="flex flex-col-reverse gap-2 sm:flex-row">
            <button
              type="button"
              class="inline-flex h-10 items-center justify-center rounded-xl border border-secondary-300 px-4 text-sm font-semibold text-secondary-700 transition hover:bg-secondary-50 dark:border-secondary-700 dark:text-secondary-200 dark:hover:bg-secondary-800"
              @click="closeFilters"
            >
              Cancel
            </button>
            <button
              type="button"
              :disabled="loading"
              class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-primary-600 px-4 text-sm font-semibold text-white transition hover:bg-primary-700 disabled:opacity-60"
              @click="applyDraftFilters"
            >
              <CheckCircle2 class="h-4 w-4" :stroke-width="2" />
              Apply Filters
            </button>
          </div>
        </footer>
      </article>
    </div>

    <AppConfirmModal
      v-if="bulkConfirmOpen"
      :title="bulkConfirmTitle"
      :confirm-label="bulkConfirmButtonLabel"
      cancel-label="Cancel"
      loading-label="Updating..."
      :variant="pendingBulkAction === 'inactive' ? 'warning' : 'primary'"
      :loading="bulkLoading"
      @cancel="closeBulkConfirm"
      @confirm="confirmBulkStatusUpdate"
    >
      <p class="text-sm text-secondary-700 dark:text-secondary-300">
        This will mark {{ selectedCount }} selected {{ selectedCount === 1 ? 'member' : 'members' }} as {{ pendingBulkActionLabel.toLowerCase() }}.
      </p>
    </AppConfirmModal>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import {
    AlertTriangle,
    CalendarClock,
    CheckCircle2,
    CreditCard,
    Download,
    Eye,
    Flame,
    Funnel,
    Plus,
    RefreshCw,
    RotateCcw,
    Search,
    ShieldCheck,
    TimerOff,
    Trash2,
    TrendingUp,
    Users,
    WalletCards,
    X,
} from 'lucide-vue-next';
import AppConfirmModal from '../../components/AppConfirmModal.vue';
import AppPageHeader from '../../components/AppPageHeader.vue';
import AppPagination from '../../components/AppPagination.vue';
import AppFormField from '../../components/forms/AppFormField.vue';
import AppFormInput from '../../components/forms/AppFormInput.vue';
import AppFormSelect from '../../components/forms/AppFormSelect.vue';
import { apiRequest } from '../../composables/useApiClient';
import { useAppContext } from '../../composables/useAppContext';
import { useDateTimeFormat } from '../../composables/useDateTimeFormat';

const { formatDate } = useDateTimeFormat();
const context = useAppContext();
const numberFormatter = new Intl.NumberFormat();
const moneyFormatter = new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const defaultFilters = () => ({
    search: '',
    member_status: '',
    outstanding_only: false,
    payment_missed_only: false,
    inactive_only: false,
    paid_not_attending_only: false,
    attending_with_expired_payment_only: false,
    regular_only: false,
    new_member_only: false,
    filter_rules: [],
    sort: 'name',
    direction: 'asc',
});

const filters = ref(defaultFilters());
const loading = ref(false);
const error = ref('');
const members = ref([]);
const meta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
const asOf = ref('');
const summary = ref(defaultSummary());
const thresholds = ref({});
const selectedMemberIds = ref([]);
const bulkAction = ref('');
const bulkLoading = ref(false);
const bulkError = ref('');
const bulkMessage = ref('');
const bulkConfirmOpen = ref(false);
const pendingBulkAction = ref('');
const planOptions = ref([]);
const filtersOpen = ref(false);
const filterDraftRules = ref([]);

const summaryCards = computed(() => [
    { key: 'total_members', label: 'Total Members', icon: Users, iconClass: 'text-sky-500', format: formatNumber },
    { key: 'active_members', label: 'Active', icon: ShieldCheck, iconClass: 'text-emerald-500', format: formatNumber },
    { key: 'inactive_members', label: 'Inactive', icon: TimerOff, iconClass: 'text-slate-500', format: formatNumber },
    { key: 'payment_missed_members', label: 'Payment Missed', icon: AlertTriangle, iconClass: 'text-amber-500', format: formatNumber },
    { key: 'outstanding_members', label: 'Outstanding', icon: WalletCards, iconClass: 'text-rose-500', format: formatNumber },
    { key: 'total_outstanding_amount', label: 'Outstanding Value', icon: CreditCard, iconClass: 'text-violet-500', format: formatMoney },
    { key: 'paid_not_attending_members', label: 'Paid Not Attending', icon: CalendarClock, iconClass: 'text-orange-500', format: formatNumber },
    { key: 'attending_with_expired_payment_members', label: 'Expired Entry', icon: Flame, iconClass: 'text-red-500', format: formatNumber },
    { key: 'regular_members', label: 'Regular', icon: TrendingUp, iconClass: 'text-emerald-500', format: formatNumber },
    { key: 'new_members', label: 'New', icon: Users, iconClass: 'text-cyan-500', format: formatNumber },
]);

const filterFields = [
    { key: 'plan', label: 'Plan', type: 'multi' },
    { key: 'active', label: 'Active', type: 'multi' },
    { key: 'verified', label: 'Verified', type: 'multi' },
    { key: 'temp', label: 'Temp', type: 'multi' },
    { key: 'payment_expiry_date', label: 'Payment Expiry', type: 'date' },
    { key: 'expiry_days', label: 'Expiry Days', type: 'number' },
    { key: 'last_attendance_date', label: 'Last Attendance', type: 'date' },
    { key: 'attendance_days', label: 'Attendance Days', type: 'number' },
    { key: 'attendance_count', label: 'Count', type: 'number' },
    { key: 'biometric', label: 'Biometric', type: 'multi' },
    { key: 'outstanding', label: 'Outstanding', type: 'number' },
];
const comparisonOperators = ['lt', 'lte', 'eq', 'gte', 'gt'];
const quickFilterTags = [
    { key: 'active', label: 'Active', rules: [{ field: 'active', operator: 'eq', value: ['active'] }] },
    { key: 'inactive', label: 'Inactive', rules: [{ field: 'active', operator: 'eq', value: ['inactive'] }] },
    { key: 'biometric_synced', label: 'Biometric Synced', rules: [{ field: 'biometric', operator: 'eq', value: ['synced'] }] },
    { key: 'biometric_not_synced', label: 'Biometric Not Synced', rules: [{ field: 'biometric', operator: 'eq', value: ['not_synced'] }] },
    { key: 'expired_60', label: 'Expired > 60 days', rules: [{ field: 'expiry_days', operator: 'lt', value: -60 }] },
    { key: 'attendance_2_months', label: 'Last attendance > 2 months', rules: [{ field: 'attendance_days', operator: 'gt', value: 60 }] },
];

const canBulkUpdateStatus = computed(() => Boolean(context.permissions?.membersEdit));
const asOfLabel = computed(() => (asOf.value ? `As of ${formatDate(asOf.value)}` : 'Current report'));
const selectedCount = computed(() => selectedMemberIds.value.length);
const visibleMemberIds = computed(() => members.value.map((member) => Number(member.member_id)));
const allVisibleSelected = computed(() => (
    visibleMemberIds.value.length > 0
    && visibleMemberIds.value.every((id) => selectedMemberIds.value.includes(id))
));
const someVisibleSelected = computed(() => visibleMemberIds.value.some((id) => selectedMemberIds.value.includes(id)));
const bulkActionDisabled = computed(() => bulkLoading.value || selectedCount.value === 0 || bulkAction.value === '');
const pendingBulkActionLabel = computed(() => statusActionLabel(pendingBulkAction.value));
const bulkConfirmTitle = computed(() => `Mark selected members ${pendingBulkActionLabel.value.toLowerCase()}?`);
const bulkConfirmButtonLabel = computed(() => `Mark ${pendingBulkActionLabel.value}`);
const activeFilterCount = computed(() => normalizedFilterRules(filters.value.filter_rules).length);
const activeDraftFilterCount = computed(() => normalizedFilterRules(filterDraftRules.value).length);

const exportUrl = computed(() => {
    const params = new URLSearchParams(reportParams());
    return `/api/reports/member-analysis/export?${params.toString()}`;
});

async function loadReport() {
    loading.value = true;
    error.value = '';

    try {
        const [summaryResponse, membersResponse] = await Promise.all([
            apiRequest('/api/reports/member-analysis/summary', { params: reportParams() }),
            apiRequest('/api/reports/member-analysis/members', { params: reportParams({ page: 1, per_page: meta.value.per_page }) }),
        ]);

        applySummary(summaryResponse);
        applyMembers(membersResponse);
    } catch (err) {
        error.value = err?.response?.data?.message || 'Failed to load member analysis report.';
    } finally {
        loading.value = false;
    }
}

async function loadMembers(page = 1) {
    loading.value = true;
    error.value = '';

    try {
        const response = await apiRequest('/api/reports/member-analysis/members', {
            params: reportParams({ page, per_page: meta.value.per_page }),
        });
        applyMembers(response);
    } catch (err) {
        error.value = err?.response?.data?.message || 'Failed to load member analysis report.';
    } finally {
        loading.value = false;
    }
}

function applySummary(response) {
    summary.value = { ...defaultSummary(), ...(response?.summary || {}) };
    thresholds.value = response?.thresholds || thresholds.value;
    asOf.value = response?.as_of || asOf.value;
}

function applyMembers(response) {
    members.value = response?.data || [];
    thresholds.value = response?.thresholds || thresholds.value;
    asOf.value = response?.as_of || asOf.value;
    meta.value = {
        current_page: response?.meta?.current_page || 1,
        last_page: response?.meta?.last_page || 1,
        per_page: response?.meta?.per_page || meta.value.per_page,
        total: response?.meta?.total || 0,
    };
    pruneSelectedMembers();
}

function applyFilters() {
    clearSelectedMembers();
    clearBulkFeedback();
    loadReport();
}

function resetFilters() {
    filters.value = defaultFilters();
    filterDraftRules.value = [];
    filtersOpen.value = false;
    clearSelectedMembers();
    clearBulkFeedback();
    loadReport();
}

function changeLimit(limit) {
    meta.value.per_page = limit;
    clearSelectedMembers();
    clearBulkFeedback();
    loadMembers(1);
}

function memberIsSelected(memberId) {
    return selectedMemberIds.value.includes(Number(memberId));
}

function setMemberSelected(memberId, checked) {
    const id = Number(memberId);

    if (!Number.isFinite(id)) return;

    if (checked) {
        selectedMemberIds.value = Array.from(new Set([...selectedMemberIds.value, id]));
        return;
    }

    selectedMemberIds.value = selectedMemberIds.value.filter((selectedId) => selectedId !== id);
}

function toggleAllVisibleMembers(checked) {
    if (checked) {
        selectedMemberIds.value = Array.from(new Set([...selectedMemberIds.value, ...visibleMemberIds.value]));
        return;
    }

    const visible = new Set(visibleMemberIds.value);
    selectedMemberIds.value = selectedMemberIds.value.filter((id) => !visible.has(id));
}

function clearSelectedMembers() {
    selectedMemberIds.value = [];
}

function pruneSelectedMembers() {
    const visible = new Set(visibleMemberIds.value);
    selectedMemberIds.value = selectedMemberIds.value.filter((id) => visible.has(id));
}

function clearBulkFeedback() {
    bulkError.value = '';
    bulkMessage.value = '';
}

async function loadFilterOptions() {
    try {
        const response = await apiRequest('/api/reports/member-analysis/filters/options');
        planOptions.value = response?.plans || [];
    } catch {
        planOptions.value = [];
    }
}

function quickFilterIsActive(tag) {
    return tag.rules.every((tagRule) => (
        filters.value.filter_rules || []
    ).some((rule) => filterRulesEqual(rule, tagRule)));
}

function toggleQuickFilter(tag) {
    const currentRules = cloneFilterRules(filters.value.filter_rules);
    const active = quickFilterIsActive(tag);
    const tagFields = new Set(tag.rules.map((rule) => rule.field));
    const nextRules = active
        ? currentRules.filter((rule) => !tag.rules.some((tagRule) => filterRulesEqual(rule, tagRule)))
        : [
            ...currentRules.filter((rule) => !tagFields.has(rule.field)),
            ...cloneFilterRules(tag.rules),
        ];

    filters.value.filter_rules = nextRules;
    applyFilters();
}

function openFilters() {
    filterDraftRules.value = cloneFilterRules(filters.value.filter_rules);
    filtersOpen.value = true;
}

function closeFilters() {
    filtersOpen.value = false;
}

function applyDraftFilters() {
    filters.value.filter_rules = cloneFilterRules(filterDraftRules.value);
    filtersOpen.value = false;
    applyFilters();
}

function clearDraftFilterRules() {
    filterDraftRules.value = [];
}

function addDraftFilterRule() {
    filterDraftRules.value = [
        ...filterDraftRules.value,
        createFilterRule(),
    ];
}

function createFilterRule(field = 'plan') {
    const definition = fieldDefinition(field);

    return {
        id: newRuleId(),
        field,
        operator: definition?.type === 'multi' ? 'eq' : 'lt',
        value: definition?.type === 'multi' ? [] : '',
    };
}

function newRuleId() {
    return `${Date.now()}-${Math.random().toString(36).slice(2)}`;
}

function cloneFilterRules(rules = []) {
    return (Array.isArray(rules) ? rules : []).map((rule) => {
        const field = fieldDefinition(rule?.field).key;
        const definition = fieldDefinition(field);
        const operator = comparisonOperators.includes(rule?.operator) ? rule.operator : 'eq';

        return {
            id: newRuleId(),
            field,
            operator: definition?.type === 'multi' ? 'eq' : operator,
            value: definition?.type === 'multi'
                ? (Array.isArray(rule?.value) ? rule.value.map(String) : [])
                : (rule?.value ?? ''),
        };
    });
}

function removeDraftFilterRule(index) {
    filterDraftRules.value = filterDraftRules.value.filter((_, ruleIndex) => ruleIndex !== index);
}

function filterRulesEqual(first, second) {
    const firstRule = ruleComparisonValue(first);
    const secondRule = ruleComparisonValue(second);

    return firstRule.field === secondRule.field
        && firstRule.operator === secondRule.operator
        && firstRule.value === secondRule.value;
}

function ruleComparisonValue(rule) {
    const field = fieldDefinition(rule?.field).key;
    const definition = fieldDefinition(field);
    const operator = comparisonOperators.includes(rule?.operator) ? rule.operator : 'eq';
    const value = definition?.type === 'multi'
        ? (Array.isArray(rule?.value) ? rule.value.map(String).sort().join('|') : '')
        : String(rule?.value ?? '');

    return { field, operator: definition?.type === 'multi' ? 'eq' : operator, value };
}

function resetRule(rule) {
    const definition = fieldDefinition(rule.field);
    rule.operator = definition?.type === 'multi' ? 'eq' : 'lt';
    rule.value = definition?.type === 'multi' ? [] : '';
}

function updateRuleField(rule, field) {
    rule.field = fieldDefinition(field).key;
    resetRule(rule);
}

function fieldDefinition(field) {
    return filterFields.find((item) => item.key === field) || filterFields[0];
}

function filterOptionsFor(field) {
    if (field === 'plan') {
        return planOptions.value.map((plan) => ({ value: String(plan.id), label: plan.name }));
    }

    if (field === 'active') {
        return [
            { value: 'active', label: 'Active' },
            { value: 'inactive', label: 'Inactive' },
        ];
    }

    if (field === 'verified') {
        return [
            { value: 'verified', label: 'Verified' },
            { value: 'unverified', label: 'Unverified' },
        ];
    }

    if (field === 'temp') {
        return [
            { value: 'temp', label: 'Temporary' },
            { value: 'full', label: 'Full Member' },
        ];
    }

    if (field === 'biometric') {
        return [
            { value: 'configured', label: 'Configured' },
            { value: 'not_configured', label: 'Not Configured' },
            { value: 'synced', label: 'Synced' },
            { value: 'not_synced', label: 'Not Synced' },
        ];
    }

    return [];
}

function ruleValueIncludes(rule, value) {
    return Array.isArray(rule.value) && rule.value.map(String).includes(String(value));
}

function setRuleValueSelected(rule, value, checked) {
    const current = Array.isArray(rule.value) ? rule.value.map(String) : [];

    if (checked) {
        rule.value = Array.from(new Set([...current, String(value)]));
        return;
    }

    rule.value = current.filter((item) => item !== String(value));
}

function requestBulkStatusUpdate() {
    clearBulkFeedback();

    if (selectedCount.value === 0) {
        bulkError.value = 'Select at least one member.';
        return;
    }

    if (bulkAction.value === '') {
        bulkError.value = 'Choose Active or Inactive before applying.';
        return;
    }

    pendingBulkAction.value = bulkAction.value;
    bulkConfirmOpen.value = true;
}

function closeBulkConfirm() {
    if (bulkLoading.value) return;

    bulkConfirmOpen.value = false;
    pendingBulkAction.value = '';
}

async function confirmBulkStatusUpdate() {
    if (pendingBulkAction.value === '' || selectedCount.value === 0) return;

    bulkLoading.value = true;
    bulkError.value = '';
    bulkMessage.value = '';

    try {
        const response = await apiRequest('/api/reports/member-analysis/members/status', {
            method: 'patch',
            data: {
                member_ids: selectedMemberIds.value,
                status: pendingBulkAction.value,
            },
        });

        bulkMessage.value = response?.message || 'Selected members updated.';
        bulkConfirmOpen.value = false;
        pendingBulkAction.value = '';
        bulkAction.value = '';
        clearSelectedMembers();
        await loadReport();
    } catch (err) {
        bulkConfirmOpen.value = false;
        bulkError.value = err?.response?.data?.message || 'Failed to update selected members.';
    } finally {
        bulkLoading.value = false;
    }
}

function reportParams(extra = {}) {
    const params = {};

    Object.entries({ ...filterPayload(), ...extra }).forEach(([key, value]) => {
        if (value === '' || value === null || value === undefined || value === false) return;
        if (key === 'filter_rules') {
            if (!Array.isArray(value) || value.length === 0) return;
            params[key] = JSON.stringify(value);
            return;
        }
        params[key] = value;
    });

    return params;
}

function filterPayload() {
    return {
        ...filters.value,
        filter_rules: normalizedFilterRules(filters.value.filter_rules),
    };
}

function normalizedFilterRules(rules = []) {
    return (Array.isArray(rules) ? rules : [])
        .map((rule) => ({
            field: rule.field,
            operator: rule.operator || 'eq',
            value: fieldDefinition(rule.field)?.type === 'multi'
                ? (Array.isArray(rule.value) ? rule.value : [])
                : rule.value,
        }))
        .filter((rule) => {
            if (fieldDefinition(rule.field)?.type === 'multi') {
                return Array.isArray(rule.value) && rule.value.length > 0;
            }

            return rule.value !== '' && rule.value !== null && rule.value !== undefined;
        });
}

function defaultSummary() {
    return {
        total_members: 0,
        active_members: 0,
        inactive_members: 0,
        low_activity_members: 0,
        payment_missed_members: 0,
        outstanding_members: 0,
        paid_not_attending_members: 0,
        attending_with_expired_payment_members: 0,
        regular_members: 0,
        new_members: 0,
        total_outstanding_amount: 0,
    };
}

function statusActionLabel(value) {
    return { active: 'Active', inactive: 'Inactive' }[value] || 'Selected';
}

function formatNumber(value) {
    return numberFormatter.format(Number(value || 0));
}

function formatMoney(value) {
    return moneyFormatter.format(Number(value || 0));
}

function paymentExpiryDaysLabel(value) {
    if (value === null || value === undefined || value === '') return '-';

    const days = Number(value);
    if (days === 0) return 'Today';
    if (days > 0) return `${formatNumber(days)} ${days === 1 ? 'day' : 'days'} left`;

    const expiredDays = Math.abs(days);
    return `${formatNumber(expiredDays)} ${expiredDays === 1 ? 'day' : 'days'} expired`;
}

function paymentExpiryDaysClass(value) {
    const base = 'font-medium';
    if (value === null || value === undefined || value === '') return `${base} text-secondary-500 dark:text-secondary-400`;

    const days = Number(value);
    if (days < 0) return `${base} text-red-700 dark:text-red-300`;
    if (days <= 3) return `${base} text-amber-700 dark:text-amber-300`;

    return `${base} text-emerald-700 dark:text-emerald-300`;
}

function attendanceDaysLabel(value) {
    if (value === null || value === undefined || value === '') return 'No attendance';

    const days = Number(value);
    if (days === 0) return 'Today';

    return `${formatNumber(days)} ${days === 1 ? 'day' : 'days'} ago`;
}

function attendanceDaysClass(value) {
    const base = 'font-medium';
    if (value === null || value === undefined || value === '') return `${base} text-red-700 dark:text-red-300`;

    const days = Number(value);
    if (days >= Number(thresholds.value.inactive_days || 30)) return `${base} text-red-700 dark:text-red-300`;
    if (days >= Number(thresholds.value.low_activity_days || 14)) return `${base} text-amber-700 dark:text-amber-300`;

    return `${base} text-emerald-700 dark:text-emerald-300`;
}

function biometricSyncLabel(member) {
    if (!member.biometric_configured) return 'Not configured';

    return member.biometric_synced ? 'Synced' : 'Not synced';
}

function biometricSyncClass(member) {
    const base = 'inline-flex rounded-full px-2 py-1 text-xs font-semibold whitespace-nowrap';
    if (!member.biometric_configured) return `${base} bg-secondary-100 text-secondary-600 dark:bg-secondary-800 dark:text-secondary-300`;
    if (member.biometric_synced) return `${base} bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300`;

    return `${base} bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300`;
}

onMounted(async () => {
    await loadFilterOptions();
    await loadReport();
});
</script>
