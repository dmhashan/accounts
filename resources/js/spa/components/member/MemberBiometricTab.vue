<template>
  <div class="mx-4 space-y-4">
    <!-- Not configured notice -->
    <div v-if="!biometricEnabled" class="rounded-xl border border-secondary-200 dark:border-secondary-700 bg-secondary-50 dark:bg-secondary-800/50 px-5 py-6 text-center">
      <Cpu class="mx-auto w-10 h-10 text-secondary-300 dark:text-secondary-600 mb-2" :stroke-width="1.5" />
      <p class="text-sm font-medium text-secondary-600 dark:text-secondary-400">
        Biometric device not configured
      </p>
      <p class="text-xs text-secondary-400 dark:text-secondary-500 mt-1">
        Enable and configure the biometric device under Settings → Biometric.
      </p>
    </div>

    <template v-else>
      <!-- ── Device Record card ────────────────────────────────────────────── -->
      <div class="app-surface rounded-2xl overflow-hidden">
        <!-- Header -->
        <div class="px-4 md:px-5 py-3 border-b border-secondary-100 dark:border-secondary-800 flex items-center justify-between gap-3">
          <div class="flex items-center gap-2">
            <ScanFace class="w-4 h-4 text-secondary-500 dark:text-secondary-400" :stroke-width="2" />
            <p class="text-sm font-semibold" style="color: var(--text-strong)">
              Device Record
            </p>
          </div>
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border border-secondary-200 dark:border-secondary-700 px-3 py-1.5 text-xs font-medium text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-800 disabled:opacity-50 transition-colors"
            :disabled="deviceInfoLoading"
            @click="fetchDeviceInfo"
          >
            <RefreshCw class="w-3.5 h-3.5" :class="deviceInfoLoading ? 'animate-spin' : ''" :stroke-width="2" />
            {{ deviceInfoLoading ? 'Fetching…' : 'Fetch from Device' }}
          </button>
        </div>

        <!-- Loading -->
        <div v-if="deviceInfoLoading" class="px-4 py-8 text-center">
          <RefreshCw class="mx-auto w-6 h-6 animate-spin text-secondary-400 mb-2" :stroke-width="2" />
          <p class="text-sm text-secondary-400">
            Querying device…
          </p>
        </div>

        <!-- Not yet fetched -->
        <div v-else-if="deviceInfo === null" class="px-4 py-8 text-center">
          <ScanFace class="mx-auto w-9 h-9 text-secondary-300 dark:text-secondary-600 mb-2" :stroke-width="1.5" />
          <p class="text-sm text-secondary-400 dark:text-secondary-500">
            Click <span class="font-medium">"Fetch from Device"</span> to retrieve the current device record.
          </p>
        </div>

        <!-- Connection failed -->
        <div v-else-if="deviceInfo.connection_failed" class="px-4 py-6">
          <div class="rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-4 flex gap-3 items-start">
            <WifiOff class="w-5 h-5 text-red-500 dark:text-red-400 shrink-0 mt-0.5" :stroke-width="2" />
            <p class="text-sm font-semibold text-red-700 dark:text-red-400">
              Failed to connect to device
            </p>
          </div>
        </div>

        <!-- No biometric ID assigned -->
        <div v-else-if="deviceInfo.not_assigned" class="px-4 py-6">
          <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4 flex gap-3 items-start">
            <AlertTriangle class="w-5 h-5 text-amber-500 dark:text-amber-400 shrink-0 mt-0.5" :stroke-width="2" />
            <p class="text-sm font-semibold text-amber-700 dark:text-amber-400">
              No biometric ID assigned
            </p>
          </div>
        </div>

        <!-- Member not found on device -->
        <div v-else-if="deviceInfo.not_found" class="px-4 py-6">
          <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4 flex gap-3 items-start">
            <AlertTriangle class="w-5 h-5 text-amber-500 dark:text-amber-400 shrink-0 mt-0.5" :stroke-width="2" />
            <p class="text-sm font-semibold text-amber-700 dark:text-amber-400">
              Member not mapped on device
            </p>
          </div>
        </div>

        <!-- Device data ─────────────────────────────────────────────────────── -->
        <div v-else class="p-4 md:p-5 space-y-5">
          <!-- Status badges row -->
          <div class="flex flex-wrap gap-2">
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-secondary-100 dark:bg-secondary-800 text-secondary-600 dark:text-secondary-400">
              <Hash class="w-3 h-3" :stroke-width="2" />
              {{ deviceInfo.person.employee_no }}
            </span>
            <!-- Valid access badge -->
            <template v-if="deviceInfo.person.valid_enabled !== null">
              <span
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium"
                :class="deviceInfo.person.valid_enabled
                  ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400'
                  : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400'"
              >
                <CheckCircle v-if="deviceInfo.person.valid_enabled" class="w-3 h-3" :stroke-width="2.5" />
                <XCircle v-else class="w-3 h-3" :stroke-width="2.5" />
                {{ deviceInfo.person.valid_enabled ? 'Access Enabled' : 'Access Disabled' }}
              </span>
            </template>
          </div>

          <!-- Person details grid -->
          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
            <div>
              <p class="text-xs text-secondary-400 dark:text-secondary-500 mb-0.5">
                Name (Device)
              </p>
              <p class="text-sm font-medium" style="color: var(--text-strong)">
                {{ deviceInfo.person.name || '—' }}
              </p>
            </div>
            <div>
              <p class="text-xs text-secondary-400 dark:text-secondary-500 mb-0.5">
                Gender
              </p>
              <p class="text-sm font-medium capitalize" style="color: var(--text-strong)">
                {{ deviceInfo.person.gender || '—' }}
              </p>
            </div>
            <div v-if="deviceInfo.person.user_type">
              <p class="text-xs text-secondary-400 dark:text-secondary-500 mb-0.5">
                User Type
              </p>
              <p class="text-sm font-medium capitalize" style="color: var(--text-strong)">
                {{ deviceInfo.person.user_type }}
              </p>
            </div>
            <div v-if="deviceInfo.person.valid_begin || deviceInfo.person.valid_end" class="col-span-2 sm:col-span-3 lg:col-span-4">
              <p class="text-xs text-secondary-400 dark:text-secondary-500 mb-0.5">
                Valid Period
              </p>
              <p class="text-sm font-medium" style="color: var(--text-strong)">
                {{ deviceInfo.person.valid_begin ? formatDeviceDate(deviceInfo.person.valid_begin) : '—' }}
                <span class="text-secondary-400 dark:text-secondary-500 mx-1">→</span>
                {{ deviceInfo.person.valid_end ? formatDeviceDate(deviceInfo.person.valid_end) : '—' }}
              </p>
            </div>
          </div>

          <!-- Divider -->
          <div class="border-t border-secondary-100 dark:border-secondary-800" />

          <!-- Credential rows -->
          <div class="space-y-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-secondary-400 dark:text-secondary-500">
              Enrolled Credentials
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <!-- Face -->
              <div
                class="flex items-center gap-3 rounded-xl border p-3"
                :class="deviceInfo.face.enrolled
                  ? 'border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20'
                  : 'border-secondary-200 dark:border-secondary-700 bg-secondary-50 dark:bg-secondary-800/50'"
              >
                <div
                  class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                  :class="deviceInfo.face.enrolled ? 'bg-green-100 dark:bg-green-900/40' : 'bg-secondary-100 dark:bg-secondary-700'"
                >
                  <ScanFace
                    class="w-4 h-4"
                    :class="deviceInfo.face.enrolled ? 'text-green-600 dark:text-green-400' : 'text-secondary-400 dark:text-secondary-500'"
                    :stroke-width="2"
                  />
                </div>
                <div class="min-w-0">
                  <p class="text-xs font-medium" style="color: var(--text-strong)">
                    Face ID
                  </p>
                  <p
                    class="text-xs mt-0.5"
                    :class="deviceInfo.face.enrolled ? 'text-green-600 dark:text-green-400' : 'text-secondary-400 dark:text-secondary-500'"
                  >
                    {{ deviceInfo.face.enrolled ? `${deviceInfo.face.count} enrolled` : 'Not enrolled' }}
                  </p>
                </div>
              </div>

              <!-- Fingerprint -->
              <div
                class="flex items-center gap-3 rounded-xl border p-3"
                :class="deviceInfo.fingerprint.enrolled
                  ? 'border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20'
                  : 'border-secondary-200 dark:border-secondary-700 bg-secondary-50 dark:bg-secondary-800/50'"
              >
                <div
                  class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                  :class="deviceInfo.fingerprint.enrolled ? 'bg-green-100 dark:bg-green-900/40' : 'bg-secondary-100 dark:bg-secondary-700'"
                >
                  <Fingerprint
                    class="w-4 h-4"
                    :class="deviceInfo.fingerprint.enrolled ? 'text-green-600 dark:text-green-400' : 'text-secondary-400 dark:text-secondary-500'"
                    :stroke-width="2"
                  />
                </div>
                <div class="min-w-0 flex-1">
                  <p class="text-xs font-medium" style="color: var(--text-strong)">
                    Fingerprint
                  </p>
                  <p
                    class="text-xs mt-0.5"
                    :class="deviceInfo.fingerprint.enrolled ? 'text-green-600 dark:text-green-400' : 'text-secondary-400 dark:text-secondary-500'"
                  >
                    {{ deviceInfo.fingerprint.enrolled ? `${deviceInfo.fingerprint.count} enrolled` : 'Not enrolled' }}
                  </p>
                </div>
                <template v-if="canSync">
                  <button
                    v-if="deviceInfo.fingerprint_setup_supported !== false"
                    type="button"
                    class="shrink-0 inline-flex items-center gap-1 rounded-lg border border-secondary-200 dark:border-secondary-700 px-2 py-1 text-[11px] font-medium text-secondary-600 dark:text-secondary-400 hover:bg-secondary-100 dark:hover:bg-secondary-700 disabled:opacity-50 transition-colors"
                    :disabled="fpSetupLoading"
                    @click="setupFingerprint"
                  >
                    <Fingerprint class="w-3 h-3" :stroke-width="2" />
                    {{ fpSetupLoading ? 'Setting up…' : 'Setup' }}
                  </button>
                  <span
                    v-else
                    class="shrink-0 text-[10px] text-secondary-400 dark:text-secondary-500 italic max-w-[80px] text-right leading-tight"
                  >Enrol at terminal</span>
                </template>
              </div>

              <!-- Card -->
              <div
                class="flex items-center gap-3 rounded-xl border p-3"
                :class="deviceInfo.card.assigned
                  ? 'border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20'
                  : 'border-secondary-200 dark:border-secondary-700 bg-secondary-50 dark:bg-secondary-800/50'"
              >
                <div
                  class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                  :class="deviceInfo.card.assigned ? 'bg-blue-100 dark:bg-blue-900/40' : 'bg-secondary-100 dark:bg-secondary-700'"
                >
                  <CreditCard
                    class="w-4 h-4"
                    :class="deviceInfo.card.assigned ? 'text-blue-600 dark:text-blue-400' : 'text-secondary-400 dark:text-secondary-500'"
                    :stroke-width="2"
                  />
                </div>
                <div class="min-w-0">
                  <p class="text-xs font-medium" style="color: var(--text-strong)">
                    Card
                  </p>
                  <p
                    class="text-xs mt-0.5"
                    :class="deviceInfo.card.assigned ? 'text-blue-600 dark:text-blue-400' : 'text-secondary-400 dark:text-secondary-500'"
                  >
                    {{ deviceInfo.card.assigned ? `${deviceInfo.card.count} assigned` : 'Not assigned' }}
                  </p>
                  <!-- Card numbers -->
                  <div v-if="deviceInfo.card.card_numbers?.length" class="mt-1 flex flex-wrap gap-1">
                    <span
                      v-for="num in deviceInfo.card.card_numbers"
                      :key="num"
                      class="inline-block px-1.5 py-0.5 rounded text-[10px] font-mono bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300"
                    >{{ num }}</span>
                  </div>
                </div>
              </div>
            </div>
            <!-- Fingerprint setup feedback -->
            <p
              v-if="fpSetupMessage"
              class="text-xs rounded-lg px-3 py-1.5"
              :class="fpSetupSuccess ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400'"
            >
              {{ fpSetupMessage }}
            </p>
          </div>
        </div>
      </div>

      <!-- ── Sync logs ──────────────────────────────────────────────────────── -->
      <div class="app-surface rounded-2xl overflow-hidden">
        <div class="px-4 md:px-5 py-3 border-b border-secondary-100 dark:border-secondary-800">
          <div class="flex items-center justify-between gap-3">
            <p class="text-sm font-semibold" style="color: var(--text-strong)">
              Sync History
            </p>
            <div class="flex items-center gap-2">
              <span class="text-xs text-secondary-400 dark:text-secondary-500 hidden sm:inline">
                Last synced: {{ localLastSyncedAt ? formatDate(localLastSyncedAt) : 'Never' }}
              </span>
              <button
                v-if="canSync"
                type="button"
                class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 hover:bg-primary-700 px-3 py-1.5 text-xs font-medium text-white disabled:opacity-50 transition-colors"
                :disabled="syncing"
                @click="syncNow"
              >
                <RefreshCw class="w-3.5 h-3.5" :class="syncing ? 'animate-spin' : ''" :stroke-width="2" />
                {{ syncing ? 'Syncing…' : 'Sync to Device' }}
              </button>
              <button type="button" class="text-xs text-secondary-400 dark:text-secondary-500 hover:text-secondary-600 dark:hover:text-secondary-300" @click="loadLogs">
                Refresh
              </button>
            </div>
          </div>
          <p v-if="syncMessage" class="mt-2 text-xs rounded-lg px-3 py-1.5" :class="syncSuccess ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400'">
            {{ syncMessage }}
          </p>
          <p class="text-xs text-secondary-400 dark:text-secondary-500 mt-1 sm:hidden">
            Last synced: {{ localLastSyncedAt ? formatDate(localLastSyncedAt) : 'Never' }}
          </p>
        </div>

        <div v-if="logsLoading" class="px-4 py-6 text-center text-sm text-secondary-400">
          Loading…
        </div>
        <div v-else-if="logs.length === 0" class="px-4 py-6 text-center text-sm text-secondary-400">
          No sync events yet.
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-secondary-50 dark:bg-secondary-800/50">
              <tr>
                <th class="px-4 py-2.5 text-left text-xs font-medium text-secondary-500">
                  Direction
                </th>
                <th class="px-4 py-2.5 text-left text-xs font-medium text-secondary-500">
                  Action
                </th>
                <th class="px-4 py-2.5 text-left text-xs font-medium text-secondary-500">
                  Status
                </th>
                <th class="px-4 py-2.5 text-left text-xs font-medium text-secondary-500 hidden sm:table-cell">
                  Error
                </th>
                <th class="px-4 py-2.5 text-left text-xs font-medium text-secondary-500">
                  Time
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-secondary-100 dark:divide-secondary-800">
              <tr v-for="log in logs" :key="log.id">
                <td class="px-4 py-2.5">
                  <span class="inline-flex items-center gap-1 text-xs font-medium" :class="log.direction === 'up' ? 'text-blue-600 dark:text-blue-400' : 'text-emerald-600 dark:text-emerald-400'">
                    <ArrowUp v-if="log.direction === 'up'" class="w-3 h-3" />
                    <ArrowDown v-else class="w-3 h-3" />
                    {{ log.direction }}
                  </span>
                </td>
                <td class="px-4 py-2.5 text-secondary-600 dark:text-secondary-400 capitalize text-xs">
                  {{ log.action.replace('_', ' ') }}
                </td>
                <td class="px-4 py-2.5">
                  <span
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                    :class="log.status === 'success' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400'"
                  >{{ log.status }}</span>
                </td>
                <td class="px-4 py-2.5 text-secondary-400 text-xs hidden sm:table-cell max-w-[180px] truncate">
                  {{ log.error_message || '—' }}
                </td>
                <td class="px-4 py-2.5 text-secondary-400 text-xs whitespace-nowrap">
                  {{ log.synced_at ? new Date(log.synced_at).toLocaleString() : '—' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import {
    AlertTriangle, ArrowDown, ArrowUp,
    CheckCircle, Cpu, CreditCard, Fingerprint,
    Hash, RefreshCw, ScanFace, WifiOff, XCircle,
} from 'lucide-vue-next';
import { apiRequest } from '../../composables/useApiClient';

const props = defineProps({
    memberId:          { type: Number, required: true },
    biometricMemberId: { type: String, default: null },
    lastSyncedAt:      { type: String, default: null },
    canSync:           { type: Boolean, default: false },
});

const emit = defineEmits(['synced', 'assigned']);

// ── State ─────────────────────────────────────────────────────────────────────
const biometricEnabled  = ref(false);
const localLastSyncedAt = ref(props.lastSyncedAt);
watch(() => props.lastSyncedAt, (v) => { localLastSyncedAt.value = v; });

// Sync
const syncing     = ref(false);
const syncMessage = ref('');
const syncSuccess = ref(false);

// Sync history
const logsLoading = ref(false);
const logs        = ref([]);

// Device record
const deviceInfoLoading = ref(false);
const deviceInfo        = ref(null); // null = not yet fetched

// Fingerprint setup
const fpSetupLoading  = ref(false);
const fpSetupMessage  = ref('');
const fpSetupSuccess  = ref(false);

// ── Helpers ───────────────────────────────────────────────────────────────────
function formatDate(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleString();
}

function formatDeviceDate(str) {
    if (!str) return '—';
    // HikVision ISO format: 2024-01-01T00:00:00
    try { return new Date(str).toLocaleDateString(); } catch { return str; }
}

// ── API calls ─────────────────────────────────────────────────────────────────
async function checkBiometricEnabled() {
    try {
        const res = await apiRequest('/api/settings/configuration');
        biometricEnabled.value = (res.data?.['biometric.enabled'] ?? '0') === '1';
    } catch {
        biometricEnabled.value = false;
    }
}

async function loadLogs() {
    logsLoading.value = true;
    try {
        const res = await apiRequest(`/api/members/${props.memberId}/biometric-logs`);
        logs.value = res.data || [];
    } catch {
        // fail silently
    } finally {
        logsLoading.value = false;
    }
}

async function syncNow() {
    syncing.value   = true;
    syncMessage.value = '';
    try {
        const res = await apiRequest(`/api/members/${props.memberId}/biometric-sync`, { method: 'POST' });
        syncSuccess.value = true;
        syncMessage.value = res.message || 'Synced successfully.';
        localLastSyncedAt.value = res.biometric_last_synced_at || new Date().toISOString();
        emit('synced', res.biometric_last_synced_at);
        loadLogs();
    } catch (err) {
        syncSuccess.value = false;
        syncMessage.value = err?.response?.data?.message || 'Sync failed.';
    } finally {
        syncing.value = false;
    }
}

async function fetchDeviceInfo() {
    deviceInfoLoading.value = true;
    deviceInfo.value        = null;
    fpSetupMessage.value    = '';
    try {
        const res = await apiRequest(`/api/members/${props.memberId}/biometric-device-info`);
        deviceInfo.value = res;
    } catch (err) {
        deviceInfo.value = {
            connection_failed: true,
            not_assigned: false,
            not_found: false,
            message: err?.response?.data?.message || 'Failed to fetch device info.',
        };
    } finally {
        deviceInfoLoading.value = false;
    }
}

async function setupFingerprint() {
    fpSetupLoading.value = true;
    fpSetupMessage.value = '';
    try {
        const res = await apiRequest(`/api/members/${props.memberId}/biometric-setup-fingerprint`, { method: 'POST' });
        fpSetupSuccess.value = res.success !== false;
        fpSetupMessage.value = fpSetupSuccess.value
            ? 'Device is ready — ask the member to scan their fingerprint.'
            : (res.message || 'Setup failed.');
    } catch (err) {
        fpSetupSuccess.value = false;
        fpSetupMessage.value = err?.response?.data?.message || 'Setup failed.';
    } finally {
        fpSetupLoading.value = false;
    }
}

// ── Lifecycle ─────────────────────────────────────────────────────────────────
onMounted(async () => {
    await checkBiometricEnabled();
    if (biometricEnabled.value) {
        loadLogs();
        // Auto-fetch device info only when member already has a biometric ID
        if (props.biometricMemberId) {
            fetchDeviceInfo();
        }
    }
});
</script>
