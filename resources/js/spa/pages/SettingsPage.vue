<template>
    <section>
        <div class="app-surface rounded-2xl p-4 sm:p-5 md:p-6 mb-4 md:mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.12em] text-secondary-500 dark:text-secondary-400">Settings</p>
                    <h2 class="text-xl md:text-2xl font-bold text-secondary-900 dark:text-white">Application Settings</h2>
                    <p class="text-sm text-secondary-500 dark:text-secondary-400">Settings module is not yet available in SPA.</p>
                </div>

                <RouterLink
                    to="/profile"
                    class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-gradient-to-r from-primary-500 to-primary-700 text-white font-semibold transition-all hover:brightness-110"
                >
                    Open Profile
                </RouterLink>
            </div>
        </div>

        <div class="app-surface rounded-2xl p-6 md:p-8">
            <div class="mt-4 rounded-xl app-surface-soft p-4">
                <p class="text-sm text-secondary-700 dark:text-secondary-300">This route is reserved for upcoming native Vue + REST implementation.</p>
            </div>
        </div>
    </section>
</template>

<script setup>
</script>
