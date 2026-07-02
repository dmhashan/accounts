<template>
  <section class="app-page-frame">
    <AppPageHeader show-back :title="employee?.name || 'Employee Profile'">
      <template #cta-slot>
        <AppHeaderAction
          v-if="employee && canManageEmployees"
          :to="`/employees/${employee.id}/edit`"
          :icon="Pencil"
          label="Edit"
        />
      </template>
    </AppPageHeader>

    <div class="app-page-scroll">
      <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-200">
        {{ errorMessage }}
      </div>

      <div v-if="loading" class="app-surface rounded-2xl p-6 text-sm text-secondary-500 dark:text-secondary-400">
        Loading employee...
      </div>

      <template v-else-if="employee">
        <div class="app-surface rounded-2xl p-5">
          <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
              <p class="text-sm font-semibold text-primary-600 dark:text-primary-400">
                {{ employee.employee_code }}
              </p>
              <h3 class="mt-1 text-2xl font-bold text-secondary-900 dark:text-white">
                {{ employee.name }}
              </h3>
              <p class="mt-1 text-sm text-secondary-500 dark:text-secondary-400">
                {{ employee.job_title || 'No job title' }}<span v-if="employee.department"> &bull; {{ employee.department }}</span>
              </p>
            </div>
            <span class="w-fit rounded-full px-3 py-1 text-xs font-semibold" :class="employeeStatusClass(employee.status)">
              {{ employee.status_label }}
            </span>
          </div>

          <div class="mt-5 flex flex-wrap gap-2">
            <button
              v-for="tab in tabs"
              :key="tab.value"
              type="button"
              class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm font-semibold transition-colors"
              :class="activeTab === tab.value
                ? 'border-primary-200 bg-primary-50 text-primary-700 dark:border-primary-800 dark:bg-primary-900/30 dark:text-primary-300'
                : 'border-secondary-200 text-secondary-600 hover:bg-secondary-50 dark:border-secondary-700 dark:text-secondary-300 dark:hover:bg-secondary-800'"
              @click="setActiveTab(tab.value)"
            >
              <component :is="tab.icon" class="h-4 w-4" />
              {{ tab.label }}
            </button>
          </div>
        </div>

        <div v-if="activeTab === 'overview'" class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1fr)_24rem]">
          <div class="space-y-4">
            <div class="app-surface rounded-2xl p-5">
              <h3 class="text-base font-semibold text-secondary-900 dark:text-white">
                Basic Details
              </h3>
              <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                <InfoRow label="Email" :value="employee.email || '—'" />
                <InfoRow label="Phone" :value="employee.phone || '—'" />
                <InfoRow label="NIC" :value="employee.nic || '—'" />
                <InfoRow label="Gender" :value="employee.gender || '—'" />
                <InfoRow label="Date of birth" :value="employee.date_of_birth || '—'" />
                <InfoRow label="Employment type" :value="employee.employment_type_label || '—'" />
                <InfoRow label="Joined" :value="employee.joined_date || '—'" />
                <InfoRow label="Left" :value="employee.left_date || '—'" />
                <InfoRow class="md:col-span-2" label="Address" :value="employee.address || '—'" />
              </div>
            </div>

            <div class="app-surface rounded-2xl p-5">
              <div class="mb-4 flex items-center justify-between gap-3">
                <h3 class="text-base font-semibold text-secondary-900 dark:text-white">
                  Documents
                </h3>
                <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-3 py-2 text-sm font-semibold text-white hover:bg-primary-700" @click="documentModalOpen = true">
                  <Upload class="h-4 w-4" />
                  Upload
                </button>
              </div>
              <div v-if="documentsLoading" class="py-6 text-sm text-secondary-500 dark:text-secondary-400">
                Loading documents...
              </div>
              <div v-else-if="documents.length === 0" class="rounded-xl border border-dashed border-secondary-300 px-5 py-8 text-center text-sm text-secondary-500 dark:border-secondary-700 dark:text-secondary-400">
                No employee documents uploaded.
              </div>
              <div v-else class="divide-y divide-secondary-100 dark:divide-secondary-800">
                <article v-for="document in documents" :key="document.id" class="flex items-start justify-between gap-3 py-3">
                  <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-secondary-900 dark:text-white">
                      {{ document.name }}
                    </p>
                    <p class="mt-0.5 text-xs text-secondary-500 dark:text-secondary-400">
                      {{ document.category_label }} &bull; {{ formatFileSize(document.file_size) }} &bull; {{ document.created_at }}
                    </p>
                    <p v-if="document.notes" class="mt-1 text-xs text-secondary-500 dark:text-secondary-400">
                      {{ document.notes }}
                    </p>
                  </div>
                  <div class="flex shrink-0 gap-1">
                    <button
                      type="button"
                      title="View"
                      class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-secondary-200 text-secondary-500 hover:bg-secondary-50 dark:border-secondary-700 dark:text-secondary-300 dark:hover:bg-secondary-800"
                      @click="viewDocument(document)"
                    >
                      <Eye class="h-4 w-4" />
                    </button>
                    <button
                      type="button"
                      title="Delete"
                      class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 text-red-500 hover:bg-red-50 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-900/20"
                      @click="deleteDocument(document)"
                    >
                      <Trash2 class="h-4 w-4" />
                    </button>
                  </div>
                </article>
              </div>
            </div>
          </div>

          <div class="space-y-4">
            <div class="app-surface rounded-2xl p-5">
              <h3 class="text-base font-semibold text-secondary-900 dark:text-white">
                Pay Setup
              </h3>
              <div class="mt-4 space-y-3">
                <InfoRow label="Pay method" :value="employee.pay_method_label || 'Daily rate'" />
                <InfoRow label="Daily rate" :value="money(employee.daily_rate)" />
                <InfoRow label="Annual leave" :value="`${formatDays(employee.annual_leave_days)} days`" />
                <InfoRow label="Employee Pay Sheet notes" :value="employee.pay_sheet_notes || '—'" />
              </div>
            </div>

            <div class="app-surface rounded-2xl p-5">
              <h3 class="text-base font-semibold text-secondary-900 dark:text-white">
                Emergency Contact
              </h3>
              <div class="mt-4 space-y-3">
                <InfoRow label="Name" :value="employee.emergency_contact_name || '—'" />
                <InfoRow label="Phone" :value="employee.emergency_contact_phone || '—'" />
              </div>
            </div>
          </div>
        </div>

        <div v-else-if="activeTab === 'attendance'" class="mt-4 app-surface rounded-2xl p-4 md:p-5">
          <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
              <h3 class="text-base font-semibold text-secondary-900 dark:text-white">
                Attendance
              </h3>
              <p class="mt-0.5 text-sm text-secondary-500 dark:text-secondary-400">
                {{ attendanceMonthLabel }}
              </p>
            </div>
            <input
              v-model="attendanceMonth"
              type="month"
              class="w-full rounded-xl border border-secondary-300 bg-white px-3 py-2 text-sm text-secondary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 md:w-48 dark:border-secondary-700 dark:bg-secondary-900 dark:text-white"
              @change="loadAttendance"
            />
          </div>

          <div class="mb-4 grid grid-cols-2 gap-2 md:grid-cols-4 xl:grid-cols-8">
            <div v-for="status in attendanceStatuses" :key="status.value" class="rounded-xl border border-secondary-200 px-3 py-2 dark:border-secondary-700">
              <p class="text-xs text-secondary-500 dark:text-secondary-400">
                {{ status.label }}
              </p>
              <p class="text-lg font-bold text-secondary-900 dark:text-white">
                {{ attendanceStats[status.value] || 0 }}
              </p>
            </div>
            <div class="rounded-xl border border-primary-200 bg-primary-50 px-3 py-2 dark:border-primary-900 dark:bg-primary-900/20">
              <p class="text-xs text-primary-600 dark:text-primary-300">
                Payable days
              </p>
              <p class="text-lg font-bold text-primary-700 dark:text-primary-200">
                {{ attendancePayableDays }}
              </p>
            </div>
            <div v-if="attendanceLeaveBalance" class="rounded-xl border border-secondary-200 px-3 py-2 dark:border-secondary-700">
              <p class="text-xs text-secondary-500 dark:text-secondary-400">
                Annual leave
              </p>
              <p class="text-lg font-bold text-secondary-900 dark:text-white">
                {{ formatDays(attendanceLeaveBalance.annual_entitlement) }}
              </p>
            </div>
            <div v-if="attendanceLeaveBalance" class="rounded-xl border border-secondary-200 px-3 py-2 dark:border-secondary-700">
              <p class="text-xs text-secondary-500 dark:text-secondary-400">
                Used leave
              </p>
              <p class="text-lg font-bold text-secondary-900 dark:text-white">
                {{ formatDays(attendanceLeaveBalance.used) }}
              </p>
            </div>
            <div v-if="attendanceLeaveBalance" class="rounded-xl border border-green-200 bg-green-50 px-3 py-2 dark:border-green-900 dark:bg-green-900/20">
              <p class="text-xs text-green-700 dark:text-green-300">
                Available leave
              </p>
              <p class="text-lg font-bold text-green-800 dark:text-green-200">
                {{ formatDays(attendanceLeaveBalance.available) }}
              </p>
            </div>
          </div>

          <div v-if="attendanceActionError" class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-200">
            {{ attendanceActionError }}
          </div>

          <div v-if="attendanceLoading" class="rounded-xl border border-secondary-200 px-5 py-8 text-center text-sm text-secondary-500 dark:border-secondary-700 dark:text-secondary-400">
            Loading attendance...
          </div>

          <div v-else class="overflow-x-auto">
            <div class="grid min-w-[46rem] grid-cols-7 overflow-hidden rounded-2xl border border-secondary-200 dark:border-secondary-700">
              <div v-for="day in weekDays" :key="day" class="bg-secondary-50 px-2 py-2 text-center text-xs font-semibold uppercase text-secondary-500 dark:bg-secondary-800 dark:text-secondary-400">
                {{ day }}
              </div>
              <div
                v-for="cell in calendarCells"
                :key="cell.key"
                class="min-h-[7.25rem] border-t border-secondary-200 p-2 dark:border-secondary-700"
                :class="cell.date ? 'bg-white dark:bg-secondary-900' : 'bg-secondary-50/70 dark:bg-secondary-950/70'"
              >
                <template v-if="cell.date">
                  <div class="mb-2 flex items-center justify-between gap-2">
                    <span class="text-sm font-semibold text-secondary-900 dark:text-white">{{ cell.day }}</span>
                    <button
                      v-if="canManageEmployees && attendanceMap[cell.date]?.id"
                      type="button"
                      title="Clear"
                      class="inline-flex h-6 w-6 items-center justify-center rounded-lg text-secondary-400 hover:bg-secondary-100 hover:text-red-500 dark:hover:bg-secondary-800"
                      :disabled="savingAttendanceDate === cell.date"
                      @click="clearAttendance(cell.date)"
                    >
                      <X class="h-3.5 w-3.5" />
                    </button>
                  </div>
                  <select
                    v-if="isAttendanceSelectable(cell.date)"
                    :value="attendanceMap[cell.date]?.status || 'present'"
                    class="w-full rounded-lg border px-2 py-1.5 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-secondary-950"
                    :class="attendanceSelectClass(attendanceMap[cell.date]?.status || 'present')"
                    :disabled="!canManageEmployees || savingAttendanceDate === cell.date"
                    @change="setAttendance(cell.date, $event.target.value)"
                  >
                    <option v-for="status in attendanceStatuses" :key="status.value" :value="status.value">
                      {{ status.label }}
                    </option>
                  </select>
                  <p v-else class="rounded-lg border border-secondary-200 bg-secondary-50 px-2 py-1.5 text-xs font-semibold text-secondary-400 dark:border-secondary-700 dark:bg-secondary-950 dark:text-secondary-500">
                    {{ disabledAttendanceLabel(cell.date) }}
                  </p>
                  <p v-if="savingAttendanceDate === cell.date" class="mt-2 text-[11px] text-secondary-400">
                    Saving...
                  </p>
                </template>
              </div>
            </div>
          </div>
        </div>

        <div v-else-if="activeTab === 'salary_advances'" class="mt-4 space-y-4">
          <div class="app-surface rounded-2xl p-4 md:p-5">
            <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
              <div>
                <h3 class="text-base font-semibold text-secondary-900 dark:text-white">
                  Salary Advances
                </h3>
                <p class="mt-0.5 text-sm text-secondary-500 dark:text-secondary-400">
                  {{ salaryAdvanceMonthLabel }}
                </p>
              </div>
              <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                <div>
                  <label class="mb-1 block text-xs font-semibold text-secondary-500 dark:text-secondary-400">Month</label>
                  <input
                    v-model="salaryAdvanceMonth"
                    type="month"
                    class="w-full rounded-xl border border-secondary-300 bg-white px-3 py-2 text-sm text-secondary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 sm:w-44 dark:border-secondary-700 dark:bg-secondary-900 dark:text-white"
                    @change="handleSalaryAdvanceMonthChange"
                  />
                </div>
                <button
                  type="button"
                  class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-primary-600 px-4 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-60"
                  :disabled="!companyAccounts.length"
                  @click="openSalaryAdvanceModal()"
                >
                  <Plus class="h-4 w-4" />
                  Add
                </button>
              </div>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-2 text-right sm:max-w-sm">
              <InfoTile label="Advances" :value="String(salaryAdvances.length)" />
              <InfoTile label="Total" :value="money(salaryAdvanceTotal)" value-class="text-red-600 dark:text-red-300" />
            </div>
            <p v-if="salaryAdvanceActionMessage" class="mt-3 rounded-xl border border-green-200 bg-green-50 px-3 py-2 text-sm font-medium text-green-700 dark:border-green-900 dark:bg-green-900/20 dark:text-green-300">
              {{ salaryAdvanceActionMessage }}
            </p>
          </div>

          <div class="app-surface overflow-hidden rounded-2xl">
            <div v-if="salaryAdvancesLoading" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">
              Loading salary advances...
            </div>
            <template v-else>
              <div class="app-table-scroll">
                <table class="w-full min-w-[58rem]">
                  <thead class="app-table-head-sticky border-b border-secondary-200 bg-secondary-50 dark:border-secondary-700 dark:bg-background-dark">
                    <tr>
                      <th class="px-5 py-3 text-left text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                        Date
                      </th>
                      <th class="px-5 py-3 text-left text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                        Account
                      </th>
                      <th class="px-5 py-3 text-left text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                        Description
                      </th>
                      <th class="px-5 py-3 text-left text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                        Expense
                      </th>
                      <th class="px-5 py-3 text-right text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                        Amount
                      </th>
                      <th class="px-5 py-3 text-right text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                        Actions
                      </th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                    <tr v-for="advance in salaryAdvances" :key="advance.id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50">
                      <td class="px-5 py-4 text-sm text-secondary-700 dark:text-secondary-300">
                        {{ advance.adjustment_date || '—' }}
                      </td>
                      <td class="px-5 py-4 text-sm font-medium text-secondary-900 dark:text-white">
                        {{ advance.account_name || '—' }}
                      </td>
                      <td class="px-5 py-4 text-sm text-secondary-700 dark:text-secondary-300">
                        <p class="font-medium text-secondary-900 dark:text-white">
                          {{ advance.description }}
                        </p>
                        <p v-if="advance.notes" class="text-xs text-secondary-500 dark:text-secondary-400">
                          {{ advance.notes }}
                        </p>
                      </td>
                      <td class="px-5 py-4 text-sm text-secondary-700 dark:text-secondary-300">
                        {{ advance.expense_id ? `#${advance.expense_id}` : '—' }}
                      </td>
                      <td class="px-5 py-4 text-right text-sm font-semibold text-red-600 dark:text-red-300">
                        {{ money(advance.amount) }}
                      </td>
                      <td class="px-5 py-4 text-right">
                        <button
                          type="button"
                          title="Edit"
                          class="mr-1 inline-flex h-8 w-8 items-center justify-center rounded-lg border border-secondary-200 text-secondary-500 hover:bg-secondary-50 dark:border-secondary-700 dark:text-secondary-300 dark:hover:bg-secondary-800"
                          @click="openSalaryAdvanceModal(advance)"
                        >
                          <Pencil class="h-4 w-4" />
                        </button>
                        <button
                          type="button"
                          title="Delete"
                          class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 text-red-500 hover:bg-red-50 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-900/20"
                          @click="deleteSalaryAdvance(advance)"
                        >
                          <Trash2 class="h-4 w-4" />
                        </button>
                      </td>
                    </tr>
                    <tr v-if="salaryAdvances.length === 0">
                      <td colspan="6" class="px-5 py-8 text-center text-sm text-secondary-500 dark:text-secondary-400">
                        No salary advances for this month.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </template>
          </div>
        </div>

        <div v-else-if="activeTab === 'pay_sheets'" class="mt-4 space-y-4">
          <div class="app-surface rounded-2xl p-4 md:p-5">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
              <div>
                <h3 class="text-base font-semibold text-secondary-900 dark:text-white">
                  Employee Pay Sheets
                </h3>
                <p class="mt-0.5 text-sm text-secondary-500 dark:text-secondary-400">
                  {{ paySheetPagination.total || 0 }} records
                </p>
              </div>
              <button
                type="button"
                class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-primary-600 px-4 text-sm font-semibold text-white hover:bg-primary-700"
                @click="openPaySheetGenerateModal"
              >
                <Plus class="h-4 w-4" />
                Generate
              </button>
            </div>
            <p v-if="paySheetActionMessage" class="mt-3 rounded-xl border border-green-200 bg-green-50 px-3 py-2 text-sm font-medium text-green-700 dark:border-green-900 dark:bg-green-900/20 dark:text-green-300">
              {{ paySheetActionMessage }}
            </p>
          </div>

          <div class="app-surface overflow-hidden rounded-2xl">
            <div v-if="paySheetsLoading" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">
              Loading employee pay sheets...
            </div>
            <template v-else>
              <div class="app-table-scroll">
                <table class="w-full min-w-[64rem]">
                  <thead>
                    <tr class="border-b border-secondary-200 bg-secondary-50 text-xs uppercase text-secondary-500 dark:border-secondary-700 dark:bg-background-dark dark:text-secondary-400">
                      <th class="px-5 py-3 text-left">
                        Period
                      </th>
                      <th class="px-5 py-3 text-left">
                        Status
                      </th>
                      <th class="px-5 py-3 text-right">
                        Attendance
                      </th>
                      <th class="px-5 py-3 text-right">
                        Payable
                      </th>
                      <th class="px-5 py-3 text-right">
                        Gross
                      </th>
                      <th class="px-5 py-3 text-right">
                        Deductions
                      </th>
                      <th class="px-5 py-3 text-right">
                        Net
                      </th>
                      <th class="px-5 py-3 text-right">
                        Actions
                      </th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                    <tr v-for="item in paySheets" :key="item.id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50">
                      <td class="px-5 py-4">
                        <p class="text-sm font-semibold text-secondary-900 dark:text-white">
                          {{ item.period_start }} to {{ item.period_end }}
                        </p>
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">
                          {{ item.account_name || 'No account selected' }}<span v-if="item.reference_number"> &bull; {{ item.reference_number }}</span>
                        </p>
                      </td>
                      <td class="px-5 py-4">
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="paySheetStatusClass(item.status)">
                          {{ item.status || 'draft' }}
                        </span>
                      </td>
                      <td class="px-5 py-4 text-right text-sm text-secondary-700 dark:text-secondary-300">
                        <p>{{ item.present_days }} present</p>
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">
                          {{ leaveDays(item) }} leave / {{ noPayDays(item) }} no pay
                        </p>
                      </td>
                      <td class="px-5 py-4 text-right text-sm text-secondary-700 dark:text-secondary-300">
                        {{ item.payable_days }}
                      </td>
                      <td class="px-5 py-4 text-right text-sm text-secondary-700 dark:text-secondary-300">
                        {{ money(item.gross_pay) }}
                      </td>
                      <td class="px-5 py-4 text-right text-sm font-semibold text-red-600 dark:text-red-300">
                        {{ money(item.deductions) }}
                      </td>
                      <td class="px-5 py-4 text-right text-sm font-semibold text-primary-600 dark:text-primary-400">
                        {{ money(item.net_pay) }}
                      </td>
                      <td class="px-5 py-4">
                        <div class="flex justify-end gap-2">
                          <button
                            v-if="item.status !== 'paid'"
                            type="button"
                            class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary-700 disabled:opacity-60"
                            :disabled="paySheetPaying"
                            @click="openPaySheetPaymentModal(item)"
                          >
                            <CreditCard class="h-3.5 w-3.5" />
                            Pay now
                          </button>
                          <RouterLink
                            v-else-if="item.expense_id"
                            :to="`/accounting/expenses/${item.expense_id}`"
                            class="inline-flex items-center gap-2 rounded-lg border border-secondary-200 px-3 py-1.5 text-xs font-semibold text-secondary-600 hover:bg-secondary-50 dark:border-secondary-700 dark:text-secondary-300 dark:hover:bg-secondary-800"
                          >
                            <FileText class="h-3.5 w-3.5" />
                            Expense
                          </RouterLink>
                          <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-lg border border-secondary-200 px-3 py-1.5 text-xs font-semibold text-secondary-600 hover:bg-secondary-50 dark:border-secondary-700 dark:text-secondary-300 dark:hover:bg-secondary-800"
                            @click="showPaySheet(item.id)"
                          >
                            <Eye class="h-3.5 w-3.5" />
                            Show
                          </button>
                        </div>
                      </td>
                    </tr>
                    <tr v-if="paySheets.length === 0">
                      <td colspan="8" class="px-5 py-8 text-center text-sm text-secondary-500 dark:text-secondary-400">
                        No employee pay sheets generated.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </template>
          </div>

          <AppPagination
            v-if="paySheetPagination.last_page > 1"
            :current-page="paySheetPagination.current_page"
            :last-page="paySheetPagination.last_page"
            :per-page="paySheetsPerPage"
            :total="paySheetPagination.total"
            :disabled="paySheetsLoading"
            @page-change="loadPaySheets"
            @limit-change="handlePaySheetLimit"
          />
        </div>
      </template>
    </div>

    <div v-if="documentModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50" @click="documentModalOpen = false" />
      <form class="relative z-10 w-full max-w-md rounded-2xl bg-white p-5 shadow-xl dark:bg-secondary-900" @submit.prevent="uploadDocument">
        <div class="mb-4 flex items-start justify-between gap-3">
          <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">
            Upload Document
          </h3>
          <button type="button" class="text-secondary-400 hover:text-secondary-700 dark:hover:text-secondary-200" @click="documentModalOpen = false">
            x
          </button>
        </div>
        <div class="space-y-3">
          <input
            ref="documentFileInput"
            type="file"
            required
            class="w-full rounded-xl border border-secondary-300 bg-white px-3 py-2 text-sm dark:border-secondary-700 dark:bg-secondary-950 dark:text-white"
            @change="onDocumentFileChange"
          />
          <input
            v-model="documentForm.name"
            type="text"
            required
            placeholder="Document name"
            class="w-full rounded-xl border border-secondary-300 bg-white px-3 py-2 text-sm dark:border-secondary-700 dark:bg-secondary-950 dark:text-white"
          />
          <select v-model="documentForm.category" required class="w-full rounded-xl border border-secondary-300 bg-white px-3 py-2 text-sm dark:border-secondary-700 dark:bg-secondary-950 dark:text-white">
            <option v-for="category in documentCategories" :key="category.value" :value="category.value">
              {{ category.label }}
            </option>
          </select>
          <textarea
            v-model="documentForm.notes"
            rows="3"
            placeholder="Notes"
            class="w-full resize-none rounded-xl border border-secondary-300 bg-white px-3 py-2 text-sm dark:border-secondary-700 dark:bg-secondary-950 dark:text-white"
          />
        </div>
        <div class="mt-4 flex justify-end gap-2">
          <button type="button" class="rounded-xl border border-secondary-300 px-4 py-2 text-sm font-semibold text-secondary-700 dark:border-secondary-700 dark:text-secondary-200" @click="documentModalOpen = false">
            Cancel
          </button>
          <button type="submit" class="rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-60" :disabled="documentUploading">
            Upload
          </button>
        </div>
      </form>
    </div>

    <div v-if="salaryAdvanceModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50" @click="closeSalaryAdvanceModal" />
      <form class="relative z-10 w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-secondary-900" @submit.prevent="saveSalaryAdvance">
        <div class="flex flex-col gap-3 border-b border-secondary-200 p-5 md:flex-row md:items-start md:justify-between dark:border-secondary-700">
          <div>
            <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">
              {{ salaryAdvanceModalTitle }}
            </h3>
            <p class="mt-0.5 text-sm text-secondary-500 dark:text-secondary-400">
              {{ employee?.name || 'Employee' }}
            </p>
          </div>
          <button type="button" class="rounded-xl border border-secondary-300 px-3 py-2 text-sm font-semibold text-secondary-700 dark:border-secondary-700 dark:text-secondary-200" @click="closeSalaryAdvanceModal">
            Close
          </button>
        </div>

        <div class="space-y-4 p-5">
          <p v-if="fieldError(salaryAdvanceFormErrors, 'form')" class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700 dark:border-red-900 dark:bg-red-900/20 dark:text-red-300">
            {{ fieldError(salaryAdvanceFormErrors, 'form') }}
          </p>

          <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
              <label class="mb-1 block text-xs font-semibold text-secondary-500 dark:text-secondary-400">Month</label>
              <input
                v-model="salaryAdvanceForm.month"
                type="month"
                required
                class="w-full rounded-xl border border-secondary-300 bg-white px-3 py-2 text-sm text-secondary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-secondary-700 dark:bg-secondary-900 dark:text-white"
                @change="handleSalaryAdvanceFormMonthChange"
              />
              <p v-if="fieldError(salaryAdvanceFormErrors, 'month')" class="mt-1 text-xs font-medium text-red-600 dark:text-red-300">
                {{ fieldError(salaryAdvanceFormErrors, 'month') }}
              </p>
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold text-secondary-500 dark:text-secondary-400">Account</label>
              <select
                v-model="salaryAdvanceForm.company_account_id"
                required
                class="w-full rounded-xl border border-secondary-300 bg-white px-3 py-2 text-sm text-secondary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-secondary-700 dark:bg-secondary-900 dark:text-white"
              >
                <option value="" disabled>
                  Select account
                </option>
                <option v-for="account in companyAccounts" :key="account.id" :value="String(account.id)">
                  {{ account.name }}
                </option>
              </select>
              <p v-if="fieldError(salaryAdvanceFormErrors, 'company_account_id')" class="mt-1 text-xs font-medium text-red-600 dark:text-red-300">
                {{ fieldError(salaryAdvanceFormErrors, 'company_account_id') }}
              </p>
            </div>
          </div>

          <div>
            <label class="mb-1 block text-xs font-semibold text-secondary-500 dark:text-secondary-400">Description</label>
            <input
              v-model="salaryAdvanceForm.description"
              type="text"
              class="w-full rounded-xl border border-secondary-300 bg-white px-3 py-2 text-sm text-secondary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-secondary-700 dark:bg-secondary-900 dark:text-white"
            />
            <p v-if="fieldError(salaryAdvanceFormErrors, 'description')" class="mt-1 text-xs font-medium text-red-600 dark:text-red-300">
              {{ fieldError(salaryAdvanceFormErrors, 'description') }}
            </p>
          </div>

          <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
              <label class="mb-1 block text-xs font-semibold text-secondary-500 dark:text-secondary-400">Amount</label>
              <input
                v-model="salaryAdvanceForm.amount"
                type="number"
                min="0.01"
                step="0.01"
                required
                class="w-full rounded-xl border border-secondary-300 bg-white px-3 py-2 text-sm text-secondary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-secondary-700 dark:bg-secondary-900 dark:text-white"
              />
              <p v-if="fieldError(salaryAdvanceFormErrors, 'amount')" class="mt-1 text-xs font-medium text-red-600 dark:text-red-300">
                {{ fieldError(salaryAdvanceFormErrors, 'amount') }}
              </p>
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold text-secondary-500 dark:text-secondary-400">Date</label>
              <input
                v-model="salaryAdvanceForm.adjustment_date"
                type="date"
                class="w-full rounded-xl border border-secondary-300 bg-white px-3 py-2 text-sm text-secondary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-secondary-700 dark:bg-secondary-900 dark:text-white"
              />
              <p v-if="fieldError(salaryAdvanceFormErrors, 'adjustment_date')" class="mt-1 text-xs font-medium text-red-600 dark:text-red-300">
                {{ fieldError(salaryAdvanceFormErrors, 'adjustment_date') }}
              </p>
            </div>
          </div>

          <div>
            <label class="mb-1 block text-xs font-semibold text-secondary-500 dark:text-secondary-400">Notes</label>
            <textarea
              v-model="salaryAdvanceForm.notes"
              rows="3"
              class="w-full resize-none rounded-xl border border-secondary-300 bg-white px-3 py-2 text-sm text-secondary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-secondary-700 dark:bg-secondary-900 dark:text-white"
            />
            <p v-if="fieldError(salaryAdvanceFormErrors, 'notes')" class="mt-1 text-xs font-medium text-red-600 dark:text-red-300">
              {{ fieldError(salaryAdvanceFormErrors, 'notes') }}
            </p>
          </div>
        </div>

        <div class="flex flex-col gap-2 border-t border-secondary-200 p-5 sm:flex-row sm:items-center sm:justify-end dark:border-secondary-700">
          <button type="button" class="rounded-xl border border-secondary-300 px-4 py-2 text-sm font-semibold text-secondary-700 dark:border-secondary-700 dark:text-secondary-200" @click="closeSalaryAdvanceModal">
            Cancel
          </button>
          <button type="submit" class="inline-flex justify-center rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-60" :disabled="salaryAdvanceSaving">
            {{ salaryAdvanceSubmitLabel }}
          </button>
        </div>
      </form>
    </div>

    <div v-if="paySheetGenerateModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50" @click="closePaySheetGenerateModal" />
      <div class="relative z-10 flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-secondary-900">
        <div class="flex flex-col gap-3 border-b border-secondary-200 p-5 md:flex-row md:items-start md:justify-between dark:border-secondary-700">
          <div>
            <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">
              Generate Employee Pay Sheet
            </h3>
            <p class="mt-0.5 text-sm text-secondary-500 dark:text-secondary-400">
              {{ employee?.name || 'Employee' }}
            </p>
          </div>
          <button type="button" class="rounded-xl border border-secondary-300 px-3 py-2 text-sm font-semibold text-secondary-700 dark:border-secondary-700 dark:text-secondary-200" @click="closePaySheetGenerateModal">
            Close
          </button>
        </div>

        <div class="space-y-4 overflow-y-auto p-5">
          <div class="grid grid-cols-1 gap-3 lg:grid-cols-[14rem_minmax(0,1fr)]">
            <div>
              <label class="mb-1 block text-xs font-semibold text-secondary-500 dark:text-secondary-400">Month</label>
              <input
                v-model="paySheetMonth"
                type="month"
                required
                class="w-full rounded-xl border border-secondary-300 bg-white px-3 py-2 text-sm text-secondary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-secondary-700 dark:bg-secondary-900 dark:text-white"
                @change="handlePaySheetMonthChange"
              />
              <p v-if="fieldError(paySheetAdjustmentFormErrors, 'month')" class="mt-1 text-xs font-medium text-red-600 dark:text-red-300">
                {{ fieldError(paySheetAdjustmentFormErrors, 'month') }}
              </p>
            </div>
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
              <InfoTile label="Selected month" :value="paySheetMonthLabel" />
              <InfoTile label="Status" :value="selectedPaySheetStatusLabel" />
              <InfoTile label="Net" :value="selectedPaySheet ? money(selectedPaySheet.net_pay) : money(0)" />
            </div>
          </div>

          <p v-if="paySheetActionMessage" class="rounded-xl border border-green-200 bg-green-50 px-3 py-2 text-sm font-medium text-green-700 dark:border-green-900 dark:bg-green-900/20 dark:text-green-300">
            {{ paySheetActionMessage }}
          </p>

          <div class="rounded-2xl border border-secondary-200 p-4 dark:border-secondary-700">
            <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
              <div>
                <h4 class="text-sm font-semibold text-secondary-900 dark:text-white">
                  Manual Earnings / Deductions
                </h4>
                <p class="mt-0.5 text-sm text-secondary-500 dark:text-secondary-400">
                  {{ paySheetMonthLabel }}
                </p>
              </div>
              <div class="grid grid-cols-2 gap-2 text-right">
                <InfoTile label="Earnings" :value="money(paySheetAdjustmentSummary.earnings)" />
                <InfoTile label="Deductions" :value="money(paySheetAdjustmentSummary.deductions)" value-class="text-red-600 dark:text-red-300" />
              </div>
            </div>

            <p v-if="fieldError(paySheetAdjustmentFormErrors, 'form')" class="mb-3 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700 dark:border-red-900 dark:bg-red-900/20 dark:text-red-300">
              {{ fieldError(paySheetAdjustmentFormErrors, 'form') }}
            </p>

            <div class="grid grid-cols-1 gap-3 xl:grid-cols-[12rem_minmax(0,1fr)_10rem_10rem_auto]">
              <div>
                <label class="mb-1 block text-xs font-semibold text-secondary-500 dark:text-secondary-400">Type</label>
                <select
                  v-model="paySheetAdjustmentForm.category"
                  class="w-full rounded-xl border border-secondary-300 bg-white px-3 py-2 text-sm text-secondary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-secondary-700 dark:bg-secondary-900 dark:text-white"
                  @change="handleAdjustmentCategoryChange"
                >
                  <option v-for="category in paySheetAdjustmentCategories" :key="category.value" :value="category.value">
                    {{ category.label }}
                  </option>
                </select>
                <p v-if="fieldError(paySheetAdjustmentFormErrors, 'category')" class="mt-1 text-xs font-medium text-red-600 dark:text-red-300">
                  {{ fieldError(paySheetAdjustmentFormErrors, 'category') }}
                </p>
              </div>
              <div>
                <label class="mb-1 block text-xs font-semibold text-secondary-500 dark:text-secondary-400">Description</label>
                <input
                  v-model="paySheetAdjustmentForm.description"
                  type="text"
                  class="w-full rounded-xl border border-secondary-300 bg-white px-3 py-2 text-sm text-secondary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-secondary-700 dark:bg-secondary-900 dark:text-white"
                />
                <p v-if="fieldError(paySheetAdjustmentFormErrors, 'description')" class="mt-1 text-xs font-medium text-red-600 dark:text-red-300">
                  {{ fieldError(paySheetAdjustmentFormErrors, 'description') }}
                </p>
              </div>
              <div>
                <label class="mb-1 block text-xs font-semibold text-secondary-500 dark:text-secondary-400">Amount</label>
                <input
                  v-model="paySheetAdjustmentForm.amount"
                  type="number"
                  min="0.01"
                  step="0.01"
                  required
                  class="w-full rounded-xl border border-secondary-300 bg-white px-3 py-2 text-sm text-secondary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-secondary-700 dark:bg-secondary-900 dark:text-white"
                />
                <p v-if="fieldError(paySheetAdjustmentFormErrors, 'amount')" class="mt-1 text-xs font-medium text-red-600 dark:text-red-300">
                  {{ fieldError(paySheetAdjustmentFormErrors, 'amount') }}
                </p>
              </div>
              <div>
                <label class="mb-1 block text-xs font-semibold text-secondary-500 dark:text-secondary-400">Date</label>
                <input
                  v-model="paySheetAdjustmentForm.adjustment_date"
                  type="date"
                  class="w-full rounded-xl border border-secondary-300 bg-white px-3 py-2 text-sm text-secondary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-secondary-700 dark:bg-secondary-900 dark:text-white"
                />
                <p v-if="fieldError(paySheetAdjustmentFormErrors, 'adjustment_date')" class="mt-1 text-xs font-medium text-red-600 dark:text-red-300">
                  {{ fieldError(paySheetAdjustmentFormErrors, 'adjustment_date') }}
                </p>
              </div>
              <button
                type="button"
                class="inline-flex h-10 items-center justify-center gap-2 self-end rounded-xl border border-secondary-200 px-4 text-sm font-semibold text-secondary-700 hover:bg-secondary-50 disabled:opacity-60 dark:border-secondary-700 dark:text-secondary-200 dark:hover:bg-secondary-800"
                :disabled="paySheetAdjustmentSaving || selectedPaySheetLocked"
                @click="addPaySheetAdjustment"
              >
                <Plus class="h-4 w-4" />
                Add
              </button>
              <textarea
                v-model="paySheetAdjustmentForm.notes"
                rows="2"
                placeholder="Notes"
                class="xl:col-span-5 w-full resize-none rounded-xl border border-secondary-300 bg-white px-3 py-2 text-sm text-secondary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-secondary-700 dark:bg-secondary-900 dark:text-white"
              />
              <p v-if="fieldError(paySheetAdjustmentFormErrors, 'notes')" class="xl:col-span-5 text-xs font-medium text-red-600 dark:text-red-300">
                {{ fieldError(paySheetAdjustmentFormErrors, 'notes') }}
              </p>
            </div>

            <div v-if="paySheetAdjustmentsLoading" class="mt-4 rounded-xl border border-secondary-200 px-5 py-6 text-center text-sm text-secondary-500 dark:border-secondary-700 dark:text-secondary-400">
              Loading adjustments...
            </div>
            <div v-else class="mt-4 overflow-x-auto">
              <table class="w-full min-w-[42rem] text-sm">
                <thead>
                  <tr class="border-b border-secondary-200 text-xs uppercase text-secondary-500 dark:border-secondary-700 dark:text-secondary-400">
                    <th class="py-2 text-left">
                      Type
                    </th>
                    <th class="py-2 text-left">
                      Description
                    </th>
                    <th class="py-2 text-left">
                      Date
                    </th>
                    <th class="py-2 text-right">
                      Amount
                    </th>
                    <th class="py-2 text-right">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-secondary-100 dark:divide-secondary-800">
                  <tr v-for="adjustment in paySheetAdjustments" :key="adjustment.id">
                    <td class="py-2">
                      <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="adjustment.type === 'earning' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'">
                        {{ adjustment.category_label }}
                      </span>
                    </td>
                    <td class="py-2 text-secondary-700 dark:text-secondary-300">
                      <p class="font-medium text-secondary-900 dark:text-white">
                        {{ adjustment.description }}
                      </p>
                      <p v-if="adjustment.notes" class="text-xs text-secondary-500 dark:text-secondary-400">
                        {{ adjustment.notes }}
                      </p>
                    </td>
                    <td class="py-2 text-secondary-600 dark:text-secondary-300">
                      {{ adjustment.adjustment_date || '—' }}
                    </td>
                    <td class="py-2 text-right font-semibold" :class="adjustment.type === 'earning' ? 'text-green-600 dark:text-green-300' : 'text-red-600 dark:text-red-300'">
                      {{ money(adjustment.amount) }}
                    </td>
                    <td class="py-2 text-right">
                      <button
                        type="button"
                        title="Delete"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 text-red-500 hover:bg-red-50 disabled:opacity-50 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-900/20"
                        :disabled="selectedPaySheetLocked"
                        @click="deletePaySheetAdjustment(adjustment)"
                      >
                        <Trash2 class="h-4 w-4" />
                      </button>
                    </td>
                  </tr>
                  <tr v-if="paySheetAdjustments.length === 0">
                    <td colspan="5" class="py-6 text-center text-secondary-500 dark:text-secondary-400">
                      No manual adjustments for this month.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="flex flex-col gap-2 border-t border-secondary-200 p-5 sm:flex-row sm:items-center sm:justify-end dark:border-secondary-700">
          <button type="button" class="rounded-xl border border-secondary-300 px-4 py-2 text-sm font-semibold text-secondary-700 dark:border-secondary-700 dark:text-secondary-200" @click="closePaySheetGenerateModal">
            Cancel
          </button>
          <button
            type="button"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-60"
            :disabled="paySheetGenerating || selectedPaySheetLocked"
            @click="generateSelectedPaySheet"
          >
            <RefreshCw class="h-4 w-4" :class="paySheetGenerating ? 'animate-spin' : ''" />
            {{ paySheetButtonLabel }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="paySheetPaymentModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50" @click="closePaySheetPaymentModal" />
      <form class="relative z-10 w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-secondary-900" @submit.prevent="paySelectedPaySheet">
        <div class="flex items-start justify-between gap-3 border-b border-secondary-200 p-5 dark:border-secondary-700">
          <div>
            <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">
              Pay Employee Pay Sheet
            </h3>
            <p class="mt-0.5 text-sm text-secondary-500 dark:text-secondary-400">
              {{ paySheetPayTarget ? `${paySheetPayTarget.period_start} to ${paySheetPayTarget.period_end}` : '—' }}
            </p>
          </div>
          <button type="button" class="text-secondary-400 hover:text-secondary-700 dark:hover:text-secondary-200" @click="closePaySheetPaymentModal">
            <X class="h-5 w-5" />
          </button>
        </div>

        <div class="space-y-4 p-5">
          <p v-if="fieldError(paySheetPayFormErrors, 'form')" class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700 dark:border-red-900 dark:bg-red-900/20 dark:text-red-300">
            {{ fieldError(paySheetPayFormErrors, 'form') }}
          </p>

          <InfoTile label="Payment total" :value="money(paySheetPaymentAmount)" />

          <div>
            <label class="mb-1 block text-xs font-semibold text-secondary-500 dark:text-secondary-400">Account</label>
            <select
              v-model="paySheetPayForm.company_account_id"
              required
              class="w-full rounded-xl border border-secondary-300 bg-white px-3 py-2 text-sm text-secondary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-secondary-700 dark:bg-secondary-900 dark:text-white"
            >
              <option value="">
                Select account
              </option>
              <option v-for="account in companyAccounts" :key="account.id" :value="String(account.id)">
                {{ account.name }}
              </option>
            </select>
            <p v-if="fieldError(paySheetPayFormErrors, 'company_account_id')" class="mt-1 text-xs font-medium text-red-600 dark:text-red-300">
              {{ fieldError(paySheetPayFormErrors, 'company_account_id') }}
            </p>
          </div>

          <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
              <label class="mb-1 block text-xs font-semibold text-secondary-500 dark:text-secondary-400">Paid date</label>
              <input
                v-model="paySheetPayForm.paid_at"
                type="date"
                class="w-full rounded-xl border border-secondary-300 bg-white px-3 py-2 text-sm text-secondary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-secondary-700 dark:bg-secondary-900 dark:text-white"
              />
              <p v-if="fieldError(paySheetPayFormErrors, 'paid_at')" class="mt-1 text-xs font-medium text-red-600 dark:text-red-300">
                {{ fieldError(paySheetPayFormErrors, 'paid_at') }}
              </p>
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold text-secondary-500 dark:text-secondary-400">Reference</label>
              <input
                v-model="paySheetPayForm.reference_number"
                type="text"
                maxlength="255"
                class="w-full rounded-xl border border-secondary-300 bg-white px-3 py-2 text-sm text-secondary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-secondary-700 dark:bg-secondary-900 dark:text-white"
              />
              <p v-if="fieldError(paySheetPayFormErrors, 'reference_number')" class="mt-1 text-xs font-medium text-red-600 dark:text-red-300">
                {{ fieldError(paySheetPayFormErrors, 'reference_number') }}
              </p>
            </div>
          </div>
        </div>

        <div class="flex flex-col gap-2 border-t border-secondary-200 p-5 sm:flex-row sm:items-center sm:justify-end dark:border-secondary-700">
          <button type="button" class="rounded-xl border border-secondary-300 px-4 py-2 text-sm font-semibold text-secondary-700 dark:border-secondary-700 dark:text-secondary-200" @click="closePaySheetPaymentModal">
            Cancel
          </button>
          <button
            type="submit"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-60"
            :disabled="paySheetPaying || !paySheetPayForm.company_account_id"
          >
            <CreditCard class="h-4 w-4" />
            {{ paySheetPaying ? 'Paying...' : 'Pay now' }}
          </button>
        </div>
      </form>
    </div>

    <div v-if="paySheetDetailOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50" @click="closePaySheetDetail" />
      <div class="relative z-10 flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-secondary-900">
        <div class="flex flex-col gap-3 border-b border-secondary-200 p-5 md:flex-row md:items-start md:justify-between dark:border-secondary-700">
          <div>
            <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">
              Employee Pay Sheet
            </h3>
            <p class="mt-0.5 text-sm text-secondary-500 dark:text-secondary-400">
              {{ selectedPaySheetDetail?.period_start || '—' }} to {{ selectedPaySheetDetail?.period_end || '—' }}
            </p>
          </div>
          <div class="flex flex-wrap gap-2">
            <a
              v-if="selectedPaySheetDetail"
              :href="selectedPaySheetPdfUrl"
              download
              class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-3 py-2 text-sm font-semibold text-white hover:bg-primary-700"
            >
              <Download class="h-4 w-4" />
              PDF
            </a>
            <button type="button" class="rounded-xl border border-secondary-300 px-3 py-2 text-sm font-semibold text-secondary-700 dark:border-secondary-700 dark:text-secondary-200" @click="closePaySheetDetail">
              Close
            </button>
          </div>
        </div>

        <div class="overflow-y-auto p-5">
          <div v-if="paySheetDetailLoading" class="rounded-xl border border-secondary-200 px-5 py-8 text-center text-sm text-secondary-500 dark:border-secondary-700 dark:text-secondary-400">
            Loading paysheet...
          </div>

          <template v-else-if="selectedPaySheetDetail">
            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
              <div class="rounded-2xl border border-secondary-200 p-4 dark:border-secondary-700">
                <h4 class="text-sm font-semibold text-secondary-900 dark:text-white">
                  Employee Details
                </h4>
                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                  <InfoRow label="Employee" :value="selectedPaySheetDetail.employee_name" />
                  <InfoRow label="Employee code" :value="selectedPaySheetDetail.employee_code || '—'" />
                  <InfoRow label="Job title" :value="selectedPaySheetDetail.job_title || '—'" />
                  <InfoRow label="Department" :value="selectedPaySheetDetail.department || '—'" />
                </div>
              </div>

              <div class="rounded-2xl border border-secondary-200 p-4 dark:border-secondary-700">
                <h4 class="text-sm font-semibold text-secondary-900 dark:text-white">
                  Pay Sheet Payment Period
                </h4>
                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                  <InfoRow label="Period" :value="`${selectedPaySheetDetail.period_start} to ${selectedPaySheetDetail.period_end}`" />
                  <InfoRow label="Daily payment" :value="money(selectedPaySheetDetail.daily_rate)" />
                  <InfoRow label="Month days" :value="String(selectedPaySheetDetail.month_day_count)" />
                  <InfoRow label="Payable days" :value="String(selectedPaySheetDetail.payable_days)" />
                </div>
              </div>
            </div>

            <div class="mt-4 rounded-2xl border border-secondary-200 p-4 dark:border-secondary-700">
              <h4 class="text-sm font-semibold text-secondary-900 dark:text-white">
                Detail
              </h4>
              <div class="mt-3 overflow-x-auto">
                <table class="w-full min-w-[48rem] text-sm">
                  <thead>
                    <tr class="border-b border-secondary-200 text-xs uppercase text-secondary-500 dark:border-secondary-700 dark:text-secondary-400">
                      <th class="py-2 text-left">
                        Description
                      </th>
                      <th class="py-2 text-left">
                        Details
                      </th>
                      <th class="py-2 text-right">
                        Amount
                      </th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-secondary-100 dark:divide-secondary-800">
                    <tr class="bg-green-50 text-xs font-semibold uppercase text-green-700 dark:bg-green-900/20 dark:text-green-300">
                      <td colspan="3" class="px-2 py-2">
                        Earnings
                      </td>
                    </tr>
                    <tr v-for="line in selectedPaySheetDetail.earning_lines || []" :key="lineKey(line, 'earning')">
                      <td class="py-2 text-secondary-900 dark:text-white">
                        <p class="font-semibold">
                          {{ line.label }}
                        </p>
                        <p v-if="line.description" class="text-xs text-secondary-500 dark:text-secondary-400">
                          {{ line.description }}
                        </p>
                      </td>
                      <td class="py-2 text-secondary-600 dark:text-secondary-300">
                        {{ lineDetails(line) || '—' }}
                      </td>
                      <td class="py-2 text-right font-semibold text-green-600 dark:text-green-300">
                        {{ money(line.amount) }}
                      </td>
                    </tr>
                    <tr class="border-t border-secondary-200 bg-secondary-50 dark:border-secondary-700 dark:bg-secondary-800/40">
                      <td colspan="2" class="py-2 text-right font-semibold text-secondary-900 dark:text-white">
                        Total earnings
                      </td>
                      <td class="py-2 text-right font-bold text-green-600 dark:text-green-300">
                        {{ money(selectedPaySheetDetail.total_earnings) }}
                      </td>
                    </tr>

                    <tr class="bg-red-50 text-xs font-semibold uppercase text-red-700 dark:bg-red-900/20 dark:text-red-300">
                      <td colspan="3" class="px-2 py-2">
                        Deductions
                      </td>
                    </tr>
                    <tr v-for="line in selectedPaySheetDetail.deduction_lines || []" :key="lineKey(line, 'deduction')">
                      <td class="py-2 text-secondary-900 dark:text-white">
                        <p class="font-semibold">
                          {{ line.label }}
                        </p>
                        <p v-if="line.description" class="text-xs text-secondary-500 dark:text-secondary-400">
                          {{ line.description }}
                        </p>
                      </td>
                      <td class="py-2 text-secondary-600 dark:text-secondary-300">
                        {{ lineDetails(line) || '—' }}
                      </td>
                      <td class="py-2 text-right font-semibold text-red-600 dark:text-red-300">
                        {{ money(line.amount) }}
                      </td>
                    </tr>
                    <tr v-if="(selectedPaySheetDetail.deduction_lines || []).length === 0">
                      <td colspan="3" class="py-6 text-center text-secondary-500 dark:text-secondary-400">
                        No deductions recorded.
                      </td>
                    </tr>
                    <tr class="border-t border-secondary-200 bg-secondary-50 dark:border-secondary-700 dark:bg-secondary-800/40">
                      <td colspan="2" class="py-2 text-right font-semibold text-secondary-900 dark:text-white">
                        Total deductions
                      </td>
                      <td class="py-2 text-right font-bold text-red-600 dark:text-red-300">
                        {{ money(selectedPaySheetDetail.total_deductions) }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div class="mt-4 rounded-2xl border-2 border-primary-200 bg-primary-50 p-5 text-right dark:border-primary-900 dark:bg-primary-900/20">
              <p class="text-xs font-semibold uppercase text-primary-600 dark:text-primary-300">
                Net pay
              </p>
              <p class="mt-1 text-3xl font-bold text-secondary-900 dark:text-white">
                {{ money(selectedPaySheetDetail.net_pay) }}
              </p>
            </div>
          </template>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, defineComponent, h, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Banknote, CalendarCheck2, CreditCard, Download, Eye, FileText, HandCoins, Pencil, Plus, RefreshCw, Trash2, Upload, X } from 'lucide-vue-next';
import AppHeaderAction from '../components/AppHeaderAction.vue';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppPagination from '../components/AppPagination.vue';
import { apiRequest } from '../composables/useApiClient';
import { useAppContext } from '../composables/useAppContext';

const InfoTile = defineComponent({
    props: {
        label: { type: String, default: '' },
        value: { type: String, default: '' },
        valueClass: { type: String, default: '' },
    },
    setup(props) {
        return () => h('div', { class: 'rounded-xl border border-secondary-200 bg-white px-3 py-2 dark:border-secondary-700 dark:bg-secondary-900' }, [
            h('p', { class: 'text-xs text-secondary-500 dark:text-secondary-400' }, props.label),
            h('p', { class: ['mt-1 text-lg font-bold', props.valueClass || 'text-secondary-900 dark:text-white'] }, props.value),
        ]);
    },
});

const InfoRow = defineComponent({
    props: {
        label: { type: String, default: '' },
        value: { type: String, default: '' },
    },
    setup(props) {
        return () => h('div', { class: 'rounded-xl border border-secondary-200 px-3 py-2 dark:border-secondary-700' }, [
            h('p', { class: 'text-xs text-secondary-500 dark:text-secondary-400' }, props.label),
            h('p', { class: 'mt-1 break-words text-sm font-medium text-secondary-900 dark:text-white' }, props.value),
        ]);
    },
});

const route = useRoute();
const router = useRouter();
const context = useAppContext();

const employee = ref(null);
const loading = ref(true);
const errorMessage = ref('');
const meta = ref({ document_categories: [], attendance_statuses: [] });

const documents = ref([]);
const documentsLoading = ref(false);
const documentModalOpen = ref(false);
const documentUploading = ref(false);
const documentFile = ref(null);
const documentFileInput = ref(null);
const documentForm = ref({ name: '', category: 'other', notes: '' });

const attendanceMonth = ref(currentMonth());
const attendanceRecords = ref([]);
const attendanceStats = ref({});
const attendancePayableDays = ref(0);
const attendanceLeaveBalance = ref(null);
const attendanceActionError = ref('');
const attendanceLoading = ref(false);
const savingAttendanceDate = ref('');
const weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

const paySheets = ref([]);
const paySheetsLoading = ref(false);
const paySheetGenerating = ref(false);
const paySheetMonth = ref(currentMonth());
const paySheetActionMessage = ref('');
const paySheetGenerateModalOpen = ref(false);
const paySheetDetailOpen = ref(false);
const paySheetDetailLoading = ref(false);
const selectedPaySheetDetail = ref(null);
const paySheetPaymentModalOpen = ref(false);
const paySheetPaying = ref(false);
const paySheetPayTarget = ref(null);
const paySheetPayFormErrors = ref({});
const paySheetPayForm = ref(defaultPaySheetPayForm());
const paySheetMeta = ref({ accounts: [] });
const paySheetMetaLoaded = ref(false);
const paySheetMetaLoading = ref(false);
const paySheetAdjustments = ref([]);
const paySheetAdjustmentsLoading = ref(false);
const paySheetAdjustmentSaving = ref(false);
const paySheetAdjustmentFormErrors = ref({});
const paySheetAdjustmentCategories = [
    { value: 'manual_earning', label: 'Manual earning', type: 'earning' },
    { value: 'manual_deduction', label: 'Manual deduction', type: 'deduction' },
];
const paySheetAdjustmentForm = ref(defaultPaySheetAdjustmentForm());
const paySheetsPerPage = ref(20);
const paySheetSummary = ref({ runs_count: 0, total_gross: 0, total_deductions: 0, total_net: 0 });
const paySheetPagination = ref({ current_page: 1, last_page: 1, per_page: 20, total: 0 });
const salaryAdvanceMonth = ref(currentMonth());
const salaryAdvances = ref([]);
const salaryAdvancesLoading = ref(false);
const salaryAdvanceSaving = ref(false);
const salaryAdvanceActionMessage = ref('');
const salaryAdvanceModalOpen = ref(false);
const salaryAdvanceEditing = ref(null);
const salaryAdvanceFormErrors = ref({});
const salaryAdvanceForm = ref(defaultSalaryAdvanceForm());

const canManagePaySheets = computed(() => Boolean(context.permissions?.employeePaySheetsManage));
const canManageEmployees = computed(() => Boolean(context.permissions?.employeesManage));
const documentCategories = computed(() => meta.value.document_categories || []);
const attendanceStatuses = computed(() => meta.value.attendance_statuses || []);
const companyAccounts = computed(() => paySheetMeta.value.accounts || []);

const tabs = computed(() => [
    { value: 'overview', label: 'Overview', icon: FileText },
    { value: 'attendance', label: 'Attendance', icon: CalendarCheck2 },
    ...(canManagePaySheets.value ? [
        { value: 'salary_advances', label: 'Salary Advances', icon: HandCoins },
        { value: 'pay_sheets', label: 'Pay Sheets', icon: Banknote },
    ] : []),
]);

const activeTab = ref(normalizeTab(route.query.tab));

const attendanceMap = computed(() => Object.fromEntries(attendanceRecords.value.map((record) => [record.attendance_date, record])));

const selectedPaySheet = computed(() => paySheets.value.find((item) => item.period_start?.slice(0, 7) === paySheetMonth.value) || null);
const selectedPaySheetPdfUrl = computed(() => selectedPaySheetDetail.value ? `/api/employees/${route.params.id}/pay-sheets/${selectedPaySheetDetail.value.id}/pdf` : '#');
const selectedPaySheetLocked = computed(() => selectedPaySheet.value?.status === 'paid');
const paySheetPaymentAmount = computed(() => Number(paySheetPayTarget.value?.run_total_net ?? paySheetPayTarget.value?.net_pay ?? 0));
const paySheetAdjustmentSummary = computed(() => ({
    earnings: paySheetAdjustments.value
        .filter((adjustment) => adjustment.type === 'earning')
        .reduce((total, adjustment) => total + Number(adjustment.amount || 0), 0),
    deductions: paySheetAdjustments.value
        .filter((adjustment) => adjustment.type === 'deduction')
        .reduce((total, adjustment) => total + Number(adjustment.amount || 0), 0),
}));
const salaryAdvanceTotal = computed(() => salaryAdvances.value.reduce((total, advance) => total + Number(advance.amount || 0), 0));
const salaryAdvanceModalTitle = computed(() => (salaryAdvanceEditing.value ? 'Edit Salary Advance' : 'Add Salary Advance'));
const salaryAdvanceSubmitLabel = computed(() => {
    if (salaryAdvanceSaving.value) return 'Saving...';
    return salaryAdvanceEditing.value ? 'Update' : 'Save';
});
const selectedPaySheetStatusLabel = computed(() => {
    if (!selectedPaySheet.value) return 'Not generated';
    return selectedPaySheet.value.status === 'paid' ? 'Paid' : 'Draft';
});
const paySheetButtonLabel = computed(() => {
    if (paySheetGenerating.value) return 'Saving...';
    if (selectedPaySheetLocked.value) return 'Paid month locked';
    return selectedPaySheet.value ? 'Regenerate Pay Sheet' : 'Generate Pay Sheet';
});

const attendanceMonthLabel = computed(() => {
    const [year, month] = attendanceMonth.value.split('-').map(Number);
    if (!year || !month) return '';

    return new Intl.DateTimeFormat(undefined, { month: 'long', year: 'numeric' }).format(new Date(year, month - 1, 1));
});

const paySheetMonthLabel = computed(() => monthLabel(paySheetMonth.value));
const salaryAdvanceMonthLabel = computed(() => monthLabel(salaryAdvanceMonth.value));

const calendarCells = computed(() => {
    const [year, month] = attendanceMonth.value.split('-').map(Number);
    const first = new Date(year, month - 1, 1);
    const daysInMonth = new Date(year, month, 0).getDate();
    const cells = [];

    for (let i = 0; i < first.getDay(); i++) cells.push({ key: `blank-${i}`, date: null });
    for (let day = 1; day <= daysInMonth; day++) {
        const date = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        cells.push({ key: date, date, day });
    }

    return cells;
});

async function loadEmployee() {
    loading.value = true;
    const response = await apiRequest(`/api/employees/${route.params.id}`);
    employee.value = response.data;
    loading.value = false;
}

async function loadMeta() {
    meta.value = await apiRequest('/api/employees/meta');
}

async function loadDocuments() {
    documentsLoading.value = true;
    const response = await apiRequest(`/api/employees/${route.params.id}/documents`);
    documents.value = response.data || [];
    documentsLoading.value = false;
}

async function loadAttendance() {
    attendanceLoading.value = true;
    errorMessage.value = '';
    attendanceActionError.value = '';
    try {
        const [year, month] = attendanceMonth.value.split('-');
        const response = await apiRequest(`/api/employees/${route.params.id}/attendance`, { params: { year, month } });
        attendanceRecords.value = response.data || [];
        attendanceStats.value = response.stats || {};
        attendancePayableDays.value = response.payable_days || 0;
        attendanceLeaveBalance.value = response.leave_balance || null;
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load attendance.';
    } finally {
        attendanceLoading.value = false;
    }
}

async function setAttendance(date, status) {
    savingAttendanceDate.value = date;
    errorMessage.value = '';
    attendanceActionError.value = '';
    try {
        await apiRequest(`/api/employees/${route.params.id}/attendance`, {
            method: 'post',
            data: { attendance_date: date, status },
        });
        await loadAttendance();
    } catch (error) {
        const errors = error?.response?.data?.errors || {};
        attendanceActionError.value = fieldError(errors, 'status')
            || fieldError(errors, 'attendance_date')
            || error?.response?.data?.message
            || 'Failed to save attendance.';
    } finally {
        savingAttendanceDate.value = '';
    }
}

async function clearAttendance(date) {
    const record = attendanceMap.value[date];
    if (!record?.id) return;

    savingAttendanceDate.value = date;
    errorMessage.value = '';
    attendanceActionError.value = '';
    try {
        await apiRequest(`/api/employees/${route.params.id}/attendance/${record.id}`, { method: 'delete' });
        await loadAttendance();
    } catch (error) {
        attendanceActionError.value = error?.response?.data?.message || 'Failed to clear attendance.';
    } finally {
        savingAttendanceDate.value = '';
    }
}

async function loadPaySheets(page = 1) {
    if (!canManagePaySheets.value) return;

    paySheetsLoading.value = true;
    errorMessage.value = '';
    try {
        const response = await apiRequest(`/api/employees/${route.params.id}/pay-sheets`, {
            params: { page, per_page: paySheetsPerPage.value },
        });
        paySheets.value = response.data || [];
        paySheetSummary.value = response.summary || paySheetSummary.value;
        paySheetPagination.value = response.meta || paySheetPagination.value;
        paySheetsPerPage.value = paySheetPagination.value.per_page || paySheetsPerPage.value;
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load employee pay sheets.';
    } finally {
        paySheetsLoading.value = false;
    }
}

async function loadPaySheetMeta() {
    if (!canManagePaySheets.value || paySheetMetaLoaded.value || paySheetMetaLoading.value) return;

    paySheetMetaLoading.value = true;
    errorMessage.value = '';
    try {
        paySheetMeta.value = await apiRequest('/api/employee-pay-sheets/meta');
        paySheetMetaLoaded.value = true;
        setDefaultSalaryAdvanceAccount();
        setDefaultPaySheetPaymentAccount();
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load employee pay sheet settings.';
    } finally {
        paySheetMetaLoading.value = false;
    }
}

async function loadPaySheetAdjustments() {
    if (!canManagePaySheets.value || !paySheetMonth.value) return;

    paySheetAdjustmentsLoading.value = true;
    errorMessage.value = '';
    try {
        const response = await apiRequest(`/api/employees/${route.params.id}/pay-sheet-adjustments`, {
            params: { month: paySheetMonth.value },
        });
        paySheetAdjustments.value = (response.data || []).filter((adjustment) => adjustment.category !== 'salary_advance');
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load employee pay sheet adjustments.';
    } finally {
        paySheetAdjustmentsLoading.value = false;
    }
}

async function loadSalaryAdvances() {
    if (!canManagePaySheets.value || !salaryAdvanceMonth.value) return;

    await loadPaySheetMeta();

    salaryAdvancesLoading.value = true;
    errorMessage.value = '';
    try {
        const response = await apiRequest(`/api/employees/${route.params.id}/pay-sheet-adjustments`, {
            params: { month: salaryAdvanceMonth.value, category: 'salary_advance' },
        });
        salaryAdvances.value = response.data || [];
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load salary advances.';
    } finally {
        salaryAdvancesLoading.value = false;
    }
}

async function generateSelectedPaySheet() {
    if (!paySheetMonth.value || selectedPaySheetLocked.value) return;

    paySheetGenerating.value = true;
    errorMessage.value = '';
    paySheetActionMessage.value = '';
    try {
        const response = await apiRequest(`/api/employees/${route.params.id}/pay-sheets/generate`, {
            method: 'post',
            data: { month: paySheetMonth.value },
        });
        paySheetActionMessage.value = response.message || 'Employee Pay Sheet saved successfully.';
        await loadPaySheets(1);
        paySheetGenerateModalOpen.value = false;
        if (response.data?.id) {
            await showPaySheet(response.data.id);
        }
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to generate employee pay sheet.';
    } finally {
        paySheetGenerating.value = false;
    }
}

async function openPaySheetPaymentModal(item) {
    await loadPaySheetMeta();
    paySheetPayTarget.value = item;
    paySheetPayFormErrors.value = {};
    paySheetPayForm.value = defaultPaySheetPayForm(
        item.company_account_id ? String(item.company_account_id) : defaultPaySheetPaymentAccountId(),
    );
    paySheetPaymentModalOpen.value = true;
}

function closePaySheetPaymentModal() {
    if (paySheetPaying.value) return;

    paySheetPaymentModalOpen.value = false;
    paySheetPayTarget.value = null;
    paySheetPayFormErrors.value = {};
}

async function paySelectedPaySheet() {
    if (!paySheetPayTarget.value?.employee_pay_sheet_run_id || !paySheetPayForm.value.company_account_id) return;

    paySheetPaying.value = true;
    errorMessage.value = '';
    paySheetActionMessage.value = '';
    paySheetPayFormErrors.value = {};

    try {
        const response = await apiRequest(`/api/employee-pay-sheets/${paySheetPayTarget.value.employee_pay_sheet_run_id}/mark-paid`, {
            method: 'post',
            data: {
                company_account_id: paySheetPayForm.value.company_account_id,
                paid_at: paySheetPayForm.value.paid_at,
                reference_number: paySheetPayForm.value.reference_number,
            },
        });

        paySheetActionMessage.value = response.message || 'Employee Pay Sheet paid and expense created.';
        paySheetPaymentModalOpen.value = false;
        paySheetPayTarget.value = null;
        await loadPaySheets(paySheetPagination.value.current_page || 1);
    } catch (error) {
        paySheetPayFormErrors.value = formErrors(error, 'Failed to pay employee pay sheet.');
    } finally {
        paySheetPaying.value = false;
    }
}

async function saveSalaryAdvance() {
    if (!salaryAdvanceForm.value.month || !salaryAdvanceForm.value.company_account_id) return;

    salaryAdvanceSaving.value = true;
    errorMessage.value = '';
    salaryAdvanceActionMessage.value = '';
    salaryAdvanceFormErrors.value = {};
    try {
        const editingId = salaryAdvanceEditing.value?.id;
        const response = await apiRequest(`/api/employees/${route.params.id}/pay-sheet-adjustments${editingId ? `/${editingId}` : ''}`, {
            method: editingId ? 'put' : 'post',
            data: salaryAdvancePayload(),
        });
        salaryAdvanceMonth.value = response.data?.period_start?.slice(0, 7) || salaryAdvanceForm.value.month;
        closeSalaryAdvanceModal();
        salaryAdvanceActionMessage.value = editingId
            ? 'Salary advance updated and linked expense refreshed.'
            : 'Salary advance saved and expense created.';
        await loadSalaryAdvances();
    } catch (error) {
        salaryAdvanceFormErrors.value = formErrors(error, 'Failed to save salary advance.');
    } finally {
        salaryAdvanceSaving.value = false;
    }
}

async function deleteSalaryAdvance(advance) {
    if (!window.confirm(`Delete ${advance.description}?`)) return;

    errorMessage.value = '';
    salaryAdvanceActionMessage.value = '';
    try {
        await apiRequest(`/api/employees/${route.params.id}/pay-sheet-adjustments/${advance.id}`, { method: 'delete' });
        salaryAdvanceActionMessage.value = 'Salary advance deleted and linked expense removed.';
        await loadSalaryAdvances();
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to delete salary advance.';
    }
}

async function addPaySheetAdjustment() {
    if (selectedPaySheetLocked.value) return;

    paySheetAdjustmentSaving.value = true;
    errorMessage.value = '';
    paySheetActionMessage.value = '';
    paySheetAdjustmentFormErrors.value = {};
    try {
        await apiRequest(`/api/employees/${route.params.id}/pay-sheet-adjustments`, {
            method: 'post',
            data: {
                month: paySheetMonth.value,
                ...paySheetAdjustmentForm.value,
            },
        });
        paySheetAdjustmentForm.value = defaultPaySheetAdjustmentForm(paySheetAdjustmentForm.value.category);
        paySheetActionMessage.value = selectedPaySheet.value
            ? 'Adjustment saved. Regenerate the Employee Pay Sheet to apply it.'
            : 'Adjustment saved.';
        await loadPaySheetAdjustments();
    } catch (error) {
        paySheetAdjustmentFormErrors.value = formErrors(error, 'Failed to save employee pay sheet adjustment.');
    } finally {
        paySheetAdjustmentSaving.value = false;
    }
}

async function deletePaySheetAdjustment(adjustment) {
    if (selectedPaySheetLocked.value) return;
    if (!window.confirm(`Delete ${adjustment.description}?`)) return;

    errorMessage.value = '';
    paySheetActionMessage.value = '';
    try {
        await apiRequest(`/api/employees/${route.params.id}/pay-sheet-adjustments/${adjustment.id}`, { method: 'delete' });
        paySheetActionMessage.value = selectedPaySheet.value
            ? 'Adjustment deleted. Regenerate the Employee Pay Sheet to apply it.'
            : 'Adjustment deleted.';
        await loadPaySheetAdjustments();
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to delete employee pay sheet adjustment.';
    }
}

async function showPaySheet(itemId) {
    paySheetDetailOpen.value = true;
    paySheetDetailLoading.value = true;
    selectedPaySheetDetail.value = null;
    errorMessage.value = '';
    try {
        const response = await apiRequest(`/api/employees/${route.params.id}/pay-sheets/${itemId}`);
        selectedPaySheetDetail.value = response.data;
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load employee pay sheet.';
        paySheetDetailOpen.value = false;
    } finally {
        paySheetDetailLoading.value = false;
    }
}

function closePaySheetDetail() {
    paySheetDetailOpen.value = false;
    selectedPaySheetDetail.value = null;
}

async function openPaySheetGenerateModal() {
    paySheetGenerateModalOpen.value = true;
    paySheetActionMessage.value = '';
    paySheetAdjustmentFormErrors.value = {};
    paySheetAdjustmentForm.value = defaultPaySheetAdjustmentForm(paySheetAdjustmentForm.value.category);
    await Promise.all([loadPaySheets(), loadPaySheetAdjustments()]);
}

function closePaySheetGenerateModal() {
    paySheetGenerateModalOpen.value = false;
    paySheetAdjustmentFormErrors.value = {};
}

async function openSalaryAdvanceModal(advance = null) {
    await loadPaySheetMeta();
    salaryAdvanceEditing.value = advance;
    salaryAdvanceFormErrors.value = {};
    salaryAdvanceActionMessage.value = '';
    salaryAdvanceForm.value = advance
        ? {
            id: advance.id,
            month: advance.period_start?.slice(0, 7) || salaryAdvanceMonth.value,
            company_account_id: advance.company_account_id ? String(advance.company_account_id) : '',
            description: advance.description || 'Salary advance',
            amount: String(advance.amount || ''),
            adjustment_date: advance.adjustment_date || defaultSalaryAdvanceDate(advance.period_start?.slice(0, 7) || salaryAdvanceMonth.value),
            notes: advance.notes || '',
        }
        : defaultSalaryAdvanceForm(salaryAdvanceForm.value.company_account_id || defaultSalaryAdvanceAccountId(), salaryAdvanceMonth.value);
    salaryAdvanceModalOpen.value = true;
}

function closeSalaryAdvanceModal() {
    salaryAdvanceModalOpen.value = false;
    salaryAdvanceEditing.value = null;
    salaryAdvanceFormErrors.value = {};
}

function handlePaySheetMonthChange() {
    paySheetActionMessage.value = '';
    paySheetAdjustmentFormErrors.value = {};
    paySheetAdjustmentForm.value.adjustment_date = defaultPaySheetAdjustmentDate();
    loadPaySheetAdjustments();
}

function handleSalaryAdvanceMonthChange() {
    salaryAdvanceActionMessage.value = '';
    loadSalaryAdvances();
}

function handleSalaryAdvanceFormMonthChange() {
    salaryAdvanceFormErrors.value = {};
    salaryAdvanceForm.value.adjustment_date = defaultSalaryAdvanceDate(salaryAdvanceForm.value.month);
}

function handleAdjustmentCategoryChange() {
    paySheetAdjustmentFormErrors.value = {};
    const category = paySheetAdjustmentCategories.find((item) => item.value === paySheetAdjustmentForm.value.category);
    paySheetAdjustmentForm.value.description = category?.label || '';
}

function defaultPaySheetAdjustmentForm(categoryValue = 'manual_earning') {
    const category = paySheetAdjustmentCategories?.find((item) => item.value === categoryValue)
        || { value: 'manual_earning', label: 'Manual earning' };

    return {
        category: category.value,
        description: category.label,
        amount: '',
        adjustment_date: defaultPaySheetAdjustmentDate(),
        notes: '',
    };
}

function defaultPaySheetAdjustmentDate() {
    const todayDate = today();
    return todayDate.startsWith(paySheetMonth.value) ? todayDate : `${paySheetMonth.value}-01`;
}

function salaryAdvancePayload() {
    return {
        month: salaryAdvanceForm.value.month,
        category: 'salary_advance',
        company_account_id: salaryAdvanceForm.value.company_account_id,
        description: salaryAdvanceForm.value.description || 'Salary advance',
        amount: salaryAdvanceForm.value.amount,
        adjustment_date: salaryAdvanceForm.value.adjustment_date,
        notes: salaryAdvanceForm.value.notes,
    };
}

function defaultPaySheetPayForm(accountId = '') {
    return {
        company_account_id: accountId ? String(accountId) : '',
        paid_at: today(),
        reference_number: '',
    };
}

function defaultSalaryAdvanceForm(accountId = '', monthValue = salaryAdvanceMonth.value) {
    return {
        month: monthValue,
        company_account_id: accountId ? String(accountId) : '',
        description: 'Salary advance',
        amount: '',
        adjustment_date: defaultSalaryAdvanceDate(monthValue),
        notes: '',
    };
}

function defaultSalaryAdvanceDate(monthValue = salaryAdvanceMonth.value) {
    const todayDate = today();
    return todayDate.startsWith(monthValue) ? todayDate : `${monthValue}-01`;
}

function defaultSalaryAdvanceAccountId() {
    return companyAccounts.value[0]?.id ? String(companyAccounts.value[0].id) : '';
}

function defaultPaySheetPaymentAccountId() {
    return companyAccounts.value[0]?.id ? String(companyAccounts.value[0].id) : '';
}

function setDefaultSalaryAdvanceAccount() {
    if (!salaryAdvanceForm.value.company_account_id && companyAccounts.value.length) {
        salaryAdvanceForm.value.company_account_id = defaultSalaryAdvanceAccountId();
    }
}

function setDefaultPaySheetPaymentAccount() {
    if (!paySheetPayForm.value.company_account_id && companyAccounts.value.length) {
        paySheetPayForm.value.company_account_id = defaultPaySheetPaymentAccountId();
    }
}

function setActiveTab(tab) {
    activeTab.value = tab;

    const query = { ...route.query };
    if (tab === 'overview') delete query.tab;
    else query.tab = tab;
    router.replace({ query });
}

function normalizeTab(tab) {
    const value = Array.isArray(tab) ? tab[0] : tab;

    return ['overview', 'attendance', 'salary_advances', 'pay_sheets'].includes(value) ? value : 'overview';
}

function onDocumentFileChange(event) {
    documentFile.value = event.target.files?.[0] || null;
    if (documentFile.value && !documentForm.value.name) {
        documentForm.value = {
            ...documentForm.value,
            name: documentFile.value.name.replace(/\.[^.]+$/, ''),
        };
    }
}

async function uploadDocument() {
    if (!documentFile.value) return;
    documentUploading.value = true;
    errorMessage.value = '';
    try {
        const data = new FormData();
        data.append('file', documentFile.value);
        data.append('name', documentForm.value.name);
        data.append('category', documentForm.value.category);
        data.append('notes', documentForm.value.notes || '');
        await apiRequest(`/api/employees/${route.params.id}/documents`, { method: 'post', data });
        documentModalOpen.value = false;
        documentFile.value = null;
        if (documentFileInput.value) documentFileInput.value.value = '';
        documentForm.value = { name: '', category: 'other', notes: '' };
        await loadDocuments();
        if (employee.value) employee.value.documents_count = documents.value.length;
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to upload document.';
    } finally {
        documentUploading.value = false;
    }
}

async function viewDocument(document) {
    const response = await apiRequest(`/api/employees/${route.params.id}/documents/${document.id}/url`);
    window.open(response.url, '_blank', 'noopener');
}

async function deleteDocument(document) {
    if (!window.confirm(`Delete ${document.name}?`)) return;
    await apiRequest(`/api/employees/${route.params.id}/documents/${document.id}`, { method: 'delete' });
    await loadDocuments();
    if (employee.value) employee.value.documents_count = documents.value.length;
}

function handlePaySheetLimit(limit) {
    paySheetsPerPage.value = Number(limit);
    loadPaySheets(1);
}

function employeeStatusClass(status) {
    if (status === 'active') return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300';
    if (status === 'terminated') return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300';
    return 'bg-secondary-100 text-secondary-700 dark:bg-secondary-800 dark:text-secondary-300';
}

function attendanceSelectClass(status) {
    if (status === 'present') return 'border-green-200 bg-green-50 text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-200';
    if (status === 'absent') return 'border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-200';
    if (status === 'half_day') return 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200';
    if (status === 'leave') return 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-200';
    if (status === 'full_paid_leave') return 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-200';
    if (status === 'half_paid_leave') return 'border-purple-200 bg-purple-50 text-purple-800 dark:border-purple-800 dark:bg-purple-900/20 dark:text-purple-200';
    if (status === 'no_pay_leave') return 'border-secondary-300 bg-secondary-50 text-secondary-700 dark:border-secondary-700 dark:bg-secondary-800 dark:text-secondary-200';
    return 'border-secondary-200 bg-white text-secondary-500 dark:border-secondary-700 dark:bg-secondary-950 dark:text-secondary-400';
}

function isAttendanceSelectable(date) {
    if (!date) return false;
    if (attendanceMap.value[date]?.id) return true;
    if (date > today()) return false;
    if (employee.value?.joined_date && date < employee.value.joined_date) return false;
    if (employee.value?.left_date && date > employee.value.left_date) return false;

    return true;
}

function disabledAttendanceLabel(date) {
    if (!date) return '';
    if (employee.value?.joined_date && date < employee.value.joined_date) return 'Before joining';
    if (employee.value?.left_date && date > employee.value.left_date) return 'After leaving';

    return 'Upcoming';
}

function paySheetStatusClass(status) {
    if (status === 'paid') return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300';
    return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300';
}

function leaveDays(item) {
    return Number(item.full_paid_leave_days || 0)
        + Number(item.half_paid_leave_days || 0)
        + (Number(item.half_day_days || 0) * 0.5);
}

function noPayDays(item) {
    return Number(item.absent_days || 0) + Number(item.no_pay_leave_days || 0);
}

function lineDetails(line) {
    const parts = [];
    if (line.details) parts.push(line.details);
    else if (line.dates_label) parts.push(line.dates_label);
    if (line.notes) parts.push(line.notes);

    return parts.filter(Boolean).join(' - ');
}

function lineKey(line, prefix) {
    return `${prefix}-${line.source || 'line'}-${line.category || 'item'}-${line.date || line.description || ''}-${line.amount}`;
}

function fieldError(errors, field) {
    const value = errors?.[field];
    if (Array.isArray(value)) return value[0] || '';
    return value || '';
}

function formErrors(error, fallback) {
    const errors = error?.response?.data?.errors;
    if (errors && Object.keys(errors).length > 0) return errors;

    const message = error?.response?.data?.message || fallback;
    if (String(message).toLowerCase().includes('adjustment date')) {
        return { adjustment_date: [message] };
    }

    return { form: [message] };
}

function money(value) {
    return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'LKR', maximumFractionDigits: 2 }).format(Number(value || 0));
}

function formatDays(value) {
    return new Intl.NumberFormat(undefined, { maximumFractionDigits: 2 }).format(Number(value || 0));
}

function monthLabel(value) {
    const [year, month] = String(value || '').split('-').map(Number);
    if (!year || !month) return '—';

    return new Intl.DateTimeFormat(undefined, { month: 'long', year: 'numeric' }).format(new Date(year, month - 1, 1));
}

function formatFileSize(bytes) {
    const size = Number(bytes || 0);
    if (size < 1024) return `${size} B`;
    if (size < 1024 * 1024) return `${(size / 1024).toFixed(1)} KB`;
    return `${(size / (1024 * 1024)).toFixed(1)} MB`;
}

function today() {
    return new Date().toISOString().slice(0, 10);
}

function currentMonth() {
    return today().slice(0, 7);
}

watch(
    () => route.query.tab,
    (tab) => {
        const next = normalizeTab(tab);
        activeTab.value = ['salary_advances', 'pay_sheets'].includes(next) && !canManagePaySheets.value ? 'overview' : next;
    },
);

watch(activeTab, async (tab) => {
    if (tab === 'attendance') await loadAttendance();
    if (tab === 'salary_advances') await loadSalaryAdvances();
    if (tab === 'pay_sheets') await loadPaySheets();
});

watch(canManagePaySheets, (allowed) => {
    if (!allowed && ['salary_advances', 'pay_sheets'].includes(activeTab.value)) {
        setActiveTab('overview');
    }

    if (allowed) {
        loadPaySheetMeta();
    }
});

onMounted(async () => {
    try {
        await loadMeta();
        await Promise.all([loadEmployee(), loadDocuments()]);
        if (['salary_advances', 'pay_sheets'].includes(activeTab.value) && !canManagePaySheets.value) activeTab.value = 'overview';
        if (canManagePaySheets.value) await loadPaySheetMeta();
        if (activeTab.value === 'attendance') await loadAttendance();
        if (activeTab.value === 'salary_advances') await loadSalaryAdvances();
        if (activeTab.value === 'pay_sheets') await loadPaySheets();
    } catch (error) {
        loading.value = false;
        errorMessage.value = error?.response?.data?.message || 'Failed to load employee.';
    }
});
</script>
