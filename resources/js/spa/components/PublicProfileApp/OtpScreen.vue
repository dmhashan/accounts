<template>
  <div class="flex-1 flex items-center justify-center px-5">
    <div class="w-full max-w-sm">
      <div class="text-center mb-8">
        <div class="member-auth-icon inline-flex items-center justify-center w-16 h-16 rounded-3xl mb-4">
          <Lock class="w-8 h-8 text-white" :stroke-width="1.8" />
        </div>
        <h1 class="member-auth-title text-2xl font-bold">
          Verify OTP
        </h1>
        <p class="member-auth-muted text-sm mt-1">
          Code sent to {{ phone }}
        </p>
      </div>

      <div class="member-auth-card rounded-3xl p-6">
        <p class="member-auth-muted text-sm mb-5 text-center">
          Enter the 6-digit code we sent via SMS.
        </p>

        <div v-if="error" class="app-alert app-alert-error rounded-2xl">
          {{ error }}
        </div>

        <form class="space-y-4" @submit.prevent="$emit('submit')">
          <div>
            <label class="member-auth-label block text-xs font-semibold mb-1.5 uppercase tracking-wide">Verification Code</label>
            <input
              :value="modelValue"
              type="text"
              inputmode="numeric"
              maxlength="6"
              placeholder="000000"
              class="member-auth-input rounded-2xl px-4 py-3.5 text-sm font-bold text-center tracking-[0.5em]"
              autocomplete="one-time-code"
              required
              @input="$emit('update:modelValue', $event.target.value)"
            />
          </div>
          <button
            type="submit"
            :disabled="isLoading || modelValue.length < 6"
            class="member-auth-primary w-full py-3.5 rounded-2xl text-sm font-bold"
          >
            <span v-if="isLoading">Verifying&hellip;</span>
            <span v-else>Verify &amp; Continue</span>
          </button>
          <button
            type="button"
            class="member-auth-secondary w-full py-2.5 rounded-xl text-sm font-semibold"
            @click="$emit('back')"
          >
            Change number
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Lock } from 'lucide-vue-next';
defineProps({
    phone:      { type: String, default: '' },
    error:      { type: String, default: '' },
    modelValue: { type: String, default: '' },
    isLoading:  { type: Boolean, default: false },
});

defineEmits(['update:modelValue', 'submit', 'back']);
</script>
