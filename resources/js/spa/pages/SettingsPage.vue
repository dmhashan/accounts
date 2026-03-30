<template>
    <section>
        <AppPageHeader>
            <template #cta-slot>
                <RouterLink
                    to="/profile"
                    class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-gradient-to-r from-primary-500 to-primary-700 text-white font-semibold transition-all hover:brightness-110"
                >
                    Open Profile
                </RouterLink>
            </template>
        </AppPageHeader>

        <div class="app-surface rounded-2xl p-6 md:p-8">
            <div class="mt-4 rounded-xl app-surface-soft p-4">
                <p class="text-sm text-secondary-700 dark:text-secondary-300">This route is reserved for upcoming native Vue + REST implementation.</p>
            </div>
        </div>
    </section>
</template>

<script setup>
import AppPageHeader from '../components/AppPageHeader.vue';
</script>
