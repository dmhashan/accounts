<template>
  <div v-if="tenant" class="space-y-6">
    <!-- Back Link -->
    <div class="flex items-center gap-2">
      <router-link to="/tenants" class="text-xs font-semibold text-slate-500 hover:text-indigo-600 flex items-center gap-1 transition-all">
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
            d="M10 19l-7-7m0 0l7-7m-7 7h18"
          />
        </svg>
        Back to Tenants Registry
      </router-link>
    </div>

    <!-- Tenant Summary Card -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm p-6 relative overflow-hidden">
      <!-- Background glow decorative status dot -->
      <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-500/5 rounded-full blur-2xl pointer-events-none" />

      <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative">
        <div class="space-y-2">
          <div class="flex items-center gap-3">
            <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
              {{ tenant.name }}
            </h2>
            <span class="px-2.5 py-1 bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 rounded-md font-mono text-xs border border-indigo-100/50 dark:border-indigo-900/30">
              {{ tenant.subdomain }}
            </span>
            <span
              class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium"
              :class="tenant.is_active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400'"
            >
              <span class="w-1.5 h-1.5 rounded-full" :class="tenant.is_active ? 'bg-emerald-500' : 'bg-rose-500'" />
              {{ tenant.is_active ? 'Active' : 'Inactive' }}
            </span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-y-2 gap-x-6 text-sm text-slate-500 dark:text-slate-400">
            <div class="flex items-center gap-1.5">
              <svg
                class="w-4 h-4 text-slate-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                />
              </svg>
              {{ tenant.email || 'No email contact' }}
            </div>
            <div class="flex items-center gap-1.5">
              <svg
                class="w-4 h-4 text-slate-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                />
              </svg>
              {{ tenant.phone || 'No phone number' }}
            </div>
            <div class="flex items-center gap-1.5 font-mono text-xs col-span-1 sm:col-span-2 md:col-span-1">
              <svg
                class="w-4 h-4 text-slate-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"
                />
              </svg>
              DB: {{ tenant.database_name }}
            </div>
          </div>
        </div>

        <!-- Summary Actions -->
        <div class="flex flex-wrap gap-2 md:self-center">
          <button class="px-3.5 py-2 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-lg text-xs font-semibold cursor-pointer transition-all" @click="promptToggleStatus">
            {{ tenant.is_active ? 'Deactivate Tenant' : 'Activate Tenant' }}
          </button>
          <button class="px-3.5 py-2 border border-indigo-500/20 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50/20 rounded-lg text-xs font-semibold cursor-pointer transition-all" @click="openEditModal">
            Edit Profile
          </button>
          <button v-if="!tenant.is_active" class="px-3.5 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-lg text-xs font-bold cursor-pointer transition-all shadow-md shadow-rose-500/20 flex items-center gap-1.5" @click="promptDeleteTenant">
            <svg
              class="w-3.5 h-3.5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
              />
            </svg>
            Delete Tenant
          </button>
        </div>
      </div>

      <!-- Guidelines card (if inactive) -->
      <div v-if="!tenant.is_active" class="mt-6 pt-6 border-t border-slate-200 dark:border-slate-800">
        <div class="p-4 bg-rose-500/5 border border-rose-500/20 rounded-xl space-y-2 max-w-3xl">
          <div class="flex items-start gap-3">
            <svg
              class="w-5 h-5 text-rose-500 mt-0.5 flex-shrink-0"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
              />
            </svg>
            <div class="space-y-1">
              <h4 class="text-xs font-bold text-rose-800 dark:text-rose-400 uppercase tracking-wider">
                Tenant Deletion Guidelines
              </h4>
              <p class="text-xs text-rose-600 dark:text-rose-400/80">
                This tenant is inactive. You can permanently delete it using the button above or by running the following Artisan command:
              </p>
              <div class="mt-3 flex items-center justify-between gap-4 bg-slate-950 dark:bg-slate-900/60 p-2.5 rounded-lg border border-slate-800 dark:border-slate-800/80 font-mono text-xs text-slate-300">
                <span>php artisan tenants:delete {{ tenant.subdomain }}</span>
                <button 
                  class="px-2 py-1 text-[10px] font-semibold bg-slate-800 hover:bg-slate-700 border border-slate-800 active:scale-95 rounded text-slate-200 transition-all cursor-pointer"
                  @click="copyCommandText(`php artisan tenants:delete ${tenant.subdomain}`)"
                >
                  {{ copied ? 'Copied!' : 'Copy' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tab View -->
    <div class="space-y-4">
      <!-- Tab Headers (No Count Badges) -->
      <div class="border-b border-slate-200 dark:border-slate-800 flex gap-6">
        <button :class="activeTab === 'members' ? 'border-indigo-500 text-indigo-600 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700'" class="pb-3 border-b-2 text-sm font-semibold transition-all cursor-pointer" @click="activeTab = 'members'">
          Member Summary
        </button>
        <button :class="activeTab === 'users' ? 'border-indigo-500 text-indigo-600 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700'" class="pb-3 border-b-2 text-sm font-semibold transition-all cursor-pointer" @click="activeTab = 'users'">
          User Summary
        </button>
      </div>

      <!-- Tab Content Area -->
      <div class="bg-white/70 dark:bg-slate-900/60 backdrop-blur-md border border-slate-200/80 dark:border-slate-800/80 rounded-xl shadow-sm overflow-hidden">
        <!-- Members Tab -->
        <div v-if="activeTab === 'members'" class="space-y-6 p-6">
          <!-- Key Metrics Grid (Active, Inactive, Temporary) -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Metric 1: Active Member Count -->
            <div class="bg-slate-50/50 dark:bg-slate-950/40 border border-slate-200/50 dark:border-slate-800/40 rounded-xl p-4 flex items-center justify-between">
              <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Active Member Count</span>
                <h3 class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-1">
                  {{ members.active_count ?? 0 }}
                </h3>
              </div>
              <div class="p-2.5 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-lg">
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
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
              </div>
            </div>

            <!-- Metric 2: Inactive Member Count -->
            <div class="bg-slate-50/50 dark:bg-slate-950/40 border border-slate-200/50 dark:border-slate-800/40 rounded-xl p-4 flex items-center justify-between">
              <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Inactive Member Count</span>
                <h3 class="text-2xl font-extrabold text-slate-700 dark:text-slate-300 mt-1">
                  {{ members.inactive_count ?? 0 }}
                </h3>
              </div>
              <div class="p-2.5 bg-slate-100 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 rounded-lg">
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
                    d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"
                  />
                </svg>
              </div>
            </div>

            <!-- Metric 3: Temporary Member Count -->
            <div class="bg-slate-50/50 dark:bg-slate-950/40 border border-slate-200/50 dark:border-slate-800/40 rounded-xl p-4 flex items-center justify-between">
              <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Temporary Member Count</span>
                <h3 class="text-2xl font-extrabold text-amber-600 dark:text-amber-400 mt-1">
                  {{ members.temp_count ?? 0 }}
                </h3>
              </div>
              <div class="p-2.5 bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 rounded-lg">
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
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
              </div>
            </div>
          </div>

          <!-- Trend Chart (12 Months SVG Bar Chart) -->
          <div class="bg-slate-50/30 dark:bg-slate-950/20 border border-slate-200/50 dark:border-slate-800/40 rounded-xl p-5">
            <h4 class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-4">
              Member Registration Trend (Last 12 Months)
            </h4>
            
            <div class="h-44 flex items-end justify-between gap-1 px-2 pb-2 border-b border-slate-200 dark:border-slate-800 relative">
              <div class="absolute inset-x-0 bottom-1/4 border-b border-slate-100 dark:border-slate-800/50 pointer-events-none" />
              <div class="absolute inset-x-0 bottom-2/4 border-b border-slate-100 dark:border-slate-800/50 pointer-events-none" />
              <div class="absolute inset-x-0 bottom-3/4 border-b border-slate-100 dark:border-slate-800/50 pointer-events-none" />

              <div 
                v-for="(t, index) in (members.trends || [])" 
                :key="index" 
                class="flex-1 flex flex-col items-center gap-2 group relative"
              >
                <div 
                  class="w-full rounded-t-md transition-all duration-300"
                  :class="index === ((members.trends || []).length - 1) ? 'bg-indigo-600 group-hover:bg-indigo-500 shadow-lg shadow-indigo-500/20' : 'bg-slate-200 dark:bg-slate-800 group-hover:bg-indigo-500/40'"
                  :style="{ height: `${Math.max(8, Math.min(130, (t.count / Math.max(1, maxTrendCount)) * 130))}px` }" 
                />
                <div class="absolute -top-6 text-[10px] font-bold opacity-0 group-hover:opacity-100 bg-slate-950 text-white px-1.5 py-0.5 rounded transition-all pointer-events-none whitespace-nowrap">
                  {{ t.count }}
                </div>
              </div>
            </div>
            <div class="flex justify-between text-[9px] font-bold uppercase text-slate-400 mt-2 px-1">
              <span v-for="(t, index) in (members.trends || [])" :key="index" class="flex-1 text-center truncate">{{ t.label }}</span>
            </div>
          </div>
        </div>

        <!-- Users Tab -->
        <div v-if="activeTab === 'users'" class="space-y-6 p-6">
          <!-- Key Metrics Grid (Active User Count & Total User Count) -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Metric 1: Active User Count -->
            <div class="bg-slate-50/50 dark:bg-slate-950/40 border border-slate-200/50 dark:border-slate-800/40 rounded-xl p-4 flex items-center justify-between">
              <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Active User Count</span>
                <h3 class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-1">
                  {{ users.active_count ?? 0 }}
                </h3>
              </div>
              <div class="p-2.5 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-lg">
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
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                  />
                </svg>
              </div>
            </div>

            <!-- Metric 2: Total User Count -->
            <div class="bg-slate-50/50 dark:bg-slate-950/40 border border-slate-200/50 dark:border-slate-800/40 rounded-xl p-4 flex items-center justify-between">
              <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total User Count</span>
                <h3 class="text-2xl font-extrabold text-indigo-600 dark:text-indigo-400 mt-1">
                  {{ users.total_count ?? 0 }}
                </h3>
              </div>
              <div class="p-2.5 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 rounded-lg">
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
                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                  />
                </svg>
              </div>
            </div>
          </div>

          <!-- Tenant System Users Header & Search -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-2">
            <div>
              <h4 class="text-base font-bold text-slate-900 dark:text-white">
                Tenant System Users
              </h4>
              <p class="text-xs text-slate-500">
                Manage administrators, staff, trainers, and user access roles for this tenant.
              </p>
            </div>
            <div class="flex items-center gap-3">
              <input 
                v-model="tenantUserSearch" 
                type="text" 
                placeholder="Search user name or email..."
                class="px-3.5 py-1.5 bg-slate-100/50 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 focus:border-indigo-500 rounded-lg text-xs outline-none transition-all w-48 sm:w-64"
                @input="fetchTenantUsers(1)"
              />
              <button class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-bold transition-all shadow-md shadow-indigo-500/20 flex items-center gap-1.5 cursor-pointer whitespace-nowrap" @click="openCreateUserModal">
                <svg
                  class="w-3.5 h-3.5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 4v16m8-8H4"
                  />
                </svg>
                Add User
              </button>
            </div>
          </div>

          <!-- Tenant Users Table -->
          <div class="bg-white/50 dark:bg-slate-950/30 border border-slate-200/80 dark:border-slate-800/80 rounded-xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
              <table class="w-full text-left border-collapse text-xs">
                <thead>
                  <tr class="border-b border-slate-200 dark:border-slate-800 font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider bg-slate-50/50 dark:bg-slate-950/50">
                    <th class="px-4 py-3">
                      User Name
                    </th>
                    <th class="px-4 py-3">
                      Email Address
                    </th>
                    <th class="px-4 py-3">
                      Role
                    </th>
                    <th class="px-4 py-3">
                      Status
                    </th>
                    <th class="px-4 py-3 text-right">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                  <tr v-if="tenantUsersLoading && tenantUsers.length === 0">
                    <td colspan="5" class="px-4 py-6 text-center text-slate-400">
                      Loading tenant users...
                    </td>
                  </tr>
                  <tr v-else-if="tenantUsers.length === 0">
                    <td colspan="5" class="px-4 py-6 text-center text-slate-400">
                      No users found for this tenant.
                    </td>
                  </tr>
                  <tr v-for="u in tenantUsers" :key="u.id" class="hover:bg-slate-50/80 dark:hover:bg-slate-800/30 transition-all">
                    <td class="px-4 py-3 font-bold text-slate-900 dark:text-white">
                      {{ u.name }}
                    </td>
                    <td class="px-4 py-3 text-slate-500 dark:text-slate-400">
                      {{ u.email }}
                    </td>
                    <td class="px-4 py-3">
                      <span class="px-2 py-0.5 bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 rounded text-[11px] font-semibold border border-indigo-100/50 dark:border-indigo-900/30">
                        {{ u.role_name || 'Staff' }}
                      </span>
                    </td>
                    <td class="px-4 py-3">
                      <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold" :class="u.is_active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400'">
                        <span class="w-1.5 h-1.5 rounded-full" :class="u.is_active ? 'bg-emerald-500' : 'bg-rose-500'" />
                        {{ u.is_active ? 'Active' : 'Inactive' }}
                      </span>
                    </td>
                    <td class="px-4 py-3 text-right space-x-1.5">
                      <button class="px-2.5 py-1 border border-indigo-500/20 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50/30 rounded text-[11px] font-semibold cursor-pointer transition-all" @click="openEditUserModal(u)">
                        Edit
                      </button>
                      <button class="px-2.5 py-1 border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800 rounded text-[11px] font-semibold cursor-pointer transition-all" @click="toggleUserStatus(u)">
                        {{ u.is_active ? 'Deactivate' : 'Activate' }}
                      </button>
                      <button class="px-2.5 py-1 bg-rose-50 text-rose-600 hover:bg-rose-100 dark:bg-rose-950/30 dark:text-rose-400 rounded text-[11px] font-semibold cursor-pointer transition-all" @click="deleteUser(u)">
                        Delete
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <div v-if="tenantUsersPagination.total > tenantUsersPagination.per_page" class="px-4 py-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
              <span class="text-[11px] text-slate-500">Page {{ tenantUsersPagination.current_page }} of {{ tenantUsersPagination.last_page }}</span>
              <div class="flex gap-1.5">
                <button :disabled="tenantUsersPagination.current_page === 1" class="px-2.5 py-1 border border-slate-200 dark:border-slate-800 rounded text-[11px] font-semibold disabled:opacity-40 cursor-pointer" @click="fetchTenantUsers(tenantUsersPagination.current_page - 1)">
                  Previous
                </button>
                <button :disabled="tenantUsersPagination.current_page === tenantUsersPagination.last_page" class="px-2.5 py-1 border border-slate-200 dark:border-slate-800 rounded text-[11px] font-semibold disabled:opacity-40 cursor-pointer" @click="fetchTenantUsers(tenantUsersPagination.current_page + 1)">
                  Next
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Tenant Profile Modal -->
    <div v-if="editModal.show" class="fixed inset-0 z-40 bg-slate-950/40 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-md shadow-2xl p-6 relative">
        <h3 class="text-lg font-bold mb-4 text-slate-900 dark:text-white">
          Edit Tenant Profile
        </h3>
        
        <form class="space-y-4" @submit.prevent="submitEditForm">
          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Tenant Name</label>
            <input
              v-model="editForm.name"
              type="text"
              required
              placeholder="e.g. CoreX Fitness"
              class="w-full px-3.5 py-2 bg-slate-100/50 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 focus:border-indigo-500 rounded-lg text-sm outline-none transition-all"
            />
          </div>

          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Email</label>
            <input
              v-model="editForm.email"
              type="email"
              placeholder="e.g. contact@gym.com"
              class="w-full px-3.5 py-2 bg-slate-100/50 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 focus:border-indigo-500 rounded-lg text-sm outline-none transition-all"
            />
          </div>

          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Phone</label>
            <input
              v-model="editForm.phone"
              type="text"
              placeholder="e.g. 0779600296"
              class="w-full px-3.5 py-2 bg-slate-100/50 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 focus:border-indigo-500 rounded-lg text-sm outline-none transition-all"
            />
          </div>

          <div class="flex justify-end gap-2 pt-4">
            <button type="button" class="px-4 py-2 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 text-sm font-medium rounded-lg transition-all cursor-pointer" @click="editModal.show = false">
              Cancel
            </button>
            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold rounded-lg transition-all cursor-pointer">
              Continue
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Add/Edit Tenant User Modal -->
    <div v-if="userModal.show" class="fixed inset-0 z-40 bg-slate-950/40 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-md shadow-2xl p-6 relative">
        <h3 class="text-lg font-bold mb-4 text-slate-900 dark:text-white">
          {{ userModal.mode === 'create' ? 'Add New Tenant User' : 'Edit Tenant User' }}
        </h3>
        
        <form class="space-y-4" @submit.prevent="submitUserForm">
          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Full Name</label>
            <input
              v-model="userForm.name"
              type="text"
              required
              placeholder="e.g. John Doe"
              class="w-full px-3.5 py-2 bg-slate-100/50 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 focus:border-indigo-500 rounded-lg text-sm outline-none transition-all"
            />
          </div>

          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Email Address</label>
            <input
              v-model="userForm.email"
              type="email"
              required
              placeholder="e.g. user@gym.com"
              class="w-full px-3.5 py-2 bg-slate-100/50 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 focus:border-indigo-500 rounded-lg text-sm outline-none transition-all"
            />
          </div>

          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">
              Password {{ userModal.mode === 'edit' ? '(Leave blank to keep unchanged)' : '' }}
            </label>
            <input
              v-model="userForm.password"
              type="password"
              :required="userModal.mode === 'create'"
              placeholder="Minimum 6 characters"
              class="w-full px-3.5 py-2 bg-slate-100/50 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 focus:border-indigo-500 rounded-lg text-sm outline-none transition-all"
            />
          </div>

          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">User Role</label>
            <select
              v-model="userForm.role_id"
              required
              class="w-full px-3.5 py-2 bg-slate-100/50 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 focus:border-indigo-500 rounded-lg text-sm outline-none transition-all dark:bg-slate-900"
            >
              <option value="" disabled>
                Select Role
              </option>
              <option v-for="r in tenantRoles" :key="r.id" :value="r.id">
                {{ r.name }}
              </option>
            </select>
          </div>

          <div class="flex items-center gap-2 pt-1">
            <input
              id="user_is_active"
              v-model="userForm.is_active"
              type="checkbox"
              class="w-4 h-4 text-indigo-600 rounded focus:ring-indigo-500 border-slate-300 dark:border-slate-700"
            />
            <label for="user_is_active" class="text-xs font-semibold text-slate-700 dark:text-slate-300">
              Active User Account
            </label>
          </div>

          <div class="flex justify-end gap-2 pt-4 border-t border-slate-100 dark:border-slate-800">
            <button type="button" class="px-4 py-2 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 text-sm font-medium rounded-lg transition-all cursor-pointer" @click="userModal.show = false">
              Cancel
            </button>
            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold rounded-lg transition-all cursor-pointer">
              Continue
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Reusable Action OTP Verification Overlay -->
    <div v-if="otpModal.show" class="fixed inset-0 z-50 bg-slate-950/50 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-sm shadow-2xl p-6 relative text-center">
        <span class="mx-auto flex items-center justify-center w-12 h-12 rounded-full bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 mb-4">
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
              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
            />
          </svg>
        </span>
        <h3 class="text-lg font-bold mb-2">
          Verification Required
        </h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-6">
          An OTP is required to complete this action. Click below to receive a code on your mobile phone.
        </p>

        <!-- Sent Step -->
        <div v-if="otpModal.codeSent" class="space-y-4">
          <div class="relative">
            <input
              v-model="otpModal.code"
              type="text"
              maxlength="6"
              placeholder="Enter 6-digit code"
              class="w-full py-2 px-3 bg-slate-100/50 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 text-center tracking-widest font-semibold focus:border-indigo-500 rounded-lg outline-none transition-all"
            />
          </div>
          <div class="flex justify-between items-center text-[11px]">
            <span v-if="otpModal.debugCode" class="text-indigo-500 dark:text-indigo-400 font-mono font-semibold">Dev OTP: {{ otpModal.debugCode }}</span>
            <span v-else />
            <button class="text-indigo-600 dark:text-indigo-400 hover:underline font-semibold" @click="sendActionOtp">
              Resend OTP
            </button>
          </div>
          <p v-if="otpModal.error" class="text-xs text-rose-500 text-left font-medium mt-1">
            {{ otpModal.error }}
          </p>
          
          <div class="flex gap-2 pt-2">
            <button class="flex-1 py-2 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-lg text-xs font-semibold transition-all cursor-pointer" @click="otpModal.show = false">
              Cancel
            </button>
            <button :disabled="otpModal.loading" class="flex-1 py-2 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white rounded-lg text-xs font-bold shadow-md shadow-indigo-500/10 hover:shadow-indigo-500/25 transition-all cursor-pointer" @click="confirmActionWithOtp">
              {{ otpModal.loading ? 'Confirming...' : 'Verify & Confirm' }}
            </button>
          </div>
        </div>

        <!-- Initial Request Button -->
        <div v-else class="space-y-3">
          <button :disabled="otpModal.loading" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-lg text-sm transition-all flex items-center justify-center gap-2 cursor-pointer" @click="sendActionOtp">
            <svg
              v-if="otpModal.loading"
              class="animate-spin h-4 w-4 text-white"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
              />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
            </svg>
            Send Verification SMS
          </button>
          <button class="w-full py-2 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-lg text-xs font-medium transition-all cursor-pointer" @click="otpModal.show = false">
            Cancel
          </button>
        </div>
      </div>
    </div>

    <!-- Step-Wise Progress Modal -->
    <TenantProgressModal
      :show="progressModal.show"
      :job-id="progressModal.jobId"
      :subdomain="progressModal.subdomain"
      :operation="progressModal.operation"
      @close="onProgressModalClose"
      @complete="onProgressModalComplete"
    />
  </div>
  <div v-else class="py-8 text-center text-slate-400">
    Loading tenant details...
  </div>
</template>

<script>
import { ref, reactive, onMounted, inject, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiRequest } from '../composables/usePortalApi';
import TenantProgressModal from '../components/TenantProgressModal.vue';

export default {
  components: {
    TenantProgressModal,
  },
  setup() {
    const route = useRoute();
    const router = useRouter();
    const showToast = inject('showToast');

    const tenant = ref(null);
    const members = ref({ total_count: 0, recent: [] });
    const users = ref({ total_count: 0, active_count: 0, recent: [] });

    // Tenant Users management state
    const tenantUsers = ref([]);
    const tenantRoles = ref([]);
    const tenantUserSearch = ref('');
    const tenantUsersLoading = ref(false);
    const tenantUsersPagination = reactive({
      current_page: 1,
      last_page: 1,
      total: 0,
      per_page: 10,
    });

    const userModal = reactive({
      show: false,
      mode: 'create', // create | edit
      userId: null,
    });

    const userForm = reactive({
      name: '',
      email: '',
      password: '',
      role_id: '',
      is_active: true,
    });

    const progressModal = reactive({
      show: false,
      jobId: '',
      subdomain: '',
      operation: 'update',
    });

    const maxTrendCount = computed(() => {
      if (!members.value?.trends) return 1;
      return Math.max(...members.value.trends.map(t => t.count), 1);
    });
    
    const activeTab = ref('members'); // members | users

    const copied = ref(false);
    const copyCommandText = (text) => {
      navigator.clipboard.writeText(text);
      copied.value = true;
      setTimeout(() => {
        copied.value = false;
      }, 2000);
    };

    const editModal = reactive({
      show: false,
    });

    const editForm = reactive({
      name: '',
      email: '',
      phone: '',
    });

    // Reusable Action OTP Modal State
    const otpModal = reactive({
      show: false,
      codeSent: false,
      code: '',
      loading: false,
      error: null,
      debugCode: null,
      onVerifySuccess: null,
    });

    const fetchTenantDetails = async () => {
      try {
        const res = await apiRequest(`/tenants/${route.params.subdomain}`);
        tenant.value = res.tenant;
        members.value = res.members;
        users.value = res.users;
      } catch {
        showToast('Failed to load tenant details.', 'error');
        router.push('/tenants');
      }
    };

    const fetchTenantUsers = async (page = 1) => {
      tenantUsersLoading.value = true;
      try {
        const res = await apiRequest(`/tenants/${route.params.subdomain}/users`, {
          params: { page, search: tenantUserSearch.value }
        });
        tenantUsers.value = res.users?.data || [];
        tenantUsersPagination.current_page = res.users?.current_page || 1;
        tenantUsersPagination.last_page = res.users?.last_page || 1;
        tenantUsersPagination.total = res.users?.total || 0;
        tenantUsersPagination.per_page = res.users?.per_page || 10;
        tenantRoles.value = res.roles || [];
      } catch {
        // Ignore fetch error if tenant user table empty
      } finally {
        tenantUsersLoading.value = false;
      }
    };

    const openCreateUserModal = () => {
      userModal.mode = 'create';
      userModal.userId = null;
      userForm.name = '';
      userForm.email = '';
      userForm.password = '';
      userForm.role_id = tenantRoles.value.length > 0 ? tenantRoles.value[0].id : '';
      userForm.is_active = true;
      userModal.show = true;
    };

    const openEditUserModal = (u) => {
      userModal.mode = 'edit';
      userModal.userId = u.id;
      userForm.name = u.name;
      userForm.email = u.email;
      userForm.password = '';
      userForm.role_id = u.role_id || (tenantRoles.value.length > 0 ? tenantRoles.value[0].id : '');
      userForm.is_active = Boolean(u.is_active);
      userModal.show = true;
    };

    const submitUserForm = () => {
      otpModal.show = true;
      otpModal.codeSent = false;
      otpModal.code = '';
      otpModal.error = null;
      otpModal.debugCode = null;

      otpModal.onVerifySuccess = async (otpCode) => {
        try {
          if (userModal.mode === 'create') {
            await apiRequest(`/tenants/${route.params.subdomain}/users`, {
              method: 'post',
              headers: { 'X-Portal-OTP': otpCode },
              data: { ...userForm }
            });
            showToast('Tenant user created successfully.');
          } else {
            await apiRequest(`/tenants/${route.params.subdomain}/users/${userModal.userId}`, {
              method: 'put',
              headers: { 'X-Portal-OTP': otpCode },
              data: { ...userForm }
            });
            showToast('Tenant user updated successfully.');
          }
          userModal.show = false;
          otpModal.show = false;
          fetchTenantUsers(tenantUsersPagination.current_page);
          fetchTenantDetails();
        } catch (err) {
          const errorMsg = err.response?.data?.message || 'Failed to save tenant user.';
          if (err.response?.status === 422 && !err.response?.data?.otp_required) {
            userModal.show = true;
            otpModal.show = false;
            showToast(errorMsg, 'error');
          } else {
            otpModal.error = errorMsg;
          }
        }
      };
    };

    const toggleUserStatus = (u) => {
      otpModal.show = true;
      otpModal.codeSent = false;
      otpModal.code = '';
      otpModal.error = null;
      otpModal.debugCode = null;

      const nextStatus = !u.is_active;

      otpModal.onVerifySuccess = async (otpCode) => {
        try {
          await apiRequest(`/tenants/${route.params.subdomain}/users/${u.id}`, {
            method: 'put',
            headers: { 'X-Portal-OTP': otpCode },
            data: {
              name: u.name,
              email: u.email,
              role_id: u.role_id,
              is_active: nextStatus,
            }
          });
          showToast(nextStatus ? 'User activated successfully.' : 'User deactivated.');
          otpModal.show = false;
          fetchTenantUsers(tenantUsersPagination.current_page);
          fetchTenantDetails();
        } catch (err) {
          otpModal.error = err.response?.data?.message || 'Failed to update user status.';
        }
      };
    };

    const deleteUser = (u) => {
      otpModal.show = true;
      otpModal.codeSent = false;
      otpModal.code = '';
      otpModal.error = null;
      otpModal.debugCode = null;

      otpModal.onVerifySuccess = async (otpCode) => {
        try {
          await apiRequest(`/tenants/${route.params.subdomain}/users/${u.id}`, {
            method: 'delete',
            headers: { 'X-Portal-OTP': otpCode },
          });
          showToast('User deleted successfully.');
          otpModal.show = false;
          fetchTenantUsers(tenantUsersPagination.current_page);
          fetchTenantDetails();
        } catch (err) {
          otpModal.error = err.response?.data?.message || 'Failed to delete user.';
        }
      };
    };

    const openEditModal = () => {
      editForm.name = tenant.value.name;
      editForm.email = tenant.value.email || '';
      editForm.phone = tenant.value.phone || '';
      editModal.show = true;
    };

    const submitEditForm = () => {
      otpModal.show = true;
      otpModal.codeSent = false;
      otpModal.code = '';
      otpModal.error = null;
      otpModal.debugCode = null;

      otpModal.onVerifySuccess = async (otpCode) => {
        try {
          const res = await apiRequest(`/tenants/${tenant.value.subdomain}`, {
            method: 'put',
            headers: { 'X-Portal-OTP': otpCode },
            data: {
              name: editForm.name,
              email: editForm.email,
              phone: editForm.phone,
              is_active: tenant.value.is_active,
            }
          });
          editModal.show = false;
          otpModal.show = false;
          progressModal.jobId = res.job_id;
          progressModal.subdomain = res.subdomain;
          progressModal.operation = 'update';
          progressModal.show = true;
        } catch (err) {
          const errorMsg = err.response?.data?.message || 'Failed to update tenant details.';
          if (err.response?.status === 422 && !err.response?.data?.otp_required) {
            editModal.show = true;
            otpModal.show = false;
            showToast(errorMsg, 'error');
          } else {
            otpModal.error = errorMsg;
          }
        }
      };
    };

    const promptToggleStatus = () => {
      otpModal.show = true;
      otpModal.codeSent = false;
      otpModal.code = '';
      otpModal.error = null;
      otpModal.debugCode = null;

      const nextStatus = !tenant.value.is_active;

      otpModal.onVerifySuccess = async (otpCode) => {
        try {
          const res = await apiRequest(`/tenants/${tenant.value.subdomain}`, {
            method: 'put',
            headers: { 'X-Portal-OTP': otpCode },
            data: {
              name: tenant.value.name,
              email: tenant.value.email,
              phone: tenant.value.phone,
              is_active: nextStatus,
            }
          });
          otpModal.show = false;
          progressModal.jobId = res.job_id;
          progressModal.subdomain = res.subdomain;
          progressModal.operation = 'update';
          progressModal.show = true;
        } catch (err) {
          otpModal.error = err.response?.data?.message || 'Failed to change tenant status.';
        }
      };
    };

    const promptDeleteTenant = () => {
      otpModal.show = true;
      otpModal.codeSent = false;
      otpModal.code = '';
      otpModal.error = null;
      otpModal.debugCode = null;

      otpModal.onVerifySuccess = async (otpCode) => {
        try {
          const res = await apiRequest(`/tenants/${tenant.value.subdomain}`, {
            method: 'delete',
            headers: { 'X-Portal-OTP': otpCode },
          });
          otpModal.show = false;
          progressModal.jobId = res.job_id;
          progressModal.subdomain = res.subdomain;
          progressModal.operation = 'delete';
          progressModal.show = true;
        } catch (err) {
          otpModal.error = err.response?.data?.message || 'Failed to request tenant deletion.';
        }
      };
    };

    const onProgressModalClose = () => {
      progressModal.show = false;
      if (progressModal.operation === 'delete') {
        router.push('/tenants');
      } else {
        fetchTenantDetails();
      }
    };

    const onProgressModalComplete = () => {
      if (progressModal.operation === 'delete') {
        showToast('Tenant permanently deleted.');
        router.push('/tenants');
      } else {
        fetchTenantDetails();
      }
    };

    const sendActionOtp = async () => {
      otpModal.loading = true;
      otpModal.error = null;
      try {
        const res = await apiRequest('/auth/action-otp', { method: 'post' });
        otpModal.codeSent = true;
        showToast(res.message);
        if (res.otp_debug) {
          otpModal.debugCode = res.otp_debug;
        }
      } catch {
        otpModal.error = 'Failed to send OTP verification code.';
      } finally {
        otpModal.loading = false;
      }
    };

    const confirmActionWithOtp = async () => {
      if (!otpModal.code || otpModal.code.length !== 6) {
        otpModal.error = 'Please enter a valid 6-digit verification code.';
        return;
      }

      otpModal.loading = true;
      otpModal.error = null;

      if (otpModal.onVerifySuccess) {
        await otpModal.onVerifySuccess(otpModal.code);
      }
      otpModal.loading = false;
    };

    watch(activeTab, (newTab) => {
      if (newTab === 'users') {
        fetchTenantUsers();
      }
    });

    onMounted(() => {
      fetchTenantDetails();
      fetchTenantUsers();
    });

    return {
      tenant,
      members,
      users,
      activeTab,
      editModal,
      editForm,
      otpModal,
      progressModal,
      tenantUsers,
      tenantRoles,
      tenantUserSearch,
      tenantUsersLoading,
      tenantUsersPagination,
      userModal,
      userForm,
      fetchTenantUsers,
      openCreateUserModal,
      openEditUserModal,
      submitUserForm,
      toggleUserStatus,
      deleteUser,
      openEditModal,
      submitEditForm,
      promptToggleStatus,
      promptDeleteTenant,
      sendActionOtp,
      confirmActionWithOtp,
      copied,
      copyCommandText,
      maxTrendCount,
      onProgressModalClose,
      onProgressModalComplete,
    };
  }
};
</script>
