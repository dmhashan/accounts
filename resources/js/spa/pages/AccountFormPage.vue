<template>
  <section class="app-page-frame">
    <AppPageHeader show-back />

    <div class="app-page-scroll">
      <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
        {{ errorMessage }}
      </div>

      <form class="app-surface rounded-2xl p-4 md:p-6" @submit.prevent="submit">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AppFormField label="Account Name" class="md:col-span-2" required>
            <AppFormInput
              v-model="form.name"
              type="text"
              required
              maxlength="255"
            />
          </AppFormField>

          <AppFormField label="Opening Balance">
            <AppFormInput v-model="form.opening_balance" type="number" step="0.01" />
          </AppFormField>

          <AppFormField label="Description" class="md:col-span-2" optional>
            <AppFormTextarea v-model="form.description" rows="4" maxlength="1000" />
          </AppFormField>
        </div>

        <div class="mt-5 flex items-center justify-end gap-2">
          <RouterLink to="/settings/accounts" class="px-4 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg text-sm">
            Cancel
          </RouterLink>
          <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm" :disabled="submitting">
            {{ submitting ? 'Saving...' : (isEdit ? 'Update Account' : 'Create Account') }}
          </button>
        </div>
      </form>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiRequest } from '../composables/useApiClient';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppFormField from '../components/forms/AppFormField.vue';
import AppFormInput from '../components/forms/AppFormInput.vue';
import AppFormTextarea from '../components/forms/AppFormTextarea.vue';

const route = useRoute();
const router = useRouter();
const isEdit = computed(() => Boolean(route.params.id));
const submitting = ref(false);
const errorMessage = ref('');

const form = ref({
    name: '',
    opening_balance: '0.00',
    description: '',
});

async function loadAccount() {
    if (!isEdit.value) {
        return;
    }

    const response = await apiRequest(`/api/accounts/${route.params.id}`);
    const account = response.data || {};

    form.value = {
        name: account.name || '',
        opening_balance: String(account.opening_balance ?? '0.00'),
        description: account.description || '',
    };
}

async function submit() {
    submitting.value = true;
    errorMessage.value = '';

    try {
        const payload = {
            name: form.value.name,
            opening_balance: form.value.opening_balance,
            description: form.value.description || null,
        };

        if (isEdit.value) {
            await apiRequest(`/api/accounts/${route.params.id}`, {
                method: 'put',
                data: payload,
            });
        } else {
            await apiRequest('/api/accounts', {
                method: 'post',
                data: payload,
            });
        }

        router.push('/settings/accounts');
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to save account.';
    } finally {
        submitting.value = false;
    }
}

onMounted(async () => {
    try {
        await loadAccount();
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load account data.';
    }
});
</script>
