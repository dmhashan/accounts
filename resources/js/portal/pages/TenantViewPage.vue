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
                This tenant is inactive. To permanently delete it, drop its isolated database, and purge all records, run the following Artisan command in your host terminal:
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
      <!-- Tab Headers -->
      <div class="border-b border-slate-200 dark:border-slate-800 flex gap-6">
        <button :class="activeTab === 'members' ? 'border-indigo-500 text-indigo-600 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700'" class="pb-3 border-b-2 text-sm font-semibold transition-all cursor-pointer" @click="activeTab = 'members'">
          Member Summary
          <span class="ml-1.5 px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-full text-xs font-bold">{{ members.total_count }}</span>
        </button>
        <button :class="activeTab === 'users' ? 'border-indigo-500 text-indigo-600 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700'" class="pb-3 border-b-2 text-sm font-semibold transition-all cursor-pointer" @click="activeTab = 'users'">
          User Summary
          <span class="ml-1.5 px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-full text-xs font-bold">{{ users.total_count }}</span>
        </button>
      </div>

      <!-- Tab Content Area -->
      <div class="bg-white/70 dark:bg-slate-900/60 backdrop-blur-md border border-slate-200/80 dark:border-slate-800/80 rounded-xl shadow-sm overflow-hidden">
        <!-- Members Tab -->
        <div v-if="activeTab === 'members'" class="space-y-6 p-6">
          <!-- Key Metrics Grid -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Metric 1: Total Registered -->
            <div class="bg-slate-50/50 dark:bg-slate-950/40 border border-slate-200/50 dark:border-slate-800/40 rounded-xl p-4 flex flex-col justify-between space-y-3">
              <div class="flex items-center justify-between">
                <div>
                  <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Members</span>
                  <h3 class="text-2xl font-extrabold text-slate-800 dark:text-white mt-1">
                    {{ members.total_count }}
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
                      d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                    />
                  </svg>
                </div>
              </div>
              
              <!-- Active / Inactive / Temporary Breakdown -->
              <div class="flex items-center justify-between pt-2 border-t border-slate-200/60 dark:border-slate-800/60 text-[10px] font-bold uppercase tracking-wider">
                <div class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500" />
                  <span>Active: {{ members.active_count ?? 0 }}</span>
                </div>
                <div class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
                  <span class="w-1.5 h-1.5 rounded-full bg-slate-400" />
                  <span>Inactive: {{ members.inactive_count ?? 0 }}</span>
                </div>
                <div class="flex items-center gap-1.5 text-amber-600 dark:text-amber-400">
                  <span class="w-1.5 h-1.5 rounded-full bg-amber-500" />
                  <span>Temporary: {{ members.temp_count ?? 0 }}</span>
                </div>
              </div>
            </div>

            <!-- Metric 2: Monthly Signups (Static) -->
            <div class="bg-slate-50/50 dark:bg-slate-950/40 border border-slate-200/50 dark:border-slate-800/40 rounded-xl p-4 flex items-center justify-between">
              <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">New This Month</span>
                <div class="flex items-baseline gap-2 mt-1">
                  <h3 class="text-2xl font-extrabold text-slate-800 dark:text-white">
                    28
                  </h3>
                  <span class="text-xs font-bold text-emerald-500 flex items-center">+14.8%</span>
                </div>
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
                    d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"
                  />
                </svg>
              </div>
            </div>

            <!-- Metric 3: Active Status (Static Circular Gauge) -->
            <div class="bg-slate-50/50 dark:bg-slate-950/40 border border-slate-200/50 dark:border-slate-800/40 rounded-xl p-4 flex items-center justify-between">
              <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Member Retention</span>
                <h3 class="text-2xl font-extrabold text-slate-800 dark:text-white mt-1">
                  92.4%
                </h3>
              </div>
              <div class="relative w-12 h-12 flex items-center justify-center">
                <svg class="w-12 h-12 transform -rotate-90">
                  <circle
                    cx="24"
                    cy="24"
                    r="18"
                    class="stroke-slate-200 dark:stroke-slate-800"
                    stroke-width="3"
                    fill="transparent"
                  />
                  <circle
                    cx="24"
                    cy="24"
                    r="18"
                    class="stroke-indigo-600 dark:stroke-indigo-400"
                    stroke-width="3"
                    stroke-dasharray="113"
                    stroke-dashoffset="8.5"
                    stroke-linecap="round"
                    fill="transparent"
                  />
                </svg>
                <span class="absolute text-[10px] font-bold text-indigo-600 dark:text-indigo-400">92%</span>
              </div>
            </div>
          </div>

          <!-- Trend Chart (SVG Bar Chart) -->
          <div class="bg-slate-50/30 dark:bg-slate-950/20 border border-slate-200/50 dark:border-slate-800/40 rounded-xl p-5">
            <h4 class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-4">
              Member Registration Trend (Last 6 Months)
            </h4>
            
            <div class="h-44 flex items-end justify-between gap-2 px-4 pb-2 border-b border-slate-200 dark:border-slate-800 relative">
              <div class="absolute inset-x-0 bottom-1/4 border-b border-slate-100 dark:border-slate-800/50 pointer-events-none" />
              <div class="absolute inset-x-0 bottom-2/4 border-b border-slate-100 dark:border-slate-800/50 pointer-events-none" />
              <div class="absolute inset-x-0 bottom-3/4 border-b border-slate-100 dark:border-slate-800/50 pointer-events-none" />

              <div class="flex-1 flex flex-col items-center gap-2 group relative">
                <div class="w-full bg-slate-200 dark:bg-slate-800 group-hover:bg-indigo-500/40 rounded-t-md transition-all duration-300" style="height: 36px" />
                <div class="absolute -top-6 text-[10px] font-bold opacity-0 group-hover:opacity-100 bg-slate-950 text-white px-1.5 py-0.5 rounded transition-all">
                  12
                </div>
              </div>
              <div class="flex-1 flex flex-col items-center gap-2 group relative">
                <div class="w-full bg-slate-200 dark:bg-slate-800 group-hover:bg-indigo-500/40 rounded-t-md transition-all duration-300" style="height: 58px" />
                <div class="absolute -top-6 text-[10px] font-bold opacity-0 group-hover:opacity-100 bg-slate-950 text-white px-1.5 py-0.5 rounded transition-all">
                  19
                </div>
              </div>
              <div class="flex-1 flex flex-col items-center gap-2 group relative">
                <div class="w-full bg-slate-200 dark:bg-slate-800 group-hover:bg-indigo-500/40 rounded-t-md transition-all duration-300" style="height: 72px" />
                <div class="absolute -top-6 text-[10px] font-bold opacity-0 group-hover:opacity-100 bg-slate-950 text-white px-1.5 py-0.5 rounded transition-all">
                  24
                </div>
              </div>
              <div class="flex-1 flex flex-col items-center gap-2 group relative">
                <div class="w-full bg-slate-200 dark:bg-slate-800 group-hover:bg-indigo-500/40 rounded-t-md transition-all duration-300" style="height: 98px" />
                <div class="absolute -top-6 text-[10px] font-bold opacity-0 group-hover:opacity-100 bg-slate-950 text-white px-1.5 py-0.5 rounded transition-all">
                  32
                </div>
              </div>
              <div class="flex-1 flex flex-col items-center gap-2 group relative">
                <div class="w-full bg-indigo-500/10 dark:bg-indigo-500/20 group-hover:bg-indigo-500/40 border border-indigo-500/20 rounded-t-md transition-all duration-300" style="height: 124px" />
                <div class="absolute -top-6 text-[10px] font-bold opacity-0 group-hover:opacity-100 bg-slate-950 text-white px-1.5 py-0.5 rounded transition-all">
                  41
                </div>
              </div>
              <div class="flex-1 flex flex-col items-center gap-2 group relative">
                <div class="w-full bg-indigo-600 group-hover:bg-indigo-500 rounded-t-md shadow-lg shadow-indigo-500/20 transition-all duration-300" :style="{ height: `${Math.min(150, 60 + members.total_count * 8)}px` }" />
                <div class="absolute -top-6 text-[10px] font-bold opacity-0 group-hover:opacity-100 bg-slate-950 text-white px-1.5 py-0.5 rounded transition-all">
                  {{ members.total_count + 12 }}
                </div>
              </div>
            </div>
            <div class="flex justify-between text-[10px] font-bold uppercase text-slate-400 mt-2 px-4">
              <span>Jan</span>
              <span>Feb</span>
              <span>Mar</span>
              <span>Apr</span>
              <span>May</span>
              <span>Jun</span>
            </div>
          </div>
        </div>

        <!-- Users Tab -->
        <div v-if="activeTab === 'users'" class="space-y-6 p-6">
          <!-- Key Metrics Grid -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Metric 1: Total Staff -->
            <div class="bg-slate-50/50 dark:bg-slate-950/40 border border-slate-200/50 dark:border-slate-800/40 rounded-xl p-4 flex items-center justify-between">
              <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Staff</span>
                <h3 class="text-2xl font-extrabold text-slate-800 dark:text-white mt-1">
                  {{ users.total_count }}
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
                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                  />
                </svg>
              </div>
            </div>

            <!-- Metric 2: Staff Split (Static progress bar) -->
            <div class="bg-slate-50/50 dark:bg-slate-950/40 border border-slate-200/50 dark:border-slate-800/40 rounded-xl p-4 flex flex-col justify-center space-y-2">
              <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Staff Split</span>
              <div class="space-y-1">
                <div class="flex items-center justify-between text-[10px] font-bold">
                  <span class="text-slate-500">Trainers / Coaches</span>
                  <span class="text-slate-700 dark:text-slate-300">60%</span>
                </div>
                <div class="w-full bg-slate-200 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                  <div class="bg-indigo-600 h-1.5 rounded-full" style="width: 60%" />
                </div>
              </div>
            </div>

            <!-- Metric 3: Active Status (Static Circular Gauge) -->
            <div class="bg-slate-50/50 dark:bg-slate-950/40 border border-slate-200/50 dark:border-slate-800/40 rounded-xl p-4 flex items-center justify-between">
              <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Access Security</span>
                <h3 class="text-2xl font-extrabold text-slate-800 dark:text-white mt-1">
                  100%
                </h3>
              </div>
              <div class="relative w-12 h-12 flex items-center justify-center">
                <svg class="w-12 h-12 transform -rotate-90">
                  <circle
                    cx="24"
                    cy="24"
                    r="18"
                    class="stroke-slate-200 dark:stroke-slate-800"
                    stroke-width="3"
                    fill="transparent"
                  />
                  <circle
                    cx="24"
                    cy="24"
                    r="18"
                    class="stroke-emerald-500"
                    stroke-width="3"
                    stroke-dasharray="113"
                    stroke-dashoffset="0"
                    stroke-linecap="round"
                    fill="transparent"
                  />
                </svg>
                <span class="absolute text-[10px] font-bold text-emerald-500">100%</span>
              </div>
            </div>
          </div>

          <!-- Weekly Activity Chart (SVG Spline Path) -->
          <div class="bg-slate-50/30 dark:bg-slate-950/20 border border-slate-200/50 dark:border-slate-800/40 rounded-xl p-5">
            <h4 class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-4">
              Staff Activity (Logins Over Last 7 Days)
            </h4>
            
            <div class="h-32 w-full relative">
              <svg viewBox="0 0 700 130" class="w-full h-full overflow-visible">
                <defs>
                  <linearGradient
                    id="areaGradient"
                    x1="0"
                    y1="0"
                    x2="0"
                    y2="1"
                  >
                    <stop offset="0%" stop-color="#6366f1" stop-opacity="0.3" />
                    <stop offset="100%" stop-color="#6366f1" stop-opacity="0" />
                  </linearGradient>
                </defs>
                <line
                  x1="0"
                  y1="32.5"
                  x2="700"
                  y2="32.5"
                  stroke="rgba(100,116,139,0.1)"
                  stroke-width="1"
                />
                <line
                  x1="0"
                  y1="65"
                  x2="700"
                  y2="65"
                  stroke="rgba(100,116,139,0.1)"
                  stroke-width="1"
                />
                <line
                  x1="0"
                  y1="97.5"
                  x2="700"
                  y2="97.5"
                  stroke="rgba(100,116,139,0.1)"
                  stroke-width="1"
                />
                
                <path d="M 0 130 C 50 110, 80 40, 116 40 C 180 40, 220 90, 280 80 C 350 70, 390 10, 450 10 C 520 10, 580 110, 700 80 L 700 130 Z" fill="url(#areaGradient)" />
                <path
                  d="M 0 130 C 50 110, 80 40, 116 40 C 180 40, 220 90, 280 80 C 350 70, 390 10, 450 10 C 520 10, 580 110, 700 80"
                  fill="none"
                  stroke="#6366f1"
                  stroke-width="3"
                  stroke-linecap="round"
                />
                
                <circle
                  cx="116"
                  cy="40"
                  r="4.5"
                  fill="#6366f1"
                  stroke="#ffffff"
                  stroke-width="1.5"
                />
                <circle
                  cx="280"
                  cy="80"
                  r="4.5"
                  fill="#6366f1"
                  stroke="#ffffff"
                  stroke-width="1.5"
                />
                <circle
                  cx="450"
                  cy="10"
                  r="4.5"
                  fill="#6366f1"
                  stroke="#ffffff"
                  stroke-width="1.5"
                />
                <circle
                  cx="700"
                  cy="80"
                  r="4.5"
                  fill="#6366f1"
                  stroke="#ffffff"
                  stroke-width="1.5"
                />
              </svg>
            </div>
            <div class="flex justify-between text-[10px] font-bold uppercase text-slate-400 mt-2 px-1">
              <span>Mon</span>
              <span>Tue</span>
              <span>Wed</span>
              <span>Thu</span>
              <span>Fri</span>
              <span>Sat</span>
              <span>Sun</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Profile Modal -->
    <div v-if="editModal.show" class="fixed inset-0 z-40 bg-slate-950/40 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-md shadow-2xl p-6 relative">
        <h3 class="text-lg font-bold mb-4">
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
  </div>
  <div v-else class="py-8 text-center text-slate-400">
    Loading tenant details...
  </div>
</template>

<script>
import { ref, reactive, onMounted, inject } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiRequest } from '../composables/usePortalApi';

export default {
  setup() {
    const route = useRoute();
    const router = useRouter();
    const showToast = inject('showToast');

    const tenant = ref(null);
    const members = ref({ total_count: 0, recent: [] });
    const users = ref({ total_count: 0, recent: [] });
    
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
          await apiRequest(`/tenants/${tenant.value.subdomain}`, {
            method: 'put',
            headers: { 'X-Portal-OTP': otpCode },
            data: {
              name: editForm.name,
              email: editForm.email,
              phone: editForm.phone,
              is_active: tenant.value.is_active,
            }
          });
          showToast('Tenant profile updated successfully.');
          editModal.show = false;
          otpModal.show = false;
          fetchTenantDetails();
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
          await apiRequest(`/tenants/${tenant.value.subdomain}`, {
            method: 'put',
            headers: { 'X-Portal-OTP': otpCode },
            data: {
              name: tenant.value.name,
              email: tenant.value.email,
              phone: tenant.value.phone,
              is_active: nextStatus,
            }
          });
          showToast(nextStatus ? 'Tenant has been activated successfully!' : 'Tenant has been temporarily suspended/blocked.');
          otpModal.show = false;
          fetchTenantDetails();
        } catch (err) {
          otpModal.error = err.response?.data?.message || 'Failed to change tenant status.';
        }
      };
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

    onMounted(fetchTenantDetails);

    return {
      tenant,
      members,
      users,
      activeTab,
      editModal,
      editForm,
      otpModal,
      openEditModal,
      submitEditForm,
      promptToggleStatus,
      sendActionOtp,
      confirmActionWithOtp,
      copied,
      copyCommandText,
    };
  }
};
</script>
