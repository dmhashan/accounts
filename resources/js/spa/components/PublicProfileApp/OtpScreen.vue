<template>
  <div class="flex-1 flex items-center justify-center px-4 sm:px-6 py-10">
    <div class="w-full max-w-sm">
      <!-- Header Branding -->
      <div class="text-center mb-7">
        <div class="member-auth-icon inline-flex items-center justify-center w-20 h-20 rounded-[1.75rem] mb-4 p-3 shadow-xl ring-4 ring-white/20 dark:ring-white/10">
          <KeyRound class="w-9 h-9 text-white" :stroke-width="1.8" />
        </div>
        <h1 class="member-auth-title text-2xl font-extrabold tracking-tight text-gray-900 dark:text-white">
          Verify Verification Code
        </h1>
        <div class="flex items-center justify-center gap-1.5 mt-1.5">
          <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">Sent to</span>
          <span class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ phone }}</span>
          <button
            type="button"
            class="text-[11px] font-bold text-red-500 hover:text-red-600 underline ml-1 cursor-pointer focus:outline-none"
            @click="$emit('back')"
          >
            Edit
          </button>
        </div>
      </div>

      <!-- OTP Card -->
      <div class="member-auth-card rounded-[2rem] p-6 sm:p-7 shadow-xl">
        <p class="member-auth-muted text-xs mb-5 text-center text-gray-500 dark:text-gray-400">
          Enter the 6-digit verification code sent to your mobile phone.
        </p>

        <div v-if="error" class="mb-4 p-3 rounded-2xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 text-xs font-semibold text-red-600 dark:text-red-400 text-center">
          {{ error }}
        </div>

        <form class="space-y-5" @submit.prevent="$emit('submit')">
          <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2 text-center">
              6-Digit Code
            </label>
            <input
              :value="modelValue"
              type="text"
              inputmode="numeric"
              pattern="[0-9]*"
              maxlength="6"
              placeholder="••••••"
              class="member-auth-input rounded-2xl px-4 py-3.5 text-2xl font-extrabold text-center tracking-[0.45em] selection:bg-red-500 focus:outline-none"
              autocomplete="one-time-code"
              autofocus
              required
              @input="handleInput"
            />
          </div>

          <button
            type="submit"
            :disabled="isLoading || modelValue.length < 6"
            class="member-auth-primary w-full py-3.5 rounded-2xl text-sm font-bold flex items-center justify-center gap-2 transition-all cursor-pointer"
          >
            <span v-if="isLoading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
            <span v-if="isLoading">Verifying code&hellip;</span>
            <span v-else>Verify &amp; Continue &rarr;</span>
          </button>

          <button
            type="button"
            class="member-auth-secondary w-full py-2.5 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 cursor-pointer"
            @click="$emit('back')"
          >
            &larr; Use a different number
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { KeyRound } from 'lucide-vue-next';

defineProps({
    phone:      { type: String, default: '' },
    error:      { type: String, default: '' },
    modelValue: { type: String, default: '' },
    isLoading:  { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'submit', 'back']);

function handleInput(event) {
    const clean = event.target.value.replace(/\D/g, '').slice(0, 6);
    emit('update:modelValue', clean);
}
</script>
