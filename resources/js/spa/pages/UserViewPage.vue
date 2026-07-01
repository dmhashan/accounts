<template>
  <section class="app-page-frame">
    <AppPageHeader show-back>
      <template #cta-slot>
        <button
          v-if="permissions.edit"
          type="button"
          class="inline-flex items-center gap-1.5 px-4 py-2 border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 text-secondary-700 dark:text-secondary-200 hover:bg-secondary-50 dark:hover:bg-secondary-800 text-sm font-semibold rounded-xl transition-colors disabled:opacity-50"
          :disabled="passwordResetting"
          @click="passwordResetConfirm = true"
        >
          <Mail class="w-4 h-4" :stroke-width="2" />
          {{ passwordResetting ? 'Sending...' : 'Send Reset Link' }}
        </button>
        <button
          v-if="permissions.edit && user.canDeactivate"
          type="button"
          class="inline-flex items-center gap-1.5 px-4 py-2 text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-50"
          :class="user.is_active ? 'bg-amber-600 hover:bg-amber-700' : 'bg-green-600 hover:bg-green-700'"
          :disabled="statusChanging"
          @click="statusConfirm = true"
        >
          <component :is="user.is_active ? UserX : UserCheck" class="w-4 h-4" :stroke-width="2" />
          {{ statusChanging ? 'Saving...' : statusActionLabel }}
        </button>
        <RouterLink
          v-if="permissions.edit"
          :to="`/users/${route.params.id}/edit`"
          class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-xl transition-colors"
        >
          Edit User
        </RouterLink>
        <button
          v-if="permissions.delete && user.canDelete"
          type="button"
          class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-50"
          :disabled="deleting"
          @click="deleteConfirm = true"
        >
          Delete
        </button>
      </template>
    </AppPageHeader>

    <div v-if="loading" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">
      Loading...
    </div>

    <div v-else-if="errorMessage" class="p-6 text-sm text-red-600 dark:text-red-400">
      {{ errorMessage }}
    </div>

    <div v-else class="app-page-scroll space-y-5">
      <div v-if="successMessage" class="rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 px-4 py-3 text-sm text-green-700 dark:text-green-200">
        {{ successMessage }}
      </div>

      <div v-if="actionErrorMessage" class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
        {{ actionErrorMessage }}
      </div>

      <div class="app-surface rounded-2xl p-4 md:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4 mb-4">
          <MemberAvatar :initials="initials(user.name)" size="lg" />
          <div>
            <h1 class="text-xl font-bold text-secondary-900 dark:text-white">
              {{ user.name }}
            </h1>
            <p class="mt-1 text-sm text-secondary-500 dark:text-secondary-400">
              {{ user.email }}
            </p>
            <span class="mt-2 inline-flex px-3 py-1 text-xs font-semibold rounded-full" :class="statusBadgeClass(user)">
              {{ user.is_active ? 'Active' : 'Inactive' }}
            </span>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 text-sm">
          <div class="rounded-xl border border-secondary-200 dark:border-secondary-700 bg-secondary-50/60 dark:bg-secondary-900/30 p-4">
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Role
            </p>
            <span
              class="px-3 py-1 inline-flex text-xs font-semibold rounded-full"
              :class="user.role?.slug === 'admin'
                ? 'bg-primary-100 text-primary-800 dark:bg-primary-900/30 dark:text-primary-300'
                : 'bg-secondary-100 text-secondary-800 dark:bg-secondary-900/30 dark:text-secondary-300'"
            >
              {{ user.role?.name || 'No Role' }}
            </span>
          </div>

          <div class="rounded-xl border border-secondary-200 dark:border-secondary-700 bg-secondary-50/60 dark:bg-secondary-900/30 p-4">
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Account Status
            </p>
            <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full" :class="statusBadgeClass(user)">
              {{ user.is_active ? 'Active' : 'Inactive' }}
            </span>
          </div>

          <div v-if="user.member" class="rounded-xl border border-secondary-200 dark:border-secondary-700 bg-secondary-50/60 dark:bg-secondary-900/30 p-4 space-y-3">
            <div>
              <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
                Linked Member
              </p>
              <p class="text-sm font-semibold text-secondary-900 dark:text-white">
                {{ user.member.name }}
              </p>
              <p class="text-xs text-secondary-500 dark:text-secondary-400">
                {{ user.member.member_id }}
              </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
              <div>
                <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
                  Email
                </p>
                <p class="text-secondary-900 dark:text-white">
                  {{ user.member.email || 'N/A' }}
                </p>
              </div>
              <div>
                <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
                  Phone
                </p>
                <p class="text-secondary-900 dark:text-white">
                  {{ user.member.phone_number || 'N/A' }}
                </p>
              </div>
              <div>
                <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
                  Joined
                </p>
                <p class="text-secondary-900 dark:text-white">
                  {{ user.member.joined_date || 'N/A' }}
                </p>
              </div>
              <div>
                <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
                  Status
                </p>
                <p class="text-secondary-900 dark:text-white">
                  {{ user.member.is_active ? 'Active' : 'Inactive' }} | {{ user.member.is_verified ? 'Verified' : 'Unverified' }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <AppConfirmModal
      v-if="passwordResetConfirm"
      title="Send Reset Link"
      :message="`Send a password reset email to ${user.email}?`"
      confirm-label="Send Link"
      loading-label="Sending..."
      variant="primary"
      :loading="passwordResetting"
      @confirm="sendPasswordReset"
      @cancel="passwordResetConfirm = false"
    />

    <AppConfirmModal
      v-if="statusConfirm"
      :title="`${statusActionLabel} User`"
      :message="`${statusActionLabel} ${user.name}? ${user.is_active ? 'They will no longer be able to sign in.' : 'They will be able to sign in again.'}`"
      :confirm-label="statusActionLabel"
      loading-label="Saving..."
      :variant="user.is_active ? 'warning' : 'primary'"
      :loading="statusChanging"
      @confirm="changeUserStatus"
      @cancel="statusConfirm = false"
    />

    <AppConfirmModal
      v-if="deleteConfirm"
      title="Delete User"
      :message="`Delete ${user.name}? This cannot be undone.`"
      confirm-label="Delete"
      loading-label="Deleting..."
      variant="danger"
      :loading="deleting"
      @confirm="deleteUser"
      @cancel="deleteConfirm = false"
    />
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Mail, UserCheck, UserX } from 'lucide-vue-next';
import { apiRequest } from '../composables/useApiClient';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppConfirmModal from '../components/AppConfirmModal.vue';
import MemberAvatar from '../../components/ui/MemberAvatar.vue';

const route = useRoute();
const router = useRouter();

const loading = ref(false);
const errorMessage = ref('');
const user = ref({});
const deleting = ref(false);
const deleteConfirm = ref(false);
const passwordResetting = ref(false);
const passwordResetConfirm = ref(false);
const statusChanging = ref(false);
const statusConfirm = ref(false);
const successMessage = ref('');
const actionErrorMessage = ref('');
const permissions = ref({ edit: false, delete: false });

const statusActionLabel = computed(() => (user.value?.is_active ? 'Deactivate' : 'Activate'));

function initials(name = '') {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('') || 'U';
}

function statusBadgeClass(targetUser) {
    return targetUser?.is_active
        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
        : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
}

async function deleteUser() {
    deleting.value = true;
    actionErrorMessage.value = '';

    try {
        await apiRequest(`/api/users/${route.params.id}`, { method: 'DELETE' });
        router.push('/users');
    } catch (e) {
        actionErrorMessage.value = e?.response?.data?.message || 'Failed to delete user.';
        deleteConfirm.value = false;
    } finally {
        deleting.value = false;
    }
}

async function sendPasswordReset() {
    if (!user.value?.email) {
        passwordResetConfirm.value = false;
        return;
    }

    passwordResetting.value = true;
    successMessage.value = '';
    actionErrorMessage.value = '';

    try {
        const response = await apiRequest(`/api/users/${route.params.id}/password-reset`, { method: 'POST' });
        successMessage.value = response.message || 'Password reset link has been sent.';
        passwordResetConfirm.value = false;
    } catch (e) {
        actionErrorMessage.value = e?.response?.data?.message || 'Failed to send password reset link.';
        passwordResetConfirm.value = false;
    } finally {
        passwordResetting.value = false;
    }
}

async function changeUserStatus() {
    const nextStatus = !user.value?.is_active;
    statusChanging.value = true;
    successMessage.value = '';
    actionErrorMessage.value = '';

    try {
        const response = await apiRequest(`/api/users/${route.params.id}/status`, {
            method: 'PATCH',
            data: { is_active: nextStatus },
        });
        user.value = response.data || { ...user.value, is_active: nextStatus };
        successMessage.value = response.message || (nextStatus ? 'User activated successfully.' : 'User deactivated successfully.');
        statusConfirm.value = false;
    } catch (e) {
        actionErrorMessage.value = e?.response?.data?.message || 'Failed to update user status.';
        statusConfirm.value = false;
    } finally {
        statusChanging.value = false;
    }
}

async function load() {
    loading.value = true;
    errorMessage.value = '';
    try {
        const response = await apiRequest(`/api/users/${route.params.id}`);
        user.value = response.data || response;
        // Load permissions from the users list response
        const listResponse = await apiRequest('/api/users', { params: { per_page: 1 } });
        permissions.value = listResponse.permissions || { edit: false, delete: false };
    } catch {
        errorMessage.value = 'Failed to load user.';
    } finally {
        loading.value = false;
    }
}

onMounted(() => load());
</script>
