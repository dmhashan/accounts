<template>
  <div class="relative w-full max-w-md">
    <!-- Background glowing decorative orbs -->
    <div class="absolute -top-16 -left-16 w-48 h-48 bg-indigo-500/20 dark:bg-indigo-500/10 rounded-full blur-3xl pointer-events-none" />
    <div class="absolute -bottom-16 -right-16 w-48 h-48 bg-purple-500/20 dark:bg-purple-500/10 rounded-full blur-3xl pointer-events-none" />

    <!-- Glass card -->
    <div class="relative bg-white/70 dark:bg-slate-900/60 backdrop-blur-xl border border-slate-200/80 dark:border-slate-800/80 rounded-2xl shadow-xl overflow-hidden p-8 transition-all">
      <div class="flex flex-col items-center mb-8">
        <span class="p-3 bg-indigo-500 rounded-xl text-white mb-4 shadow-lg shadow-indigo-500/30">
          <svg
            class="w-8 h-8"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
            />
          </svg>
        </span>
        <h2 class="text-2xl font-extrabold tracking-tight text-slate-950 dark:text-white">
          Administration Portal
        </h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 text-center">
          {{ step === 1 ? 'Enter your email or phone number to receive an OTP' : 'Enter the 6-digit code sent to your phone' }}
        </p>
      </div>

      <!-- Step 1: Identifier Input -->
      <form v-if="step === 1" class="space-y-5" @submit.prevent="requestOtp">
        <div>
          <label for="identifier" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-2">Email or Mobile Number</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 dark:text-slate-500 pointer-events-none">
              <svg
                class="w-5 h-5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                />
              </svg>
            </span>
            <input 
              id="identifier" 
              v-model="identifier" 
              type="text" 
              required 
              placeholder="admin@portal.com or 0771234567"
              class="w-full pl-11 pr-4 py-3 bg-slate-100/50 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/25 rounded-xl text-sm font-medium outline-none transition-all"
            />
          </div>
          <p v-if="error" class="text-xs text-rose-500 mt-2 font-medium">
            {{ error }}
          </p>
        </div>

        <button 
          type="submit" 
          :disabled="loading"
          class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-500 disabled:bg-indigo-600/50 text-white border border-indigo-500/20 rounded-xl text-sm font-bold shadow-lg shadow-indigo-500/20 hover:shadow-indigo-500/35 transition-all flex items-center justify-center gap-2 cursor-pointer"
        >
          <svg
            v-if="loading"
            class="animate-spin h-5 w-5 text-white"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
          </svg>
          {{ loading ? 'Sending OTP...' : 'Send Verification Code' }}
        </button>
      </form>

      <!-- Step 2: OTP Verification -->
      <form v-else class="space-y-5" @submit.prevent="verifyOtp">
        <div>
          <label for="otp" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-2">Verification Code</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 dark:text-slate-500 pointer-events-none">
              <svg
                class="w-5 h-5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                />
              </svg>
            </span>
            <input 
              id="otp" 
              v-model="otp" 
              type="text" 
              required 
              maxlength="6"
              placeholder="Enter 6-digit code"
              class="w-full pl-11 pr-4 py-3 bg-slate-100/50 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/25 rounded-xl text-sm font-semibold tracking-widest text-center outline-none transition-all"
            />
          </div>
          <div class="flex justify-between items-center mt-2">
            <p v-if="error" class="text-xs text-rose-500 font-medium">
              {{ error }}
            </p>
            <p v-else-if="otpDebug" class="text-xs text-indigo-500 dark:text-indigo-400 font-mono font-semibold">
              Local Dev OTP: {{ otpDebug }}
            </p>
            <button type="button" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline" @click="step = 1">
              Change details
            </button>
          </div>
        </div>

        <button 
          type="submit" 
          :disabled="loading"
          class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-500 disabled:bg-indigo-600/50 text-white border border-indigo-500/20 rounded-xl text-sm font-bold shadow-lg shadow-indigo-500/20 hover:shadow-indigo-500/35 transition-all flex items-center justify-center gap-2 cursor-pointer"
        >
          <svg
            v-if="loading"
            class="animate-spin h-5 w-5 text-white"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
          </svg>
          {{ loading ? 'Verifying...' : 'Verify & Sign In' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script>
import { ref, inject } from 'vue';
import { useRouter } from 'vue-router';
import { apiRequest } from '../composables/usePortalApi';

export default {
  setup() {
    const identifier = ref('');
    const otp = ref('');
    const step = ref(1);
    const loading = ref(false);
    const error = ref(null);
    const otpDebug = ref(null);

    const showToast = inject('showToast');
    const updateAuthStatus = inject('updateAuthStatus');
    const router = useRouter();

    const requestOtp = async () => {
      loading.value = true;
      error.value = null;
      otpDebug.value = null;

      try {
        const res = await apiRequest('/auth/request-otp', {
          method: 'post',
          data: { identifier: identifier.value },
        });
        showToast(res.message);
        if (res.otp_debug) {
          otpDebug.value = res.otp_debug;
        }
        step.value = 2;
      } catch (err) {
        error.value = err.response?.data?.message || 'Failed to request code. Please check your credentials.';
        showToast(error.value, 'error');
      } finally {
        loading.value = false;
      }
    };

    const verifyOtp = async () => {
      loading.value = true;
      error.value = null;

      try {
        const res = await apiRequest('/auth/login', {
          method: 'post',
          data: {
            identifier: identifier.value,
            otp: otp.value,
          },
        });
        updateAuthStatus(true, res.user);
        showToast('Login successful!');
        router.push('/dashboard');
      } catch (err) {
        error.value = err.response?.data?.message || 'Invalid or expired verification code.';
        showToast(error.value, 'error');
      } finally {
        loading.value = false;
      }
    };

    return {
      identifier,
      otp,
      step,
      loading,
      error,
      otpDebug,
      requestOtp,
      verifyOtp,
    };
  }
};
</script>
