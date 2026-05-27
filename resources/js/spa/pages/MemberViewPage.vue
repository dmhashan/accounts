<template>
  <section class="app-page-frame">
    <div class="app-page-scroll">
      <div class="max-w-4xl mx-auto px-0 pb-8 space-y-4">
        <!-- Alerts -->
        <div v-if="errorMessage" class="mx-4 mt-4 rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
          {{ errorMessage }}
        </div>
        <div v-if="successMessage" class="mx-4 mt-4 rounded-xl border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 px-4 py-3 text-sm text-green-700 dark:text-green-200">
          {{ successMessage }}
        </div>

        <div v-if="loading" class="mt-8 p-8 text-center text-sm text-secondary-400 dark:text-secondary-500">
          Loading...
        </div>

        <template v-else-if="member">
          <!-- Hero Card -->
          <MemberHeroCard
            :member="member"
            :permissions="permissions"
            :action-in-progress="actionInProgress"
            @toggle-status="toggleStatus"
            @toggle-verification="toggleVerification"
            @remove="removeMember"
            @open-topup="handleOpenTopup"
            @open-redeem="handleOpenRedeem"
            @update:photo-url="member.profile_photo_url = $event"
          />

          <!-- Tabs -->
          <div class="border-b border-secondary-200 dark:border-secondary-700 mx-4">
            <nav class="-mb-px flex" aria-label="Member tabs">
              <button
                v-for="tab in tabs"
                :key="tab.id"
                type="button"
                class="flex-1 sm:flex-none flex items-center justify-center sm:justify-start gap-1.5 px-2 sm:px-0 sm:mr-6 pb-3 text-sm font-medium border-b-2 transition-colors"
                :class="activeTab === tab.id
                  ? 'border-primary-600 text-primary-600 dark:text-primary-400 dark:border-primary-400'
                  : 'border-transparent text-secondary-500 dark:text-secondary-400 hover:text-secondary-700 dark:hover:text-secondary-200'"
                :title="tab.label"
                @click="switchTab(tab.id)"
              >
                <component :is="tab.icon" class="w-4 h-4 shrink-0" />
                <span class="hidden sm:inline whitespace-nowrap">{{ tab.label }}</span>
              </button>
            </nav>
          </div>

          <!-- Tab content -->
          <MemberOverviewTab v-if="activeTab === 'overview'" :member="member" />

          <MemberWalletTab
            v-if="activeTab === 'wallet'"
            ref="walletTabRef"
            :member-id="member.id"
            :current-balance="member.current_balance"
            :can-manage="permissions.edit"
            @balance-updated="member = { ...member, current_balance: $event }"
          />

          <MemberPaymentsTab
            v-if="activeTab === 'payments'"
            ref="paymentsTabRef"
            :member-id="member.id"
          />

          <MemberSalesTab
            v-if="activeTab === 'sales'"
            ref="salesTabRef"
            :member-id="member.id"
          />

          <MemberWorkoutsTab
            v-if="activeTab === 'workouts'"
            ref="workoutsTabRef"
            :member-id="member.id"
          />

          <MemberDocumentsTab
            v-if="activeTab === 'documents'"
            ref="documentsTabRef"
            :member-id="member.id"
            :member="member"
            :can-manage="permissions.edit"
          />

          <MemberAttendanceTab
            v-if="activeTab === 'calendar'"
            ref="attendanceTabRef"
            :member-id="member.id"
            :joined-date="member.joined_date"
          />

          <MemberBiometricTab
            v-if="activeTab === 'biometric'"
            :member-id="member.id"
            :biometric-member-id="member.biometric_member_id"
            :last-synced-at="member.biometric_last_synced_at"
            :can-sync="permissions.edit"
            @synced="member = { ...member, biometric_last_synced_at: $event }"
            @assigned="member = { ...member, biometric_member_id: $event }"
          />
        </template>

        <div v-else-if="!loading" class="p-8 text-center text-sm text-secondary-400 dark:text-secondary-500">
          Member details are unavailable.
        </div>
      </div><!-- max-w-4xl -->
    </div><!-- app-page-scroll -->
  </section>

  <AppConfirmModal
    v-if="deleteMemberConfirm"
    title="Delete Member"
    message="Are you sure you want to delete this member? This cannot be undone."
    confirm-label="Delete"
    loading-label="Deleting..."
    :loading="actionInProgress === 'delete'"
    @confirm="confirmRemoveMember"
    @cancel="deleteMemberConfirm = false"
  />
</template>

<script setup>
import { nextTick, onMounted, ref } from 'vue';
import AppConfirmModal from '../components/AppConfirmModal.vue';
import { Banknote, CalendarDays, Cpu, Dumbbell, FileText, ShoppingBag, UserRound, Wallet } from 'lucide-vue-next';
import { useRoute, useRouter } from 'vue-router';
import { apiRequest } from '../composables/useApiClient';
import MemberHeroCard from '../components/member/MemberHeroCard.vue';
import MemberOverviewTab from '../components/member/MemberOverviewTab.vue';
import MemberWalletTab from '../components/member/MemberWalletTab.vue';
import MemberPaymentsTab from '../components/member/MemberPaymentsTab.vue';
import MemberSalesTab from '../components/member/MemberSalesTab.vue';
import MemberWorkoutsTab from '../components/member/MemberWorkoutsTab.vue';
import MemberDocumentsTab from '../components/member/MemberDocumentsTab.vue';
import MemberAttendanceTab from '../components/member/MemberAttendanceTab.vue';
import MemberBiometricTab from '../components/member/MemberBiometricTab.vue';

const route = useRoute();
const router = useRouter();

const loading = ref(false);
const actionInProgress = ref('');
const errorMessage = ref('');
const successMessage = ref('');
const member = ref(null);
const permissions = ref({ edit: false, delete: false });

// ── Tabs ──
const tabs = [
    { id: 'overview',  label: 'Overview',  icon: UserRound },
    { id: 'wallet',    label: 'Wallet',    icon: Wallet },
    { id: 'payments',  label: 'Payments',  icon: Banknote },
    { id: 'sales',     label: 'Sales',     icon: ShoppingBag },
    { id: 'workouts',  label: 'Workouts',  icon: Dumbbell },
    { id: 'documents', label: 'Documents', icon: FileText },
    { id: 'calendar',  label: 'Calendar',  icon: CalendarDays },
    { id: 'biometric', label: 'Biometric', icon: Cpu },
];
const activeTab = ref('overview');

// Tab component refs for lazy load triggering
const walletTabRef = ref(null);
const paymentsTabRef = ref(null);
const salesTabRef = ref(null);
const workoutsTabRef = ref(null);
const documentsTabRef = ref(null);
const attendanceTabRef = ref(null);

async function switchTab(id) {
    activeTab.value = id;
    await nextTick(); // wait for v-if to mount the component before calling exposed methods
    if (!member.value) return;
    if (id === 'wallet') walletTabRef.value?.loadWalletData();
    if (id === 'payments') paymentsTabRef.value?.loadMemberPayments(1);
    if (id === 'sales') salesTabRef.value?.loadMemberSales(1);
    if (id === 'workouts') workoutsTabRef.value?.loadMemberWorkouts(1);
    if (id === 'documents') {
        documentsTabRef.value?.loadDocuments();
        documentsTabRef.value?.loadMemberForms();
    }
    if (id === 'calendar') attendanceTabRef.value?.loadAttendance();
}

async function handleOpenTopup() {
    if (activeTab.value !== 'wallet') await switchTab('wallet');
    walletTabRef.value?.openTopupModal();
}

async function handleOpenRedeem() {
    if (activeTab.value !== 'wallet') await switchTab('wallet');
    walletTabRef.value?.openRedeemModal();
}

async function loadMember() {
    loading.value = true;
    errorMessage.value = '';
    try {
        const response = await apiRequest(`/api/members/${route.params.id}`);
        member.value = response.data || null;
        permissions.value = response.permissions || permissions.value;
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load member details.';
    } finally {
        loading.value = false;
    }
}

async function toggleStatus() {
    if (!member.value) return;
    actionInProgress.value = 'status';
    errorMessage.value = '';
    successMessage.value = '';
    try {
        const response = await apiRequest(`/api/members/${member.value.id}/toggle-status`, { method: 'patch' });
        member.value = { ...member.value, is_active: response.is_active };
        successMessage.value = response.message || 'Member status updated successfully.';
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to update member status.';
    } finally {
        actionInProgress.value = '';
    }
}

async function toggleVerification() {
    if (!member.value) return;
    actionInProgress.value = 'verification';
    errorMessage.value = '';
    successMessage.value = '';
    try {
        const response = await apiRequest(`/api/members/${member.value.id}/toggle-verification`, { method: 'patch' });
        member.value = { ...member.value, is_verified: response.is_verified };
        successMessage.value = response.message || 'Member verification updated successfully.';
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to update member verification.';
    } finally {
        actionInProgress.value = '';
    }
}

const deleteMemberConfirm = ref(false);

function removeMember() {
    if (!member.value) return;
    deleteMemberConfirm.value = true;
}

async function confirmRemoveMember() {
    actionInProgress.value = 'delete';
    deleteMemberConfirm.value = false;
    errorMessage.value = '';
    successMessage.value = '';
    try {
        await apiRequest(`/api/members/${member.value.id}`, { method: 'delete' });
        router.push('/members');
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to delete member.';
    } finally {
        actionInProgress.value = '';
    }
}

onMounted(() => loadMember());
</script>
