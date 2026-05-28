<template>
  <!-- Floating notification button -->
  <button
    type="button"
    class="fixed z-30 bottom-[72px] right-5 flex items-center gap-2 shadow-lg transition-all active:scale-95 focus:outline-none"
    :class="isNotifActive
      ? 'bg-gray-900 text-white shadow-gray-900/40 px-4 py-2.5 rounded-2xl'
      : 'bg-white border border-gray-100 text-gray-500 shadow-gray-200/60 px-3.5 py-2.5 rounded-2xl hover:border-gray-200'"
    aria-label="Notifications"
    @click="router.push('/notifications')"
  >
    <!-- pulse ring when inactive -->
    <span v-if="!isNotifActive" class="absolute inset-0 rounded-2xl animate-ping opacity-20 bg-gray-300 pointer-events-none" />
    <Bell class="w-5 h-5 relative" />
    <span class="relative text-xs font-bold leading-none">{{ isNotifActive ? 'Notifications' : 'Alerts' }}</span>
  </button>

  <nav class="fixed bottom-0 inset-x-0 z-20 bg-white border-t border-gray-100 safe-area-bottom">
    <div class="max-w-lg mx-auto flex">
      <button
        v-for="tab in navTabs"
        :key="tab.key"
        type="button"
        class="flex-1 flex flex-col items-center justify-center gap-1 py-3 transition-colors focus:outline-none"
        :class="isActive(tab) ? 'text-gray-900' : 'text-gray-400 hover:text-gray-600'"
        @click="router.push(tab.path)"
      >
        <component :is="tab.icon" class="w-5 h-5" />
        <span class="text-[10px] font-semibold leading-none">{{ tab.label }}</span>
        <span v-if="isActive(tab)" class="w-5 h-0.5 rounded-full bg-red-500 mt-0.5" />
        <span v-else class="w-5 h-0.5 mt-0.5" />
      </button>
    </div>
  </nav>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Bell, Home, Zap, CreditCard, ClipboardList, User } from 'lucide-vue-next';

const route  = useRoute();
const router = useRouter();

function isActive(tab) {
    return route.path === tab.path;
}

const isNotifActive = computed(() => route.path === '/notifications');

const navTabs = [
    { key: 'home',          path: '/',              label: 'Home',     icon: Home },
    { key: 'workout',       path: '/workout',        label: 'Workout',  icon: Zap },
    { key: 'wallet',        path: '/wallet',         label: 'Wallet',   icon: CreditCard },
    { key: 'transactions',  path: '/transactions',   label: 'Payments', icon: ClipboardList },
    { key: 'profile',       path: '/profile',        label: 'Profile',  icon: User },
];
</script>
