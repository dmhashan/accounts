<template>
  <!-- eslint-disable-next-line vue/valid-v-on -->
  <div class="fixed inset-0 z-40 flex items-center justify-center p-4" @keydown.escape.window="$emit('close')">
    <div class="absolute inset-0 bg-black/45" @click="$emit('close')" />

    <div class="relative z-10 w-full max-w-md rounded-2xl app-surface shadow-xl">
      <!-- Header -->
      <div class="flex items-start justify-between gap-3 px-5 py-4 border-b border-secondary-200 dark:border-secondary-700">
        <div>
          <h3 class="text-base font-semibold text-secondary-900 dark:text-white">
            Add Temporary Member
          </h3>
          <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">
            Quick entry — only a name is required.
          </p>
        </div>
        <button
          type="button"
          class="text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-200 mt-0.5"
          aria-label="Close"
          @click="$emit('close')"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Form -->
      <form class="px-5 py-4 space-y-4" @submit.prevent="submit">
        <!-- Name row -->
        <div class="grid grid-cols-2 gap-3">
          <AppFormField label="First Name" :required="!form.last_name">
            <AppFormInput
              v-model.trim="form.first_name"
              placeholder="e.g. John"
              autocomplete="off"
            />
          </AppFormField>
          <AppFormField label="Last Name" :required="!form.first_name">
            <AppFormInput
              v-model.trim="form.last_name"
              placeholder="e.g. Silva"
              autocomplete="off"
            />
          </AppFormField>
        </div>
        <p v-if="errors.first_name" class="text-xs text-red-600 dark:text-red-400 -mt-2">
          {{ errors.first_name }}
        </p>

        <!-- Phone -->
        <AppFormField label="Phone Number" optional>
          <AppFormInput
            v-model.trim="form.phone_number"
            type="tel"
            placeholder="e.g. 0771234567"
            autocomplete="off"
          />
          <p v-if="errors.phone_number" class="mt-1 text-xs text-red-600 dark:text-red-400">
            {{ errors.phone_number }}
          </p>
        </AppFormField>

        <!-- Email -->
        <AppFormField label="Email" optional>
          <AppFormInput
            v-model.trim="form.email"
            type="email"
            placeholder="e.g. john@example.com"
            autocomplete="off"
          />
          <p v-if="errors.email" class="mt-1 text-xs text-red-600 dark:text-red-400">
            {{ errors.email }}
          </p>
        </AppFormField>

        <!-- General error -->
        <div v-if="generalError" class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-3 py-2 text-sm text-red-700 dark:text-red-300">
          {{ generalError }}
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-2 pt-1">
          <button
            type="button"
            class="px-4 py-2 text-sm rounded-xl border border-secondary-300 dark:border-secondary-600 text-secondary-700 dark:text-secondary-200 hover:bg-secondary-100 dark:hover:bg-secondary-800 transition-colors"
            :disabled="saving"
            @click="$emit('close')"
          >
            Cancel
          </button>
          <button
            type="submit"
            class="px-4 py-2 text-sm font-semibold rounded-xl bg-gradient-to-r from-primary-500 to-primary-700 text-white disabled:opacity-50 transition-opacity"
            :disabled="saving"
          >
            {{ saving ? 'Saving...' : 'Create Temp Member' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { X } from 'lucide-vue-next';
import AppFormField from './forms/AppFormField.vue';
import AppFormInput from './forms/AppFormInput.vue';
import { apiRequest } from '../composables/useApiClient';

const emit = defineEmits(['close', 'created']);

const form = reactive({
    first_name: '',
    last_name: '',
    phone_number: '',
    email: '',
});

const saving = ref(false);
const errors = ref({});
const generalError = ref('');

async function submit() {
    errors.value = {};
    generalError.value = '';

    const firstName = form.first_name.trim();
    const lastName = form.last_name.trim();

    if (!firstName && !lastName) {
        errors.value.first_name = 'Either first name or last name is required.';
        return;
    }

    saving.value = true;

    try {
        const payload = {};
        if (firstName) payload.first_name = firstName;
        if (lastName) payload.last_name = lastName;
        if (form.phone_number.trim()) payload.phone_number = form.phone_number.trim();
        if (form.email.trim()) payload.email = form.email.trim();

        const response = await apiRequest('/api/members/temp', {
            method: 'POST',
            data: payload,
        });

        emit('created', response.data?.id);
    } catch (error) {
        const data = error?.response?.data;
        if (data?.errors) {
            errors.value = Object.fromEntries(
                Object.entries(data.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v])
            );
        }
        generalError.value = data?.message || 'Failed to create temporary member.';
    } finally {
        saving.value = false;
    }
}
</script>
