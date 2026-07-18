<template>
  <section class="app-page-frame">
    <AppPageHeader show-back :title="event.name ? `${event.name} — Registrations` : 'Registrations'" />

    <div v-if="loading" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">
      Loading...
    </div>

    <div v-else-if="errorMessage" class="p-6 text-sm text-red-600 dark:text-red-400">
      {{ errorMessage }}
    </div>

    <div v-else class="app-page-scroll">
      <div class="app-surface rounded-2xl overflow-hidden">
        <div class="px-4 py-3 border-b border-secondary-200 dark:border-secondary-700 flex flex-col gap-2">
          <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="shrink-0 space-y-1">
              <h2 class="text-sm font-semibold text-secondary-900 dark:text-white">
                Registrations ({{ regMeta.total }})
              </h2>
              <div class="flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs text-secondary-500 dark:text-secondary-400">
                <span class="inline-flex items-center gap-1">
                  <Check class="w-3 h-3 text-blue-500" :stroke-width="2.5" />
                  Attended: <strong class="text-secondary-700 dark:text-secondary-200 ml-0.5">{{ regMeta.attended_total }}</strong>
                </span>
                <span class="text-secondary-300 dark:text-secondary-600">|</span>
                <span>Members: <strong class="text-secondary-700 dark:text-secondary-200">{{ regMeta.attended_members }}</strong></span>
                <span class="text-secondary-300 dark:text-secondary-600">|</span>
                <span>Additional: <strong class="text-secondary-700 dark:text-secondary-200">{{ regMeta.attended_guests }}</strong></span>
              </div>
            </div>
            <div class="flex items-center gap-2 flex-1 sm:justify-end">
              <AppSearchField
                v-model="regSearch"
                placeholder="Search by name or phone"
                class="w-full sm:w-64"
                :disabled="regLoading"
              />
              <button
                type="button"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-primary-600 hover:bg-primary-700 text-white transition-colors shrink-0"
                @click="openRegModal"
              >
                <Plus class="w-3.5 h-3.5" :stroke-width="2.5" />
                Add Registration
              </button>
            </div>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <select
              v-model="regStatusFilter"
              class="rounded-lg border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-800 px-3 py-1.5 text-sm text-secondary-700 dark:text-secondary-200 focus:outline-none focus:ring-2 focus:ring-primary-500"
              @change="loadRegistrations(1)"
            >
              <option value="">
                All payments
              </option>
              <option value="paid">
                Paid
              </option>
              <option value="unpaid">
                Unpaid
              </option>
            </select>
            <select
              v-model="regTypeFilter"
              class="rounded-lg border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-800 px-3 py-1.5 text-sm text-secondary-700 dark:text-secondary-200 focus:outline-none focus:ring-2 focus:ring-primary-500"
              @change="loadRegistrations(1)"
            >
              <option value="">
                All types
              </option>
              <option value="member">
                Members only
              </option>
              <option value="walkin">
                Walk-ins only
              </option>
            </select>
            <button
              v-if="hasActiveFilters"
              type="button"
              class="rounded-lg px-3 py-1.5 text-sm font-medium text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
              @click="clearFilters"
            >
              Clear filters
            </button>
          </div>
        </div>

        <div v-if="regLoading" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">
          Loading registrations...
        </div>

        <template v-else-if="registrations.length === 0">
          <div class="p-8 text-center text-secondary-400 dark:text-secondary-500 text-sm">
            {{ regSearch.trim() || regStatusFilter || regTypeFilter ? 'No registrations match your filters.' : 'No registrations yet.' }}
          </div>
        </template>

        <template v-else>
          <!-- Mobile cards -->
          <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
            <article v-for="reg in registrations" :key="reg.id" class="p-4 space-y-1">
              <div class="flex items-start justify-between gap-2">
                <div>
                  <template v-if="reg.member">
                    <RouterLink :to="`/members/${reg.member.id}`" class="text-sm font-semibold text-primary-600 dark:text-primary-400 hover:underline">
                      {{ reg.member.name }}
                    </RouterLink>
                    <span class="ml-1 text-xs text-secondary-400">({{ reg.member.gender === 'female' ? 'F' : 'M' }})</span>
                    <p v-if="reg.member.phone_number" class="text-xs text-secondary-500 dark:text-secondary-400">
                      {{ reg.member.phone_number }}
                    </p>
                  </template>
                  <template v-else>
                    <p class="text-sm font-semibold text-secondary-900 dark:text-white">
                      {{ reg.name }}
                    </p>
                    <p v-if="reg.phone" class="text-xs text-secondary-500 dark:text-secondary-400">
                      {{ reg.phone }}
                    </p>
                  </template>
                  <p v-if="reg.guests.length > 0" class="text-xs text-secondary-400">
                    + {{ reg.guests.length }} guest{{ reg.guests.length !== 1 ? 's' : '' }}
                  </p>
                </div>
                <div class="text-right shrink-0 space-y-1">
                  <p class="text-sm font-semibold text-secondary-900 dark:text-white">
                    {{ formatFee(reg.total_fee) }}
                  </p>
                  <p class="text-xs text-secondary-400">
                    {{ formatDateTime(reg.created_at) }}
                  </p>
                  <div class="flex items-center justify-end gap-1.5 flex-wrap">
                    <span v-if="reg.is_paid" class="inline-block px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300">Paid</span>
                    <button
                      v-else-if="reg.total_fee > 0"
                      type="button"
                      class="inline-block px-2 py-0.5 text-xs font-semibold rounded-full bg-primary-100 text-primary-700 hover:bg-primary-200 dark:bg-primary-900/30 dark:text-primary-300 transition-colors"
                      @click="openPayModal(reg)"
                    >
                      Pay Now
                    </button>
                    <span
                      v-if="reg.is_attended"
                      class="inline-block px-2 py-0.5 text-xs font-semibold rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300"
                    >Attended</span>
                    <button
                      v-else
                      type="button"
                      class="inline-block px-2 py-0.5 text-xs font-semibold rounded-full bg-secondary-100 text-secondary-500 dark:bg-secondary-800 dark:text-secondary-400"
                      :disabled="attendanceLoading[reg.id]"
                      @click="toggleAttendance(reg)"
                    >
                      Mark Attended
                    </button>
                  </div>
                  <template v-if="!reg.is_paid">
                    <div class="flex items-center justify-end gap-1.5 mt-0.5">
                      <button type="button" class="text-xs text-secondary-400 hover:text-secondary-700 dark:hover:text-secondary-200 transition-colors" @click="openEditRegModal(reg)">
                        Edit
                      </button>
                      <span class="text-secondary-300 dark:text-secondary-600">·</span>
                      <button type="button" class="text-xs text-red-400 hover:text-red-600 dark:hover:text-red-400 transition-colors" @click="confirmDeleteReg(reg)">
                        Delete
                      </button>
                    </div>
                  </template>
                </div>
              </div>
            </article>
          </div>

          <!-- Desktop table -->
          <table class="hidden md:table w-full text-sm">
            <thead class="border-b border-secondary-200 dark:border-secondary-700 bg-secondary-50 dark:bg-secondary-800/50">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-secondary-700 dark:text-secondary-300">
                  Name
                </th>
                <th class="px-4 py-3 text-center font-semibold text-secondary-700 dark:text-secondary-300">
                  Guests
                </th>
                <th class="px-4 py-3 text-right font-semibold text-secondary-700 dark:text-secondary-300">
                  Total Fee
                </th>
                <th class="px-4 py-3 text-left font-semibold text-secondary-700 dark:text-secondary-300">
                  Registered At
                </th>
                <th class="px-4 py-3 text-center font-semibold text-secondary-700 dark:text-secondary-300">
                  Payment
                </th>
                <th class="px-4 py-3 text-center font-semibold text-secondary-700 dark:text-secondary-300">
                  Attended
                </th>
                <th class="px-4 py-3 text-center font-semibold text-secondary-700 dark:text-secondary-300">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
              <tr v-for="reg in registrations" :key="reg.id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/40 transition-colors align-top">
                <td class="px-4 py-3 align-top">
                  <template v-if="reg.member">
                    <RouterLink :to="`/members/${reg.member.id}`" class="font-medium text-primary-600 dark:text-primary-400 hover:underline">
                      {{ reg.member.name }}
                    </RouterLink>
                    <span class="ml-1 text-xs text-secondary-400">({{ reg.member.gender === 'female' ? 'F' : 'M' }})</span>
                    <div v-if="reg.member.phone_number" class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">
                      {{ reg.member.phone_number }}
                    </div>
                  </template>
                  <template v-else>
                    <span class="font-medium text-secondary-900 dark:text-white">{{ reg.name }}</span>
                    <div v-if="reg.phone" class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">
                      {{ reg.phone }}
                    </div>
                  </template>
                </td>
                <td class="px-4 py-3 text-center text-secondary-600 dark:text-secondary-400">
                  {{ reg.guests.length > 0 ? reg.guests.length : '—' }}
                </td>
                <td class="px-4 py-3 text-right font-semibold text-secondary-800 dark:text-secondary-200 whitespace-nowrap">
                  {{ formatFee(reg.total_fee) }}
                </td>
                <td class="px-4 py-3 text-secondary-500 dark:text-secondary-400 whitespace-nowrap">
                  {{ formatDateTime(reg.created_at) }}
                </td>
                <td class="px-4 py-3 text-center">
                  <span v-if="reg.is_paid" class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300">
                    <Check class="w-3 h-3" :stroke-width="2.5" />
                    Paid
                  </span>
                  <button
                    v-else-if="reg.total_fee > 0"
                    type="button"
                    class="px-3 py-1 text-xs font-semibold rounded-lg bg-primary-600 hover:bg-primary-700 text-white transition-colors"
                    @click="openPayModal(reg)"
                  >
                    Pay Now
                  </button>
                  <span v-else class="text-secondary-400 text-xs">Free</span>
                </td>
                <td class="px-4 py-3 text-center">
                  <span
                    v-if="reg.is_attended"
                    class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300"
                  >
                    <Check class="w-3 h-3" :stroke-width="2.5" />
                    Attended
                  </span>
                  <button
                    v-else
                    type="button"
                    class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full bg-secondary-100 text-secondary-500 dark:bg-secondary-800 dark:text-secondary-400 hover:bg-secondary-200 dark:hover:bg-secondary-700 transition-colors"
                    :disabled="attendanceLoading[reg.id]"
                    @click="toggleAttendance(reg)"
                  >
                    Mark
                  </button>
                </td>
                <td class="px-4 py-3 text-center">
                  <template v-if="!reg.is_paid">
                    <div class="flex items-center justify-center gap-2">
                      <button type="button" class="text-xs text-secondary-500 hover:text-secondary-800 dark:hover:text-secondary-200 transition-colors font-medium" @click="openEditRegModal(reg)">
                        Edit
                      </button>
                      <span class="text-secondary-300 dark:text-secondary-600">·</span>
                      <button type="button" class="text-xs text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition-colors font-medium" @click="confirmDeleteReg(reg)">
                        Delete
                      </button>
                    </div>
                  </template>
                  <span v-else class="text-secondary-300 dark:text-secondary-600 text-xs">—</span>
                </td>
              </tr>
            </tbody>
          </table>
        </template>

        <AppPagination
          v-if="regMeta.last_page > 1"
          :meta="regMeta"
          :disabled="regLoading"
          class="p-4"
          @page="loadRegistrations"
        />
      </div>
    </div>
  </section>

  <!-- Add / Edit Registration Modal -->
  <div v-if="regModalOpen" class="fixed inset-0 z-40 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/45" @click="closeRegModal" />
    <div class="relative z-10 w-full max-w-lg rounded-xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 shadow-xl flex flex-col max-h-[90vh]">
      <div class="flex items-start justify-between gap-3 px-5 py-4 border-b border-secondary-200 dark:border-secondary-700 shrink-0">
        <div>
          <h3 class="text-base font-semibold text-secondary-900 dark:text-white">
            {{ regEditTarget ? 'Edit Registration' : 'Add Registration' }}
          </h3>
          <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">
            {{ regEditTarget ? 'Update registration details' : 'Register a member for this event' }}
          </p>
        </div>
        <button type="button" class="text-secondary-400 hover:text-secondary-700 dark:hover:text-secondary-200 mt-0.5" @click="closeRegModal">
          ✕
        </button>
      </div>

      <div class="overflow-y-auto flex-1 px-5 py-4 space-y-4">
        <AppFormField v-if="!regEditTarget" label="Member" optional>
          <AppSearchableDropdown
            v-model="regMemberId"
            :options="availableRegMembers"
            :option-label="opt => opt.label"
            :option-key="opt => opt.id"
            placeholder="Walk-in (optional)"
            search-placeholder="Search by name or phone…"
            no-results-text="No matching members found."
            clearable
            @update:model-value="onRegMemberSelected"
          />
        </AppFormField>

        <div v-if="regEditTarget && regEditTarget.member" class="flex items-center gap-2 px-3 py-2 bg-secondary-50 dark:bg-secondary-800/50 rounded-xl text-sm">
          <User class="w-4 h-4 text-primary-500 shrink-0" />
          <span class="font-medium text-primary-700 dark:text-primary-300">{{ regEditTarget.member.name }}</span>
          <span class="text-secondary-400 text-xs">{{ regEditTarget.member.biometric_member_id }}</span>
        </div>

        <AppFormField label="Name" required>
          <AppFormInput v-model="regForm.name" placeholder="Full name" :disabled="!regEditTarget" />
        </AppFormField>

        <div class="grid grid-cols-2 gap-3">
          <AppFormField label="Email" optional>
            <AppFormInput v-model="regForm.email" placeholder="Email" :disabled="!regEditTarget" />
          </AppFormField>
          <AppFormField label="Phone" optional>
            <AppFormPhoneInput v-model="regForm.phone" :disabled="!regEditTarget" />
          </AppFormField>
        </div>

        <AppFormField label="Notes" optional>
          <AppFormTextarea v-model="regForm.notes" placeholder="Optional notes" />
        </AppFormField>

        <div>
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-medium text-secondary-700 dark:text-secondary-300">Additional Members</span>
            <button type="button" class="text-xs font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300" @click="addRegGuest">
              + Add
            </button>
          </div>
          <div v-if="regForm.guests.length === 0" class="text-xs text-secondary-400 py-1">
            No additional members.
          </div>
          <div v-for="(guest, idx) in regForm.guests" :key="idx" class="flex items-end gap-2 mb-2">
            <div class="flex-1">
              <AppFormInput v-model="guest.name" placeholder="Full name" />
            </div>
            <button type="button" class="mb-0.5 text-secondary-400 hover:text-red-500 dark:hover:text-red-400 text-sm shrink-0 h-12 flex items-center" @click="removeRegGuest(idx)">
              ✕
            </button>
          </div>
        </div>

        <div class="flex items-center justify-between rounded-2xl bg-secondary-50 dark:bg-secondary-800/50 px-4 py-3">
          <span class="text-sm text-secondary-600 dark:text-secondary-400">
            Total Fee
            <template v-if="regForm.guests.length > 0">
              <span class="text-xs text-secondary-400 ml-1">({{ formatFee(event.ticket_fee) }} + {{ regForm.guests.length }} × {{ formatFee(event.additional_ticket_fee) }})</span>
            </template>
          </span>
          <span class="text-base font-bold text-secondary-900 dark:text-white">
            {{ formatFee((Number(event.ticket_fee) || 0) + regForm.guests.length * (Number(event.additional_ticket_fee) || 0)) }}
          </span>
        </div>

        <template v-if="!regEditTarget">
          <div class="space-y-3 rounded-2xl border border-secondary-200 dark:border-secondary-700 px-4 py-3">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-secondary-700 dark:text-secondary-300">
                  Mark as Paid
                </p>
                <p class="text-xs text-secondary-400">
                  Record payment immediately on registration
                </p>
              </div>
              <button
                type="button"
                :class="regForm.is_paid
                  ? 'relative inline-flex h-6 w-11 shrink-0 rounded-full bg-primary-600 transition-colors'
                  : 'relative inline-flex h-6 w-11 shrink-0 rounded-full bg-secondary-300 dark:bg-secondary-600 transition-colors'"
                role="switch"
                :aria-checked="regForm.is_paid"
                @click="regForm.is_paid = !regForm.is_paid"
              >
                <span :class="regForm.is_paid ? 'translate-x-5' : 'translate-x-1'" class="inline-block h-4 w-4 mt-1 rounded-full bg-white shadow transition-transform" />
              </button>
            </div>
            <div v-if="regForm.is_paid">
              <label class="block text-xs font-semibold text-secondary-600 dark:text-secondary-400 uppercase tracking-wide mb-1.5">Company Account</label>
              <select
                v-model.number="regForm.company_account_id"
                class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 text-secondary-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
              >
                <option :value="null">
                  Select account
                </option>
                <option v-for="acc in companyAccounts" :key="acc.id" :value="acc.id">
                  {{ acc.label || acc.name }}
                </option>
              </select>
              <p v-if="companyAccounts.length === 0" class="mt-1 text-xs text-red-600 dark:text-red-400">
                No company accounts found.
              </p>
            </div>
            <div class="flex items-center justify-between border-t border-secondary-100 dark:border-secondary-700 pt-3">
              <div>
                <p class="text-sm font-medium text-secondary-700 dark:text-secondary-300">
                  Mark as Attended
                </p>
                <p class="text-xs text-secondary-400">
                  Record attendance immediately on registration
                </p>
              </div>
              <button
                type="button"
                :class="regForm.is_attended
                  ? 'relative inline-flex h-6 w-11 shrink-0 rounded-full bg-blue-600 transition-colors'
                  : 'relative inline-flex h-6 w-11 shrink-0 rounded-full bg-secondary-300 dark:bg-secondary-600 transition-colors'"
                role="switch"
                :aria-checked="regForm.is_attended"
                @click="regForm.is_attended = !regForm.is_attended"
              >
                <span :class="regForm.is_attended ? 'translate-x-5' : 'translate-x-1'" class="inline-block h-4 w-4 mt-1 rounded-full bg-white shadow transition-transform" />
              </button>
            </div>
          </div>
        </template>

        <p v-if="regError" class="text-sm text-red-600 dark:text-red-400">
          {{ regError }}
        </p>
      </div>

      <div class="px-5 py-4 border-t border-secondary-200 dark:border-secondary-700 shrink-0 flex items-center justify-end gap-2">
        <button type="button" class="px-4 py-2 text-sm rounded-lg border border-secondary-300 dark:border-secondary-600 text-secondary-700 dark:text-secondary-100 hover:bg-secondary-100 dark:hover:bg-secondary-800" @click="closeRegModal">
          Cancel
        </button>
        <button
          type="button"
          class="px-4 py-2 text-sm font-semibold rounded-lg bg-primary-600 hover:bg-primary-700 text-white disabled:opacity-50"
          :disabled="regSubmitting || !regForm.name.trim()"
          @click="submitRegModal"
        >
          {{ regSubmitting ? (regEditTarget ? 'Saving…' : 'Registering…') : (regEditTarget ? 'Save Changes' : 'Register') }}
        </button>
      </div>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/45" @click="deleteTarget = null" />
    <div class="relative z-10 w-full max-w-sm rounded-xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 p-5 shadow-xl">
      <h3 class="text-base font-semibold text-secondary-900 dark:text-white">
        Delete Registration
      </h3>
      <p class="mt-1.5 text-sm text-secondary-500 dark:text-secondary-400">
        Remove registration for <span class="font-medium text-secondary-800 dark:text-secondary-200">{{ deleteTarget.name }}</span>? This cannot be undone.
      </p>
      <p v-if="deleteError" class="mt-2 text-sm text-red-600 dark:text-red-400">
        {{ deleteError }}
      </p>
      <div class="mt-4 flex items-center justify-end gap-2">
        <button type="button" class="px-4 py-2 text-sm rounded-lg border border-secondary-300 dark:border-secondary-600 text-secondary-700 dark:text-secondary-100 hover:bg-secondary-100 dark:hover:bg-secondary-800" @click="deleteTarget = null">
          Cancel
        </button>
        <button
          type="button"
          class="px-4 py-2 text-sm font-semibold rounded-lg bg-red-600 hover:bg-red-700 text-white disabled:opacity-50"
          :disabled="deleteSubmitting"
          @click="executeDeleteReg"
        >
          {{ deleteSubmitting ? 'Deleting…' : 'Delete' }}
        </button>
      </div>
    </div>
  </div>

  <!-- Pay Now Modal -->
  <div v-if="payModalOpen" class="fixed inset-0 z-40 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/45" @click="closePayModal" />
    <div class="relative z-10 w-full max-w-md rounded-xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 p-4 md:p-5 shadow-xl">
      <div class="flex items-start justify-between gap-3 mb-4">
        <div>
          <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">
            Record Payment
          </h3>
          <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-0.5">
            {{ payTarget?.name }} &mdash; {{ formatFee(payTarget?.total_fee) }}
          </p>
        </div>
        <button type="button" class="text-secondary-400 hover:text-secondary-700 dark:hover:text-secondary-200" @click="closePayModal">
          ✕
        </button>
      </div>

      <div>
        <label class="block text-xs font-semibold text-secondary-600 dark:text-secondary-400 uppercase tracking-wide mb-1.5">Company Account</label>
        <select
          v-model.number="payAccountId"
          class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 text-secondary-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
        >
          <option :value="null">
            Select account
          </option>
          <option v-for="acc in companyAccounts" :key="acc.id" :value="acc.id">
            {{ acc.label || acc.name }}
          </option>
        </select>
        <p v-if="companyAccounts.length === 0" class="mt-2 text-sm text-red-600 dark:text-red-400">
          No company accounts found. Please add one first.
        </p>
      </div>

      <p v-if="payError" class="mt-3 text-sm text-red-600 dark:text-red-400">
        {{ payError }}
      </p>

      <div class="mt-5 flex items-center justify-end gap-2">
        <button type="button" class="px-4 py-2 text-sm rounded-lg border border-secondary-300 dark:border-secondary-600 text-secondary-700 dark:text-secondary-100 hover:bg-secondary-100 dark:hover:bg-secondary-800" @click="closePayModal">
          Cancel
        </button>
        <button
          type="button"
          class="px-4 py-2 text-sm font-semibold rounded-lg bg-primary-600 hover:bg-primary-700 text-white disabled:opacity-50"
          :disabled="paySubmitting || !payAccountId"
          @click="confirmPayment"
        >
          {{ paySubmitting ? 'Processing...' : 'Confirm Payment' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, computed, watch } from 'vue';
import { Check, Plus, User } from 'lucide-vue-next';
import { useRoute } from 'vue-router';
import { apiRequest } from '../composables/useApiClient';
import { useDateTimeFormat } from '../composables/useDateTimeFormat';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppPagination from '../components/AppPagination.vue';
import AppSearchField from '../components/AppSearchField.vue';
import AppFormField from '../components/forms/AppFormField.vue';
import AppFormInput from '../components/forms/AppFormInput.vue';
import AppFormPhoneInput from '../components/forms/AppFormPhoneInput.vue';
import AppFormTextarea from '../components/forms/AppFormTextarea.vue';
import AppSearchableDropdown from '../components/forms/AppSearchableDropdown.vue';

const route = useRoute();

const loading      = ref(false);
const errorMessage = ref('');
const event        = ref({});

const regLoading      = ref(false);
const regSearch       = ref('');
const regStatusFilter = ref('');
const regTypeFilter   = ref('');
const registrations   = ref([]);
const regMeta         = ref({ current_page: 1, last_page: 1, per_page: 20, total: 0, attended_total: 0, attended_members: 0, attended_guests: 0 });

// Pay modal
const companyAccounts = ref([]);
const payModalOpen    = ref(false);
const payTarget       = ref(null);
const payAccountId    = ref(null);
const paySubmitting   = ref(false);
const payError        = ref('');

// Add / Edit modal
const regModalOpen   = ref(false);
const regMembers     = ref([]);
const regMemberId    = ref(null);
const regEditTarget  = ref(null);
const regSubmitting  = ref(false);
const regError       = ref('');
const regForm        = ref({ name: '', email: '', phone: '', notes: '', guests: [], is_paid: false, company_account_id: null, is_attended: false });

// Attendance loading per registration
const attendanceLoading = ref({});

// Delete
const deleteTarget     = ref(null);
const deleteSubmitting = ref(false);
const deleteError      = ref('');

const availableRegMembers = computed(() => {
    const registeredIds = new Set(registrations.value.map(r => r.member?.id).filter(Boolean));
    return regMembers.value.filter(m => !registeredIds.has(m.id));
});

function onRegMemberSelected(id) {
    if (!id) {
        regForm.value.name  = '';
        regForm.value.phone = '';
        return;
    }
    const member = regMembers.value.find(m => m.id === id);
    if (!member) return;
    regForm.value.name  = member.customer_name.trim();
    regForm.value.phone = member.phone_number !== 'N/A' ? member.phone_number : '';
}

function openRegModal() {
    regForm.value       = { name: '', email: '', phone: '', notes: '', guests: [], is_paid: false, company_account_id: companyAccounts.value.length > 0 ? companyAccounts.value[0].id : null, is_attended: false };
    regMemberId.value   = null;
    regEditTarget.value = null;
    regError.value      = '';
    regModalOpen.value  = true;
    if (regMembers.value.length === 0) loadRegMembers();
}

function openEditRegModal(reg) {
    regEditTarget.value = reg;
    regMemberId.value   = reg.member?.id ?? null;
    regForm.value = {
        name:  reg.name  || '',
        email: reg.email || '',
        phone: reg.phone || '',
        notes: reg.notes || '',
        guests: (reg.guests || []).map(g => ({ name: g.name, notes: g.notes || '' })),
    };
    regError.value     = '';
    regModalOpen.value = true;
}

function closeRegModal() {
    regModalOpen.value  = false;
    regEditTarget.value = null;
}

function confirmDeleteReg(reg) {
    deleteTarget.value     = reg;
    deleteError.value      = '';
    deleteSubmitting.value = false;
}

async function executeDeleteReg() {
    if (!deleteTarget.value) return;
    deleteSubmitting.value = true;
    deleteError.value      = '';
    try {
        await apiRequest(`/api/events/${route.params.id}/registrations/${deleteTarget.value.id}`, { method: 'DELETE' });
        registrations.value = registrations.value.filter(r => r.id !== deleteTarget.value.id);
        regMeta.value = { ...regMeta.value, total: Math.max(0, regMeta.value.total - 1) };
        deleteTarget.value = null;
    } catch (e) {
        deleteError.value = e?.response?.data?.message || e?.message || 'Delete failed.';
    } finally {
        deleteSubmitting.value = false;
    }
}

function addRegGuest() {
    regForm.value.guests.push({ name: '', notes: '' });
}

function removeRegGuest(idx) {
    regForm.value.guests.splice(idx, 1);
}

async function loadRegMembers() {
    try {
        const res = await apiRequest('/api/sales/meta');
        regMembers.value = res.members || [];
    } catch {
        // non-critical
    }
}

async function submitRegModal() {
    if (!regForm.value.name.trim()) return;
    regSubmitting.value = true;
    regError.value      = '';
    try {
        const guests = regForm.value.guests
            .filter(g => g.name.trim())
            .map(g => ({ name: g.name.trim(), notes: g.notes?.trim() || null }));

        if (regEditTarget.value) {
            const payload = {
                name:  regForm.value.name.trim(),
                email: regForm.value.email.trim() || null,
                phone: regForm.value.phone.trim() || null,
                notes: regForm.value.notes.trim() || null,
                guests,
            };
            const res = await apiRequest(`/api/events/${route.params.id}/registrations/${regEditTarget.value.id}`, { method: 'PUT', data: payload });
            const idx = registrations.value.findIndex(r => r.id === regEditTarget.value.id);
            if (idx !== -1) registrations.value[idx] = res.data;
        } else {
            const payload = {
                member_id:           regMemberId.value ?? null,
                name:                regForm.value.name.trim(),
                email:               regForm.value.email.trim() || null,
                phone:               regForm.value.phone.trim() || null,
                notes:               regForm.value.notes.trim() || null,
                guests,
                is_paid:             regForm.value.is_paid,
                company_account_id:  regForm.value.is_paid ? regForm.value.company_account_id : null,
                is_attended:         regForm.value.is_attended,
            };
            const res = await apiRequest(`/api/events/${route.params.id}/registrations`, { method: 'POST', data: payload });
            registrations.value.unshift(res.data);
            regMeta.value = { ...regMeta.value, total: regMeta.value.total + 1 };
        }

        closeRegModal();
    } catch (e) {
        regError.value = e?.response?.data?.message || e?.message || 'Failed to save registration.';
    } finally {
        regSubmitting.value = false;
    }
}

function openPayModal(reg) {
    payTarget.value    = reg;
    payAccountId.value = companyAccounts.value.length > 0 ? companyAccounts.value[0].id : null;
    payError.value     = '';
    payModalOpen.value = true;
}

function closePayModal() {
    payModalOpen.value = false;
    payTarget.value    = null;
    payError.value     = '';
}

async function confirmPayment() {
    if (!payAccountId.value) return;
    paySubmitting.value = true;
    payError.value      = '';
    try {
        const res = await apiRequest(
            `/api/events/${route.params.id}/registrations/${payTarget.value.id}/mark-paid`,
            { method: 'POST', data: { account_id: payAccountId.value } }
        );
        const idx = registrations.value.findIndex(r => r.id === payTarget.value.id);
        if (idx !== -1) registrations.value[idx] = res.data;
        closePayModal();
    } catch (e) {
        payError.value = e?.response?.data?.message || e?.message || 'Payment failed.';
    } finally {
        paySubmitting.value = false;
    }
}

async function toggleAttendance(reg) {
    if (reg.is_attended) return;
    attendanceLoading.value[reg.id] = true;
    try {
        const res = await apiRequest(
            `/api/events/${route.params.id}/registrations/${reg.id}/mark-attendance`,
            { method: 'POST' }
        );
        const idx = registrations.value.findIndex(r => r.id === reg.id);
        if (idx !== -1) registrations.value[idx] = res.data;
        // update attendance stats
        const guestDelta = (reg.guests?.length ?? 0);
        regMeta.value = {
            ...regMeta.value,
            attended_total:   regMeta.value.attended_total + 1,
            attended_members: reg.member ? regMeta.value.attended_members + 1 : regMeta.value.attended_members,
            attended_guests:  regMeta.value.attended_guests + guestDelta,
        };
    } catch {
        // silently fail
    } finally {
        delete attendanceLoading.value[reg.id];
    }
}

async function loadEvent() {
    loading.value = true;
    errorMessage.value = '';
    try {
        event.value = await apiRequest(`/api/events/${route.params.id}`);
    } catch {
        errorMessage.value = 'Failed to load event.';
    } finally {
        loading.value = false;
    }
}

async function loadCompanyAccounts() {
    try {
        const res = await apiRequest('/api/accounts');
        companyAccounts.value = res.data || [];
    } catch {
        // non-critical
    }
}

const hasActiveFilters = computed(() => regStatusFilter.value || regTypeFilter.value);

function clearFilters() {
    regStatusFilter.value = '';
    regTypeFilter.value   = '';
    loadRegistrations(1);
}

async function loadRegistrations(page = 1) {
    regLoading.value = true;
    try {
        const params = new URLSearchParams({ page, per_page: 20 });
        if (regSearch.value.trim()) params.set('search', regSearch.value.trim());
        if (regStatusFilter.value) params.set('status', regStatusFilter.value);
        if (regTypeFilter.value)   params.set('type',   regTypeFilter.value);
        const res = await apiRequest(`/api/events/${route.params.id}/registrations?${params}`);
        registrations.value = res.data;
        regMeta.value       = res.meta;
    } catch {
        // silently fail
    } finally {
        regLoading.value = false;
    }
}

let _regSearchTimer = null;
watch(regSearch, () => {
    clearTimeout(_regSearchTimer);
    _regSearchTimer = setTimeout(() => loadRegistrations(1), 350);
});

const { formatDateTime } = useDateTimeFormat();

function formatFee(fee) {
    return Number(fee) > 0 ? `$${Number(fee).toFixed(2)}` : 'Free';
}

onMounted(async () => {
    await Promise.all([loadEvent(), loadCompanyAccounts()]);
    await loadRegistrations();
});
</script>
