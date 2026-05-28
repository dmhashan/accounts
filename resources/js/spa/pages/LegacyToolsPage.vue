<template>
  <section class="app-page-frame">
    <AppPageHeader>
      <template #cta-slot>
        <AppHeaderAction :icon="Terminal" label="Run Command" @click="openModal" />
      </template>
    </AppPageHeader>

    <!-- History -->
    <div class="app-page-scroll">
      <div class="app-surface rounded-2xl overflow-hidden">
        <div v-if="logsLoading" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">
          Loading…
        </div>

        <div v-else-if="logs.length === 0" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">
          No runs recorded yet.
        </div>

        <template v-else>
          <!-- Mobile -->
          <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
            <div
              v-for="log in logs"
              :key="log.id"
              class="p-4 space-y-2 cursor-pointer hover:bg-secondary-50 dark:hover:bg-secondary-800/40 transition-colors"
              @click="toggleLog(log.id)"
            >
              <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2 min-w-0">
                  <span class="shrink-0 w-2 h-2 rounded-full" :class="log.success === null ? 'bg-blue-400 animate-pulse' : log.success ? 'bg-green-500' : 'bg-red-500'" />
                  <p class="text-sm font-medium text-secondary-900 dark:text-white truncate">
                    {{ commandLabel(log.command) }}
                  </p>
                </div>
                <span class="text-xs font-semibold shrink-0" :class="log.success === null ? 'text-blue-500 dark:text-blue-400' : log.success ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">{{ log.success === null ? 'RUNNING' : log.success ? 'OK' : 'FAIL' }}</span>
              </div>
              <div class="flex items-center justify-between text-xs text-secondary-500 dark:text-secondary-400">
                <span>{{ log.user || '—' }}</span>
                <span>{{ log.created_at }}</span>
              </div>
              <div v-if="expandedLog === log.id && log.output" class="mt-1">
                <pre class="text-xs bg-secondary-50 dark:bg-secondary-900 border border-secondary-200 dark:border-secondary-700 rounded-xl p-3 overflow-auto max-h-48 whitespace-pre-wrap break-all">{{ log.output }}</pre>
              </div>
            </div>
          </div>

          <!-- Desktop -->
          <div class="hidden md:block app-table-scroll">
            <table class="w-full">
              <thead class="app-table-head-sticky bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Command
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Params
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Run By
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Date
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Status
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                <template v-for="log in logs" :key="log.id">
                  <tr class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50 cursor-pointer" @click="toggleLog(log.id)">
                    <td class="px-6 py-4">
                      <div class="flex items-center gap-2">
                        <span class="shrink-0 w-2 h-2 rounded-full" :class="log.success === null ? 'bg-blue-400 animate-pulse' : log.success ? 'bg-green-500' : 'bg-red-500'" />
                        <div>
                          <p class="text-sm font-medium text-secondary-900 dark:text-white">
                            {{ commandLabel(log.command) }}
                          </p>
                          <code class="text-xs text-secondary-400 dark:text-secondary-500">{{ log.command }}</code>
                        </div>
                      </div>
                    </td>
                    <td class="px-6 py-4">
                      <div class="flex flex-wrap gap-x-3 gap-y-0.5">
                        <span v-for="(val, key) in log.params" :key="key" class="text-xs text-secondary-500 dark:text-secondary-400">{{ key }}={{ val }}</span>
                      </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">
                      {{ log.user || '—' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">
                      {{ log.created_at }}
                    </td>
                    <td class="px-6 py-4">
                      <span class="px-2.5 py-1 text-xs font-semibold rounded-full" :class="log.success === null ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' : log.success ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'">{{ log.success === null ? 'RUNNING' : log.success ? 'OK' : 'FAIL' }}</span>
                    </td>
                  </tr>
                  <tr v-if="expandedLog === log.id && log.output">
                    <td colspan="5" class="px-6 pb-4">
                      <pre class="text-xs bg-secondary-50 dark:bg-secondary-900 border border-secondary-200 dark:border-secondary-700 rounded-xl p-4 overflow-auto max-h-64 whitespace-pre-wrap break-all">{{ log.output }}</pre>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </template>
      </div>
    </div>

    <!-- Run Command Modal -->
    <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4">
      <div class="absolute inset-0 bg-black/45" @click="closeModal" />

      <div class="relative z-10 w-full sm:max-w-lg rounded-t-2xl sm:rounded-2xl app-surface shadow-xl overflow-hidden flex flex-col max-h-[90dvh]">
        <!-- Modal header -->
        <div class="flex items-center justify-between px-5 py-4 border-b border-secondary-200 dark:border-secondary-700 shrink-0">
          <h3 class="text-sm font-semibold text-secondary-900 dark:text-white">
            Run Command
          </h3>
          <button type="button" class="text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-200 transition-colors" @click="closeModal">
            <X class="w-5 h-5" :stroke-width="2" />
          </button>
        </div>

        <!-- Modal body -->
        <div class="overflow-y-auto p-5 space-y-5">
          <!-- Command selector -->
          <AppFormField label="Command" required>
            <div class="grid grid-cols-1 gap-2">
              <label
                v-for="cmd in commands"
                :key="cmd.key"
                class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-colors"
                :class="selectedCommand === cmd.key
                  ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
                  : 'border-secondary-200 dark:border-secondary-700 hover:border-secondary-300 dark:hover:border-secondary-600'"
              >
                <input
                  v-model="selectedCommand"
                  type="radio"
                  :value="cmd.key"
                  class="mt-0.5 accent-primary-600"
                />
                <div class="min-w-0">
                  <p class="text-sm font-medium text-secondary-900 dark:text-white">{{ cmd.label }}</p>
                  <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">{{ cmd.description }}</p>
                </div>
              </label>
            </div>
          </AppFormField>

          <!-- Form for selected command -->
          <template v-if="selectedCommand">
            <AppFormField label="Access Token" required>
              <AppFormTextarea
                v-model="form.access_token"
                rows="2"
                placeholder="Paste bearer token from legacy API"
                class="font-mono text-xs"
              />
            </AppFormField>

            <!-- Attendance: both dates required -->
            <template v-if="selectedCommand === 'legacy:sync-attendance'">
              <div class="grid grid-cols-2 gap-4">
                <AppFormField label="Date Start" required>
                  <AppFormInput v-model="form.date_start" type="date" />
                </AppFormField>
                <AppFormField label="Date End" required>
                  <AppFormInput v-model="form.date_end" type="date" />
                </AppFormField>
              </div>
            </template>

            <!-- Payments: dates optional + extra params -->
            <template v-else-if="selectedCommand === 'legacy:sync-payments'">
              <div class="grid grid-cols-2 gap-4">
                <AppFormField label="Date Start" optional>
                  <AppFormInput v-model="form.date_start" type="date" />
                </AppFormField>
                <AppFormField label="Date End" optional>
                  <AppFormInput v-model="form.date_end" type="date" />
                </AppFormField>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <AppFormField label="Account Name" optional>
                  <AppFormInput v-model="form.account_name" type="text" placeholder="Cash Account" />
                </AppFormField>
                <AppFormField label="Page Size" optional>
                  <AppFormInput
                    v-model.number="form.page_size"
                    type="number"
                    min="1"
                    max="500"
                    placeholder="100"
                  />
                </AppFormField>
              </div>
            </template>
            <!-- sync-members: no date options -->
          </template>
        </div>

        <!-- Modal footer -->
        <div class="px-5 py-4 border-t border-secondary-200 dark:border-secondary-700 flex items-center justify-between gap-3 shrink-0">
          <span v-if="runState === 'queued' || runState === 'polling'" class="text-sm text-blue-600 dark:text-blue-400 font-medium flex items-center gap-1.5">
            <Loader2 class="animate-spin h-3.5 w-3.5" />
            Running in background…
          </span>
          <span v-else-if="runState === 'success'" class="text-sm text-green-600 dark:text-green-400 font-medium">Completed successfully</span>
          <span v-else-if="runState === 'error'" class="text-sm text-red-600 dark:text-red-400 font-medium">Run failed</span>
          <span v-else class="" />
          <div class="flex items-center gap-2">
            <button type="button" class="px-4 py-2 text-sm border border-secondary-300 dark:border-secondary-700 rounded-lg text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-800 transition-colors" @click="closeModal">
              Close
            </button>
            <button
              type="button"
              class="px-4 py-2 bg-primary-600 hover:bg-primary-700 disabled:opacity-50 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2"
              :disabled="!selectedCommand || runState === 'queued' || runState === 'polling' || (selectedCommand === 'legacy:sync-attendance' && (!form.date_start || !form.date_end))"
              @click="run"
            >
              {{ (runState === 'queued' || runState === 'polling') ? 'Queued…' : 'Run' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import { Terminal, X, Loader2 } from 'lucide-vue-next';
import { apiRequest } from '../composables/useApiClient.js';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppHeaderAction from '../components/AppHeaderAction.vue';
import AppFormField from '../components/forms/AppFormField.vue';
import AppFormInput from '../components/forms/AppFormInput.vue';
import AppFormTextarea from '../components/forms/AppFormTextarea.vue';

const commands = [
    {
        key: 'legacy:sync-members',
        label: 'Nanosoft GymHive Member Sync',
        description: 'Import member profiles from the legacy GymHive API into this system.',
    },
    {
        key: 'legacy:sync-attendance',
        label: 'Nanosoft GymHive Attendance Sync',
        description: 'Import daily attendance records from the legacy GymHive API.',
    },
    {
        key: 'legacy:sync-payments',
        label: 'Nanosoft GymHive Payments Sync',
        description: 'Import membership payment history and create payment plan records.',
    },
];

const commandLabel = (key) => commands.find(c => c.key === key)?.label ?? key;

const today = new Date().toISOString().slice(0, 10);

const makeForm = () => ({
    access_token: '',
    date_start: '',
    date_end: today,
    account_name: 'Cash Account',
    page_size: 100,
});

// Modal state
const modalOpen       = ref(false);
const selectedCommand = ref(null);
const form            = reactive(makeForm());
const runState        = ref('idle'); // idle | queued | polling | success | error

// History state
const logs        = ref([]);
const logsLoading = ref(false);
const expandedLog = ref(null);

// Polling
let pollInterval  = null;
let pendingLogId  = null;

function startPolling(logId) {
    pendingLogId = logId;
    runState.value = 'polling';
    pollInterval = setInterval(async () => {
        await loadLogs();
        const entry = logs.value.find(l => l.id === pendingLogId);
        if (entry && entry.success !== null) {
            stopPolling();
            runState.value = entry.success ? 'success' : 'error';
            expandedLog.value = entry.id;
        }
    }, 3000);
}

function stopPolling() {
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
    pendingLogId = null;
}

onUnmounted(stopPolling);

function openModal() {
    Object.assign(form, makeForm());
    selectedCommand.value = null;
    runState.value  = 'idle';
    modalOpen.value = true;
}

function closeModal() {
    modalOpen.value = false;
}

watch(modalOpen, (open) => {
    if (open) {
        window.addEventListener('keydown', onWindowEscape);
    } else {
        window.removeEventListener('keydown', onWindowEscape);
    }
});

function onWindowEscape(e) {
    if (e.key === 'Escape') closeModal();
}

// Reset form fields (keep token) when command changes
watch(selectedCommand, () => {
    const token = form.access_token;
    Object.assign(form, makeForm());
    form.access_token = token;
    runState.value  = 'idle';
});

async function run() {
    if (!selectedCommand.value) return;

    if (!form.access_token.trim()) {
        runState.value = 'error';
        return;
    }

    runState.value = 'queued';

    const payload = {
        command:      selectedCommand.value,
        access_token: form.access_token.trim(),
    };

    if (selectedCommand.value === 'legacy:sync-attendance') {
        if (!form.date_start || !form.date_end) {
            runState.value = 'error';
            return;
        }
        payload.date_start = form.date_start;
        payload.date_end   = form.date_end;
    } else if (selectedCommand.value === 'legacy:sync-payments') {
        if (form.date_start) payload.date_start = form.date_start;
        if (form.date_end)   payload.date_end   = form.date_end;
        if (form.account_name) payload.account_name = form.account_name;
        if (form.page_size)    payload.page_size    = form.page_size;
    }
    // legacy:sync-members: no additional params

    try {
        const res = await apiRequest('/api/settings/legacy-tools/run', { method: 'post', data: payload });
        await loadLogs();
        startPolling(res.log_id);
    } catch {
        runState.value = 'error';
        await loadLogs();
    }
}

async function loadLogs() {
    logsLoading.value = true;
    try {
        const res = await apiRequest('/api/settings/legacy-tools/logs');
        logs.value = res.data ?? [];
    } catch {
        // silent
    } finally {
        logsLoading.value = false;
    }
}

function toggleLog(id) {
    expandedLog.value = expandedLog.value === id ? null : id;
}

onMounted(loadLogs);
</script>
