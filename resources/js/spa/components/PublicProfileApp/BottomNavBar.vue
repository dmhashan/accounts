<template>
  <nav class="member-bottom-nav fixed bottom-0 inset-x-0 z-30 select-none">
    <div class="max-w-lg mx-auto px-3 py-1.5 flex items-center justify-around">
      <button
        v-for="tab in navTabs"
        :key="tab.key"
        type="button"
        class="relative flex-1 flex flex-col items-center justify-center py-1.5 px-1 rounded-2xl transition-all duration-200 focus:outline-none active:scale-90"
        :class="isActive(tab)
          ? 'text-red-600 dark:text-red-400 font-bold'
          : 'text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 font-medium'"
        :aria-label="tab.label"
        @click="navigate(tab.path)"
      >
        <!-- Active Background Glow Pill -->
        <div
          v-if="isActive(tab)"
          class="absolute inset-x-1.5 inset-y-1 rounded-2xl bg-red-500/10 dark:bg-red-500/20 -z-10 animate-in fade-in zoom-in-90 duration-150"
        />

        <!-- Icon -->
        <component
          :is="tab.icon"
          class="w-5 h-5 transition-transform duration-200"
          :class="isActive(tab) ? 'scale-110' : 'scale-100'"
          :stroke-width="isActive(tab) ? 2.3 : 1.8"
        />

        <!-- Label -->
        <span class="text-[10px] tracking-tight leading-tight mt-1 truncate">
          {{ tab.label }}
        </span>

        <!-- Active Dot -->
        <span
          v-if="isActive(tab)"
          class="w-1 h-1 rounded-full bg-red-500 mt-0.5"
        />
        <span v-else class="w-1 h-1 mt-0.5 opacity-0" />
      </button>
    </div>
  </nav>
</template>

<script setup>
import { useRoute, useRouter } from 'vue-router';
import { Home, Zap, Scale, Calendar, Wallet, User } from 'lucide-vue-next';

const route  = useRoute();
const router = useRouter();

function isActive(tab) {
    if (tab.path === '/') {
        return route.path === '/';
    }
    return route.path.startsWith(tab.path);
}

function navigate(path) {
    if (route.path !== path) {
        router.push(path);
    }
}

const navTabs = [
    { key: 'home',         path: '/',             label: 'Home',              icon: Home },
    { key: 'workout',      path: '/workout',      label: 'Workouts',          icon: Zap },
    { key: 'measurements', path: '/measurements', label: 'Body Stats',        icon: Scale },
    { key: 'calendar',     path: '/calendar',     label: 'Calendar',          icon: Calendar },
    { key: 'wallet',       path: '/wallet',       label: 'Wallet & Payments', icon: Wallet },
    { key: 'profile',      path: '/profile',      label: 'Profile',           icon: User },
];
</script>
