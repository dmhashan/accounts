<template>
  <section>
    <div class="bg-gradient-to-r from-primary-500 to-primary-700 rounded-2xl shadow-xl overflow-hidden mb-6">
      <div class="px-5 py-6 md:px-7 md:py-8 lg:px-8 lg:py-10 text-white">
        <h1 class="text-xl md:text-2xl lg:text-3xl font-bold">
          {{ title }}
        </h1>
        <p class="mt-2 text-primary-100 text-sm md:text-base">
          {{ description }}
        </p>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-5 lg:gap-6">
      <article class="rounded-xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 p-5 md:p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-secondary-900 dark:text-white">
          Modern SPA View
        </h2>
        <p class="mt-2 text-sm text-secondary-600 dark:text-secondary-400">
          This module is now loaded through Vue Router chunk loading for faster page transitions.
        </p>
      </article>

      <article class="rounded-xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 p-5 md:p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-secondary-900 dark:text-white">
          Legacy Integration
        </h2>
        <p class="mt-2 text-sm text-secondary-600 dark:text-secondary-400">
          Use the legacy page for full CRUD workflows while SPA migration is in progress.
        </p>
        <a :href="legacyUrl" class="mt-4 inline-flex items-center px-4 py-2 rounded-lg bg-secondary-900 dark:bg-secondary-700 text-white text-sm font-semibold hover:bg-secondary-800 dark:hover:bg-secondary-600 transition-colors">
          Open Legacy Module
        </a>
      </article>
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
    legacyUrl: {
        type: String,
        default: '/dashboard',
    },
});
</script>
