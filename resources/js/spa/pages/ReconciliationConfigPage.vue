<template>
    <section class="app-page-frame">
        <AppPageHeader :show-back="true" />

        <div class="app-page-scroll space-y-6">
            <div v-if="errorMessage" class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
                {{ errorMessage }}
            </div>

            <div v-if="loading" class="app-surface rounded-2xl p-8 text-center text-sm text-secondary-500 dark:text-secondary-400">
                Loading…
            </div>

            <template v-else>
                <div class="app-surface rounded-2xl p-5 md:p-6 space-y-2">
                    <h2 class="text-sm font-semibold text-secondary-900 dark:text-white">Reconciliation Form Settings</h2>
                    <p class="text-sm text-secondary-500 dark:text-secondary-400">
                        For each role, choose which accounts employees will be asked to count during reconciliation. All products are always included.
                    </p>
                </div>

                <!-- Role tabs -->
                <div class="inline-flex flex-wrap rounded-xl app-surface-soft p-1 gap-1">
                    <button
                        v-for="role in roles"
                        :key="role.id"
                        type="button"
                        class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors"
                        :class="activeRoleId === role.id
                            ? 'bg-gradient-to-r from-primary-500 to-primary-700 text-white shadow-sm'
                            : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-200 dark:hover:bg-secondary-700'"
                        @click="selectRole(role.id)"
                    >
                        {{ role.name }}
                    </button>
                </div>

                <div v-if="activeRoleId" class="space-y-4">
                    <!-- Accounts -->
                    <div class="app-surface rounded-2xl p-5 md:p-6 space-y-4">
                        <h3 class="text-sm font-semibold text-secondary-700 dark:text-secondary-300 uppercase tracking-wide">Accounts</h3>
                        <div v-if="accounts.length === 0" class="text-sm text-secondary-500 dark:text-secondary-400">No accounts created yet.</div>
                        <div v-else class="space-y-2">
                            <label
                                v-for="account in accounts"
                                :key="account.id"
                                class="flex items-center gap-3 p-3 rounded-lg border border-secondary-200 dark:border-secondary-700 hover:bg-secondary-50 dark:hover:bg-secondary-800/40 cursor-pointer transition-colors"
                            >
                                <input
                                    type="checkbox"
                                    class="w-4 h-4 text-primary-600 rounded border-secondary-300 dark:border-secondary-600"
                                    :checked="isEnabled('account', account.id)"
                                    @change="toggle('account', account.id, $event.target.checked)"
                                />
                                <span class="text-sm text-secondary-800 dark:text-secondary-200">{{ account.name }}</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="button"
                            class="px-5 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium"
                            :disabled="saving"
                            @click="save"
                        >
                            {{ saving ? 'Saving…' : 'Save Configuration' }}
                        </button>
                    </div>

                    <p v-if="savedMessage" class="text-sm text-green-600 dark:text-green-400 text-right">{{ savedMessage }}</p>
                </div>
            </template>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { apiRequest } from '../composables/useApiClient';
import AppPageHeader from '../components/AppPageHeader.vue';

const loading      = ref(true);
const saving       = ref(false);
const errorMessage = ref('');
const savedMessage = ref('');

const roles    = ref([]);
const accounts = ref([]);

// { [roleId]: { account: Set<id> } }
const selections = ref({});

const activeRoleId = ref(null);

function selectRole(roleId) {
    activeRoleId.value = roleId;
}

function isEnabled(type, refId) {
    const roleSelections = selections.value[activeRoleId.value];
    if (!roleSelections) return false;
    return roleSelections[type]?.has(refId) ?? false;
}

function toggle(type, refId, checked) {
    const roleId = activeRoleId.value;
    if (!selections.value[roleId]) {
        selections.value[roleId] = { account: new Set() };
    }
    if (checked) {
        selections.value[roleId][type].add(refId);
    } else {
        selections.value[roleId][type].delete(refId);
    }
}

async function loadConfig() {
    loading.value = true;
    const data = await apiRequest('/api/reconciliation/config');
    roles.value    = data?.roles    ?? [];
    accounts.value = data?.accounts ?? [];

    // Initialise selections from existing configs
    const configs = data?.configs ?? {};
    roles.value.forEach(role => {
        const roleConfigs = configs[role.id] ?? [];
        selections.value[role.id] = { account: new Set() };
        roleConfigs.forEach(cfg => {
            if (cfg.is_active && cfg.type === 'account') {
                selections.value[role.id].account.add(cfg.reference_id);
            }
        });
    });

    if (roles.value.length) activeRoleId.value = roles.value[0].id;
    loading.value = false;
}

async function save() {
    saving.value       = true;
    savedMessage.value = '';
    errorMessage.value = '';

    const roleId = activeRoleId.value;
    const sel    = selections.value[roleId] ?? { account: new Set() };

    const items = [
        ...accounts.value.map(a => ({ type: 'account', reference_id: a.id, is_active: sel.account.has(a.id) })),
    ];

    try {
        await apiRequest('/api/reconciliation/config', {
            method: 'POST',
            data:   { role_id: roleId, items },
        });
        savedMessage.value = 'Saved!';
        setTimeout(() => { savedMessage.value = ''; }, 3000);
    } catch (err) {
        const errData = err?.response?.data;
        if (errData?.errors) {
            const messages = Object.values(errData.errors).flat();
            errorMessage.value = messages.join(' ');
        } else {
            errorMessage.value = errData?.message ?? 'Save failed.';
        }
    } finally {
        saving.value = false;
    }
}

onMounted(loadConfig);
</script>
