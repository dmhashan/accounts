<template>
    <section>
        <div class="max-w-5xl mx-auto">
            <div class="bg-white dark:bg-secondary-900 rounded-xl shadow-sm border border-secondary-200 dark:border-secondary-700 p-6 md:p-10">
                <div class="text-center">
                    <div class="mx-auto h-20 w-20 md:h-24 md:w-24 bg-gradient-to-r from-primary-500 to-primary-700 rounded-full flex items-center justify-center mb-6">
                        <svg class="h-10 w-10 md:h-12 md:w-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <h2 class="text-2xl md:text-3xl font-bold text-secondary-900 dark:text-white mb-3">Reports Coming Soon</h2>
                    <p class="text-base md:text-lg text-secondary-600 dark:text-secondary-400 mb-8">Advanced analytics and reporting features are in development.</p>
                </div>

                <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
                    {{ errorMessage }}
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                    <article v-for="feature in features" :key="feature.title" class="p-5 md:p-6 bg-secondary-50 dark:bg-secondary-800 rounded-lg">
                        <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">{{ feature.title }}</h3>
                        <p class="mt-2 text-sm text-secondary-600 dark:text-secondary-400">{{ feature.description }}</p>
                    </article>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { apiRequest } from '../composables/useApiClient';

const features = ref([]);
const errorMessage = ref('');

async function loadOverview() {
    try {
        const response = await apiRequest('/api/reports/overview');
        features.value = response.features || [];
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load report overview.';
    }
}

onMounted(() => {
    loadOverview();
});
</script>
