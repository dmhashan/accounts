<template>
  <section class="app-page-frame">
    <AppPageHeader>
      <template #cta-slot>
        <div class="flex flex-row gap-2 w-full lg:w-auto">
          <AppHeaderAction
            v-if="activeTab === 'members'"
            :icon="Download"
            :label="exporting ? 'Exporting...' : 'Export to Google Contact'"
            variant="secondary"
            :disabled="exporting"
            @click="exportGoogleContacts"
          />
          <AppHeaderAction
            v-if="permissions.create && activeTab === 'temp'"
            variant="secondary"
            :icon="Clock"
            label="Add Temp Member"
            @click="tempModalOpen = true"
          />
          <AppHeaderAction
            v-if="permissions.create && activeTab === 'members'"
            to="/members/new"
            :icon="UserRoundPlus"
            label="Add Member"
          />
        </div>
      </template>

      <template #extra-slot>
        <div class="flex flex-col gap-3">
          <AppSearchField
            v-model="search"
            placeholder="Search members by id, name, email, or phone"
            :disabled="loading"
            @search="loadMembers(1)"
          >
            <template #filter-trigger>
              <button
                type="button"
                :disabled="loading"
                class="inline-flex h-12 shrink-0 items-center justify-center gap-2 rounded-full border border-secondary-300 px-4 text-sm font-semibold text-secondary-700 transition hover:bg-secondary-50 disabled:opacity-60 dark:border-secondary-700 dark:text-secondary-300 dark:hover:bg-secondary-800"
                title="Open filters"
                @click="openFilters"
              >
                <Funnel class="h-4 w-4" :stroke-width="2" />
                <span class="hidden sm:inline">Filters</span>
                <span
                  v-if="activeFilterCount > 0"
                  class="inline-flex min-w-5 items-center justify-center rounded-full bg-secondary-900 px-1.5 py-0.5 text-xs font-bold text-white dark:bg-white dark:text-secondary-900"
                >
                  {{ activeFilterCount }}
                </span>
              </button>
            </template>
          </AppSearchField>

          <div v-if="appliedFilterLabels.length > 0" class="flex flex-wrap items-center gap-2">
            <AppBadge v-for="label in appliedFilterLabels" :key="label" color="secondary">
              {{ label }}
            </AppBadge>
            <button
              v-if="hasListFilters"
              type="button"
              :disabled="loading"
              class="inline-flex h-7 items-center gap-1 rounded-full border border-secondary-300 px-2.5 text-xs font-semibold text-secondary-600 transition hover:bg-secondary-50 disabled:opacity-50 dark:border-secondary-700 dark:text-secondary-300 dark:hover:bg-secondary-800"
              @click="resetFilters"
            >
              <RotateCcw class="h-3.5 w-3.5" :stroke-width="2" />
              Reset
            </button>
          </div>
        </div>
      </template>
    </AppPageHeader>

    <div
      v-if="filtersOpen"
      class="fixed inset-0 z-50 flex items-end justify-center bg-secondary-950/50 p-3 sm:items-center"
      @click.self="closeFilters"
    >
      <article class="app-surface flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl">
        <header class="flex items-center justify-between gap-3 border-b border-secondary-200 px-5 py-4 dark:border-secondary-700">
          <div class="flex min-w-0 items-center gap-3">
            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">
              <Funnel class="h-5 w-5" :stroke-width="2" />
            </span>
            <div class="min-w-0">
              <h3 class="text-base font-semibold text-secondary-900 dark:text-white">
                Member filters
              </h3>
              <p class="mt-0.5 text-xs text-secondary-500 dark:text-secondary-400">
                {{ draftFilterCount }} selected
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

        <div class="flex-1 overflow-y-auto p-5">
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <label class="space-y-1.5">
              <span class="text-xs font-semibold uppercase text-secondary-500 dark:text-secondary-400">Status</span>
              <AppFormSelect v-model="draftFilters.active" :disabled="loading">
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </AppFormSelect>
            </label>

            <label class="space-y-1.5">
              <span class="text-xs font-semibold uppercase text-secondary-500 dark:text-secondary-400">Verification</span>
              <AppFormSelect v-model="draftFilters.verified" :disabled="loading">
                <option value="">All verification</option>
                <option value="verified">Verified</option>
                <option value="unverified">Not verified</option>
              </AppFormSelect>
            </label>

            <label class="space-y-1.5">
              <span class="text-xs font-semibold uppercase text-secondary-500 dark:text-secondary-400">Gender</span>
              <AppFormSelect v-model="draftFilters.gender" :disabled="loading">
                <option value="">All genders</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="other">Other</option>
              </AppFormSelect>
            </label>

            <label class="space-y-1.5">
              <span class="text-xs font-semibold uppercase text-secondary-500 dark:text-secondary-400">Plan</span>
              <AppFormSelect v-model.number="draftFilters.plan_id" :disabled="loading || Boolean(planId)">
                <option value="">All plans</option>
                <option v-for="plan in paymentPlans" :key="plan.id" :value="plan.id">
                  {{ plan.name }}
                </option>
              </AppFormSelect>
            </label>

            <label class="space-y-1.5">
              <span class="text-xs font-semibold uppercase text-secondary-500 dark:text-secondary-400">Expiry</span>
              <AppFormSelect v-model="draftFilters.expiry_preset" :disabled="loading">
                <option value="">All expiry</option>
                <option value="expired_30">Expired 30+ days</option>
                <option value="expired_60">Expired 60+ days</option>
                <option value="expired_90">Expired 90+ days</option>
              </AppFormSelect>
            </label>

            <label class="space-y-1.5">
              <span class="text-xs font-semibold uppercase text-secondary-500 dark:text-secondary-400">Last visit</span>
              <AppFormSelect v-model="draftFilters.attendance_preset" :disabled="loading">
                <option value="">All visits</option>
                <option value="older_30">Last visit 30+ days</option>
                <option value="older_60">Last visit 60+ days</option>
                <option value="older_90">Last visit 90+ days</option>
              </AppFormSelect>
            </label>

            <label class="space-y-1.5 sm:col-span-2">
              <span class="text-xs font-semibold uppercase text-secondary-500 dark:text-secondary-400">Outstanding</span>
              <AppFormSelect v-model="draftFilters.outstanding" :disabled="loading">
                <option value="">All balances</option>
                <option value="with">Outstanding</option>
                <option value="without">No outstanding</option>
              </AppFormSelect>
            </label>
          </div>
        </div>

        <footer class="flex flex-col-reverse gap-2 border-t border-secondary-200 px-5 py-4 dark:border-secondary-700 sm:flex-row sm:items-center sm:justify-between">
          <button
            type="button"
            class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-secondary-300 px-3 text-sm font-semibold text-secondary-700 transition hover:bg-secondary-50 disabled:opacity-50 dark:border-secondary-700 dark:text-secondary-200 dark:hover:bg-secondary-800"
            :disabled="loading"
            @click="resetDraftFilters"
          >
            <RotateCcw class="h-4 w-4" :stroke-width="2" />
            Reset
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
              <Funnel class="h-4 w-4" :stroke-width="2" />
              Apply filters
            </button>
          </div>
        </footer>
      </article>
    </div>

    <div v-if="errorMessage" class="app-alert app-alert-error">
      {{ errorMessage }}
    </div>

    <div v-if="planId" class="mx-4 mt-3 flex items-center gap-2 px-3 py-2 rounded-lg bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-700 text-sm text-primary-700 dark:text-primary-300">
      <span class="flex-1">
        Filtered by plan<span v-if="planName">: <strong>{{ planName }}</strong></span>
      </span>
      <button
        type="button"
        class="flex items-center gap-1 text-xs hover:text-primary-900 dark:hover:text-primary-100"
        @click="router.push('/members')"
      >
        <X class="w-3.5 h-3.5" />
        Clear filter
      </button>
    </div>

    <div class="min-h-0 flex flex-1 flex-col">
      <div class="app-page-scroll">
        <div class="app-surface rounded-2xl overflow-hidden">
          <!-- Skeleton loading -->
          <template v-if="loading">
            <div class="divide-y divide-secondary-200 dark:divide-secondary-700">
              <div v-for="i in 6" :key="i" class="p-4 flex items-center gap-3">
                <div class="app-skeleton h-10 w-10 rounded-full shrink-0" />
                <div class="flex-1 space-y-2">
                  <div class="app-skeleton h-3.5 w-40 rounded" />
                  <div class="app-skeleton h-3 w-56 rounded" />
                </div>
                <div class="app-skeleton h-5 w-14 rounded-full shrink-0" />
              </div>
            </div>
          </template>

          <template v-else>
            <!-- Mobile cards -->
            <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
              <article
                v-for="member in members"
                :key="member.id"
                class="p-4 flex items-center gap-3 cursor-pointer transition-colors hover:bg-secondary-50 dark:hover:bg-secondary-800/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary-500"
                role="link"
                tabindex="0"
                @click="openMember(member.id)"
                @keydown.enter.prevent="openMember(member.id)"
                @keydown.space.prevent="openMember(member.id)"
              >
                <!-- Avatar -->
                <MemberAvatar
                  :src="member.profile_photo_url"
                  :initials="memberInitials(member)"
                  size="sm"
                />

                <!-- Info -->
                <div class="min-w-0 flex-1">
                  <div class="flex flex-wrap items-center gap-1.5">
                    <p class="text-sm font-semibold text-secondary-900 dark:text-white">
                      {{ memberFullName(member) }}
                    </p>
                    <AppBadge v-if="member.biometric_member_id" color="secondary">
                      #{{ member.biometric_member_id }}
                    </AppBadge>
                    <AppBadge :color="member.gender === 'male' ? 'indigo' : member.gender === 'female' ? 'purple' : 'secondary'">
                      {{ capitalize(member.gender) || 'N/A' }}
                    </AppBadge>
                    <AppBadge :color="member.is_active ? 'green' : 'red'">
                      {{ member.is_active ? 'Active' : 'Inactive' }}
                    </AppBadge>
                    <AppBadge v-if="activeTab === 'members'" :color="member.is_verified ? 'blue' : 'amber'">
                      {{ member.is_verified ? 'Verified' : 'Unverified' }}
                    </AppBadge>
                    <AppBadge v-if="member.registration_source === 'campaign'" color="purple">
                      Campaign
                    </AppBadge>
                  </div>
                  <p class="mt-0.5 text-xs text-secondary-500 dark:text-secondary-400 truncate">
                    {{ member.phone_number || member.email || 'No contact' }}
                  </p>
                  <div v-if="hasMemberDetails(member)" class="mt-2 flex flex-wrap gap-1.5">
                    <AppBadge v-if="hasDetailValue(member.days_until_payment_expiry)" :color="expiryBadgeColor(member.days_until_payment_expiry)">
                      Expiry {{ expiryDaysLabel(member.days_until_payment_expiry) }}
                    </AppBadge>
                    <AppBadge v-if="member.last_attendance_date" :color="attendanceBadgeColor(member.days_since_last_attendance)">
                      Last {{ lastAttendanceDateLabel(member.last_attendance_date) }}
                    </AppBadge>
                    <AppBadge v-if="Number(member.total_outstanding_amount || 0) > 0" color="red">
                      Due {{ formatMoney(member.total_outstanding_amount) }}
                    </AppBadge>
                  </div>
                </div>

                <!-- Chevron -->
                <ChevronRight class="h-4 w-4 text-secondary-400 shrink-0" :stroke-width="2" />
              </article>

              <AppEmptyState
                v-if="members.length === 0"
                :icon="Users"
                title="No members found"
                description="Try adjusting your search or add a new member."
              />
            </div>

            <!-- Desktop table -->
            <div class="hidden md:block app-table-scroll">
              <table class="w-full">
                <thead class="app-table-head-sticky bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                  <tr>
                    <th class="app-table-th">
                      Member
                    </th>
                    <th class="app-table-th">
                      Contact
                    </th>
                    <th class="app-table-th">
                      Plan
                    </th>
                    <th class="app-table-th">
                      Details
                    </th>
                    <th class="app-table-th">
                      Status
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                  <tr
                    v-for="member in members"
                    :key="member.id"
                    class="app-table-row cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary-500"
                    role="link"
                    tabindex="0"
                    @click="openMember(member.id)"
                    @keydown.enter.prevent="openMember(member.id)"
                    @keydown.space.prevent="openMember(member.id)"
                  >
                    <td class="app-table-td">
                      <div class="flex items-center gap-3">
                        <MemberAvatar
                          :src="member.profile_photo_url"
                          :initials="memberInitials(member)"
                          size="xs"
                        />
                        <div>
                          <p class="text-sm font-semibold text-secondary-900 dark:text-white">
                            {{ memberFullName(member) }}
                          </p>
                          <div class="mt-0.5 flex flex-wrap gap-1">
                            <AppBadge v-if="member.biometric_member_id" color="secondary">
                              #{{ member.biometric_member_id }}
                            </AppBadge>
                            <AppBadge :color="member.gender === 'male' ? 'indigo' : member.gender === 'female' ? 'purple' : 'secondary'">
                              {{ capitalize(member.gender) || 'N/A' }}
                            </AppBadge>
                            <AppBadge v-if="member.registration_source === 'campaign'" color="purple">
                              Campaign
                            </AppBadge>
                          </div>
                        </div>
                      </div>
                    </td>
                    <td class="app-table-td text-secondary-600 dark:text-secondary-300 text-xs">
                      <div>{{ member.phone_number || '—' }}</div>
                      <div class="text-secondary-400 dark:text-secondary-500">
                        {{ member.email || '—' }}
                      </div>
                    </td>
                    <td class="app-table-td text-secondary-600 dark:text-secondary-300 text-xs">
                      {{ member.plan_name || '—' }}
                    </td>
                    <td class="app-table-td">
                      <div class="flex max-w-sm flex-wrap gap-1.5">
                        <AppBadge v-if="hasDetailValue(member.days_until_payment_expiry)" :color="expiryBadgeColor(member.days_until_payment_expiry)">
                          Expiry {{ expiryDaysLabel(member.days_until_payment_expiry) }}
                        </AppBadge>
                        <AppBadge v-if="member.last_attendance_date" :color="attendanceBadgeColor(member.days_since_last_attendance)">
                          Last {{ lastAttendanceDateLabel(member.last_attendance_date) }}
                        </AppBadge>
                        <AppBadge v-if="Number(member.total_outstanding_amount || 0) > 0" color="red">
                          Due {{ formatMoney(member.total_outstanding_amount) }}
                        </AppBadge>
                      </div>
                    </td>
                    <td class="app-table-td">
                      <div class="flex flex-wrap gap-1.5">
                        <AppBadge :color="member.is_active ? 'green' : 'red'">
                          {{ member.is_active ? 'Active' : 'Inactive' }}
                        </AppBadge>
                        <AppBadge v-if="activeTab === 'members'" :color="member.is_verified ? 'blue' : 'amber'">
                          {{ member.is_verified ? 'Verified' : 'Unverified' }}
                        </AppBadge>
                        <AppBadge v-if="member.registration_source === 'campaign'" color="purple">
                          Campaign
                        </AppBadge>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="members.length === 0">
                    <td colspan="5">
                      <AppEmptyState :icon="Users" title="No members found" description="Try adjusting your search or add a new member." />
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </template>
        </div>
      </div>

      <div class="app-page-pagination">
        <AppPagination
          :current-page="meta.current_page"
          :last-page="meta.last_page"
          :per-page="perPage"
          :total="meta.total"
          :disabled="loading"
          @page-change="handlePageChange"
          @limit-change="handleLimitChange"
        />
      </div>
    </div>
  </section>

  <TempMemberFormModal
    v-if="tempModalOpen"
    @close="tempModalOpen = false"
    @created="onTempMemberCreated"
  />
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppPagination from '../components/AppPagination.vue';
import AppHeaderAction from '../components/AppHeaderAction.vue';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppSearchField from '../components/AppSearchField.vue';
import AppBadge from '../components/AppBadge.vue';
import AppEmptyState from '../components/AppEmptyState.vue';
import AppFormSelect from '../components/forms/AppFormSelect.vue';
import TempMemberFormModal from '../components/TempMemberFormModal.vue';
import MemberAvatar from '../../components/ui/MemberAvatar.vue';
import { ChevronRight, Clock, Download, Funnel, RotateCcw, Users, UserRoundPlus, X } from 'lucide-vue-next';
import { apiRequest } from '../composables/useApiClient';
import { useDateTimeFormat } from '../composables/useDateTimeFormat';

const route = useRoute();

const router = useRouter();
const { formatDate } = useDateTimeFormat();
const moneyFormatter = new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const members = ref([]);
const loading = ref(false);
const exporting = ref(false);
const errorMessage = ref('');
const search = ref('');
const permissions = ref({ create: false, edit: false, delete: false });
const meta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
const perPage = ref(15);
const tempModalOpen = ref(false);
const activeTab = ref(route.path === '/members/temp' ? 'temp' : 'members');
const paymentPlans = ref([]);
const filters = ref(defaultFilters());
const draftFilters = ref(defaultFilters());
const filtersOpen = ref(false);

const planId = computed(() => route.query.plan_id ? Number(route.query.plan_id) : null);
const planName = computed(() => route.query.plan_name || null);
const hasListFilters = computed(() => Boolean(
    filters.value.gender
    || filters.value.plan_id
    || filters.value.expiry_preset
    || filters.value.attendance_preset
    || filters.value.outstanding
    || filters.value.active !== 'active'
    || filters.value.verified,
));
const appliedFilterLabels = computed(() => filterLabels(filters.value));
const activeFilterCount = computed(() => appliedFilterLabels.value.length);
const draftFilterCount = computed(() => filterLabels(draftFilters.value).length);

const ACTIVE_LABELS = {
    active: 'Active',
    inactive: 'Inactive',
};

const VERIFIED_LABELS = {
    verified: 'Verified',
    unverified: 'Not verified',
};

const GENDER_LABELS = {
    male: 'Male',
    female: 'Female',
    other: 'Other',
};

const EXPIRY_PRESET_LABELS = {
    expired_30: 'Expired 30+ days',
    expired_60: 'Expired 60+ days',
    expired_90: 'Expired 90+ days',
};

const ATTENDANCE_PRESET_LABELS = {
    older_30: 'Last visit 30+ days',
    older_60: 'Last visit 60+ days',
    older_90: 'Last visit 90+ days',
};

const OUTSTANDING_LABELS = {
    with: 'Outstanding',
    without: 'No outstanding',
};

function defaultFilters() {
    return {
        active: 'active',
        verified: '',
        gender: '',
        plan_id: '',
        expiry_preset: '',
        attendance_preset: '',
        outstanding: '',
    };
}

function cloneFilters(source) {
    return { ...defaultFilters(), ...source };
}

function filterLabels(source) {
    const labels = [];
    const selectedPlanId = planId.value || Number(source.plan_id || 0);

    if (source.active && ACTIVE_LABELS[source.active]) labels.push(ACTIVE_LABELS[source.active]);
    if (source.verified && VERIFIED_LABELS[source.verified]) labels.push(VERIFIED_LABELS[source.verified]);
    if (source.gender && GENDER_LABELS[source.gender]) labels.push(GENDER_LABELS[source.gender]);
    if (selectedPlanId) labels.push(resolvePlanLabel(selectedPlanId));
    if (source.expiry_preset && EXPIRY_PRESET_LABELS[source.expiry_preset]) labels.push(EXPIRY_PRESET_LABELS[source.expiry_preset]);
    if (source.attendance_preset && ATTENDANCE_PRESET_LABELS[source.attendance_preset]) labels.push(ATTENDANCE_PRESET_LABELS[source.attendance_preset]);
    if (source.outstanding && OUTSTANDING_LABELS[source.outstanding]) labels.push(OUTSTANDING_LABELS[source.outstanding]);

    return labels;
}

function resolvePlanLabel(id) {
    if (planName.value) return `Plan: ${planName.value}`;

    const plan = paymentPlans.value.find((item) => Number(item.id) === Number(id));

    return `Plan: ${plan?.name || id}`;
}

function switchTab(tab) {
    if (activeTab.value === tab) return;
    activeTab.value = tab;
    search.value = '';
    filters.value = defaultFilters();
    draftFilters.value = defaultFilters();
    filtersOpen.value = false;
    perPage.value = 15;
    loadMembers(1);
}

watch(
    () => route.path,
    (path) => {
        const newTab = path === '/members/temp' ? 'temp' : 'members';
        if (activeTab.value !== newTab) switchTab(newTab);
    }
);

watch(planId, () => {
    loadMembers(1);
});

function onTempMemberCreated(memberId) {
    tempModalOpen.value = false;
    if (memberId) {
        router.push(`/members/${memberId}`);
    } else {
        loadMembers(1);
    }
}

function capitalize(value = '') {
    return value ? value.charAt(0).toUpperCase() + value.slice(1) : '';
}

function memberInitials(member) {
    const first = (member.first_name || '').trim();
    const last = (member.last_name || '').trim();
    if (first || last) {
        return `${first[0] || ''}${last[0] || ''}`.toUpperCase();
    }
    return (member.name || '?')[0].toUpperCase();
}

function memberFullName(member) {
    const firstName = (member.first_name || '').trim();
    const lastName = (member.last_name || '').trim();

    if (firstName || lastName) {
        return `${firstName} ${lastName}`.trim();
    }

    return member.name || '-';
}

function expiryDaysLabel(days) {
    if (days === null || days === undefined || days === '') return 'N/A';

    const value = Number(days);

    if (Number.isNaN(value)) return 'N/A';
    if (value < 0) return `${Math.abs(value)}d overdue`;
    if (value === 0) return 'today';

    return `${value}d left`;
}

function hasDetailValue(value) {
    return value !== null && value !== undefined && value !== '' && !Number.isNaN(Number(value));
}

function hasMemberDetails(member) {
    return hasDetailValue(member.days_until_payment_expiry)
        || Boolean(member.last_attendance_date)
        || Number(member.total_outstanding_amount || 0) > 0;
}

function expiryBadgeColor(days) {
    if (days === null || days === undefined || days === '') return 'secondary';

    const value = Number(days);

    if (Number.isNaN(value)) return 'secondary';
    if (value < 0) return 'red';
    if (value <= 3) return 'amber';
    if (value <= 7) return 'orange';

    return 'green';
}

function attendanceBadgeColor(days) {
    if (days === null || days === undefined || days === '') return 'secondary';

    const value = Number(days);

    if (Number.isNaN(value)) return 'secondary';
    if (value > 30) return 'red';
    if (value > 14) return 'amber';

    return 'green';
}

function lastAttendanceDateLabel(value) {
    return formatDate(value, 'N/A');
}

function formatMoney(value) {
    return moneyFormatter.format(Number(value || 0));
}

function openMember(memberId) {
    router.push(`/members/${memberId}`);
}

function resetFilters() {
    filters.value = defaultFilters();
    draftFilters.value = defaultFilters();
    filtersOpen.value = false;
    loadMembers(1);
}

function openFilters() {
    draftFilters.value = cloneFilters({
        ...filters.value,
        plan_id: planId.value || filters.value.plan_id,
    });
    filtersOpen.value = true;
}

function closeFilters() {
    filtersOpen.value = false;
}

function applyDraftFilters() {
    filters.value = cloneFilters(draftFilters.value);
    filtersOpen.value = false;
    loadMembers(1);
}

function resetDraftFilters() {
    draftFilters.value = cloneFilters({
        plan_id: planId.value || '',
    });
}

function handleWindowKeydown(event) {
    if (event.key === 'Escape' && filtersOpen.value) {
        closeFilters();
    }
}

async function loadPaymentPlans() {
    const response = await apiRequest('/api/members/form/payment-plans');
    paymentPlans.value = response?.data || [];
}

async function loadMembers(page = 1) {
    loading.value = true;
    errorMessage.value = '';

    try {
        const params = {
            page,
            per_page: perPage.value,
            search: search.value,
            is_temp: activeTab.value === 'temp' ? '1' : '0',
        };

        const selectedPlanId = planId.value || Number(filters.value.plan_id || 0);

        if (selectedPlanId) {
            params.plan_id = selectedPlanId;
        }

        if (filters.value.gender) {
            params.gender = filters.value.gender;
        }

        if (filters.value.active) {
            params.active = filters.value.active;
        }

        if (filters.value.verified) {
            params.verified = filters.value.verified;
        }

        if (filters.value.expiry_preset) {
            params.expiry_preset = filters.value.expiry_preset;
        }

        if (filters.value.attendance_preset) {
            params.attendance_preset = filters.value.attendance_preset;
        }

        if (filters.value.outstanding) {
            params.outstanding = filters.value.outstanding;
        }

        const response = await apiRequest('/api/members', { params });

        members.value = response.data || [];
        permissions.value = response.permissions || permissions.value;
        meta.value = response.meta || meta.value;
        perPage.value = meta.value.per_page || perPage.value;
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load members.';
    } finally {
        loading.value = false;
    }
}

function handlePageChange(page) {
    loadMembers(page);
}

function handleLimitChange(limit) {
    perPage.value = Number(limit);
    loadMembers(1);
}

async function exportGoogleContacts() {
    exporting.value = true;
    errorMessage.value = '';

    try {
        const csvBlob = await apiRequest('/api/members/export/google-contacts', {
            responseType: 'blob',
            headers: {
                Accept: 'text/csv',
            },
        });

        const downloadUrl = window.URL.createObjectURL(csvBlob);
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.download = `google-contacts-members-${new Date().toISOString().slice(0, 10)}.csv`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(downloadUrl);
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to export members for Google Contacts.';
    } finally {
        exporting.value = false;
    }
}

onMounted(async () => {
    window.addEventListener('keydown', handleWindowKeydown);

    try {
        await Promise.all([loadPaymentPlans(), loadMembers()]);
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load members.';
    }
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleWindowKeydown);
});
</script>
