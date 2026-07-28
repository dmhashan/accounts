<template>
  <section class="app-page-frame">
    <AppPageHeader title="Member Reachable Configurations" />

    <div class="app-page-scroll">
      <div v-if="loadError" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
        {{ loadError }}
      </div>

      <div v-if="successMessage" class="mb-4 rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 px-4 py-3 text-sm text-green-700 dark:text-green-200">
        {{ successMessage }}
      </div>

      <div v-if="saveError" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
        {{ saveError }}
      </div>

      <div class="app-surface rounded-2xl p-4 md:p-6">
        <h3 class="text-sm font-semibold text-secondary-700 dark:text-secondary-300 uppercase tracking-wide mb-4">
          Member Reachable Configurations
        </h3>

        <div v-if="loading" class="py-4 text-center text-sm text-secondary-500 dark:text-secondary-400">
          Loading...
        </div>

        <template v-else>
          <MemberReachableConfigFields v-model="memberReachableConfig" />

          <div class="mt-6 flex items-center justify-end">
            <button
              type="button"
              class="px-5 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium disabled:opacity-50 transition-colors"
              :disabled="saving"
              @click="saveMemberNotifications"
            >
              {{ saving ? 'Saving...' : 'Save Member Notifications' }}
            </button>
          </div>
        </template>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import AppPageHeader from '../components/AppPageHeader.vue';
import MemberReachableConfigFields from '../components/settings/MemberReachableConfigFields.vue';
import { apiRequest } from '../composables/useApiClient';

const loading = ref(false);
const saving = ref(false);
const loadError = ref('');
const saveError = ref('');
const successMessage = ref('');

const memberReachableConfig = ref({
    member_login_url: '',
    whatsapp_group_url: '',
    whatsapp_groups: [],
});

function parseMemberReachableConfig(raw) {
    try {
        const config = JSON.parse(raw || '{}') || {};
        return {
            member_login_url: config.member_login_url || '',
            whatsapp_group_url: config.whatsapp_group_url || '',
            whatsapp_groups: Array.isArray(config.whatsapp_groups) ? config.whatsapp_groups : [],
        };
    } catch {
        return {
            member_login_url: '',
            whatsapp_group_url: '',
            whatsapp_groups: [],
        };
    }
}

function serializeMemberNotificationConfig() {
    return JSON.stringify({
        member_login_url: memberReachableConfig.value.member_login_url || '',
        whatsapp_group_url: memberReachableConfig.value.whatsapp_group_url || '',
        whatsapp_groups: Array.isArray(memberReachableConfig.value.whatsapp_groups)
            ? memberReachableConfig.value.whatsapp_groups
            : [],
    });
}

async function load() {
    loading.value = true;
    loadError.value = '';
    try {
        const response = await apiRequest('/api/settings/configuration');
        const cfg = response.data || {};
        memberReachableConfig.value = parseMemberReachableConfig(cfg['general.member_notifications']);
    } catch {
        loadError.value = 'Failed to load member notification settings.';
    } finally {
        loading.value = false;
    }
}

async function saveMemberNotifications() {
    saving.value = true;
    saveError.value = '';
    successMessage.value = '';
    try {
        await apiRequest('/api/settings/configuration', {
            method: 'put',
            data: {
                'general.member_notifications': serializeMemberNotificationConfig(),
            },
        });
        successMessage.value = 'Member notification settings saved successfully.';
        setTimeout(() => { successMessage.value = ''; }, 3000);
    } catch (error) {
        saveError.value = error?.response?.data?.message || error?.message || 'Failed to save member notification settings.';
    } finally {
        saving.value = false;
    }
}

onMounted(() => {
    load();
});
</script>
