<template>
  <div class="flex-1 flex items-center justify-center px-5">
    <div class="w-full max-w-sm">
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-3xl bg-gray-900 mb-4">
          <Lock class="w-8 h-8 text-white" :stroke-width="1.8" />
        </div>
        <h1 class="text-2xl font-bold text-gray-900">
          Verify OTP
        </h1>
        <p class="text-sm text-gray-500 mt-1">
          Code sent to {{ phone }}
        </p>
      </div>

      <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6">
        <p class="text-sm text-gray-600 mb-5 text-center">
          Enter the 6-digit code we sent via SMS.
        </p>

        <div v-if="error" class="mb-4 px-4 py-3 rounded-2xl bg-red-50 border border-red-100 text-sm text-red-600">
          {{ error }}
        </div>

        <form class="space-y-4" @submit.prevent="$emit('submit')">
          <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Verification Code</label>
            <input
              :value="modelValue"
              type="text"
              inputmode="numeric"
              maxlength="6"
              placeholder="000000"
              class="w-full rounded-2xl border border-gray-200 px-4 py-3.5 text-sm font-bold text-gray-900 text-center tracking-[0.5em] placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent"
              autocomplete="one-time-code"
              required
              @input="$emit('update:modelValue', $event.target.value)"
            />
          </div>
          <button
            type="submit"
            :disabled="isLoading || modelValue.length < 6"
            class="w-full py-3.5 rounded-2xl bg-gray-900 text-white text-sm font-bold hover:bg-gray-800 active:bg-black disabled:opacity-60 transition-colors"
          >
            <span v-if="isLoading">Verifying&hellip;</span>
            <span v-else>Verify &amp; Continue</span>
          </button>
          <button
            type="button"
            class="w-full py-2.5 text-sm font-semibold text-gray-500 hover:text-gray-800 transition-colors"
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
