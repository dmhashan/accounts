<template>
    <section>
        <div class="bg-gradient-to-r from-primary-500 to-primary-700 rounded-2xl shadow-xl overflow-hidden mb-6">
            <div class="px-5 py-6 md:px-7 md:py-8 lg:px-8 lg:py-12 text-white">
                <h2 class="text-xl md:text-2xl lg:text-3xl font-bold">Welcome back, {{ context.user?.name }}! 👋</h2>
                <p class="mt-2 text-primary-100 text-sm md:text-base">You're logged into {{ context.tenant?.name }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 md:gap-5 lg:gap-6">
            <article class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-700 p-5 md:p-6 shadow-sm">
                <h3 class="text-sm font-medium text-secondary-500 dark:text-secondary-400">Tenant</h3>
                <p class="mt-2 text-2xl font-bold text-secondary-900 dark:text-white">{{ context.tenant?.name }}</p>
            </article>
            <article class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-700 p-5 md:p-6 shadow-sm">
                <h3 class="text-sm font-medium text-secondary-500 dark:text-secondary-400">Domain</h3>
                <p class="mt-2 text-base font-mono font-semibold text-secondary-900 dark:text-white break-all">{{ context.tenant?.domain }}</p>
            </article>
            <article class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-700 p-5 md:p-6 shadow-sm md:col-span-2 xl:col-span-1">
                <h3 class="text-sm font-medium text-secondary-500 dark:text-secondary-400">Account</h3>
                <p class="mt-2 text-lg font-semibold text-secondary-900 dark:text-white">{{ context.user?.email }}</p>
            </article>
        </div>
    </section>
</template>

<script setup>
import { useAppContext } from '../composables/useAppContext';

const context = useAppContext();
</script>
