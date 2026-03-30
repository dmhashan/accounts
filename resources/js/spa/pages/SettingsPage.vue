<template>
    <section>
        <AppPageHeader>
            <template #cta-slot>
                <AppHeaderAction to="/profile" :icon="UserCircle2" label="Open Profile" />
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
import AppHeaderAction from '../components/AppHeaderAction.vue';
import AppPageHeader from '../components/AppPageHeader.vue';
import { UserCircle2 } from 'lucide-vue-next';
</script>
