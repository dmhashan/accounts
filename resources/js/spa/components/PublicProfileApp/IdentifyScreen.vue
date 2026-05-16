<template>
    <div class="flex-1 flex items-center justify-center px-5">
        <div class="w-full max-w-sm">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-3xl bg-gray-900 mb-4 overflow-hidden">
                    <img v-if="tenantLogoUrl" :src="tenantLogoUrl" :alt="tenantName" class="w-full h-full object-contain" />
                    <svg v-else class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">{{ tenantName || 'Member Portal' }}</h1>
                <p class="text-sm text-gray-500 mt-1">Member Portal</p>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6">
                <p class="text-sm text-gray-600 mb-5 text-center">Enter your registered mobile number to continue.</p>

                <div v-if="error" class="mb-4 px-4 py-3 rounded-2xl bg-red-50 border border-red-100 text-sm text-red-600">
                    {{ error }}
                </div>

                <form @submit.prevent="$emit('submit')" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Mobile Number</label>
                        <input
                            :value="modelValue"
                            @input="$emit('update:modelValue', $event.target.value)"
                            type="tel"
                            inputmode="tel"
                            placeholder="e.g. 0771234567"
                            class="w-full rounded-2xl border border-gray-200 px-4 py-3.5 text-sm font-medium text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                            autocomplete="tel"
                            required
                        />
                    </div>
                    <button
                        type="submit"
                        :disabled="isLoading"
                        class="w-full py-3.5 rounded-2xl bg-gray-900 text-white text-sm font-bold hover:bg-gray-800 active:bg-black disabled:opacity-60 transition-colors"
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
defineProps({
    tenantName:    { type: String, default: '' },
    tenantLogoUrl: { type: String, default: null },
    error:         { type: String, default: '' },
    modelValue:    { type: String, default: '' },
    isLoading:     { type: Boolean, default: false },
});

defineEmits(['update:modelValue', 'submit']);
</script>
