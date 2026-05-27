<template>
  <section class="app-page-frame">
    <AppPageHeader show-back :title="isEdit ? 'Edit Notification' : 'New Notification'" />

    <div class="app-page-scroll">
      <div v-if="pageLoading" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">
        Loading...
      </div>

      <template v-else>
        <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
          {{ errorMessage }}
        </div>

        <form class="space-y-4" @submit.prevent="save">
          <!-- Basic info -->
          <div class="app-surface rounded-2xl p-4 md:p-6 space-y-4">
            <h3 class="text-base font-semibold text-secondary-900 dark:text-white">
              Notification Details
            </h3>

            <AppFormField label="Name" required>
              <AppFormInput
                v-model="form.name"
                type="text"
                maxlength="255"
                placeholder="e.g. Monthly membership reminder"
                required
              />
            </AppFormField>

            <AppFormField label="Message" required>
              <AppFormTextarea
                v-model="form.message"
                rows="5"
                maxlength="621"
                placeholder="Type your SMS message here..."
                required
              />
              <p class="mt-1 text-xs text-secondary-400 dark:text-secondary-500 text-right">
                {{ form.message.length }} / 621
              </p>
            </AppFormField>
          </div>

          <!-- Member selector -->
          <div class="app-surface rounded-2xl p-4 md:p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
              <h3 class="text-base font-semibold text-secondary-900 dark:text-white">
                Recipients
                <span class="ml-1 text-sm font-normal text-secondary-500 dark:text-secondary-400">({{ selectedIds.size }} selected)</span>
              </h3>
              <div class="flex flex-wrap gap-2">
                <button type="button" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-secondary-300 dark:border-secondary-600 text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700 transition-colors" @click="selectAll">
                  Select All
                </button>
                <button type="button" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-secondary-300 dark:border-secondary-600 text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700 transition-colors" @click="selectActive">
                  Active Only
                </button>
                <button type="button" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-secondary-300 dark:border-secondary-600 text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700 transition-colors" @click="selectInactive">
                  Inactive Only
                </button>
                <button type="button" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors" @click="clearAll">
                  Clear
                </button>
              </div>
            </div>

            <!-- Search -->
            <div class="relative">
              <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-secondary-400" />
              <input
                v-model="memberSearch"
                type="text"
                placeholder="Search members by name, ID, or phone..."
                class="w-full pl-9 pr-4 py-2 text-sm rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 text-secondary-900 dark:text-white placeholder-secondary-400 focus:outline-none focus:ring-2 focus:ring-primary-500"
              />
            </div>

            <!-- List -->
            <div v-if="membersLoading" class="py-6 text-center text-sm text-secondary-500 dark:text-secondary-400">
              Loading members...
            </div>

            <div v-else-if="filteredMembers.length === 0" class="py-6 text-center text-sm text-secondary-500 dark:text-secondary-400">
              No members with phone numbers found.
            </div>

            <div v-else class="border border-secondary-200 dark:border-secondary-700 rounded-xl overflow-hidden">
              <!-- Header row -->
              <div class="flex items-center gap-3 px-4 py-2.5 bg-secondary-50 dark:bg-secondary-800/50 border-b border-secondary-200 dark:border-secondary-700">
                <input
                  type="checkbox"
                  class="h-4 w-4 rounded border-secondary-300 text-primary-600 focus:ring-primary-500 cursor-pointer"
                  :checked="allFilteredSelected"
                  :indeterminate="someFilteredSelected && !allFilteredSelected"
                  @change="toggleFilteredAll"
                />
                <span class="text-xs font-semibold text-secondary-700 dark:text-secondary-300">Member</span>
                <span class="ml-auto text-xs text-secondary-500 dark:text-secondary-400">{{ filteredMembers.length }} shown</span>
              </div>

              <!-- Scrollable member list -->
              <div class="max-h-80 overflow-y-auto divide-y divide-secondary-100 dark:divide-secondary-800">
                <label
                  v-for="m in filteredMembers"
                  :key="m.id"
                  class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-secondary-50 dark:hover:bg-secondary-800/40 transition-colors"
                >
                  <input
                    type="checkbox"
                    class="h-4 w-4 rounded border-secondary-300 text-primary-600 focus:ring-primary-500 cursor-pointer shrink-0"
                    :checked="selectedIds.has(m.id)"
                    @change="toggle(m.id)"
                  />
                  <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-secondary-900 dark:text-white truncate">{{ m.name }}</p>
                    <p class="text-xs text-secondary-500 dark:text-secondary-400 truncate">{{ m.biometric_member_id }} &bull; {{ m.phone_number }}</p>
                  </div>
                  <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full shrink-0" :class="m.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'">
                    {{ m.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </label>
              </div>
            </div>

            <p v-if="recipientError" class="text-xs text-red-600 dark:text-red-400">
              {{ recipientError }}
            </p>
          </div>

          <!-- Actions -->
          <div class="flex flex-col sm:flex-row items-center justify-end gap-2 pb-4">
            <RouterLink to="/notifications" class="w-full sm:w-auto text-center px-4 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg text-sm text-secondary-700 dark:text-secondary-300">
              Cancel
            </RouterLink>
            <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm disabled:opacity-50 transition-colors" :disabled="submitting">
              {{ submitting ? 'Saving...' : (isEdit ? 'Update Draft' : 'Save Draft') }}
            </button>
          </div>
        </form>
      </template>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Search } from 'lucide-vue-next';
import { apiRequest } from '../composables/useApiClient';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppFormField from '../components/forms/AppFormField.vue';
import AppFormInput from '../components/forms/AppFormInput.vue';
import AppFormTextarea from '../components/forms/AppFormTextarea.vue';

const route = useRoute();
const router = useRouter();

const isEdit = computed(() => Boolean(route.params.id));
const pageLoading = ref(false);
const membersLoading = ref(false);
const submitting = ref(false);
const errorMessage = ref('');
const recipientError = ref('');
const memberSearch = ref('');

const form = ref({ name: '', message: '' });
const allMembers = ref([]);
const selectedIds = ref(new Set());

// ─── Member filtering ────────────────────────────────────────────────────────

const filteredMembers = computed(() => {
    const q = memberSearch.value.trim().toLowerCase();
    if (!q) return allMembers.value;
    return allMembers.value.filter(m =>
        m.name.toLowerCase().includes(q) ||
        m.biometric_member_id?.toLowerCase().includes(q) ||
        m.phone_number.includes(q)
    );
});

const allFilteredSelected = computed(() =>
    filteredMembers.value.length > 0 && filteredMembers.value.every(m => selectedIds.value.has(m.id))
);

const someFilteredSelected = computed(() =>
    filteredMembers.value.some(m => selectedIds.value.has(m.id))
);

// ─── Selection helpers ───────────────────────────────────────────────────────

function toggle(id) {
    const next = new Set(selectedIds.value);
    next.has(id) ? next.delete(id) : next.add(id);
    selectedIds.value = next;
}

function toggleFilteredAll() {
    const next = new Set(selectedIds.value);
    if (allFilteredSelected.value) {
        filteredMembers.value.forEach(m => next.delete(m.id));
    } else {
        filteredMembers.value.forEach(m => next.add(m.id));
    }
    selectedIds.value = next;
}

function selectAll() {
    selectedIds.value = new Set(allMembers.value.map(m => m.id));
}

function selectActive() {
    selectedIds.value = new Set(allMembers.value.filter(m => m.is_active).map(m => m.id));
}

function selectInactive() {
    selectedIds.value = new Set(allMembers.value.filter(m => !m.is_active).map(m => m.id));
}

function clearAll() {
    selectedIds.value = new Set();
}

// ─── Data loading ────────────────────────────────────────────────────────────

async function loadMembers() {
    membersLoading.value = true;
    try {
        const res = await apiRequest('/api/notifications/members');
        allMembers.value = res.data;
    } catch {
        errorMessage.value = 'Failed to load members.';
    } finally {
        membersLoading.value = false;
    }
}

async function loadNotification() {
    pageLoading.value = true;
    try {
        const data = await apiRequest(`/api/notifications/${route.params.id}`);
        form.value.name = data.name;
        form.value.message = data.message;
        selectedIds.value = new Set(data.recipients.map(r => r.member_id));
    } catch {
        errorMessage.value = 'Failed to load notification.';
    } finally {
        pageLoading.value = false;
    }
}

// ─── Submit ──────────────────────────────────────────────────────────────────

async function save() {
    recipientError.value = '';
    if (selectedIds.value.size === 0) {
        recipientError.value = 'Please select at least one recipient.';
        return;
    }

    submitting.value = true;
    errorMessage.value = '';
    try {
        const body = {
            name: form.value.name,
            message: form.value.message,
            member_ids: [...selectedIds.value],
        };

        if (isEdit.value) {
            await apiRequest(`/api/notifications/${route.params.id}`, { method: 'PUT', data: body });
            router.push(`/notifications/${route.params.id}`);
        } else {
            const created = await apiRequest('/api/notifications', { method: 'POST', data: body });
            router.push(`/notifications/${created.id}`);
        }
    } catch (e) {
        errorMessage.value = e?.message || 'Failed to save notification.';
    } finally {
        submitting.value = false;
    }
}

// ─── Init ────────────────────────────────────────────────────────────────────

onMounted(async () => {
    await loadMembers();
    if (isEdit.value) await loadNotification();
});
</script>
