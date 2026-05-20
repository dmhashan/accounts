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

          <div class="w-32 h-32 rounded-xl overflow-hidden bg-secondary-100 dark:bg-secondary-800 flex items-center justify-center shrink-0 border border-secondary-200 dark:border-secondary-700">
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
                  <AppFormInput
                    v-model="form.phone"
                    type="tel"
                    maxlength="50"
                    placeholder="+94 77 123 4567"
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
import { onMounted, ref } from 'vue';
import { ImageIcon, Upload, Trash2 } from 'lucide-vue-next';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppFormField from '../components/forms/AppFormField.vue';
import AppFormInput from '../components/forms/AppFormInput.vue';
import AppFormTextarea from '../components/forms/AppFormTextarea.vue';
import AvatarCropModal from '../components/AvatarCropModal.vue';
import { apiRequest } from '../composables/useApiClient';

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

onMounted(load);
</script>
