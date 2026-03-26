<template>
    <div class="app-shell flex overflow-hidden">
        <div class="pointer-events-none fixed inset-0 -z-10">
            <div class="absolute -top-24 -right-32 h-96 w-96 rounded-full bg-primary-500/15 blur-3xl dark:bg-primary-700/10"></div>
            <div class="absolute -bottom-36 left-0 h-[26rem] w-[26rem] rounded-full bg-emerald-500/12 blur-3xl dark:bg-emerald-700/10"></div>
        </div>

        <AppSidebar />

        <AppMobileDrawer :open="mobileMenuOpen" @close="mobileMenuOpen = false" />

        <div class="relative flex min-w-0 flex-1 flex-col overflow-hidden h-screen">
            <div v-if="routeLoader.loading" class="h-1 app-surface-soft border-x-0 border-b-0 border-t-0">
                <div class="h-1 w-1/3 bg-gradient-to-r from-primary-500 via-red-400 to-orange-500 animate-pulse"></div>
            </div>

            <main class="flex min-h-0 flex-1 flex-col overflow-hidden px-3 py-4 sm:px-4 md:px-6 md:py-6 pb-24 lg:pb-8 [padding-bottom:calc(6.25rem+env(safe-area-inset-bottom))] lg:[padding-bottom:2rem]">
                <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                    <RouterView />
                </div>
            </main>

            <AppBottomNav @open-menu="mobileMenuOpen = true" />
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import AppSidebar from './layout/AppSidebar.vue';
import AppMobileDrawer from './layout/AppMobileDrawer.vue';
import AppBottomNav from './layout/AppBottomNav.vue';
import { routeLoader } from './routeLoader';

const mobileMenuOpen = ref(false);
</script>
