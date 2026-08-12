<template>
  <div class="flex-1 flex items-center justify-center px-4 sm:px-6 py-10">
    <div class="w-full max-w-sm">
      <!-- Header Branding -->
      <div class="text-center mb-7">
        <div class="member-auth-icon inline-flex items-center justify-center w-20 h-20 rounded-[1.75rem] mb-4 p-3 shadow-xl ring-4 ring-white/20 dark:ring-white/10">
          <img
            v-if="tenantLogoUrl"
            :src="tenantLogoUrl"
            :alt="tenantName"
            class="w-full h-full object-contain filter drop-shadow"
          />
          <Dumbbell v-else class="w-9 h-9 text-white" :stroke-width="1.8" />
        </div>
        <h1 class="member-auth-title text-2xl font-extrabold tracking-tight text-gray-900 dark:text-white">
          {{ tenantName || 'Member Portal' }}
        </h1>
        <p class="member-auth-muted text-xs font-semibold uppercase tracking-widest mt-1 text-gray-500 dark:text-gray-400">
          Member Self-Service Portal
        </p>
      </div>

      <!-- Login Card -->
      <div class="member-auth-card rounded-[2rem] p-6 sm:p-7 shadow-xl">
        <div class="mb-5 text-center">
          <h2 class="text-base font-bold text-gray-900 dark:text-white">
            Welcome back
          </h2>
          <p class="member-auth-muted text-xs mt-1 text-gray-500 dark:text-gray-400">
            Enter your registered mobile number to receive a verification code.
          </p>
        </div>

        <div v-if="error" class="mb-4 p-3 rounded-2xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 text-xs font-semibold text-red-600 dark:text-red-400 text-center animate-shake">
          {{ error }}
        </div>

        <form class="space-y-4" @submit.prevent="$emit('submit')">
          <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
              Mobile Number
            </label>
            <AppFormPhoneInput
              :model-value="modelValue"
              :disabled="isLoading"
              @update:model-value="$emit('update:modelValue', $event)"
            />
          </div>

          <button
            type="submit"
            :disabled="isLoading || !modelValue"
            class="member-auth-primary w-full py-3.5 rounded-2xl text-sm font-bold flex items-center justify-center gap-2 transition-all cursor-pointer"
          >
            <span v-if="isLoading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
            <span v-if="isLoading">Sending verification code&hellip;</span>
            <span v-else>Continue with OTP &rarr;</span>
          </button>
        </form>

        <div class="mt-6 pt-4 border-t border-gray-100 dark:border-zinc-800 flex items-center justify-center gap-1.5 text-[11px] font-medium text-gray-400 dark:text-gray-500">
          <ShieldCheck class="w-3.5 h-3.5 text-emerald-500" :stroke-width="2" />
          <span>Secure &amp; Instant Verification</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Dumbbell, ShieldCheck } from 'lucide-vue-next';
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
