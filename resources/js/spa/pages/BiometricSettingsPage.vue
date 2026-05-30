<template>
  <section class="app-page-frame">
    <AppPageHeader title="Biometric Device" />

    <div class="app-page-scroll">
      <!-- Load error -->
      <div v-if="loadError" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
        {{ loadError }}
      </div>

      <div v-if="loading" class="py-12 text-center text-sm text-secondary-500 dark:text-secondary-400">
        Loading configuration…
      </div>

      <template v-else>
        <!-- Save feedback -->
        <div v-if="successMessage" class="mb-4 rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 px-4 py-3 text-sm text-green-700 dark:text-green-200">
          {{ successMessage }}
        </div>
        <div v-if="saveError" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
          {{ saveError }}
        </div>

        <!-- ── Card 1: Device Setup ── -->
        <div class="app-surface rounded-2xl overflow-hidden mb-4">
          <div class="px-4 md:px-6 py-4 border-b border-secondary-200/70 dark:border-secondary-700/70 flex items-center gap-3">
            <Cpu class="w-5 h-5 text-primary-500 flex-shrink-0" :stroke-width="2" />
            <div>
              <h2 class="text-base font-semibold" style="color: var(--text-strong)">
                Device Setup
              </h2>
              <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">
                Connect a biometric access terminal to sync members and attendance
              </p>
            </div>
          </div>

          <div class="px-4 md:px-6 py-4 space-y-4">
            <!-- Master enable switch -->
            <div class="flex items-center justify-between gap-4">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center flex-shrink-0">
                  <Power class="w-4 h-4 text-primary-600 dark:text-primary-400" :stroke-width="2" />
                </div>
                <div>
                  <p class="text-sm font-medium" style="color: var(--text-strong)">
                    Enable Biometric Integration
                  </p>
                  <p class="text-xs text-secondary-500 dark:text-secondary-400">
                    Turn on to connect and use a biometric device
                  </p>
                </div>
              </div>
              <button
                type="button"
                role="switch"
                :aria-checked="form['biometric.enabled'] === '1'"
                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-400 flex-shrink-0"
                :class="form['biometric.enabled'] === '1' ? 'bg-primary-600' : 'bg-secondary-300 dark:bg-secondary-600'"
                @click="toggle('biometric.enabled')"
              >
                <span
                  class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform"
                  :class="form['biometric.enabled'] === '1' ? 'translate-x-6' : 'translate-x-1'"
                />
              </button>
            </div>

            <!-- Device configuration form -->
            <Transition
              enter-active-class="transition-all duration-200 ease-out"
              enter-from-class="opacity-0 -translate-y-1"
              enter-to-class="opacity-100 translate-y-0"
              leave-active-class="transition-all duration-150 ease-in"
              leave-from-class="opacity-100 translate-y-0"
              leave-to-class="opacity-0 -translate-y-1"
            >
              <div v-if="form['biometric.enabled'] === '1'" class="ml-0 md:ml-12 rounded-xl border border-secondary-200 dark:border-secondary-700 p-4 bg-secondary-50/50 dark:bg-secondary-800/30">
                <p class="text-xs font-semibold text-secondary-500 dark:text-secondary-400 uppercase tracking-wide mb-3">
                  Device Configuration
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <!-- Device Maker -->
                  <AppFormField label="Device Maker">
                    <select
                      v-model="form['biometric.device_maker']"
                      class="app-form-input w-full"
                      @change="onMakerChange"
                    >
                      <option value="">
                        Select maker…
                      </option>
                      <option v-for="(maker, key) in DEVICE_REGISTRY" :key="key" :value="key">
                        {{ maker.label }}
                      </option>
                    </select>
                  </AppFormField>

                  <!-- Device Model -->
                  <AppFormField label="Device Model">
                    <select
                      v-model="form['biometric.device_model']"
                      class="app-form-input w-full"
                      :disabled="!form['biometric.device_maker']"
                    >
                      <option value="">
                        Select model…
                      </option>
                      <option
                        v-for="model in availableModels"
                        :key="model.value"
                        :value="model.value"
                      >
                        {{ model.label }}
                      </option>
                    </select>
                  </AppFormField>

                  <!-- IP -->
                  <AppFormField label="Device IP / Hostname">
                    <AppFormInput
                      v-model="form['biometric.device_ip']"
                      type="text"
                      placeholder="192.168.1.100"
                      maxlength="255"
                    />
                  </AppFormField>

                  <!-- Port -->
                  <AppFormField label="Port">
                    <AppFormInput
                      v-model="form['biometric.device_port']"
                      type="number"
                      placeholder="80"
                      min="1"
                      max="65535"
                    />
                  </AppFormField>

                  <!-- Username -->
                  <AppFormField label="Username">
                    <AppFormInput
                      v-model="form['biometric.device_username']"
                      type="text"
                      placeholder="admin"
                      maxlength="100"
                      autocomplete="off"
                    />
                  </AppFormField>

                  <!-- Password -->
                  <AppFormField label="Password">
                    <AppFormInput
                      v-model="form['biometric.device_password']"
                      type="password"
                      placeholder="••••••••"
                      maxlength="255"
                      autocomplete="new-password"
                    />
                  </AppFormField>
                </div>

                <!-- Test connection -->
                <div class="mt-4 flex items-center gap-3">
                  <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-4 py-2 text-sm font-medium text-secondary-700 dark:text-secondary-200 hover:bg-secondary-50 dark:hover:bg-secondary-700 disabled:opacity-50 transition-colors"
                    :disabled="testing"
                    @click="testConnection"
                  >
                    <Wifi class="w-4 h-4" :stroke-width="2" />
                    {{ testing ? 'Testing…' : 'Test Connection' }}
                  </button>
                  <span v-if="testResult !== null" class="text-sm font-medium" :class="testResult ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                    <template v-if="testResult">✓ Connected</template>
                    <template v-else>✗ {{ testError }}</template>
                  </span>
                </div>
              </div>
            </Transition>
          </div>
        </div>

        <!-- ── Card 2: Sync Settings ── -->
        <Transition
          enter-active-class="transition-all duration-200 ease-out"
          enter-from-class="opacity-0 -translate-y-1"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition-all duration-150 ease-in"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 -translate-y-1"
        >
          <div v-if="form['biometric.enabled'] === '1'" class="app-surface rounded-2xl overflow-hidden mb-4">
            <div class="px-4 md:px-6 py-4 border-b border-secondary-200/70 dark:border-secondary-700/70 flex items-center gap-3">
              <RefreshCw class="w-5 h-5 text-indigo-500 flex-shrink-0" :stroke-width="2" />
              <div>
                <h2 class="text-base font-semibold" style="color: var(--text-strong)">
                  Sync Settings
                </h2>
                <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">
                  Control what data flows to and from the device
                </p>
              </div>
            </div>

            <div class="divide-y divide-secondary-100 dark:divide-secondary-800">
              <!-- Up-sync: Members → Device -->
              <div class="px-4 md:px-6 py-4 space-y-3">
                <div class="flex items-center justify-between gap-4">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                      <Upload class="w-4 h-4 text-blue-600 dark:text-blue-400" :stroke-width="2" />
                    </div>
                    <div>
                      <p class="text-sm font-medium" style="color: var(--text-strong)">
                        Sync Members to Device
                      </p>
                      <p class="text-xs text-secondary-500 dark:text-secondary-400">
                        Auto-push member create / update / delete to the device
                      </p>
                    </div>
                  </div>
                  <button
                    type="button"
                    role="switch"
                    :aria-checked="form['biometric.sync_members'] === '1'"
                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-400 flex-shrink-0"
                    :class="form['biometric.sync_members'] === '1' ? 'bg-primary-600' : 'bg-secondary-300 dark:bg-secondary-600'"
                    @click="toggle('biometric.sync_members')"
                  >
                    <span
                      class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform"
                      :class="form['biometric.sync_members'] === '1' ? 'translate-x-6' : 'translate-x-1'"
                    />
                  </button>
                </div>

                <!-- Bulk sync action -->
                <div v-if="form['biometric.sync_members'] === '1'" class="ml-0 md:ml-12">
                  <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 hover:bg-blue-700 px-4 py-2 text-sm font-medium text-white disabled:opacity-50 transition-colors"
                    :disabled="syncing"
                    @click="syncAllMembers"
                  >
                    <Users class="w-4 h-4" :stroke-width="2" />
                    {{ syncing ? 'Syncing…' : 'Sync All Members Now' }}
                  </button>
                  <p v-if="syncResult" class="mt-1.5 text-xs text-secondary-500 dark:text-secondary-400">
                    {{ syncResult }}
                  </p>
                </div>
              </div>

              <!-- Down-sync: Device → Attendance -->
              <div class="px-4 md:px-6 py-4 space-y-3">
                <div class="flex items-center justify-between gap-4">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center flex-shrink-0">
                      <Download class="w-4 h-4 text-emerald-600 dark:text-emerald-400" :stroke-width="2" />
                    </div>
                    <div>
                      <p class="text-sm font-medium" style="color: var(--text-strong)">
                        Sync Attendance from Device
                      </p>
                      <p class="text-xs text-secondary-500 dark:text-secondary-400">
                        Pull access events from device every 30 minutes as attendance records
                      </p>
                    </div>
                  </div>
                  <button
                    type="button"
                    role="switch"
                    :aria-checked="form['biometric.sync_attendance'] === '1'"
                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-400 flex-shrink-0"
                    :class="form['biometric.sync_attendance'] === '1' ? 'bg-primary-600' : 'bg-secondary-300 dark:bg-secondary-600'"
                    @click="toggle('biometric.sync_attendance')"
                  >
                    <span
                      class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform"
                      :class="form['biometric.sync_attendance'] === '1' ? 'translate-x-6' : 'translate-x-1'"
                    />
                  </button>
                </div>
                <div v-if="form['biometric.sync_attendance'] === '1'" class="ml-0 md:ml-12">
                  <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 px-4 py-2 text-sm font-medium text-white disabled:opacity-50 transition-colors"
                    :disabled="attendanceSyncing"
                    @click="syncAttendance"
                  >
                    <Download class="w-4 h-4" :stroke-width="2" />
                    {{ attendanceSyncing ? 'Pulling…' : 'Pull Attendance Now' }}
                  </button>
                  <p v-if="attendanceSyncResult" class="mt-1.5 text-xs text-secondary-500 dark:text-secondary-400">
                    {{ attendanceSyncResult }}
                  </p>
                </div>
              </div>

              <!-- Access control by payment -->
              <div class="px-4 md:px-6 py-4 space-y-3">
                <div class="flex items-center justify-between gap-4">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center flex-shrink-0">
                      <ShieldCheck class="w-4 h-4 text-orange-600 dark:text-orange-400" :stroke-width="2" />
                    </div>
                    <div>
                      <p class="text-sm font-medium" style="color: var(--text-strong)">
                        Enforce Access by Payment Status
                      </p>
                      <p class="text-xs text-secondary-500 dark:text-secondary-400">
                        Block device access for members with expired memberships
                      </p>
                    </div>
                  </div>
                  <button
                    type="button"
                    role="switch"
                    :aria-checked="form['biometric.access_control'] === '1'"
                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-400 flex-shrink-0"
                    :class="form['biometric.access_control'] === '1' ? 'bg-primary-600' : 'bg-secondary-300 dark:bg-secondary-600'"
                    @click="toggle('biometric.access_control')"
                  >
                    <span
                      class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform"
                      :class="form['biometric.access_control'] === '1' ? 'translate-x-6' : 'translate-x-1'"
                    />
                  </button>
                </div>

                <!-- Grace period -->
                <div v-if="form['biometric.access_control'] === '1'" class="ml-0 md:ml-12 max-w-xs">
                  <AppFormField label="Grace Period (days after membership end)">
                    <AppFormInput
                      v-model="form['biometric.grace_period_days']"
                      type="number"
                      placeholder="0"
                      min="0"
                      max="365"
                    />
                  </AppFormField>
                </div>
              </div>
            </div>
          </div>
        </Transition>

        <!-- ── Card 3: Real-Time Event Push ── -->
        <Transition
          enter-active-class="transition-all duration-200 ease-out"
          enter-from-class="opacity-0 -translate-y-1"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition-all duration-150 ease-in"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 -translate-y-1"
        >
          <div v-if="form['biometric.enabled'] === '1'" class="app-surface rounded-2xl overflow-hidden mb-4">
            <div class="px-4 md:px-6 py-4 border-b border-secondary-200/70 dark:border-secondary-700/70 flex items-center gap-3">
              <Zap class="w-5 h-5 text-yellow-500 flex-shrink-0" :stroke-width="2" />
              <div>
                <h2 class="text-base font-semibold" style="color: var(--text-strong)">
                  Real-Time Event Push
                </h2>
                <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">
                  Device pushes access events instantly instead of waiting for the 30-min poll
                </p>
              </div>
            </div>

            <div class="px-4 md:px-6 py-4 space-y-4">
              <!-- Enable toggle -->
              <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-xl bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center flex-shrink-0">
                    <Zap class="w-4 h-4 text-yellow-600 dark:text-yellow-400" :stroke-width="2" />
                  </div>
                  <div>
                    <p class="text-sm font-medium" style="color: var(--text-strong)">
                      Enable Real-Time Push
                    </p>
                    <p class="text-xs text-secondary-500 dark:text-secondary-400">
                      Accept incoming events from the device webhook
                    </p>
                  </div>
                </div>
                <button
                  type="button"
                  role="switch"
                  :aria-checked="form['biometric.webhook_enabled'] === '1'"
                  class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-400 flex-shrink-0"
                  :class="form['biometric.webhook_enabled'] === '1' ? 'bg-primary-600' : 'bg-secondary-300 dark:bg-secondary-600'"
                  @click="toggle('biometric.webhook_enabled')"
                >
                  <span
                    class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform"
                    :class="form['biometric.webhook_enabled'] === '1' ? 'translate-x-6' : 'translate-x-1'"
                  />
                </button>
              </div>

              <Transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-1"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-1"
              >
                <div v-if="form['biometric.webhook_enabled'] === '1'" class="ml-0 md:ml-12 space-y-4">
                  <!-- Server config -->
                  <div class="rounded-xl border border-secondary-200 dark:border-secondary-700 p-4 bg-secondary-50/50 dark:bg-secondary-800/30">
                    <p class="text-xs font-semibold text-secondary-500 dark:text-secondary-400 uppercase tracking-wide mb-3">
                      Server Reachability
                    </p>
                    <p class="text-xs text-secondary-500 dark:text-secondary-400 mb-3">
                      Enter the IP address or hostname of this server as seen from the biometric device (LAN or public IP).
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                      <div class="sm:col-span-2">
                        <AppFormField label="Server Host (reachable from device)">
                          <AppFormInput
                            v-model="form['biometric.webhook_server_host']"
                            type="text"
                            placeholder="192.168.1.10 or myserver.com"
                            maxlength="255"
                          />
                        </AppFormField>
                      </div>
                      <AppFormField label="Server Port">
                        <AppFormInput
                          v-model="form['biometric.webhook_server_port']"
                          type="number"
                          placeholder="80"
                          min="1"
                          max="65535"
                        />
                      </AppFormField>
                    </div>
                  </div>

                  <!-- Webhook token -->
                  <div class="rounded-xl border border-secondary-200 dark:border-secondary-700 p-4 bg-secondary-50/50 dark:bg-secondary-800/30">
                    <p class="text-xs font-semibold text-secondary-500 dark:text-secondary-400 uppercase tracking-wide mb-3">
                      Webhook Token
                    </p>
                    <p class="text-xs text-secondary-500 dark:text-secondary-400 mb-3">
                      A secret token included in the device's webhook URL. Regenerate to invalidate any existing device configuration.
                    </p>
                    <div class="flex items-center gap-2">
                      <div class="flex-1 min-w-0 rounded-lg border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-800 px-3 py-2 text-xs font-mono text-secondary-600 dark:text-secondary-300 truncate">
                        {{ webhookToken || '— not generated yet —' }}
                      </div>
                      <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-xs font-medium text-secondary-700 dark:text-secondary-200 hover:bg-secondary-50 dark:hover:bg-secondary-700 disabled:opacity-50 transition-colors flex-shrink-0"
                        :disabled="generatingToken"
                        @click="generateToken"
                      >
                        <RefreshCw class="w-3.5 h-3.5" :class="generatingToken ? 'animate-spin' : ''" :stroke-width="2" />
                        {{ generatingToken ? '' : 'Regenerate' }}
                      </button>
                    </div>
                  </div>

                  <!-- Webhook URL preview -->
                  <div v-if="webhookToken && form['biometric.webhook_server_host']" class="rounded-xl border border-blue-200 dark:border-blue-800 p-4 bg-blue-50/50 dark:bg-blue-900/10">
                    <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wide mb-2">
                      Device Webhook URL
                    </p>
                    <p class="text-xs font-mono text-blue-700 dark:text-blue-300 break-all">
                      {{ webhookUrlPreview }}
                    </p>
                  </div>

                  <!-- Apply to device -->
                  <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                    <button
                      type="button"
                      class="inline-flex items-center gap-2 rounded-lg bg-yellow-500 hover:bg-yellow-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50 transition-colors"
                      :disabled="configuringWebhook || !webhookToken || !form['biometric.webhook_server_host']"
                      @click="configureWebhook"
                    >
                      <Zap class="w-4 h-4" :stroke-width="2" />
                      {{ configuringWebhook ? 'Applying…' : 'Apply to Device' }}
                    </button>
                    <button
                      type="button"
                      class="inline-flex items-center gap-2 rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-4 py-2 text-sm font-medium text-secondary-700 dark:text-secondary-200 hover:bg-secondary-50 dark:hover:bg-secondary-700 disabled:opacity-50 transition-colors"
                      :disabled="checkingWebhookStatus"
                      @click="checkWebhookStatus"
                    >
                      <MonitorCheck class="w-4 h-4" :stroke-width="2" />
                      {{ checkingWebhookStatus ? 'Checking…' : 'Check Device Config' }}
                    </button>
                  </div>

                  <p v-if="webhookConfigResult" class="text-xs font-medium" :class="webhookConfigOk ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                    {{ webhookConfigResult }}
                  </p>

                  <!-- Device current config readback -->
                  <div v-if="deviceWebhookConfig" class="rounded-xl border border-secondary-200 dark:border-secondary-700 p-4 bg-secondary-50/50 dark:bg-secondary-800/30 text-xs space-y-1.5">
                    <p class="font-semibold text-secondary-500 dark:text-secondary-400 uppercase tracking-wide mb-2">
                      Current Device Notification Config
                    </p>
                    <div class="grid grid-cols-2 gap-x-4 gap-y-1">
                      <span class="text-secondary-500 dark:text-secondary-400">Host</span>
                      <span class="font-mono text-secondary-700 dark:text-secondary-300">{{ deviceWebhookConfig.ip || '—' }}</span>
                      <span class="text-secondary-500 dark:text-secondary-400">Port</span>
                      <span class="font-mono text-secondary-700 dark:text-secondary-300">{{ deviceWebhookConfig.port || '—' }}</span>
                      <span class="text-secondary-500 dark:text-secondary-400">Path</span>
                      <span class="font-mono text-secondary-700 dark:text-secondary-300 break-all">{{ deviceWebhookConfig.path || '—' }}</span>
                    </div>
                  </div>
                </div>
              </Transition>
            </div>
          </div>
        </Transition>

        <!-- ── Card 4: Recent Sync Logs ── -->
        <div v-if="form['biometric.enabled'] === '1'" class="app-surface rounded-2xl overflow-hidden mb-4">
          <div class="px-4 md:px-6 py-4 border-b border-secondary-200/70 dark:border-secondary-700/70 flex items-center gap-3">
            <Activity class="w-5 h-5 text-secondary-400 flex-shrink-0" :stroke-width="2" />
            <h2 class="text-base font-semibold" style="color: var(--text-strong)">
              Recent Sync Events
            </h2>
            <span v-if="logsFailedCount > 0" class="inline-flex items-center gap-1 rounded-full bg-red-100 dark:bg-red-900/30 px-2 py-0.5 text-xs font-medium text-red-700 dark:text-red-400">
              {{ logsFailedCount }} failed
            </span>
            <button type="button" class="ml-auto text-xs text-primary-600 dark:text-primary-400 hover:underline" @click="() => loadRecentLogs(1)">
              Refresh
            </button>
          </div>

          <div v-if="logsLoading" class="px-4 md:px-6 py-6 text-center text-sm text-secondary-400">
            Loading logs…
          </div>
          <div v-else-if="recentLogs.length === 0" class="px-4 md:px-6 py-6 text-center text-sm text-secondary-400">
            No sync events yet.
          </div>
          <template v-else>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-secondary-50 dark:bg-secondary-800/50">
                  <tr>
                    <th class="px-4 py-2.5 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400">
                      Member
                    </th>
                    <th class="px-4 py-2.5 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400">
                      Direction
                    </th>
                    <th class="px-4 py-2.5 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400">
                      Action
                    </th>
                    <th class="px-4 py-2.5 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400">
                      Status
                    </th>
                    <th class="px-4 py-2.5 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 hidden md:table-cell">
                      Message
                    </th>
                    <th class="px-4 py-2.5 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400">
                      Time
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-secondary-100 dark:divide-secondary-800">
                  <tr v-for="log in recentLogs" :key="log.id" :class="log.status === 'failed' ? 'bg-red-50/40 dark:bg-red-900/10' : ''">
                    <td class="px-4 py-2.5 text-secondary-700 dark:text-secondary-300">
                      {{ log.member ? log.member.name : '—' }}
                    </td>
                    <td class="px-4 py-2.5">
                      <span class="inline-flex items-center gap-1 text-xs font-medium" :class="log.direction === 'up' ? 'text-blue-600 dark:text-blue-400' : 'text-emerald-600 dark:text-emerald-400'">
                        <ArrowUp v-if="log.direction === 'up'" class="w-3 h-3" />
                        <ArrowDown v-else class="w-3 h-3" />
                        {{ log.direction }}
                      </span>
                    </td>
                    <td class="px-4 py-2.5 text-secondary-600 dark:text-secondary-400 capitalize">
                      {{ log.action.replace('_', ' ') }}
                    </td>
                    <td class="px-4 py-2.5">
                      <span
                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                        :class="log.status === 'success'
                          ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400'
                          : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400'"
                      >
                        {{ log.status }}
                      </span>
                    </td>
                    <td class="px-4 py-2.5 text-secondary-500 dark:text-secondary-400 text-xs hidden md:table-cell max-w-[200px] truncate" :title="log.error_message || ''">
                      {{ log.error_message || '—' }}
                    </td>
                    <td class="px-4 py-2.5 text-secondary-500 dark:text-secondary-400 text-xs whitespace-nowrap">
                      {{ formatDateTime(log.synced_at) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <div v-if="logsMeta.last_page > 1" class="px-4 md:px-6 py-3 border-t border-secondary-100 dark:border-secondary-800 flex items-center justify-between gap-3">
              <p class="text-xs text-secondary-500 dark:text-secondary-400">
                Page {{ logsMeta.current_page }} of {{ logsMeta.last_page }} &middot; {{ logsMeta.total }} events
              </p>
              <div class="flex items-center gap-1">
                <button
                  type="button"
                  class="inline-flex items-center justify-center w-7 h-7 rounded-lg border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-800 text-secondary-600 dark:text-secondary-400 hover:bg-secondary-50 dark:hover:bg-secondary-700 disabled:opacity-40 transition-colors"
                  :disabled="logsMeta.current_page <= 1"
                  @click="loadRecentLogs(logsMeta.current_page - 1)"
                >
                  <ChevronLeft class="w-3.5 h-3.5" />
                </button>
                <template v-for="page in paginationPages" :key="page">
                  <span v-if="page === '...'" class="px-1 text-xs text-secondary-400">…</span>
                  <button
                    v-else
                    type="button"
                    class="inline-flex items-center justify-center w-7 h-7 rounded-lg border text-xs font-medium transition-colors"
                    :class="page === logsMeta.current_page
                      ? 'border-primary-500 bg-primary-600 text-white'
                      : 'border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-800 text-secondary-600 dark:text-secondary-400 hover:bg-secondary-50 dark:hover:bg-secondary-700'"
                    @click="loadRecentLogs(page)"
                  >
                    {{ page }}
                  </button>
                </template>
                <button
                  type="button"
                  class="inline-flex items-center justify-center w-7 h-7 rounded-lg border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-800 text-secondary-600 dark:text-secondary-400 hover:bg-secondary-50 dark:hover:bg-secondary-700 disabled:opacity-40 transition-colors"
                  :disabled="logsMeta.current_page >= logsMeta.last_page"
                  @click="loadRecentLogs(logsMeta.current_page + 1)"
                >
                  <ChevronRight class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>
          </template>
        </div>

        <!-- Save button -->
        <div class="flex justify-end">
          <button
            type="button"
            class="w-full sm:w-auto px-5 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium disabled:opacity-50 transition-colors"
            :disabled="submitting"
            @click="save"
          >
            {{ submitting ? 'Saving…' : 'Save Configuration' }}
          </button>
        </div>
      </template>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import {
    Activity,
    ArrowDown,
    ArrowUp,
    ChevronLeft,
    ChevronRight,
    Cpu,
    Download,
    MonitorCheck,
    Power,
    RefreshCw,
    ShieldCheck,
    Upload,
    Users,
    Wifi,
    Zap,
} from 'lucide-vue-next';
import AppFormField from '../components/forms/AppFormField.vue';
import AppFormInput from '../components/forms/AppFormInput.vue';
import AppPageHeader from '../components/AppPageHeader.vue';
import { apiRequest } from '../composables/useApiClient';
import { useDateTimeFormat } from '../composables/useDateTimeFormat';
const { formatDateTime } = useDateTimeFormat();
// ── Supported device registry ──────────────────────────────────────────────
const DEVICE_REGISTRY = {
    hikvision: {
        label: 'HikVision',
        models: [
            { value: 'DS-K1T320MFWX-B', label: 'DS-K1T320MFWX-B (Face Recognition Terminal)' },
        ],
    },
};

// ── State ──────────────────────────────────────────────────────────────────
const loading    = ref(true);
const loadError  = ref('');
const submitting = ref(false);
const saveError  = ref('');
const successMessage = ref('');

const testing    = ref(false);
const testResult = ref(null);   // null | true | false
const testError  = ref('');

const syncing    = ref(false);
const syncResult = ref('');

const attendanceSyncing    = ref(false);
const attendanceSyncResult = ref('');

// Real-time webhook
const webhookToken           = ref('');
const generatingToken        = ref(false);
const configuringWebhook     = ref(false);
const webhookConfigResult    = ref('');
const webhookConfigOk        = ref(false);
const checkingWebhookStatus  = ref(false);
const deviceWebhookConfig    = ref(null);

const webhookUrlPreview = computed(() => {
    const host  = form.value['biometric.webhook_server_host'];
    const port  = form.value['biometric.webhook_server_port'] || '80';
    const token = webhookToken.value;
    if (!host || !token) return '';
    return `http://${host}:${port}/api/biometric/events/{tenantDomain}?token=${token}`;
});

const logsLoading    = ref(false);
const recentLogs     = ref([]);
const logsFailedCount = ref(0);
const logsMeta       = ref({ current_page: 1, last_page: 1, per_page: 20, total: 0 });

const paginationPages = computed(() => {
    const current = logsMeta.value.current_page;
    const last    = logsMeta.value.last_page;
    if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1);
    const pages = [];
    pages.push(1);
    if (current > 3) pages.push('...');
    for (let p = Math.max(2, current - 1); p <= Math.min(last - 1, current + 1); p++) pages.push(p);
    if (current < last - 2) pages.push('...');
    pages.push(last);
    return pages;
});

const form = ref({
    'biometric.enabled':             '0',
    'biometric.device_maker':        '',
    'biometric.device_model':        '',
    'biometric.device_ip':           '',
    'biometric.device_port':         '80',
    'biometric.device_username':     'admin',
    'biometric.device_password':     '',
    'biometric.sync_members':        '0',
    'biometric.sync_attendance':     '0',
    'biometric.access_control':      '0',
    'biometric.grace_period_days':   '0',
    'biometric.webhook_enabled':     '0',
    'biometric.webhook_server_host': '',
    'biometric.webhook_server_port': '80',
});

// ── Computed ───────────────────────────────────────────────────────────────
const availableModels = computed(() => {
    const maker = form.value['biometric.device_maker'];
    return DEVICE_REGISTRY[maker]?.models ?? [];
});

// ── Helpers ────────────────────────────────────────────────────────────────
function toggle(key) {
    form.value[key] = form.value[key] === '1' ? '0' : '1';
}

function onMakerChange() {
    form.value['biometric.device_model'] = '';
}

// ── API calls ──────────────────────────────────────────────────────────────
async function load() {
    loading.value  = true;
    loadError.value = '';
    try {
        const response = await apiRequest('/api/settings/configuration');
        const data = response.data || {};
        Object.keys(form.value).forEach((key) => {
            if (data[key] !== undefined) form.value[key] = data[key];
        });
        // Load webhook token (not part of the form, managed separately)
        if (data['biometric.webhook_token']) {
            webhookToken.value = data['biometric.webhook_token'];
        }
        if (form.value['biometric.enabled'] === '1') loadRecentLogs();
    } catch {
        loadError.value = 'Failed to load configuration.';
    } finally {
        loading.value = false;
    }
}

async function save() {
    submitting.value = true;
    saveError.value  = '';
    successMessage.value = '';
    try {
        await apiRequest('/api/settings/configuration', { method: 'PUT', data: form.value });
        successMessage.value = 'Configuration saved successfully.';
        setTimeout(() => { successMessage.value = ''; }, 3000);
    } catch (err) {
        saveError.value = err?.response?.data?.message || 'Failed to save configuration.';
    } finally {
        submitting.value = false;
    }
}

async function testConnection() {
    testing.value   = true;
    testResult.value = null;
    testError.value  = '';

    // Save first so the backend uses the latest values
    await save();

    try {
        const res = await apiRequest('/api/settings/biometric/test-connection', { method: 'POST' });
        testResult.value = res.success !== false;
    } catch (err) {
        testResult.value = false;
        testError.value  = err?.response?.data?.message || 'Connection failed.';
    } finally {
        testing.value = false;
    }
}

async function syncAllMembers() {
    syncing.value    = true;
    syncResult.value = '';
    try {
        const res = await apiRequest('/api/settings/biometric/sync-all', { method: 'POST' });
        syncResult.value = res.message || 'Sync complete.';
        loadRecentLogs();
    } catch (err) {
        syncResult.value = err?.response?.data?.message || 'Sync failed.';
    } finally {
        syncing.value = false;
    }
}

async function syncAttendance() {
    attendanceSyncing.value    = true;
    attendanceSyncResult.value = '';
    try {
        const res = await apiRequest('/api/settings/biometric/sync-attendance', { method: 'POST' });
        attendanceSyncResult.value = res.message || 'Done.';
        loadRecentLogs();
    } catch (err) {
        attendanceSyncResult.value = err?.response?.data?.message || 'Failed to pull attendance.';
    } finally {
        attendanceSyncing.value = false;
    }
}

async function generateToken() {
    generatingToken.value = true;
    try {
        const res = await apiRequest('/api/settings/biometric/webhook/generate-token', { method: 'POST' });
        webhookToken.value = res.token || '';
        webhookConfigResult.value = '';
        deviceWebhookConfig.value = null;
    } catch (err) {
        saveError.value = err?.response?.data?.message || 'Failed to generate token.';
    } finally {
        generatingToken.value = false;
    }
}

async function configureWebhook() {
    configuringWebhook.value = true;
    webhookConfigResult.value = '';
    webhookConfigOk.value = false;

    // Save server host/port and webhook_enabled first
    await save();

    try {
        const res = await apiRequest('/api/settings/biometric/webhook/configure', { method: 'POST' });
        webhookConfigOk.value = res.success !== false;
        webhookConfigResult.value = res.message || (webhookConfigOk.value ? 'Device configured.' : 'Failed.');
        if (webhookConfigOk.value) loadRecentLogs();
    } catch (err) {
        webhookConfigOk.value = false;
        webhookConfigResult.value = err?.response?.data?.message || 'Configuration failed.';
    } finally {
        configuringWebhook.value = false;
    }
}

async function checkWebhookStatus() {
    checkingWebhookStatus.value = true;
    deviceWebhookConfig.value = null;
    webhookConfigResult.value = '';
    try {
        const res = await apiRequest('/api/settings/biometric/webhook/status');
        if (res.success) {
            deviceWebhookConfig.value = res.config;
        } else {
            webhookConfigOk.value = false;
            webhookConfigResult.value = res.message || 'Could not read device config.';
        }
    } catch (err) {
        webhookConfigOk.value = false;
        webhookConfigResult.value = err?.response?.data?.message || 'Failed to read device config.';
    } finally {
        checkingWebhookStatus.value = false;
    }
}

async function loadRecentLogs(page = 1) {    logsLoading.value = true;
    try {
        const res = await apiRequest(`/api/settings/biometric/recent-logs?page=${page}&per_page=20`);
        recentLogs.value      = res.data || [];
        logsFailedCount.value = res.failed_count ?? 0;
        if (res.meta) logsMeta.value = res.meta;
    } catch {
        // non-critical, fail silently
    } finally {
        logsLoading.value = false;
    }
}

onMounted(load);
</script>
