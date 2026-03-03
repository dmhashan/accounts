<template>
    <div class="h-screen flex bg-background-light dark:bg-background-dark">
        <AppSidebar />

        <AppMobileDrawer :open="mobileMenuOpen" @close="mobileMenuOpen = false" />

        <div class="flex-1 flex flex-col overflow-hidden">
            <AppHeader @open-menu="mobileMenuOpen = true" />

            <div v-if="routeLoader.loading" class="h-1 bg-secondary-100 dark:bg-secondary-800">
                <div class="h-1 w-1/3 bg-gradient-to-r from-primary-500 to-primary-700 animate-pulse"></div>
            </div>

            <main class="flex-1 overflow-y-auto p-4 md:p-6 pb-24 lg:pb-6">
                <RouterView />
            </main>

            <AppBottomNav @open-menu="mobileMenuOpen = true" />
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import AppHeader from './layout/AppHeader.vue';
import AppSidebar from './layout/AppSidebar.vue';
import AppMobileDrawer from './layout/AppMobileDrawer.vue';
import AppBottomNav from './layout/AppBottomNav.vue';
import { routeLoader } from './routeLoader';

const mobileMenuOpen = ref(false);
</script>
