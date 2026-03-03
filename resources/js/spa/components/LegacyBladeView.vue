<template>
    <section>
        <div class="bg-gradient-to-r from-primary-500 to-primary-700 rounded-2xl shadow-xl overflow-hidden mb-4 md:mb-6">
            <div class="px-5 py-6 md:px-7 md:py-8 lg:px-8 lg:py-10 text-white">
                <h1 class="text-xl md:text-2xl lg:text-3xl font-bold">{{ title }}</h1>
                <p class="mt-2 text-primary-100 text-sm md:text-base">{{ description }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-5 lg:gap-6">
            <aside class="lg:col-span-1 rounded-xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 p-5 md:p-6 shadow-sm">
                <h2 class="text-base md:text-lg font-semibold text-secondary-900 dark:text-white">SPA Migration Bridge</h2>
                <p class="mt-2 text-sm text-secondary-600 dark:text-secondary-400 leading-relaxed">
                    This route is served in the Vue SPA with no iframe usage. Module conversion is in progress with REST API integration.
                </p>
                <ul class="mt-4 space-y-2 text-sm text-secondary-600 dark:text-secondary-400">
                    <li>• No iframe rendering</li>
                    <li>• SPA navigation remains active</li>
                    <li>• Legacy route fallback remains available</li>
                </ul>
                <a :href="url" class="mt-5 inline-flex items-center px-4 py-2 rounded-lg bg-secondary-900 dark:bg-secondary-700 text-white text-sm font-semibold hover:bg-secondary-800 dark:hover:bg-secondary-600 transition-colors">
                    Open Full Legacy Page
                </a>
            </aside>

            <div class="lg:col-span-2 rounded-xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 p-5 md:p-6 shadow-sm">
                <h3 class="text-base md:text-lg font-semibold text-secondary-900 dark:text-white">Conversion Status</h3>
                <p class="mt-2 text-sm text-secondary-600 dark:text-secondary-400 leading-relaxed">
                    This module is queued for conversion from Blade to native Vue components backed by `/api/*` endpoints.
                </p>
                <div class="mt-4 rounded-lg bg-secondary-50 dark:bg-secondary-800/60 border border-secondary-200 dark:border-secondary-700 p-4">
                    <p class="text-sm text-secondary-700 dark:text-secondary-300">
                        Use the button to open the current legacy route while SPA-native implementation is completed.
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        required: true,
    },
    url: {
        type: String,
        required: true,
    },
});
</script>
