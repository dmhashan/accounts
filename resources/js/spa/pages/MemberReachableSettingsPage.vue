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

      <div class="app-surface rounded-2xl p-4 md:p-6 space-y-6">
        <!-- Tabs Header -->
        <div class="border-b border-secondary-200 dark:border-secondary-700 flex items-center gap-6">
          <button
            type="button"
            class="pb-3 text-sm font-semibold transition-colors relative"
            :class="activeTab === 'manual' ? 'text-primary-600 dark:text-primary-400 border-b-2 border-primary-600 dark:border-primary-400' : 'text-secondary-500 hover:text-secondary-800 dark:text-secondary-400 dark:hover:text-secondary-200'"
            @click="activeTab = 'manual'"
          >
            Group Links (Manual)
          </button>

          <button
            type="button"
            class="pb-3 text-sm font-semibold transition-colors relative flex items-center gap-2"
            :class="activeTab === 'gowa' ? 'text-primary-600 dark:text-primary-400 border-b-2 border-primary-600 dark:border-primary-400' : 'text-secondary-500 hover:text-secondary-800 dark:text-secondary-400 dark:hover:text-secondary-200'"
            @click="activeTab = 'gowa'"
          >
            GoWA Integration
            <span v-if="goWaConfig.enabled === '1' || goWaConfig.enabled === true || goWaConfig.enabled === 'true'" class="w-2 h-2 rounded-full bg-green-500 inline-block" />
          </button>
        </div>

        <div v-if="loading" class="py-8 text-center text-sm text-secondary-500 dark:text-secondary-400">
          Loading settings...
        </div>

        <template v-else>
          <!-- Tab 1: Manual Group Links -->
          <div v-show="activeTab === 'manual'">
            <MemberReachableConfigFields v-model="memberReachableConfig" />
          </div>

          <!-- Tab 2: GoWA Integration -->
          <div v-show="activeTab === 'gowa'">
            <GoWaConfigFields v-model="goWaConfig" />
          </div>

          <!-- Save Button -->
          <div class="pt-4 border-t border-secondary-200 dark:border-secondary-700 flex items-center justify-end">
            <button
              type="button"
              class="px-5 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium disabled:opacity-50 transition-colors"
              :disabled="saving"
              @click="saveAllSettings"
            >
              {{ saving ? 'Saving...' : 'Save Settings' }}
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
import GoWaConfigFields from '../components/settings/GoWaConfigFields.vue';
import { apiRequest } from '../composables/useApiClient';

const activeTab = ref('manual');
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

const goWaConfig = ref({
    enabled: '0',
    url: '',
    api_key: '',
    session_id: '',
    groups: [],
});

function parseJson(raw, fallback = {}) {
    try {
        return JSON.parse(raw || '{}') || fallback;
    } catch {
        return fallback;
    }
}

function parseMemberReachableConfig(raw) {
    const config = parseJson(raw, {});
    return {
        member_login_url: config.member_login_url || '',
        whatsapp_group_url: config.whatsapp_group_url || '',
        whatsapp_groups: Array.isArray(config.whatsapp_groups) ? config.whatsapp_groups : [],
    };
}

function parseGoWaGroups(raw) {
    try {
        const parsed = JSON.parse(raw || '[]');
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
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

function serializeGoWaGroups() {
    return JSON.stringify(
        Array.isArray(goWaConfig.value.groups) ? goWaConfig.value.groups : []
    );
}

async function load() {
    loading.value = true;
    loadError.value = '';
    try {
        const response = await apiRequest('/api/settings/configuration');
        const cfg = response.data || {};
        memberReachableConfig.value = parseMemberReachableConfig(cfg['general.member_notifications']);

        goWaConfig.value = {
            enabled: cfg['general.gowa_enabled'] || cfg['general.openwa_enabled'] || '0',
            url: cfg['general.gowa_url'] || cfg['general.openwa_url'] || '',
            api_key: cfg['general.gowa_api_key'] || cfg['general.openwa_api_key'] || '',
            session_id: cfg['general.gowa_session_id'] || cfg['general.openwa_session_id'] || '',
            groups: parseGoWaGroups(cfg['general.gowa_groups'] || cfg['general.openwa_groups']),
        };
    } catch {
        loadError.value = 'Failed to load member reachable settings.';
    } finally {
        loading.value = false;
    }
}

async function saveAllSettings() {
    saving.value = true;
    saveError.value = '';
    successMessage.value = '';
    try {
        await apiRequest('/api/settings/configuration', {
            method: 'put',
            data: {
                'general.member_notifications': serializeMemberNotificationConfig(),
                'general.gowa_enabled': goWaConfig.value.enabled === '1' || goWaConfig.value.enabled === true || goWaConfig.value.enabled === 'true' ? '1' : '0',
                'general.gowa_url': goWaConfig.value.url || '',
                'general.gowa_api_key': goWaConfig.value.api_key || '',
                'general.gowa_session_id': goWaConfig.value.session_id || '',
                'general.gowa_groups': serializeGoWaGroups(),
            },
        });
        successMessage.value = 'Settings saved successfully.';
        setTimeout(() => { successMessage.value = ''; }, 3000);
    } catch (error) {
        saveError.value = error?.response?.data?.message || error?.message || 'Failed to save settings.';
    } finally {
        saving.value = false;
    }
}

onMounted(() => {
    load();
});
</script>
