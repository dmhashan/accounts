<template>
  <div class="app-shell flex overflow-hidden">
    <div class="pointer-events-none fixed inset-0 -z-10">
      <div class="absolute -top-24 -right-32 h-96 w-96 rounded-full bg-primary-500/15 blur-3xl dark:bg-primary-700/10" />
      <div class="absolute -bottom-36 left-0 h-[26rem] w-[26rem] rounded-full bg-emerald-500/12 blur-3xl dark:bg-emerald-700/10" />
    </div>

    <AppSidebar />

    <AppMobileDrawer :open="mobileMenuOpen" @close="mobileMenuOpen = false" />

    <div class="relative flex min-w-0 flex-1 flex-col overflow-hidden h-screen">
      <div v-if="routeLoader.loading" class="app-progress-bar shrink-0" />

      <main class="flex min-h-0 flex-1 flex-col overflow-hidden px-3 py-4 sm:px-4 md:px-6 md:py-6 pb-24 lg:pb-8 [padding-bottom:calc(6.25rem+env(safe-area-inset-bottom))] lg:[padding-bottom:2rem]">
        <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
          <RouterView />
        </div>
      </main>

      <AppBottomNav @open-menu="mobileMenuOpen = true" />
    </div>

    <!-- AssistiveTouch -->
    <AssistiveTouchButton :actions="assistiveActions" @menu-open="handleAssistiveMenuOpen" />

    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 translate-y-1"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 -translate-y-1"
    >
      <div
        v-if="assistiveFeedback.show"
        class="fixed top-4 left-1/2 -translate-x-1/2 z-[70] pointer-events-none rounded-xl border px-4 py-2.5 text-sm font-medium shadow-xl backdrop-blur"
        :class="assistiveFeedback.type === 'success'
          ? 'border-green-200 bg-green-50/95 text-green-800 dark:border-green-800 dark:bg-green-900/85 dark:text-green-100'
          : 'border-red-200 bg-red-50/95 text-red-800 dark:border-red-800 dark:bg-red-900/85 dark:text-red-100'
        "
      >
        {{ assistiveFeedback.message }}
      </div>
    </Transition>

    <CalculatorModal v-if="calculatorOpen" @close="calculatorOpen = false" />
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Calculator, LoaderCircle, LockKeyholeOpen, LockOpen, RotateCcw } from 'lucide-vue-next';
import AppSidebar from './layout/AppSidebar.vue';
import AppMobileDrawer from './layout/AppMobileDrawer.vue';
import AppBottomNav from './layout/AppBottomNav.vue';
import AssistiveTouchButton from './components/AssistiveTouchButton.vue';
import CalculatorModal from './components/CalculatorModal.vue';
import { apiRequest } from './composables/useApiClient';
import { useAppContext } from './composables/useAppContext';
import { routeLoader } from './routeLoader';

const mobileMenuOpen = ref(false);
const calculatorOpen = ref(false);
const biometricConnected = ref(false);
const doorStatusLoading = ref(false);
const doorKeepUnlocked = ref(false);
const assistiveFeedback = ref({ show: false, type: 'success', message: '' });
const appContext = useAppContext();
let assistiveFeedbackTimer = null;

function showAssistiveFeedback(type, message) {
  assistiveFeedback.value = { show: true, type, message };
  if (assistiveFeedbackTimer) clearTimeout(assistiveFeedbackTimer);
  assistiveFeedbackTimer = setTimeout(() => {
    assistiveFeedback.value.show = false;
  }, 2400);
}

async function refreshBiometricConnection() {
  if (!appContext.permissions?.settings) {
    biometricConnected.value = false;
    doorKeepUnlocked.value = false;
    return;
  }

  try {
    const cfg = await apiRequest('/api/settings/configuration');
    const enabled = (cfg?.data?.['biometric.enabled'] ?? '0') === '1';
    const maker = cfg?.data?.['biometric.device_maker'] ?? '';
    const ip = cfg?.data?.['biometric.device_ip'] ?? '';

    if (!enabled || !maker || !ip) {
      biometricConnected.value = false;
      doorKeepUnlocked.value = false;
      return;
    }

    const result = await apiRequest('/api/settings/biometric/test-connection', { method: 'post' });
    biometricConnected.value = Boolean(result?.success);
    if (!biometricConnected.value) doorKeepUnlocked.value = false;
  } catch {
    biometricConnected.value = false;
    doorKeepUnlocked.value = false;
  }
}

async function triggerBiometricAction(path, options = {}) {
  try {
    const response = await apiRequest(path, { method: 'post' });
    showAssistiveFeedback('success', options.successMessage || response?.message || 'Action completed successfully.');
    if (typeof options.onSuccess === 'function') options.onSuccess();
  } catch (err) {
    biometricConnected.value = false;
    doorKeepUnlocked.value = false;
    showAssistiveFeedback('error', err?.response?.data?.message || 'Action failed. Check biometric connection.');
    if (typeof options.onError === 'function') options.onError(err);
  }
}

async function refreshDoorStatus() {
  if (!biometricConnected.value) return;

  doorStatusLoading.value = true;
  try {
    const response = await apiRequest('/api/settings/biometric/door-status');
    doorKeepUnlocked.value = (response?.state === 'keep_unlock');
  } catch {
    // Keep existing local state if status lookup fails.
  } finally {
    doorStatusLoading.value = false;
  }
}

async function handleAssistiveMenuOpen() {
  if (!biometricConnected.value) {
    await refreshBiometricConnection();
  }
  await refreshDoorStatus();
}

/**
 * AssistiveTouch actions registry.
 * To add a new option later: push a new entry here.
 * Each entry: { id, label, icon (Lucide component), handler }
 */
const assistiveActions = computed(() => {
  const base = [
    {
      id: 'calculator',
      label: 'Calculator',
      icon: Calculator,
      color: 'orange',
      handler: () => { calculatorOpen.value = true; },
    },
  ];

  if (biometricConnected.value) {
    if (doorStatusLoading.value) {
      base.push({
        id: 'biometric-status-loading',
        label: 'Checking Door...',
        icon: LoaderCircle,
        color: 'indigo',
        loading: true,
        disabled: true,
        handler: () => {},
      });

      return base;
    }

    if (!doorKeepUnlocked.value) {
      base.push(
        {
          id: 'biometric-unlock',
          label: 'Door Unlock',
          icon: LockOpen,
          color: 'green',
          handler: () => triggerBiometricAction('/api/settings/biometric/unlock'),
        },
        {
          id: 'biometric-keep-unlock',
          label: 'Door Keep Unlock',
          icon: LockKeyholeOpen,
          color: 'blue',
          handler: () => triggerBiometricAction('/api/settings/biometric/keep-unlock', {
            onSuccess: () => { doorKeepUnlocked.value = true; },
          }),
        },
      );
    } else {
      base.push(
        {
          id: 'biometric-reset-mode',
          label: 'Reset to Usual Access',
          icon: RotateCcw,
          color: 'amber',
          handler: () => triggerBiometricAction('/api/settings/biometric/close', {
            successMessage: 'Door reset to usual mode. Only valid authenticated access is allowed.',
            onSuccess: () => { doorKeepUnlocked.value = false; },
          }),
        },
      );
    }
  }

  return base;
});

onMounted(() => {
  refreshBiometricConnection();
});

onUnmounted(() => {
  if (assistiveFeedbackTimer) clearTimeout(assistiveFeedbackTimer);
});
</script>
