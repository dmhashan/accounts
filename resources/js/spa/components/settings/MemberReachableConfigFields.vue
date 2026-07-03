<template>
  <div class="space-y-5">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <AppFormField label="Member Portal URL" for-id="member-reachable-login-url" optional>
        <AppFormInput
          id="member-reachable-login-url"
          v-model="localConfig.member_login_url"
          type="url"
          placeholder="https://members.example.com/profile"
          maxlength="500"
          @input="emitChange"
        />
      </AppFormField>

      <AppFormField label="Default WhatsApp Group URL" for-id="member-reachable-default-whatsapp-url" optional>
        <AppFormInput
          id="member-reachable-default-whatsapp-url"
          v-model="localConfig.whatsapp_group_url"
          type="url"
          placeholder="https://chat.whatsapp.com/..."
          maxlength="500"
          @input="emitChange"
        />
      </AppFormField>
    </div>

    <div class="space-y-3">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
          <p class="text-sm font-medium text-secondary-900 dark:text-white">
            Rule-Based WhatsApp Groups
          </p>
        </div>

        <button
          type="button"
          class="inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg border border-secondary-300 dark:border-secondary-600 text-sm text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-800 transition-colors"
          @click="addGroup"
        >
          <Plus class="w-4 h-4" />
          Add Group
        </button>
      </div>

      <div v-if="localConfig.whatsapp_groups.length === 0" class="rounded-lg border border-dashed border-secondary-300 dark:border-secondary-700 p-4 text-sm text-secondary-500 dark:text-secondary-400">
        No conditional WhatsApp groups configured.
      </div>

      <div
        v-for="(group, groupIndex) in localConfig.whatsapp_groups"
        :key="group.id"
        class="rounded-lg border border-secondary-200 dark:border-secondary-700 p-4 space-y-4"
      >
        <div class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-3 items-end">
          <AppFormField label="WhatsApp URL" :for-id="`member-reachable-group-url-${group.id}`">
            <AppFormInput
              :id="`member-reachable-group-url-${group.id}`"
              v-model="group.url"
              type="url"
              placeholder="https://chat.whatsapp.com/..."
              maxlength="500"
              @input="emitChange"
            />
          </AppFormField>

          <button
            type="button"
            class="inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg border border-red-200 dark:border-red-800 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
            @click="removeGroup(groupIndex)"
          >
            <Trash2 class="w-4 h-4" />
            Remove
          </button>
        </div>

        <div class="space-y-3">
          <div
            v-for="(rule, ruleIndex) in group.rules"
            :key="rule.id"
            class="grid grid-cols-1 md:grid-cols-[120px_1fr_1fr_auto] gap-3 items-end"
          >
            <AppFormField
              :label="ruleIndex === 0 ? 'Join' : 'AND / OR'"
              :for-id="`member-reachable-rule-boolean-${rule.id}`"
            >
              <AppFormSelect
                :id="`member-reachable-rule-boolean-${rule.id}`"
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

            <AppFormField label="Member Column" :for-id="`member-reachable-rule-field-${rule.id}`">
              <AppFormSelect
                :id="`member-reachable-rule-field-${rule.id}`"
                v-model="rule.field"
                @change="emitChange"
              >
                <option v-for="column in memberColumns" :key="column.value" :value="column.value">
                  {{ column.label }}
                </option>
              </AppFormSelect>
            </AppFormField>

            <AppFormField label="Column Value" :for-id="`member-reachable-rule-value-${rule.id}`">
              <AppFormInput
                :id="`member-reachable-rule-value-${rule.id}`"
                v-model="rule.value"
                type="text"
                placeholder="male"
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
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue';
import { Plus, Trash2 } from 'lucide-vue-next';
import AppFormField from '../forms/AppFormField.vue';
import AppFormInput from '../forms/AppFormInput.vue';
import AppFormSelect from '../forms/AppFormSelect.vue';

const props = defineProps({
    modelValue: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['update:modelValue']);

const memberColumns = [
    { value: 'gender', label: 'Gender' },
    { value: 'email', label: 'Email' },
    { value: 'payment_plan_id', label: 'Payment Plan ID' },
    { value: 'is_active', label: 'Active Status' },
    { value: 'is_verified', label: 'Verified Status' },
    { value: 'is_temp', label: 'Temporary Status' },
    { value: 'address', label: 'Address' },
];

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

function normalizeConfig(config) {
    return {
        member_login_url: config?.member_login_url || '',
        whatsapp_group_url: config?.whatsapp_group_url || '',
        whatsapp_groups: Array.isArray(config?.whatsapp_groups)
            ? config.whatsapp_groups.map(normalizeGroup)
            : [],
    };
}

function normalizeGroup(group) {
    return {
        id: nextId++,
        url: group?.url || '',
        rules: Array.isArray(group?.rules) && group.rules.length > 0
            ? group.rules.map((rule, index) => normalizeRule(rule, index))
            : [normalizeRule({}, 0)],
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
    localConfig.whatsapp_groups.push(normalizeGroup({}));
    emitChange();
}

function removeGroup(index) {
    localConfig.whatsapp_groups.splice(index, 1);
    emitChange();
}

function addRule(groupIndex) {
    localConfig.whatsapp_groups[groupIndex].rules.push(normalizeRule({ boolean: 'and' }, 1));
    emitChange();
}

function removeRule(groupIndex, ruleIndex) {
    const rules = localConfig.whatsapp_groups[groupIndex].rules;
    rules.splice(ruleIndex, 1);

    if (rules.length === 0) {
        rules.push(normalizeRule({}, 0));
    }

    rules[0].boolean = 'and';
    emitChange();
}

function emitChange() {
    const payload = {
        member_login_url: localConfig.member_login_url,
        whatsapp_group_url: localConfig.whatsapp_group_url,
        whatsapp_groups: localConfig.whatsapp_groups.map(group => ({
            url: group.url,
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
        member_login_url: config?.member_login_url || '',
        whatsapp_group_url: config?.whatsapp_group_url || '',
        whatsapp_groups: Array.isArray(config?.whatsapp_groups)
            ? config.whatsapp_groups.map(group => ({
                url: group?.url || '',
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
