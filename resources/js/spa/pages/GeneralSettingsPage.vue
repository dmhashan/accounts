<template>
  <section class="app-page-frame">
    <AppPageHeader title="Fitness Center Details" />

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

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Logo card -->
        <div class="app-surface rounded-2xl p-4 md:p-6 flex flex-col items-center gap-4">
          <h3 class="text-sm font-semibold text-secondary-700 dark:text-secondary-300 uppercase tracking-wide self-start">
            Logo
          </h3>

          <div class="app-logo-tile w-32 h-32 rounded-xl overflow-hidden flex items-center justify-center shrink-0">
            <img
              v-if="logoUrl"
              :src="logoUrl"
              alt="Fitness center logo"
              class="w-full h-full object-contain"
            />
            <ImageIcon v-else class="w-12 h-12 text-secondary-400 dark:text-secondary-600" />
          </div>

          <div class="flex flex-col gap-2 w-full">
            <label class="w-full cursor-pointer">
              <input
                ref="logoInput"
                type="file"
                accept="image/jpeg,image/png,image/webp,image/svg+xml"
                class="sr-only"
                @change="handleLogoChange"
              />
              <span
                class="flex items-center justify-center gap-2 w-full px-3 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg text-sm text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-800 transition-colors"
                :class="{ 'opacity-50 pointer-events-none': logoUploading }"
              >
                <Upload class="w-4 h-4" />
                {{ logoUploading ? 'Uploading...' : 'Upload Logo' }}
              </span>
            </label>

            <button
              v-if="logoUrl"
              type="button"
              class="flex items-center justify-center gap-2 w-full px-3 py-2 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 rounded-lg text-sm hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors disabled:opacity-50"
              :disabled="logoUploading"
              @click="removeLogo"
            >
              <Trash2 class="w-4 h-4" />
              Remove Logo
            </button>
          </div>

          <p class="text-xs text-secondary-400 dark:text-secondary-500 text-center">
            JPG, PNG, WebP or SVG · Max 2 MB
          </p>
        </div>

        <!-- Details form -->
        <div class="lg:col-span-2">
          <form class="app-surface rounded-2xl p-4 md:p-6" @submit.prevent="submit">
            <div v-if="loading" class="py-8 text-center text-sm text-secondary-500 dark:text-secondary-400">
              Loading...
            </div>

            <template v-else>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <AppFormField label="Fitness Center Name" class="md:col-span-2" required>
                  <AppFormInput
                    v-model="form.name"
                    type="text"
                    required
                    maxlength="255"
                    placeholder="e.g. Iron Gym Colombo"
                  />
                </AppFormField>

                <AppFormField label="Email Address" optional>
                  <AppFormInput
                    v-model="form.email"
                    type="email"
                    maxlength="255"
                    placeholder="info@example.com"
                  />
                </AppFormField>

                <AppFormField label="Contact Number" optional>
                  <AppFormPhoneInput
                    v-model="form.phone"
                  />
                </AppFormField>

                <AppFormField label="Address" class="md:col-span-2" optional>
                  <AppFormTextarea
                    v-model="form.address"
                    rows="3"
                    maxlength="500"
                    placeholder="Street, City, Country"
                  />
                </AppFormField>
              </div>

              <div class="mt-5 flex items-center justify-end">
                <button
                  type="submit"
                  class="px-5 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium disabled:opacity-50 transition-colors"
                  :disabled="submitting"
                >
                  {{ submitting ? 'Saving...' : 'Save Changes' }}
                </button>
              </div>
            </template>
          </form>
        </div>
      </div>

      <!-- Appearance -->
      <div class="app-surface rounded-2xl p-4 md:p-6 mt-6">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
          <div>
            <h3 class="text-sm font-semibold text-secondary-700 dark:text-secondary-300 uppercase tracking-wide">
              Appearance
            </h3>
            <p class="mt-1 text-sm text-secondary-500 dark:text-secondary-400">
              Set the brand palette and default display mode for everyone in this tenant.
            </p>
          </div>
          <span class="mt-2 inline-flex w-fit items-center rounded-full bg-primary-50 px-2.5 py-1 text-xs font-semibold text-primary-700 dark:bg-primary-900/30 dark:text-primary-300 sm:mt-0">
            Live preview
          </span>
        </div>

        <div v-if="formatsLoading" class="py-6 text-center text-sm text-secondary-500 dark:text-secondary-400">
          Loading...
        </div>

        <template v-else>
          <fieldset class="mt-6">
            <legend class="text-xs font-semibold text-secondary-700 dark:text-secondary-300">
              Color palette
            </legend>
            <div class="mt-3 grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
              <button
                v-for="theme in colorThemes"
                :key="theme.value"
                type="button"
                class="group relative overflow-hidden rounded-xl border p-3 text-left transition-all hover:-translate-y-0.5 hover:shadow-md"
                :class="appearance.colorTheme === theme.value
                  ? 'border-primary-500 bg-primary-50/70 ring-2 ring-primary-500/25 dark:bg-primary-900/20'
                  : 'border-secondary-200 bg-white/60 hover:border-secondary-300 dark:border-secondary-700 dark:bg-secondary-900/40 dark:hover:border-secondary-600'"
                :aria-pressed="appearance.colorTheme === theme.value"
                @click="previewColorTheme(theme.value)"
              >
                <span class="mb-3 flex h-8 overflow-hidden rounded-lg shadow-sm">
                  <span
                    v-for="color in theme.colors"
                    :key="color"
                    class="flex-1"
                    :style="{ backgroundColor: color }"
                  />
                </span>
                <span class="flex items-center justify-between gap-2">
                  <span>
                    <span class="block text-sm font-semibold text-secondary-900 dark:text-white">{{ theme.label }}</span>
                    <span class="mt-0.5 block text-[11px] leading-tight text-secondary-500 dark:text-secondary-400">{{ theme.description }}</span>
                  </span>
                  <Check v-if="appearance.colorTheme === theme.value" class="h-4 w-4 shrink-0 text-primary-600 dark:text-primary-400" />
                </span>
              </button>
            </div>
          </fieldset>

          <fieldset class="mt-6">
            <legend class="text-xs font-semibold text-secondary-700 dark:text-secondary-300">
              Default display mode
            </legend>
            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
              <button
                v-for="mode in colorModes"
                :key="mode.value"
                type="button"
                class="flex items-center gap-3 rounded-xl border p-3 text-left transition-colors"
                :class="appearance.colorMode === mode.value
                  ? 'border-primary-500 bg-primary-50/70 ring-2 ring-primary-500/25 dark:bg-primary-900/20'
                  : 'border-secondary-200 bg-white/60 hover:border-secondary-300 dark:border-secondary-700 dark:bg-secondary-900/40 dark:hover:border-secondary-600'"
                :aria-pressed="appearance.colorMode === mode.value"
                @click="previewColorMode(mode.value)"
              >
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-secondary-100 text-secondary-600 dark:bg-secondary-800 dark:text-secondary-300">
                  <Monitor v-if="mode.value === 'system'" class="h-5 w-5" />
                  <Sun v-else-if="mode.value === 'light'" class="h-5 w-5" />
                  <Moon v-else class="h-5 w-5" />
                </span>
                <span class="min-w-0 flex-1">
                  <span class="block text-sm font-semibold text-secondary-900 dark:text-white">{{ mode.label }}</span>
                  <span class="mt-0.5 block text-xs text-secondary-500 dark:text-secondary-400">{{ mode.description }}</span>
                </span>
                <Check v-if="appearance.colorMode === mode.value" class="h-4 w-4 shrink-0 text-primary-600 dark:text-primary-400" />
              </button>
            </div>
          </fieldset>

          <div class="mt-5 flex items-center justify-end">
            <button
              type="button"
              class="px-5 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium disabled:opacity-50 transition-colors"
              :disabled="appearanceSaving"
              @click="saveAppearance"
            >
              {{ appearanceSaving ? 'Saving...' : 'Save Appearance' }}
            </button>
          </div>
        </template>
      </div>

      <!-- Date & Time Format -->  
      <div class="app-surface rounded-2xl p-4 md:p-6 mt-6">
        <h3 class="text-sm font-semibold text-secondary-700 dark:text-secondary-300 uppercase tracking-wide mb-4">
          Date &amp; Time Format
        </h3>

        <div v-if="formatsLoading" class="py-4 text-center text-sm text-secondary-500 dark:text-secondary-400">
          Loading...
        </div>

        <template v-else>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <AppFormField label="Date Format" required>
              <select
                v-model="formats.dateFormat"
                class="app-form-control w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
              >
                <option v-for="opt in dateFormatOptions" :key="opt.value" :value="opt.value">
                  {{ opt.label }} ({{ opt.example }})
                </option>
              </select>
            </AppFormField>

            <AppFormField label="Time Format" required>
              <select
                v-model="formats.timeFormat"
                class="app-form-control w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
              >
                <option v-for="opt in timeFormatOptions" :key="opt.value" :value="opt.value">
                  {{ opt.label }} ({{ opt.example }})
                </option>
              </select>
            </AppFormField>
          </div>

          <div class="mt-1">
            <p class="text-xs text-secondary-400 dark:text-secondary-500">
              Preview: <span class="font-medium text-secondary-700 dark:text-secondary-300">{{ formatPreview }}</span>
            </p>
          </div>

          <div class="mt-4 flex items-center justify-end">
            <button
              type="button"
              class="px-5 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium disabled:opacity-50 transition-colors"
              :disabled="formatsSaving"
              @click="saveFormats"
            >
              {{ formatsSaving ? 'Saving...' : 'Save Format' }}
            </button>
          </div>
        </template>
      </div>

      <!-- Member Reachable Configurations -->
      <div class="app-surface rounded-2xl p-4 md:p-6 mt-6">
        <h3 class="text-sm font-semibold text-secondary-700 dark:text-secondary-300 uppercase tracking-wide mb-4">
          Member Reachable Configurations
        </h3>

        <div v-if="formatsLoading" class="py-4 text-center text-sm text-secondary-500 dark:text-secondary-400">
          Loading...
        </div>

        <template v-else>
          <MemberReachableConfigFields v-model="memberReachableConfig" />

          <div class="mt-4 flex items-center justify-end">
            <button
              type="button"
              class="px-5 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium disabled:opacity-50 transition-colors"
              :disabled="memberNotificationsSaving"
              @click="saveMemberNotifications"
            >
              {{ memberNotificationsSaving ? 'Saving...' : 'Save Member Notifications' }}
            </button>
          </div>
        </template>
      </div>
    </div>

    <!-- Logo Crop Modal -->
    <AvatarCropModal
      v-if="cropSrc"
      :image-src="cropSrc"
      title="Crop Logo"
      confirm-label="Crop & Upload"
      :output-size="512"
      output-format="image/png"
      @confirm="onCropConfirm"
      @cancel="onCropCancel"
    />
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { Check, ImageIcon, Monitor, Moon, Sun, Upload, Trash2 } from 'lucide-vue-next';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppFormField from '../components/forms/AppFormField.vue';
import AppFormInput from '../components/forms/AppFormInput.vue';
import AppFormPhoneInput from '../components/forms/AppFormPhoneInput.vue';
import AppFormTextarea from '../components/forms/AppFormTextarea.vue';
import AvatarCropModal from '../components/AvatarCropModal.vue';
import MemberReachableConfigFields from '../components/settings/MemberReachableConfigFields.vue';
import { apiRequest } from '../composables/useApiClient';
import { COLOR_MODES, COLOR_THEMES, setColorTheme, setDefaultMode, setUserMode } from '../composables/useTheme';

const loading = ref(true);
const loadError = ref('');
const submitting = ref(false);
const saveError = ref('');
const successMessage = ref('');
const logoUploading = ref(false);
const logoUrl = ref(null);
const logoInput = ref(null);
const cropSrc = ref(null);

const form = ref({
    name: '',
    email: '',
    phone: '',
    address: '',
});

const formatsLoading = ref(false);
const formatsSaving = ref(false);
const formats = ref({ dateFormat: 'D MMM YYYY', timeFormat: 'HH:mm' });
const appearanceSaving = ref(false);
const appearance = ref({ colorTheme: 'crimson', colorMode: 'system' });
const colorThemes = COLOR_THEMES;
const colorModes = COLOR_MODES;
const dateFormatOptions = ref([]);
const timeFormatOptions = ref([]);
const memberNotificationsSaving = ref(false);
const memberReachableConfig = ref({
    member_login_url: '',
});

function parseMemberReachableConfig(raw) {
    try {
        const config = JSON.parse(raw || '{}') || {};

        return {
            member_login_url: config.member_login_url || '',
        };
    } catch {
        return {
            member_login_url: '',
        };
    }
}

function serializeMemberNotificationConfig() {
    return JSON.stringify({
        member_login_url: memberReachableConfig.value.member_login_url || '',
    });
}

const MONTHS_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
const formatPreview = computed(() => {
    const now = new Date();
    const d = now.getDate(), m = now.getMonth(), y = now.getFullYear();
    const mm = String(m + 1).padStart(2, '0'), dd = String(d).padStart(2, '0');
    let datePart;
    switch (formats.value.dateFormat) {
        case 'DD/MM/YYYY':  datePart = `${dd}/${mm}/${y}`; break;
        case 'MM/DD/YYYY':  datePart = `${mm}/${dd}/${y}`; break;
        case 'YYYY-MM-DD':  datePart = `${y}-${mm}-${dd}`; break;
        case 'MMM D, YYYY': datePart = `${MONTHS_SHORT[m]} ${d}, ${y}`; break;
        default:            datePart = `${d} ${MONTHS_SHORT[m]} ${y}`;
    }
    const h = now.getHours(), min = String(now.getMinutes()).padStart(2, '0');
    const timePart = formats.value.timeFormat === 'h:mm A'
        ? `${h % 12 || 12}:${min} ${h < 12 ? 'AM' : 'PM'}`
        : `${String(h).padStart(2, '0')}:${min}`;
    return `${datePart} ${timePart}`;
});

async function load() {
    loading.value = true;
    loadError.value = '';
    try {
        const response = await apiRequest('/api/settings/general');
        const data = response.data || {};
        form.value = {
            name: data.name || '',
            email: data.email || '',
            phone: data.phone || '',
            address: data.address || '',
        };
        logoUrl.value = data.logo_url || null;
    } catch {
        loadError.value = 'Failed to load settings.';
    } finally {
        loading.value = false;
    }
}

async function loadFormats() {
    formatsLoading.value = true;
    try {
        const [cfgRes, optRes] = await Promise.all([
            apiRequest('/api/settings/configuration'),
            apiRequest('/api/settings/configuration/format-options'),
        ]);
        const cfg = cfgRes.data || {};
        formats.value = {
            dateFormat: cfg['general.date_format'] || 'D MMM YYYY',
            timeFormat: cfg['general.time_format'] || 'HH:mm',
        };
        appearance.value = {
            colorTheme: cfg['general.color_theme'] || 'crimson',
            colorMode: cfg['general.color_mode'] || 'system',
        };
        memberReachableConfig.value = parseMemberReachableConfig(cfg['general.member_notifications']);
        dateFormatOptions.value = optRes.date_formats || [];
        timeFormatOptions.value = optRes.time_formats || [];
    } catch { /* ignore */ } finally {
        formatsLoading.value = false;
    }
}

function previewColorTheme(theme) {
    appearance.value.colorTheme = theme;
    setColorTheme(theme);
}

function previewColorMode(mode) {
    appearance.value.colorMode = mode;
    setDefaultMode(mode);
    setUserMode(mode);
}

async function saveAppearance() {
    appearanceSaving.value = true;
    saveError.value = '';
    successMessage.value = '';
    try {
        await apiRequest('/api/settings/configuration', {
            method: 'put',
            data: {
                'general.color_theme': appearance.value.colorTheme,
                'general.color_mode': appearance.value.colorMode,
            },
        });
        setColorTheme(appearance.value.colorTheme);
        setDefaultMode(appearance.value.colorMode);
        setUserMode(appearance.value.colorMode);
        successMessage.value = 'Appearance saved for this tenant.';
        setTimeout(() => { successMessage.value = ''; }, 3000);
    } catch (error) {
        saveError.value = error?.response?.data?.message || 'Failed to save appearance.';
    } finally {
        appearanceSaving.value = false;
    }
}

async function saveFormats() {
    formatsSaving.value = true;
    saveError.value = '';
    try {
        await apiRequest('/api/settings/configuration', {
            method: 'put',
            data: {
                'general.date_format': formats.value.dateFormat,
                'general.time_format': formats.value.timeFormat,
            },
        });
        successMessage.value = 'Format settings saved. Reload the page to see updated dates.';
        setTimeout(() => { successMessage.value = ''; }, 4000);
    } catch (error) {
        saveError.value = error?.response?.data?.message || 'Failed to save format settings.';
    } finally {
        formatsSaving.value = false;
    }
}

async function saveMemberNotifications() {
    memberNotificationsSaving.value = true;
    saveError.value = '';
    successMessage.value = '';
    try {
        await apiRequest('/api/settings/configuration', {
            method: 'put',
            data: {
                'general.member_notifications': serializeMemberNotificationConfig(),
            },
        });
        successMessage.value = 'Member notification settings saved.';
        setTimeout(() => { successMessage.value = ''; }, 3000);
    } catch (error) {
        saveError.value = error?.response?.data?.message || error?.message || 'Failed to save member notification settings.';
    } finally {
        memberNotificationsSaving.value = false;
    }
}

async function submit() {
    submitting.value = true;
    saveError.value = '';
    successMessage.value = '';
    try {
        const response = await apiRequest('/api/settings/general', {
            method: 'put',
            data: {
                name: form.value.name,
                email: form.value.email || null,
                phone: form.value.phone || null,
                address: form.value.address || null,
            },
        });
        const data = response.data || {};
        form.value = {
            name: data.name || '',
            email: data.email || '',
            phone: data.phone || '',
            address: data.address || '',
        };
        logoUrl.value = data.logo_url || logoUrl.value;
        successMessage.value = 'Settings saved successfully.';
        setTimeout(() => { successMessage.value = ''; }, 3000);
    } catch (error) {
        saveError.value = error?.response?.data?.message || 'Failed to save settings.';
    } finally {
        submitting.value = false;
    }
}

function handleLogoChange(event) {
    const file = event.target.files?.[0];
    if (logoInput.value) logoInput.value.value = '';
    if (!file) return;

    // SVG is a vector — no crop needed, upload directly
    if (file.type === 'image/svg+xml') {
        uploadLogoFile(file);
        return;
    }

    const reader = new FileReader();
    reader.onload = (e) => { cropSrc.value = e.target.result; };
    reader.readAsDataURL(file);
}

async function onCropConfirm(blob) {
    cropSrc.value = null;
    await uploadLogoFile(new File([blob], 'logo.png', { type: 'image/png' }));
}

function onCropCancel() {
    cropSrc.value = null;
}

async function uploadLogoFile(file) {
    logoUploading.value = true;
    saveError.value = '';
    try {
        const formData = new FormData();
        formData.append('logo', file);
        const response = await apiRequest('/api/settings/general/logo', {
            method: 'post',
            data: formData,
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        logoUrl.value = response.logo_url || null;
    } catch (error) {
        saveError.value = error?.response?.data?.message || 'Failed to upload logo.';
    } finally {
        logoUploading.value = false;
    }
}

async function removeLogo() {
    logoUploading.value = true;
    saveError.value = '';
    try {
        await apiRequest('/api/settings/general/logo', { method: 'delete' });
        logoUrl.value = null;
    } catch (error) {
        saveError.value = error?.response?.data?.message || 'Failed to remove logo.';
    } finally {
        logoUploading.value = false;
    }
}

onMounted(() => { load(); loadFormats(); });
</script>
