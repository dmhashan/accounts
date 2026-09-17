<template>
  <section class="app-page-frame">
    <AppPageHeader title="Gym Member Terms & Conditions" />

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
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h3 class="text-base font-semibold text-secondary-900 dark:text-white">
              Member Terms &amp; Conditions Settings
            </h3>
            <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-0.5">
              Turn on member terms &amp; conditions and customize the content using the rich text editor below.
            </p>
          </div>

          <div v-if="publicTermsUrl" class="mt-2 sm:mt-0">
            <a
              :href="publicTermsUrl"
              target="_blank"
              rel="noreferrer"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-secondary-300 dark:border-secondary-600 text-xs font-medium text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-800 transition-colors"
            >
              <ExternalLink class="w-3.5 h-3.5" />
              Preview Public Page
            </a>
          </div>
        </div>

        <div v-if="loading" class="py-8 text-center text-sm text-secondary-500 dark:text-secondary-400">
          Loading configuration...
        </div>

        <template v-else>
          <!-- Enable Switch -->
          <div class="p-4 rounded-xl bg-secondary-50 dark:bg-secondary-900/60 border border-secondary-200 dark:border-secondary-700 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
              <p class="text-sm font-semibold text-secondary-900 dark:text-white">
                Enable Gym Member Terms &amp; Conditions
              </p>
              <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">
                When enabled, your terms will be publicly readable at your tenant's dedicated URL.
              </p>
            </div>
            <div class="w-full sm:w-48 shrink-0">
              <AppFormSwitch
                v-model="enabled"
                true-label="Enabled"
                false-label="Disabled"
              />
            </div>
          </div>

          <!-- Public Link Share Box -->
          <div v-if="publicTermsUrl" class="p-4 rounded-xl border border-primary-200 dark:border-primary-800/40 bg-primary-50/50 dark:bg-primary-900/10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div class="min-w-0">
              <span class="text-xs font-semibold uppercase tracking-wider text-primary-700 dark:text-primary-300">Public Link</span>
              <p class="text-xs font-mono text-secondary-800 dark:text-secondary-200 truncate mt-0.5 select-all">
                {{ publicTermsUrl }}
              </p>
            </div>
            <button
              type="button"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-xs font-medium transition-colors shrink-0"
              @click="copyPublicUrl"
            >
              <Check v-if="copied" class="w-3.5 h-3.5" />
              <Copy v-else class="w-3.5 h-3.5" />
              {{ copied ? 'Copied!' : 'Copy Link' }}
            </button>
          </div>

          <!-- Rich Text Editor Section (if turned on) -->
          <div v-if="enabled" class="space-y-2">
            <label class="block text-sm font-semibold text-secondary-800 dark:text-secondary-200">
              Terms &amp; Conditions Content
            </label>
            <AppRichTextEditor
              v-model="content"
              placeholder="Enter your gym member terms and conditions, safety rules, membership policies, or cancellation terms..."
            />
          </div>

          <!-- Save Button -->
          <div class="pt-4 border-t border-secondary-200 dark:border-secondary-700 flex items-center justify-end">
            <button
              type="button"
              class="px-5 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium disabled:opacity-50 transition-colors"
              :disabled="saving"
              @click="save"
            >
              {{ saving ? 'Saving...' : 'Save Changes' }}
            </button>
          </div>
        </template>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { Copy, Check, ExternalLink } from 'lucide-vue-next';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppFormSwitch from '../components/forms/AppFormSwitch.vue';
import AppRichTextEditor from '../components/forms/AppRichTextEditor.vue';
import { apiRequest } from '../composables/useApiClient';
import { useAppContext } from '../composables/useAppContext';

const context = useAppContext();

const loading = ref(true);
const saving = ref(false);
const loadError = ref('');
const saveError = ref('');
const successMessage = ref('');
const copied = ref(false);

const enabled = ref(false);
const content = ref('');

const publicTermsUrl = computed(() => {
    const domain = context.tenant?.domain;
    if (!domain) {
        return `${window.location.origin}/members_terms_and_conditions`;
    }
    // Form URL like https://<subdomain>.beforward.lk/members_terms_and_conditions or current host
    const protocol = window.location.protocol;
    const host = window.location.host;
    return `${protocol}//${host}/members_terms_and_conditions`;
});

async function load() {
    loading.value = true;
    loadError.value = '';
    try {
        const response = await apiRequest('/api/settings/configuration');
        const data = response.data || {};
        enabled.value = (data['terms.enabled'] ?? '0') === '1';
        content.value = data['terms.content'] || '';
    } catch (error) {
        loadError.value = error?.response?.data?.message || 'Failed to load configuration.';
    } finally {
        loading.value = false;
    }
}

async function save() {
    saving.value = true;
    saveError.value = '';
    successMessage.value = '';
    try {
        await apiRequest('/api/settings/configuration', {
            method: 'put',
            data: {
                'terms.enabled': enabled.value ? '1' : '0',
                'terms.content': content.value,
            },
        });
        successMessage.value = 'Gym member terms & conditions updated successfully.';
        setTimeout(() => {
            successMessage.value = '';
        }, 4000);
    } catch (error) {
        saveError.value = error?.response?.data?.message || 'Failed to save settings.';
    } finally {
        saving.value = false;
    }
}

function copyPublicUrl() {
    if (!publicTermsUrl.value) return;
    navigator.clipboard.writeText(publicTermsUrl.value);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2500);
}

onMounted(() => {
    load();
});
</script>
