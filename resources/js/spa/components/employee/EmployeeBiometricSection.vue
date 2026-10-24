<template>
  <div class="app-surface rounded-2xl p-5 space-y-4">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-secondary-100 dark:border-secondary-800 pb-4">
      <div class="flex items-center gap-2.5">
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary-50 text-primary-600 dark:bg-primary-900/30 dark:text-primary-400 shrink-0">
          <Fingerprint class="w-5 h-5" :stroke-width="2" />
        </div>
        <div>
          <h3 class="text-base font-semibold text-secondary-900 dark:text-white">
            Biometric Device &amp; Access
          </h3>
          <p class="text-xs text-secondary-500 dark:text-secondary-400">
            Terminal identifier, door access rights, and credential verification
          </p>
        </div>
      </div>

      <!-- Action buttons when Biometric ID is defined -->
      <div v-if="employee?.biometric_id" class="flex flex-wrap items-center gap-2">
        <button
          v-if="canManage"
          type="button"
          class="inline-flex items-center gap-1.5 rounded-xl border border-secondary-200 dark:border-secondary-700 px-3 py-1.5 text-xs font-semibold text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-800 disabled:opacity-50 transition-colors"
          :disabled="deviceInfoLoading"
          @click="fetchDeviceInfo"
        >
          <RefreshCw class="w-3.5 h-3.5" :class="deviceInfoLoading ? 'animate-spin' : ''" :stroke-width="2" />
          {{ deviceInfoLoading ? 'Fetching…' : 'Fetch from Device' }}
        </button>

        <button
          v-if="canManage"
          type="button"
          class="inline-flex items-center gap-1.5 rounded-xl bg-primary-600 hover:bg-primary-700 px-3 py-1.5 text-xs font-semibold text-white disabled:opacity-50 transition-colors"
          :disabled="syncing"
          @click="syncToDevice"
        >
          <RefreshCw class="w-3.5 h-3.5" :class="syncing ? 'animate-spin' : ''" :stroke-width="2" />
          {{ syncing ? 'Syncing…' : 'Sync to Device' }}
        </button>

        <button
          v-if="canManage"
          type="button"
          class="inline-flex items-center gap-1 rounded-xl border border-secondary-200 dark:border-secondary-700 px-2.5 py-1.5 text-xs font-medium text-secondary-600 dark:text-secondary-400 hover:bg-secondary-50 dark:hover:bg-secondary-800 transition-colors"
          title="Edit Biometric ID"
          @click="openEditModal"
        >
          <Pencil class="w-3.5 h-3.5" :stroke-width="2" />
          <span>Edit ID</span>
        </button>
      </div>
    </div>

    <!-- Alert / Feedback Messages -->
    <div
      v-if="feedbackMessage"
      class="rounded-xl px-4 py-2.5 text-xs font-medium flex items-center justify-between gap-2"
      :class="feedbackSuccess ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400 border border-green-200 dark:border-green-800' : 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400 border border-red-200 dark:border-red-800'"
    >
      <span>{{ feedbackMessage }}</span>
      <button type="button" class="text-xs opacity-70 hover:opacity-100" @click="feedbackMessage = ''">
        ✕
      </button>
    </div>

    <!-- ── STATE 1: Biometric ID NOT defined ─────────────────────────────────── -->
    <div v-if="!employee?.biometric_id" class="rounded-xl border border-dashed border-secondary-300 dark:border-secondary-700 p-5 md:p-6 text-center space-y-4">
      <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-secondary-100 dark:bg-secondary-800 text-secondary-400 dark:text-secondary-500">
        <Fingerprint class="w-6 h-6" :stroke-width="1.5" />
      </div>

      <div class="max-w-md mx-auto space-y-1">
        <p class="text-sm font-semibold text-secondary-900 dark:text-white">
          No Biometric ID Assigned
        </p>
        <p class="text-xs text-secondary-500 dark:text-secondary-400">
          Define an employee ID / PIN to connect this employee with the biometric terminal. Once defined, terminal access rights, validity, and enrolled credentials will be retrieved automatically.
        </p>
      </div>

      <div v-if="canManage" class="max-w-md mx-auto flex flex-col sm:flex-row items-center gap-2 pt-1">
        <div class="relative flex-1 w-full">
          <input
            v-model="newBiometricId"
            type="text"
            placeholder="e.g. 1001 or EMP-01"
            class="w-full rounded-xl border border-secondary-300 bg-white px-3.5 py-2 text-sm text-secondary-900 placeholder:text-secondary-400 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-secondary-700 dark:bg-secondary-900 dark:text-white dark:placeholder:text-secondary-500 font-mono"
            :disabled="savingId"
            @keyup.enter="saveBiometricId(newBiometricId)"
          />
        </div>
        <button
          type="button"
          class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 rounded-xl bg-primary-600 hover:bg-primary-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50 transition-colors shrink-0"
          :disabled="!newBiometricId.trim() || savingId"
          @click="saveBiometricId(newBiometricId)"
        >
          <span v-if="savingId">Saving…</span>
          <span v-else>Define Biometric ID</span>
        </button>
      </div>

      <p v-if="idError" class="text-xs text-red-600 dark:text-red-400 font-medium">
        {{ idError }}
      </p>
    </div>

    <!-- ── STATE 2: Biometric ID IS defined ─────────────────────────────────── -->
    <div v-else class="space-y-4">
      <!-- Status Badges & Info Bar -->
      <div class="flex flex-wrap items-center justify-between gap-2.5 rounded-xl bg-secondary-50 dark:bg-secondary-800/40 border border-secondary-200/70 dark:border-secondary-700/60 p-3">
        <div class="flex flex-wrap items-center gap-2">
          <!-- Biometric ID badge -->
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold font-mono bg-primary-100 text-primary-800 dark:bg-primary-900/40 dark:text-primary-300 border border-primary-200 dark:border-primary-800">
            <Hash class="w-3.5 h-3.5" :stroke-width="2.5" />
            {{ employee.biometric_id }}
          </span>

          <!-- Valid access badge from device record -->
          <template v-if="deviceInfo?.person?.valid_enabled !== undefined && deviceInfo?.person?.valid_enabled !== null">
            <span
              class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold"
              :class="deviceInfo.person.valid_enabled
                ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800'
                : 'bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800'"
            >
              <CheckCircle v-if="deviceInfo.person.valid_enabled" class="w-3.5 h-3.5" :stroke-width="2.5" />
              <XCircle v-else class="w-3.5 h-3.5" :stroke-width="2.5" />
              {{ deviceInfo.person.valid_enabled ? 'Access Enabled' : 'Access Disabled' }}
            </span>
          </template>

          <!-- Door right badge -->
          <span
            v-if="deviceInfo?.person?.door_right"
            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium bg-secondary-200/60 dark:bg-secondary-700/60 text-secondary-700 dark:text-secondary-300"
          >
            <DoorClosed class="w-3 h-3" :stroke-width="2" />
            Door {{ deviceInfo.person.door_right }}
          </span>

          <!-- User type badge -->
          <span
            v-if="deviceInfo?.person?.user_type"
            class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium capitalize bg-secondary-200/60 dark:bg-secondary-700/60 text-secondary-700 dark:text-secondary-300"
          >
            {{ deviceInfo.person.user_type }} User
          </span>
        </div>

        <div class="text-[11px] text-secondary-500 dark:text-secondary-400 flex items-center gap-1">
          <Clock class="w-3.5 h-3.5 shrink-0" :stroke-width="2" />
          <span>Last synced: {{ employee.biometric_last_synced_at ? formatDateTime(employee.biometric_last_synced_at) : 'Never' }}</span>
        </div>
      </div>

      <!-- Loading device info -->
      <div v-if="deviceInfoLoading" class="p-6 text-center text-sm text-secondary-500 dark:text-secondary-400">
        <RefreshCw class="mx-auto w-5 h-5 animate-spin text-secondary-400 mb-2" :stroke-width="2" />
        Querying biometric device for ID #{{ employee.biometric_id }}…
      </div>

      <!-- Device connection failed -->
      <div v-else-if="deviceInfo?.connection_failed" class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4 flex gap-3 items-start">
        <WifiOff class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" :stroke-width="2" />
        <div class="min-w-0">
          <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">
            Terminal Unreachable / Not Configured
          </p>
          <p class="text-xs text-amber-700 dark:text-amber-400 mt-0.5">
            {{ deviceInfo.message || 'Could not connect to biometric terminal. Please verify IP/port under Settings → Biometric.' }}
          </p>
        </div>
      </div>

      <!-- Member/employee not found on device -->
      <div v-else-if="deviceInfo?.not_found" class="rounded-xl border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex gap-3 items-start">
          <AlertTriangle class="w-5 h-5 text-blue-600 dark:text-blue-400 shrink-0 mt-0.5" :stroke-width="2" />
          <div>
            <p class="text-sm font-semibold text-blue-900 dark:text-blue-300">
              Not Registered on Terminal
            </p>
            <p class="text-xs text-blue-700 dark:text-blue-400 mt-0.5">
              Employee ID <span class="font-mono font-bold">#{{ employee.biometric_id }}</span> has not been pushed to the hardware terminal yet.
            </p>
          </div>
        </div>
        <button
          v-if="canManage"
          type="button"
          class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 px-3 py-1.5 text-xs font-semibold text-white transition-colors shrink-0"
          :disabled="syncing"
          @click="syncToDevice"
        >
          <RefreshCw class="w-3.5 h-3.5" :class="syncing ? 'animate-spin' : ''" :stroke-width="2" />
          {{ syncing ? 'Syncing…' : 'Sync to Device' }}
        </button>
      </div>

      <!-- Device Person Info Grid (when available) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <!-- Biometric record name card -->
        <div class="rounded-xl border border-secondary-200 dark:border-secondary-700 p-3 bg-white dark:bg-secondary-900/40">
          <p class="text-xs text-secondary-500 dark:text-secondary-400 mb-1">
            Biometric Record Name
          </p>
          <p class="text-sm font-semibold text-secondary-900 dark:text-white flex items-center gap-1.5">
            <span v-if="deviceInfo?.person?.name">{{ deviceInfo.person.name }}</span>
            <span v-else class="text-secondary-400 dark:text-secondary-500 italic text-xs">
              {{ deviceInfo ? 'Not registered on device' : 'Fetch from device to view' }}
            </span>
            <button
              v-if="canManage"
              type="button"
              class="text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-200 ml-auto shrink-0"
              title="Edit Biometric ID"
              @click="openEditModal"
            >
              <Pencil class="w-3.5 h-3.5" :stroke-width="2" />
            </button>
          </p>
          <p class="text-[11px] font-mono text-secondary-500 dark:text-secondary-400 mt-0.5">
            ID: {{ employee.biometric_id }}
          </p>
        </div>

        <!-- Which Access Given -->
        <div class="rounded-xl border border-secondary-200 dark:border-secondary-700 p-3 bg-white dark:bg-secondary-900/40">
          <p class="text-xs text-secondary-500 dark:text-secondary-400 mb-1">
            Which Access Given
          </p>
          <p class="text-sm font-semibold text-secondary-900 dark:text-white flex items-center gap-1.5">
            <span
              class="inline-block w-2 h-2 rounded-full"
              :class="accessGivenEnabled ? 'bg-emerald-500' : 'bg-red-500'"
            />
            <span>{{ accessGivenText }}</span>
          </p>
        </div>

        <!-- Valid Period -->
        <div class="rounded-xl border border-secondary-200 dark:border-secondary-700 p-3 bg-white dark:bg-secondary-900/40 sm:col-span-2 lg:col-span-1">
          <p class="text-xs text-secondary-500 dark:text-secondary-400 mb-1">
            Valid Period
          </p>
          <p class="text-sm font-medium text-secondary-900 dark:text-white">
            {{ validPeriodText }}
          </p>
        </div>
      </div>

      <!-- Credential Verification Cards -->
      <div class="space-y-2 pt-1">
        <p class="text-xs font-semibold uppercase tracking-wider text-secondary-500 dark:text-secondary-400">
          Enrolled Biometric Credentials
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <!-- Face ID -->
          <div
            class="flex items-center gap-3 rounded-xl border p-3"
            :class="faceEnrolled
              ? 'border-emerald-200 dark:border-emerald-800 bg-emerald-50/50 dark:bg-emerald-900/20'
              : 'border-secondary-200 dark:border-secondary-700 bg-secondary-50/50 dark:bg-secondary-800/40'"
          >
            <div class="shrink-0">
              <img
                v-if="deviceInfo?.face?.face_url"
                :src="`/api/employees/${employee.id}/biometric-face-image`"
                alt="Enrolled face"
                class="w-9 h-9 rounded-lg object-cover border border-emerald-300 dark:border-emerald-700"
              />
              <div
                v-else
                class="w-9 h-9 rounded-lg flex items-center justify-center"
                :class="faceEnrolled ? 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400' : 'bg-secondary-100 dark:bg-secondary-700 text-secondary-400 dark:text-secondary-500'"
              >
                <ScanFace class="w-5 h-5" :stroke-width="2" />
              </div>
            </div>
            <div class="min-w-0">
              <p class="text-xs font-semibold text-secondary-900 dark:text-white">
                Face ID
              </p>
              <p
                class="text-xs mt-0.5 font-medium"
                :class="faceEnrolled ? 'text-emerald-600 dark:text-emerald-400' : 'text-secondary-400 dark:text-secondary-500'"
              >
                {{ faceEnrolled ? `${deviceInfo?.face?.count || 1} Enrolled` : 'Not enrolled' }}
              </p>
            </div>
          </div>

          <!-- Fingerprint -->
          <div
            class="flex items-center gap-3 rounded-xl border p-3"
            :class="fingerprintEnrolled
              ? 'border-emerald-200 dark:border-emerald-800 bg-emerald-50/50 dark:bg-emerald-900/20'
              : 'border-secondary-200 dark:border-secondary-700 bg-secondary-50/50 dark:bg-secondary-800/40'"
          >
            <div
              class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0"
              :class="fingerprintEnrolled ? 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400' : 'bg-secondary-100 dark:bg-secondary-700 text-secondary-400 dark:text-secondary-500'"
            >
              <Fingerprint class="w-5 h-5" :stroke-width="2" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-xs font-semibold text-secondary-900 dark:text-white">
                Fingerprint
              </p>
              <p
                class="text-xs mt-0.5 font-medium"
                :class="fingerprintEnrolled ? 'text-emerald-600 dark:text-emerald-400' : 'text-secondary-400 dark:text-secondary-500'"
              >
                {{ fingerprintEnrolled ? `${deviceInfo?.fingerprint?.count || 1} Enrolled` : 'Not enrolled' }}
              </p>
            </div>

            <!-- Fingerprint trigger if supported -->
            <button
              v-if="canManage && !fingerprintEnrolled && deviceInfo?.fingerprint_setup_supported"
              type="button"
              class="shrink-0 inline-flex items-center gap-1 rounded-lg border border-secondary-300 dark:border-secondary-600 px-2 py-1 text-[11px] font-medium text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700 disabled:opacity-50 transition-colors"
              :disabled="fpSetupLoading"
              @click="setupFingerprint"
            >
              <Fingerprint class="w-3 h-3" :stroke-width="2" />
              {{ fpSetupLoading ? 'Setting up…' : 'Setup' }}
            </button>
          </div>

          <!-- RFID Card -->
          <div
            class="flex items-center gap-3 rounded-xl border p-3"
            :class="cardAssigned
              ? 'border-blue-200 dark:border-blue-800 bg-blue-50/50 dark:bg-blue-900/20'
              : 'border-secondary-200 dark:border-secondary-700 bg-secondary-50/50 dark:bg-secondary-800/40'"
          >
            <div
              class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0"
              :class="cardAssigned ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400' : 'bg-secondary-100 dark:bg-secondary-700 text-secondary-400 dark:text-secondary-500'"
            >
              <CreditCard class="w-5 h-5" :stroke-width="2" />
            </div>
            <div class="min-w-0">
              <p class="text-xs font-semibold text-secondary-900 dark:text-white">
                RFID / Smart Card
              </p>
              <p
                class="text-xs mt-0.5 font-medium"
                :class="cardAssigned ? 'text-blue-600 dark:text-blue-400' : 'text-secondary-400 dark:text-secondary-500'"
              >
                {{ cardAssigned ? `${deviceInfo?.card?.count || 1} Assigned` : 'Not assigned' }}
              </p>
              <div v-if="deviceInfo?.card?.card_numbers?.length" class="mt-1 flex flex-wrap gap-1">
                <span
                  v-for="num in deviceInfo.card.card_numbers"
                  :key="num"
                  class="inline-block px-1.5 py-0.5 rounded text-[10px] font-mono bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300"
                >
                  {{ num }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── EDIT BIOMETRIC ID MODAL ────────────────────────────────────────────── -->
    <div
      v-if="editModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-xs"
    >
      <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-2xl dark:bg-secondary-900 border border-secondary-200 dark:border-secondary-800 space-y-4">
        <div class="flex items-center justify-between border-b border-secondary-100 pb-3 dark:border-secondary-800">
          <h4 class="text-base font-bold text-secondary-900 dark:text-white">
            Edit Biometric ID
          </h4>
          <button
            type="button"
            class="text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-200"
            @click="editModalOpen = false"
          >
            ✕
          </button>
        </div>

        <div class="space-y-3">
          <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300">
            Biometric ID / Terminal Number
          </label>
          <input
            v-model="editBiometricIdValue"
            type="text"
            placeholder="e.g. 1001 or EMP-01"
            class="w-full rounded-xl border border-secondary-300 bg-white px-3.5 py-2.5 text-sm font-mono text-secondary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-secondary-700 dark:bg-secondary-800 dark:text-white"
            :disabled="savingId"
          />
          <p class="text-xs text-secondary-400 dark:text-secondary-500">
            Leave blank to remove the biometric ID from this employee.
          </p>
          <p v-if="editError" class="text-xs text-red-600 dark:text-red-400 font-medium">
            {{ editError }}
          </p>
        </div>

        <div class="flex items-center justify-between pt-2 border-t border-secondary-100 dark:border-secondary-800">
          <button
            type="button"
            class="rounded-xl px-3 py-2 text-xs font-semibold text-secondary-600 hover:bg-secondary-100 dark:text-secondary-400 dark:hover:bg-secondary-800"
            :disabled="savingId"
            @click="editModalOpen = false"
          >
            Cancel
          </button>
          <div class="flex items-center gap-2">
            <button
              v-if="employee?.biometric_id"
              type="button"
              class="rounded-xl border border-red-200 text-red-600 hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-900/20 px-3 py-2 text-xs font-semibold"
              :disabled="savingId"
              @click="saveBiometricId('')"
            >
              Clear ID
            </button>
            <button
              type="button"
              class="rounded-xl bg-primary-600 px-4 py-2 text-xs font-semibold text-white hover:bg-primary-700 disabled:opacity-50"
              :disabled="savingId"
              @click="saveBiometricId(editBiometricIdValue)"
            >
              {{ savingId ? 'Saving…' : 'Save Changes' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import {
    AlertTriangle,
    CheckCircle,
    CreditCard,
    DoorClosed,
    Fingerprint,
    Hash,
    Pencil,
    RefreshCw,
    ScanFace,
    WifiOff,
    XCircle,
    Clock,
} from 'lucide-vue-next';
import { apiRequest } from '../../composables/useApiClient';
import { useDateTimeFormat } from '../../composables/useDateTimeFormat';

const props = defineProps({
    employee: {
        type: Object,
        required: true,
    },
    canManage: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['updated']);

const { formatDate, formatDateTime } = useDateTimeFormat();

// ── State ─────────────────────────────────────────────────────────────────────
const deviceInfo = ref(null);
const deviceInfoLoading = ref(false);
const syncing = ref(false);
const savingId = ref(false);
const fpSetupLoading = ref(false);

const feedbackMessage = ref('');
const feedbackSuccess = ref(true);

const newBiometricId = ref('');
const idError = ref('');

const editModalOpen = ref(false);
const editBiometricIdValue = ref('');
const editError = ref('');

// ── Computed Access Helpers ───────────────────────────────────────────────────
const accessGivenEnabled = computed(() => {
    if (deviceInfo.value?.person?.valid_enabled !== undefined && deviceInfo.value?.person?.valid_enabled !== null) {
        return Boolean(deviceInfo.value.person.valid_enabled);
    }
    return props.employee?.status === 'active';
});

const accessGivenText = computed(() => {
    const parts = [];
    if (accessGivenEnabled.value) {
        parts.push('Access Enabled');
    } else {
        parts.push('Access Disabled');
    }

    if (deviceInfo.value?.person?.door_right) {
        parts.push(`(Door ${deviceInfo.value.person.door_right})`);
    } else {
        parts.push('(All Doors)');
    }

    return parts.join(' ');
});

const validPeriodText = computed(() => {
    const begin = deviceInfo.value?.person?.valid_begin || props.employee?.joined_date;
    const end = deviceInfo.value?.person?.valid_end || props.employee?.left_date;

    if (!begin && !end) {
        return 'Permanent / No Expiration';
    }

    const beginStr = begin ? formatDate(begin) : 'Start';
    const endStr = end ? formatDate(end) : 'No expiration';

    return `${beginStr} → ${endStr}`;
});

const faceEnrolled = computed(() => Boolean(deviceInfo.value?.face?.enrolled));
const fingerprintEnrolled = computed(() => Boolean(deviceInfo.value?.fingerprint?.enrolled));
const cardAssigned = computed(() => Boolean(deviceInfo.value?.card?.assigned));

// ── Methods ───────────────────────────────────────────────────────────────────
function openEditModal() {
    editBiometricIdValue.value = props.employee?.biometric_id || '';
    editError.value = '';
    editModalOpen.value = true;
}

async function saveBiometricId(idValue) {
    savingId.value = true;
    idError.value = '';
    editError.value = '';
    feedbackMessage.value = '';

    const cleanId = idValue ? String(idValue).trim() : null;

    try {
        const response = await apiRequest(`/api/employees/${props.employee.id}/biometric-id`, {
            method: 'PUT',
            data: { biometric_id: cleanId },
        });

        emit('updated', { biometric_id: cleanId });

        feedbackSuccess.value = true;
        feedbackMessage.value = response.message || 'Biometric ID updated successfully.';
        editModalOpen.value = false;
        newBiometricId.value = '';

        if (cleanId) {
            fetchDeviceInfo();
        } else {
            deviceInfo.value = null;
        }
    } catch (err) {
        const msg = err?.response?.data?.message || 'Failed to save biometric ID.';
        idError.value = msg;
        editError.value = msg;
        feedbackSuccess.value = false;
        feedbackMessage.value = msg;
    } finally {
        savingId.value = false;
    }
}

async function fetchDeviceInfo() {
    if (!props.employee?.biometric_id) return;

    deviceInfoLoading.value = true;
    try {
        const response = await apiRequest(`/api/employees/${props.employee.id}/biometric-device-info`);
        deviceInfo.value = response;
    } catch (err) {
        deviceInfo.value = {
            connection_failed: true,
            not_assigned: false,
            not_found: false,
            message: err?.response?.data?.message || 'Failed to retrieve terminal info.',
        };
    } finally {
        deviceInfoLoading.value = false;
    }
}

async function syncToDevice() {
    if (!props.employee?.biometric_id) return;

    syncing.value = true;
    feedbackMessage.value = '';
    try {
        const response = await apiRequest(`/api/employees/${props.employee.id}/biometric-sync`, {
            method: 'POST',
        });

        feedbackSuccess.value = true;
        feedbackMessage.value = response.message || 'Employee synced to biometric device successfully.';
        if (response.biometric_last_synced_at) {
            emit('updated', { biometric_last_synced_at: response.biometric_last_synced_at });
        }
        await fetchDeviceInfo();
    } catch (err) {
        feedbackSuccess.value = false;
        feedbackMessage.value = err?.response?.data?.message || 'Failed to sync employee to biometric device.';
    } finally {
        syncing.value = false;
    }
}

async function setupFingerprint() {
    if (!props.employee?.biometric_id) return;

    fpSetupLoading.value = true;
    feedbackMessage.value = '';
    try {
        const response = await apiRequest(`/api/employees/${props.employee.id}/biometric-setup-fingerprint`, {
            method: 'POST',
        });

        feedbackSuccess.value = true;
        feedbackMessage.value = response.message || 'Terminal ready — please scan fingerprint at the device.';
    } catch (err) {
        feedbackSuccess.value = false;
        feedbackMessage.value = err?.response?.data?.message || 'Remote fingerprint setup failed.';
    } finally {
        fpSetupLoading.value = false;
    }
}

// ── Lifecycle ─────────────────────────────────────────────────────────────────
onMounted(() => {
    if (props.employee?.biometric_id) {
        fetchDeviceInfo();
    }
});

watch(() => props.employee?.biometric_id, (newVal) => {
    if (newVal) {
        fetchDeviceInfo();
    } else {
        deviceInfo.value = null;
    }
});
</script>
