<template>
  <div :class="{ 'dark': isDark }">
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 transition-colors duration-200">
      <!-- Toast Notification -->
      <div
        v-if="toast"
        class="fixed top-4 right-4 z-50 flex items-center p-4 rounded-lg shadow-lg border backdrop-blur-md transition-all duration-300 transform translate-y-0"
        :class="toast.type === 'success' 
          ? 'bg-emerald-50/90 dark:bg-emerald-950/80 border-emerald-200 dark:border-emerald-900 text-emerald-800 dark:text-emerald-200' 
          : 'bg-rose-50/90 dark:bg-rose-950/80 border-rose-200 dark:border-rose-900 text-rose-800 dark:text-rose-200'"
      >
        <div class="mr-3 font-medium">
          {{ toast.message }}
        </div>
        <button class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 font-bold ml-2" @click="toast = null">
          &times;
        </button>
      </div>

      <!-- Authenticated Layout -->
      <div v-if="authenticated" class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 border-r border-slate-200/80 dark:border-slate-800/80 bg-white/70 dark:bg-slate-900/70 backdrop-blur-md flex flex-col justify-between p-6">
          <div>
            <div class="flex items-center gap-3 mb-8">
              <span class="p-2 bg-indigo-500 rounded-lg text-white">
                <svg
                  class="w-6 h-6"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                  />
                </svg>
              </span>
              <h1 class="text-xl font-bold tracking-tight bg-gradient-to-r from-indigo-500 to-violet-500 bg-clip-text text-transparent">
                SaaS Portal
              </h1>
            </div>
            
            <nav class="space-y-1.5">
              <router-link
                to="/dashboard"
                class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all"
                :class="isActive('/dashboard') ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 shadow-sm border border-indigo-100/50 dark:border-indigo-900/30' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200'"
              >
                <svg
                  class="w-5 h-5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z"
                  />
                </svg>
                Dashboard
              </router-link>

              <router-link
                to="/tenants"
                class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all"
                :class="isActive('/tenants') ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 shadow-sm border border-indigo-100/50 dark:border-indigo-900/30' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200'"
              >
                <svg
                  class="w-5 h-5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                  />
                </svg>
                Tenants
              </router-link>

              <router-link
                to="/portal-users"
                class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all"
                :class="isActive('/portal-users') ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 shadow-sm border border-indigo-100/50 dark:border-indigo-900/30' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200'"
              >
                <svg
                  class="w-5 h-5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                  />
                </svg>
                Administrators
              </router-link>
            </nav>
          </div>

          <!-- Bottom Footer/Profile -->
          <div class="space-y-4">
            <!-- Theme Toggle -->
            <button class="flex items-center justify-between w-full px-4 py-2.5 rounded-lg text-sm font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 transition-all border border-transparent hover:border-slate-200/50 dark:hover:border-slate-700/50" @click="toggleTheme">
              <span class="flex items-center gap-3">
                <svg
                  v-if="isDark"
                  class="w-5 h-5 text-amber-400"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 3v1m0 16v1m9-9h-1M4 9H3m15.364-3.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z"
                  />
                </svg>
                <svg
                  v-else
                  class="w-5 h-5 text-indigo-500"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
                  />
                </svg>
                {{ isDark ? 'Light Mode' : 'Dark Mode' }}
              </span>
            </button>

            <!-- User profile & Logout -->
            <div class="border-t border-slate-200 dark:border-slate-800 pt-4">
              <div class="flex items-center justify-between gap-2 mb-3">
                <div class="truncate">
                  <div class="text-sm font-semibold truncate">
                    {{ currentUser?.name }}
                  </div>
                  <div class="text-xs text-slate-500 dark:text-slate-400 truncate">
                    {{ currentUser?.email }}
                  </div>
                </div>
              </div>
              <button class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-rose-50 dark:bg-rose-950/20 text-rose-600 dark:text-rose-400 border border-rose-100 dark:border-rose-900/30 rounded-lg text-sm font-semibold hover:bg-rose-100 dark:hover:bg-rose-950/40 transition-all" @click="logout">
                <svg
                  class="w-4 h-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                  />
                </svg>
                Sign Out
              </button>
            </div>
          </div>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto p-10">
          <router-view />
        </main>
      </div>

      <!-- Guest Layout (Login) -->
      <div v-else class="min-h-screen flex items-center justify-center p-4">
        <router-view />
      </div>
    </div>
  </div>
</template>

<script>
import { ref, inject, provide, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiRequest } from './composables/usePortalApi';

export default {
  setup() {
    const context = inject('portalContext') || { authenticated: false, user: null };
    const authenticated = ref(context.authenticated);
    const currentUser = ref(context.user);
    const isDark = ref(localStorage.getItem('portal_theme') === 'dark');

    const route = useRoute();
    const router = useRouter();

    const toast = ref(null);

    const showToast = (message, type = 'success') => {
      toast.value = { message, type };
      setTimeout(() => {
        toast.value = null;
      }, 4000);
    };

    provide('showToast', showToast);

    const isActive = (path) => {
      return route.path === path;
    };

    const toggleTheme = () => {
      isDark.value = !isDark.value;
      localStorage.setItem('portal_theme', isDark.value ? 'dark' : 'light');
      document.documentElement.classList.toggle('dark', isDark.value);
    };

    const logout = async () => {
      try {
        await apiRequest('/auth/logout', { method: 'post' });
        authenticated.value = false;
        currentUser.value = null;
        if (window.portalContext) {
          window.portalContext.authenticated = false;
          window.portalContext.user = null;
        }
        showToast('Logged out successfully.');
        router.push('/login');
      } catch {
        showToast('Logout failed, redirecting anyway.', 'error');
        authenticated.value = false;
        router.push('/login');
      }
    };

    // Listen to authenticated status updates from children (e.g. LoginPage)
    const updateAuthStatus = (status, user) => {
      authenticated.value = status;
      currentUser.value = user;
      if (window.portalContext) {
        window.portalContext.authenticated = status;
        window.portalContext.user = user;
      }
    };
    provide('updateAuthStatus', updateAuthStatus);

    onMounted(() => {
      document.documentElement.classList.toggle('dark', isDark.value);
    });

    return {
      authenticated,
      currentUser,
      isDark,
      toast,
      isActive,
      toggleTheme,
      logout,
    };
  }
};
</script>

<style>
/* CSS Scrollbar & Smooth Transition */
::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}
::-webkit-scrollbar-track {
  background: transparent;
}
::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}
.dark ::-webkit-scrollbar-thumb {
  background: #334155;
}
</style>
