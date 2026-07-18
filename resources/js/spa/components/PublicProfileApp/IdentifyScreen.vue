<template>
  <div class="flex-1 flex items-center justify-center px-5">
    <div class="w-full max-w-sm">
      <div class="text-center mb-8">
        <div class="member-auth-icon inline-flex items-center justify-center w-16 h-16 rounded-3xl mb-4 overflow-hidden">
          <img
            v-if="tenantLogoUrl"
            :src="tenantLogoUrl"
            :alt="tenantName"
            class="member-auth-logo w-full h-full object-contain"
          />
          <Phone v-else class="w-8 h-8 text-white" :stroke-width="1.8" />
        </div>
        <h1 class="member-auth-title text-2xl font-bold">
          {{ tenantName || 'Member Portal' }}
        </h1>
        <p class="member-auth-muted text-sm mt-1">
          Member Portal
        </p>
      </div>

      <div class="member-auth-card rounded-3xl p-6">
        <p class="member-auth-muted text-sm mb-5 text-center">
          Enter your registered mobile number to continue.
        </p>

        <div v-if="error" class="app-alert app-alert-error rounded-2xl">
          {{ error }}
        </div>

        <form class="space-y-4" @submit.prevent="$emit('submit')">
          <div>
            <AppFormPhoneInput
              :model-value="modelValue"
              :disabled="isLoading"
              @update:model-value="$emit('update:modelValue', $event)"
            />
          </div>
          <button
            type="submit"
            :disabled="isLoading"
            class="member-auth-primary w-full py-3.5 rounded-2xl text-sm font-bold"
          >
            <span v-if="isLoading">Sending OTP&hellip;</span>
            <span v-else>Send OTP</span>
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Phone } from 'lucide-vue-next';
import AppFormPhoneInput from '../forms/AppFormPhoneInput.vue';
defineProps({
    tenantName:    { type: String, default: '' },
    tenantLogoUrl: { type: String, default: null },
    error:         { type: String, default: '' },
    modelValue:    { type: String, default: '' },
    isLoading:     { type: Boolean, default: false },
});

defineEmits(['update:modelValue', 'submit']);
</script>
