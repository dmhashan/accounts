<template>
  <div class="space-y-8">
    <!-- Welcome Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-3xl font-extrabold tracking-tight">
          System Dashboard
        </h2>
        <p class="text-slate-500 dark:text-slate-400 mt-1">
          Overview of your multi-tenant SaaS application registry.
        </p>
      </div>
      <div class="text-sm font-medium text-slate-500 dark:text-slate-400 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg px-4 py-2">
        {{ currentDate }}
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <!-- Tenants Card -->
      <div class="bg-white/70 dark:bg-slate-900/60 backdrop-blur-md border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm flex items-center justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
            Active Tenants
          </p>
          <h3 class="text-4xl font-extrabold tracking-tight mt-1">
            {{ stats.tenant_count ?? 0 }}
          </h3>
        </div>
        <span class="p-4 bg-indigo-500/10 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 rounded-2xl">
          <svg
            class="w-8 h-8"
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
        </span>
      </div>

      <!-- Admins Card -->
      <div class="bg-white/70 dark:bg-slate-900/60 backdrop-blur-md border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm flex items-center justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
            Portal Admins
          </p>
          <h3 class="text-4xl font-extrabold tracking-tight mt-1">
            {{ stats.admin_count ?? 0 }}
          </h3>
        </div>
        <span class="p-4 bg-purple-500/10 dark:bg-purple-500/20 text-purple-600 dark:text-purple-400 rounded-2xl">
          <svg
            class="w-8 h-8"
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
        </span>
      </div>

      <!-- Status Card -->
      <div class="bg-white/70 dark:bg-slate-900/60 backdrop-blur-md border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm flex items-center justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
            Portal System
          </p>
          <h3 class="text-xl font-extrabold tracking-tight mt-2 flex items-center gap-2">
            <span class="w-2.5 h-2.5 bg-emerald-500 rounded-full animate-ping" />
            <span class="text-emerald-600 dark:text-emerald-400">Healthy</span>
          </h3>
        </div>
        <span class="p-4 bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 rounded-2xl">
          <svg
            class="w-8 h-8"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
            />
          </svg>
        </span>
      </div>
    </div>

    <!-- Quick action shortcuts -->
    <div class="bg-white/50 dark:bg-slate-900/40 border border-slate-200/60 dark:border-slate-800/60 rounded-2xl p-8">
      <h4 class="text-lg font-bold mb-4">
        Quick Administrative Tools
      </h4>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <router-link to="/tenants" class="flex items-center justify-between p-4 rounded-xl border border-slate-200 dark:border-slate-800 hover:border-indigo-500/50 hover:bg-indigo-50/10 dark:hover:bg-indigo-950/10 hover:shadow-sm transition-all group">
          <div class="flex items-center gap-3">
            <span class="p-2.5 bg-indigo-500/10 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 rounded-xl group-hover:scale-105 transition-all">
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
                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                />
              </svg>
            </span>
            <div>
              <p class="font-bold text-sm">
                Tenant Provisioning
              </p>
              <p class="text-xs text-slate-500 dark:text-slate-400">
                Add, edit, or remove isolated databases and gym profiles.
              </p>
            </div>
          </div>
          <svg
            class="w-5 h-5 text-slate-400 group-hover:translate-x-1 transition-all"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 5l7 7-7 7"
            />
          </svg>
        </router-link>

        <router-link to="/portal-users" class="flex items-center justify-between p-4 rounded-xl border border-slate-200 dark:border-slate-800 hover:border-purple-500/50 hover:bg-purple-50/10 dark:hover:bg-purple-950/10 hover:shadow-sm transition-all group">
          <div class="flex items-center gap-3">
            <span class="p-2.5 bg-purple-500/10 dark:bg-purple-500/20 text-purple-600 dark:text-purple-400 rounded-xl group-hover:scale-105 transition-all">
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
                  d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                />
              </svg>
            </span>
            <div>
              <p class="font-bold text-sm">
                Portal User Audits
              </p>
              <p class="text-xs text-slate-500 dark:text-slate-400">
                Manage portal administrative roles and contact details.
              </p>
            </div>
          </div>
          <svg
            class="w-5 h-5 text-slate-400 group-hover:translate-x-1 transition-all"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 5l7 7-7 7"
            />
          </svg>
        </router-link>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { apiRequest } from '../composables/usePortalApi';

export default {
  setup() {
    const stats = ref({ tenant_count: 0, admin_count: 0 });
    const currentDate = ref(new Date().toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }));

    onMounted(async () => {
      try {
        const res = await apiRequest('/dashboard/stats');
        stats.value = res;
      } catch (err) {
        console.error('Failed to load dashboard stats', err);
      }
    });

    return {
      stats,
      currentDate,
    };
  }
};
</script>
