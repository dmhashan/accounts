<template>
  <div class="space-y-6">
    <!-- Enable/Disable Switch -->
    <div class="flex items-center justify-between rounded-xl border border-secondary-200 dark:border-secondary-700 p-4 bg-secondary-50/50 dark:bg-secondary-900/30">
      <div>
        <h4 class="text-sm font-semibold text-secondary-900 dark:text-white">
          Enable GoWA Integration
        </h4>
        <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">
          Connect to a GoWA (Go WhatsApp Web Multi-Device) server to automate WhatsApp group memberships.
        </p>
      </div>

      <AppFormSwitch
        id="gowa-enabled-switch"
        :model-value="localConfig.enabled === '1' || localConfig.enabled === true || localConfig.enabled === 'true'"
        true-label="Enabled"
        false-label="Disabled"
        @update:model-value="val => { localConfig.enabled = val ? '1' : '0'; emitChange(); }"
      />
    </div>

    <!-- Active Configurations -->
    <div v-if="localConfig.enabled === '1' || localConfig.enabled === true || localConfig.enabled === 'true'" class="space-y-6">
      <!-- Server Settings Card -->
      <div class="rounded-xl border border-secondary-200 dark:border-secondary-700 p-4 space-y-4">
        <div class="flex items-center justify-between">
          <h4 class="text-sm font-semibold text-secondary-900 dark:text-white flex items-center gap-2">
            <Radio class="w-4 h-4 text-primary-600 dark:text-primary-400" />
            GoWA Server Configuration
          </h4>

          <button
            type="button"
            class="inline-flex items-center justify-center gap-2 px-3 py-1.5 rounded-lg border border-secondary-300 dark:border-secondary-600 text-xs font-medium text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-800 transition-colors disabled:opacity-50"
            :disabled="testingConnection"
            @click="testConnection"
          >
            <RefreshCw v-if="testingConnection" class="w-3.5 h-3.5 animate-spin" />
            <Plug v-else class="w-3.5 h-3.5" />
            {{ testingConnection ? 'Testing...' : 'Test Connection' }}
          </button>
        </div>

        <div v-if="connectionStatus" class="rounded-lg p-3 text-xs flex items-center justify-between" :class="connectionStatus.success ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300'">
          <span>{{ connectionStatus.message }}</span>
          <CheckCircle2 v-if="connectionStatus.success" class="w-4 h-4 text-green-600 dark:text-green-400 shrink-0" />
          <AlertCircle v-else class="w-4 h-4 text-red-600 dark:text-red-400 shrink-0" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <AppFormField label="GoWA Server URL" for-id="gowa-url" required>
            <AppFormInput
              id="gowa-url"
              v-model="localConfig.url"
              type="url"
              placeholder="http://76.13.212.71:32769"
              maxlength="500"
              @input="emitChange"
            />
          </AppFormField>

          <AppFormField label="API Key / Auth Token" for-id="gowa-api-key" optional>
            <AppFormInput
              id="gowa-api-key"
              v-model="localConfig.api_key"
              type="password"
              placeholder="Optional Basic Auth / Token"
              maxlength="255"
              @input="emitChange"
            />
          </AppFormField>

          <AppFormField label="Device ID / Session ID" for-id="gowa-session-id" optional>
            <AppFormInput
              id="gowa-session-id"
              v-model="localConfig.session_id"
              type="text"
              placeholder="Optional Device ID (X-Device-Id)"
              maxlength="255"
              @input="emitChange"
            />
          </AppFormField>
        </div>
      </div>

      <!-- Rule-Based WhatsApp Groups Card -->
      <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div>
            <h4 class="text-sm font-semibold text-secondary-900 dark:text-white">
              Rule-Based GoWA Groups
            </h4>
            <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">
              Map GoWA WhatsApp Group JIDs to system member criteria for bulk synchronization.
            </p>
          </div>

          <button
            type="button"
            class="inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg border border-secondary-300 dark:border-secondary-600 text-sm text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-800 transition-colors"
            @click="addGroup"
          >
            <Plus class="w-4 h-4" />
            Add GoWA Group
          </button>
        </div>

        <div v-if="localConfig.groups.length === 0" class="rounded-lg border border-dashed border-secondary-300 dark:border-secondary-700 p-4 text-sm text-secondary-500 dark:text-secondary-400 text-center">
          No GoWA rule-based WhatsApp groups configured yet.
        </div>

        <div
          v-for="(group, groupIndex) in localConfig.groups"
          :key="group.id"
          class="rounded-xl border border-secondary-200 dark:border-secondary-700 p-4 space-y-4 bg-white dark:bg-secondary-900"
        >
          <!-- Group ID Input & Remove Button -->
          <div class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-3 items-end">
            <AppFormField label="GoWA WhatsApp Group ID / JID" :for-id="`gowa-group-id-${group.id}`" required>
              <AppFormInput
                :id="`gowa-group-id-${group.id}`"
                v-model="group.group_id"
                type="text"
                placeholder="120363023456789012@g.us"
                maxlength="255"
                @input="emitChange"
              />
            </AppFormField>

            <button
              type="button"
              class="inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg border border-red-200 dark:border-red-800 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
              @click="removeGroup(groupIndex)"
            >
              <Trash2 class="w-4 h-4" />
              Remove Group
            </button>
          </div>

          <!-- Rules List -->
          <div class="space-y-3 pl-2 border-l-2 border-primary-500/30">
            <p class="text-xs font-semibold text-secondary-700 dark:text-secondary-300 uppercase tracking-wider">
              Member Filter Rules
            </p>

            <div
              v-for="(rule, ruleIndex) in group.rules"
              :key="rule.id"
              class="grid grid-cols-1 md:grid-cols-[120px_1fr_1fr_auto] gap-3 items-end"
            >
              <AppFormField
                :label="ruleIndex === 0 ? 'Join' : 'AND / OR'"
                :for-id="`gowa-rule-boolean-${rule.id}`"
              >
                <AppFormSelect
                  :id="`gowa-rule-boolean-${rule.id}`"
                  v-model="rule.boolean"
                  :disabled="ruleIndex === 0"
                  @change="emitChange"
                >
                  <option value="and">
                    AND
                  </option>
                  <option value="or">
                    OR
                  </option>
                </AppFormSelect>
              </AppFormField>

              <AppFormField label="Member Column" :for-id="`gowa-rule-field-${rule.id}`">
                <AppFormSelect
                  :id="`gowa-rule-field-${rule.id}`"
                  v-model="rule.field"
                  @change="onFieldChange(rule)"
                >
                  <option v-for="column in memberColumns" :key="column.value" :value="column.value">
                    {{ column.label }}
                  </option>
                </AppFormSelect>
              </AppFormField>

              <AppFormField label="Column Value" :for-id="`gowa-rule-value-${rule.id}`">
                <AppFormSelect
                  v-if="rule.field === 'gender'"
                  :id="`gowa-rule-value-${rule.id}`"
                  v-model="rule.value"
                  @change="emitChange"
                >
                  <option value="">
                    Select gender
                  </option>
                  <option value="male">
                    Male
                  </option>
                  <option value="female">
                    Female
                  </option>
                  <option value="other">
                    Other
                  </option>
                </AppFormSelect>

                <AppFormSelect
                  v-else-if="rule.field === 'payment_plan_id'"
                  :id="`gowa-rule-value-${rule.id}`"
                  v-model="rule.value"
                  :disabled="loadingPlans"
                  @change="emitChange"
                >
                  <option value="">
                    {{ loadingPlans ? 'Loading plans...' : 'Select payment plan' }}
                  </option>
                  <option v-for="plan in paymentPlans" :key="plan.id" :value="String(plan.id)">
                    {{ plan.name }}
                  </option>
                </AppFormSelect>

                <AppFormSwitch
                  v-else-if="['is_active', 'is_verified', 'is_temp'].includes(rule.field)"
                  :id="`gowa-rule-value-${rule.id}`"
                  :model-value="rule.value === '1' || rule.value === 'true' || rule.value === true"
                  :true-label="getSwitchLabels(rule.field).trueLabel"
                  :false-label="getSwitchLabels(rule.field).falseLabel"
                  @update:model-value="val => { rule.value = val ? '1' : '0'; emitChange(); }"
                />

                <AppFormInput
                  v-else
                  :id="`gowa-rule-value-${rule.id}`"
                  v-model="rule.value"
                  type="text"
                  placeholder="Enter value"
                  maxlength="255"
                  @input="emitChange"
                />
              </AppFormField>

              <button
                type="button"
                class="inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg border border-secondary-300 dark:border-secondary-600 text-sm text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-800 transition-colors"
                @click="removeRule(groupIndex, ruleIndex)"
              >
                <Trash2 class="w-4 h-4" />
                Rule
              </button>
            </div>

            <button
              type="button"
              class="inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg border border-secondary-300 dark:border-secondary-600 text-sm text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-800 transition-colors"
              @click="addRule(groupIndex)"
            >
              <Plus class="w-4 h-4" />
              Add Rule
            </button>
          </div>

          <!-- Comparison & Bulk Sync Card -->
          <div class="rounded-lg border border-secondary-200 dark:border-secondary-700 p-4 bg-secondary-50/50 dark:bg-secondary-800/30 space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
              <div>
                <h5 class="text-xs font-semibold text-secondary-900 dark:text-white uppercase tracking-wider">
                  Member Comparison &amp; Bulk Actions
                </h5>
                <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">
                  Compare system members matching rules against actual GoWA group participants.
                </p>
              </div>

              <button
                type="button"
                class="inline-flex items-center justify-center gap-2 px-3 py-1.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-xs font-semibold disabled:opacity-50 transition-colors"
                :disabled="group.comparing || !group.group_id"
                @click="compareGroup(group)"
              >
                <RefreshCw v-if="group.comparing" class="w-3.5 h-3.5 animate-spin" />
                <Users v-else class="w-3.5 h-3.5" />
                {{ group.comparing ? 'Comparing...' : 'Compare Members' }}
              </button>
            </div>

            <div v-if="group.compareError" class="text-xs text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-2.5">
              {{ group.compareError }}
            </div>

            <!-- Comparison Results Summary -->
            <div v-if="group.comparison" class="space-y-3 mt-2">
              <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="rounded-lg border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 p-3 text-center">
                  <span class="block text-xs text-secondary-500 dark:text-secondary-400 font-medium">Matching System</span>
                  <span class="text-lg font-bold text-secondary-900 dark:text-white">{{ group.comparison.matching_system_count }}</span>
                </div>

                <div class="rounded-lg border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 p-3 text-center">
                  <span class="block text-xs text-secondary-500 dark:text-secondary-400 font-medium">GoWA Group</span>
                  <span class="text-lg font-bold text-secondary-900 dark:text-white">{{ group.comparison.gowa_participants_count }}</span>
                </div>

                <div class="rounded-lg border border-green-200 dark:border-green-800 bg-green-50/50 dark:bg-green-900/20 p-3 text-center">
                  <span class="block text-xs text-green-700 dark:text-green-300 font-medium">To Add</span>
                  <span class="text-lg font-bold text-green-700 dark:text-green-300">+{{ group.comparison.to_add_count }}</span>
                </div>

                <div class="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50/50 dark:bg-amber-900/20 p-3 text-center">
                  <span class="block text-xs text-amber-700 dark:text-amber-300 font-medium">To Remove</span>
                  <span class="text-lg font-bold text-amber-700 dark:text-amber-300">-{{ group.comparison.to_remove_count }}</span>
                </div>
              </div>

              <!-- Action buttons -->
              <div class="flex flex-wrap items-center justify-end gap-3 pt-2">
                <button
                  type="button"
                  class="inline-flex items-center justify-center gap-2 px-3 py-1.5 rounded-lg bg-green-600 hover:bg-green-700 text-white text-xs font-medium disabled:opacity-50 transition-colors"
                  :disabled="group.syncing || group.comparison.to_add_count === 0"
                  @click="syncAction(group, 'add')"
                >
                  <UserPlus class="w-3.5 h-3.5" />
                  Bulk Add Missing ({{ group.comparison.to_add_count }})
                </button>

                <button
                  type="button"
                  class="inline-flex items-center justify-center gap-2 px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-medium disabled:opacity-50 transition-colors"
                  :disabled="group.syncing || group.comparison.to_remove_count === 0"
                  @click="syncAction(group, 'remove')"
                >
                  <UserMinus class="w-3.5 h-3.5" />
                  Bulk Remove Non-Matching ({{ group.comparison.to_remove_count }})
                </button>
              </div>

              <div v-if="group.syncResult" class="text-xs rounded-lg p-2.5" :class="group.syncResult.success ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300' : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300'">
                {{ group.syncResult.message }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import { Plus, Trash2, Plug, RefreshCw, Radio, CheckCircle2, AlertCircle, Users, UserPlus, UserMinus } from 'lucide-vue-next';
import AppFormField from '../forms/AppFormField.vue';
import AppFormInput from '../forms/AppFormInput.vue';
import AppFormSelect from '../forms/AppFormSelect.vue';
import AppFormSwitch from '../forms/AppFormSwitch.vue';
import { apiRequest } from '../../composables/useApiClient';

const props = defineProps({
    modelValue: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['update:modelValue']);

const memberColumns = [
    { value: 'gender', label: 'Gender' },
    { value: 'email', label: 'Email' },
    { value: 'payment_plan_id', label: 'Payment Plan' },
    { value: 'is_active', label: 'Active Status' },
    { value: 'is_verified', label: 'Verified Status' },
    { value: 'is_temp', label: 'Temporary Status' },
    { value: 'address', label: 'Address' },
];

const paymentPlans = ref([]);
const loadingPlans = ref(false);
const testingConnection = ref(false);
const connectionStatus = ref(null);

async function fetchPaymentPlans() {
    loadingPlans.value = true;
    try {
        const response = await apiRequest('/api/payment-plans');
        paymentPlans.value = response.data || [];
    } catch {
        paymentPlans.value = [];
    } finally {
        loadingPlans.value = false;
    }
}

let nextId = 1;
let lastEmittedSnapshot = '';
const localConfig = reactive(normalizeConfig(props.modelValue));

watch(
    () => props.modelValue,
    (value) => {
        const incomingSnapshot = snapshotConfig(value);

        if (incomingSnapshot === lastEmittedSnapshot) {
            return;
        }

        Object.assign(localConfig, normalizeConfig(value));
    },
    { deep: true },
);

onMounted(() => {
    fetchPaymentPlans();
});

async function testConnection() {
    if (!localConfig.url) {
        connectionStatus.value = { success: false, message: 'Please enter GoWA Server URL first.' };
        return;
    }

    testingConnection.value = true;
    connectionStatus.value = null;

    try {
        const response = await apiRequest('/api/settings/gowa/test-connection', {
            method: 'post',
            data: {
                url: localConfig.url,
                api_key: localConfig.api_key,
                session_id: localConfig.session_id,
            },
        });
        connectionStatus.value = { success: true, message: response.message || 'Connected successfully to GoWA.' };
    } catch (error) {
        connectionStatus.value = {
            success: false,
            message: error?.response?.data?.message || error?.message || 'Failed to connect to GoWA server.',
        };
    } finally {
        testingConnection.value = false;
    }
}

async function compareGroup(group) {
    if (!group.group_id) return;

    group.comparing = true;
    group.compareError = null;
    group.syncResult = null;

    try {
        const response = await apiRequest('/api/settings/gowa/groups/compare', {
            method: 'post',
            data: {
                url: localConfig.url,
                api_key: localConfig.api_key,
                session_id: localConfig.session_id,
                group: {
                    group_id: group.group_id,
                    rules: group.rules.map((rule, index) => ({
                        boolean: index === 0 ? 'and' : rule.boolean,
                        field: rule.field,
                        value: rule.value,
                    })),
                },
            },
        });
        group.comparison = response;
    } catch (error) {
        group.compareError = error?.response?.data?.message || error?.message || 'Failed to compare members.';
    } finally {
        group.comparing = false;
    }
}

async function syncAction(group, action) {
    if (!group.comparison || !group.group_id) return;

    const phones = action === 'add'
        ? group.comparison.to_add.map(m => m.phone).filter(Boolean)
        : group.comparison.to_remove.map(m => m.raw_phone).filter(Boolean);

    if (phones.length === 0) return;

    group.syncing = true;
    group.syncResult = null;

    try {
        const response = await apiRequest('/api/settings/gowa/groups/sync', {
            method: 'post',
            data: {
                url: localConfig.url,
                api_key: localConfig.api_key,
                session_id: localConfig.session_id,
                group_id: group.group_id,
                action,
                phones,
            },
        });

        const successCount = action === 'add' ? (response.added?.length || 0) : (response.removed?.length || 0);
        group.syncResult = {
            success: response.success,
            message: `Bulk ${action} completed: ${successCount} member(s) processed.`,
        };

        // Re-run comparison to refresh numbers
        await compareGroup(group);
    } catch (error) {
        group.syncResult = {
            success: false,
            message: error?.response?.data?.message || error?.message || `Failed to ${action} members.`,
        };
    } finally {
        group.syncing = false;
    }
}

function getSwitchLabels(field) {
    switch (field) {
        case 'is_active':
            return { trueLabel: 'Active', falseLabel: 'Inactive' };
        case 'is_verified':
            return { trueLabel: 'Verified', falseLabel: 'Unverified' };
        case 'is_temp':
            return { trueLabel: 'Temporary', falseLabel: 'Permanent' };
        default:
            return { trueLabel: 'Yes', falseLabel: 'No' };
    }
}

function onFieldChange(rule) {
    if (rule.field === 'gender') {
        if (!['male', 'female', 'other'].includes(rule.value)) {
            rule.value = 'male';
        }
    } else if (rule.field === 'payment_plan_id') {
        if (!paymentPlans.value.some(p => String(p.id) === String(rule.value))) {
            rule.value = paymentPlans.value.length > 0 ? String(paymentPlans.value[0].id) : '';
        }
    } else if (['is_active', 'is_verified', 'is_temp'].includes(rule.field)) {
        if (!['1', '0'].includes(String(rule.value))) {
            rule.value = '1';
        }
    }
    emitChange();
}

function normalizeConfig(config) {
    return {
        enabled: config?.enabled === '1' || config?.enabled === true || config?.enabled === 'true' ? '1' : '0',
        url: config?.url || '',
        api_key: config?.api_key || '',
        session_id: config?.session_id || '',
        groups: Array.isArray(config?.groups)
            ? config.groups.map(normalizeGroup)
            : [],
    };
}

function normalizeGroup(group) {
    return {
        id: nextId++,
        group_id: group?.group_id || '',
        rules: Array.isArray(group?.rules) && group.rules.length > 0
            ? group.rules.map((rule, index) => normalizeRule(rule, index))
            : [normalizeRule({}, 0)],
        comparing: false,
        syncing: false,
        comparison: null,
        compareError: null,
        syncResult: null,
    };
}

function normalizeRule(rule, index) {
    return {
        id: nextId++,
        boolean: index === 0 ? 'and' : (rule?.boolean === 'or' ? 'or' : 'and'),
        field: rule?.field || 'gender',
        value: rule?.value ?? '',
    };
}

function addGroup() {
    localConfig.groups.push(normalizeGroup({}));
    emitChange();
}

function removeGroup(index) {
    localConfig.groups.splice(index, 1);
    emitChange();
}

function addRule(groupIndex) {
    localConfig.groups[groupIndex].rules.push(normalizeRule({ boolean: 'and' }, 1));
    emitChange();
}

function removeRule(groupIndex, ruleIndex) {
    const rules = localConfig.groups[groupIndex].rules;
    rules.splice(ruleIndex, 1);

    if (rules.length === 0) {
        rules.push(normalizeRule({}, 0));
    }

    rules[0].boolean = 'and';
    emitChange();
}

function emitChange() {
    const payload = {
        enabled: localConfig.enabled === '1' ? '1' : '0',
        url: localConfig.url,
        api_key: localConfig.api_key,
        session_id: localConfig.session_id,
        groups: localConfig.groups.map(group => ({
            group_id: group.group_id,
            rules: group.rules.map((rule, index) => ({
                boolean: index === 0 ? 'and' : rule.boolean,
                field: rule.field,
                operator: 'equals',
                value: rule.value,
            })),
        })),
    };

    lastEmittedSnapshot = snapshotConfig(payload);
    emit('update:modelValue', payload);
}

function snapshotConfig(config) {
    return JSON.stringify({
        enabled: config?.enabled === '1' || config?.enabled === true || config?.enabled === 'true' ? '1' : '0',
        url: config?.url || '',
        api_key: config?.api_key || '',
        session_id: config?.session_id || '',
        groups: Array.isArray(config?.groups)
            ? config.groups.map(group => ({
                group_id: group?.group_id || '',
                rules: Array.isArray(group?.rules)
                    ? group.rules.map((rule, index) => ({
                        boolean: index === 0 ? 'and' : (rule?.boolean === 'or' ? 'or' : 'and'),
                        field: rule?.field || 'gender',
                        operator: rule?.operator || 'equals',
                        value: rule?.value ?? '',
                    }))
                    : [],
            }))
            : [],
    });
}
</script>
