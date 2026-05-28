<template>
  <div class="app-shell flex overflow-hidden">
    <div class="pointer-events-none fixed inset-0 -z-10">
      <div class="absolute -top-24 -right-32 h-96 w-96 rounded-full bg-primary-500/15 blur-3xl dark:bg-primary-700/10" />
      <div class="absolute -bottom-36 left-0 h-[26rem] w-[26rem] rounded-full bg-emerald-500/12 blur-3xl dark:bg-emerald-700/10" />
    </div>

    <AppSidebar />

    <AppMobileDrawer :open="mobileMenuOpen" @close="mobileMenuOpen = false" />

    <div class="relative flex min-w-0 flex-1 flex-col overflow-hidden h-screen">
      <div v-if="routeLoader.loading" class="app-progress-bar shrink-0" />

      <main class="flex min-h-0 flex-1 flex-col overflow-hidden px-3 py-4 sm:px-4 md:px-6 md:py-6 pb-24 lg:pb-8 [padding-bottom:calc(6.25rem+env(safe-area-inset-bottom))] lg:[padding-bottom:2rem]">
        <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
          <RouterView />
        </div>
      </main>

      <AppBottomNav @open-menu="mobileMenuOpen = true" />
    </div>

    <!-- Calculator FAB -->
    <button
      type="button"
      aria-label="Open calculator"
      class="fixed right-4 bottom-[calc(6.5rem+env(safe-area-inset-bottom))] lg:bottom-6 z-40 flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-primary-500 to-primary-700 shadow-lg shadow-primary-500/30 text-white transition-transform hover:scale-110 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-400"
      @click="calculatorOpen = true"
    >
      <Calculator class="w-5 h-5" />
    </button>

    <CalculatorModal v-if="calculatorOpen" @close="calculatorOpen = false" />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Calculator } from 'lucide-vue-next';
import AppSidebar from './layout/AppSidebar.vue';
import AppMobileDrawer from './layout/AppMobileDrawer.vue';
import AppBottomNav from './layout/AppBottomNav.vue';
import CalculatorModal from './components/CalculatorModal.vue';
import { routeLoader } from './routeLoader';

const mobileMenuOpen = ref(false);
const calculatorOpen = ref(false);
</script>
