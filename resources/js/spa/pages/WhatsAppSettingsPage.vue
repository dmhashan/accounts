<template>
  <section class="app-page-frame">
    <AppPageHeader title="WhatsApp Integration">
      <template #extra-slot>
        <div class="flex items-center gap-2">
          <span
            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
            :class="config.enabled ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/40' : 'bg-secondary-100 dark:bg-secondary-800 text-secondary-600 dark:text-secondary-400 border border-secondary-200 dark:border-secondary-700'"
          >
            <span class="w-2 h-2 rounded-full" :class="config.enabled ? 'bg-emerald-500 animate-pulse' : 'bg-secondary-400'" />
            {{ config.enabled ? 'WhatsApp Active' : 'WhatsApp Disabled' }}
          </span>
        </div>
      </template>
    </AppPageHeader>

    <!-- Top Alert Messages -->
    <div v-if="pageError" class="mb-4 rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200 flex items-center justify-between">
      <span>{{ pageError }}</span>
      <button type="button" class="text-red-500 hover:text-red-700 font-bold ml-2" @click="pageError = ''">
        ×
      </button>
    </div>

    <div v-if="pageSuccess" class="mb-4 rounded-xl border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 px-4 py-3 text-sm text-green-700 dark:text-green-200 flex items-center justify-between">
      <span>{{ pageSuccess }}</span>
      <button type="button" class="text-green-500 hover:text-green-700 font-bold ml-2" @click="pageSuccess = ''">
        ×
      </button>
    </div>

    <div class="app-surface rounded-2xl flex flex-col flex-1 min-h-0 overflow-hidden">
      <!-- Tabs Navigation Header -->
      <div class="border-b border-secondary-200 dark:border-secondary-700 px-4 md:px-6 pt-3 flex items-center gap-6 shrink-0 bg-secondary-50/50 dark:bg-secondary-900/30">
        <button
          type="button"
          class="pb-3 text-sm font-semibold transition-colors relative flex items-center gap-2"
          :class="activeTab === 'config' ? 'text-primary-600 dark:text-primary-400 border-b-2 border-primary-600 dark:border-primary-400' : 'text-secondary-500 hover:text-secondary-800 dark:text-secondary-400 dark:hover:text-secondary-200'"
          @click="activeTab = 'config'"
        >
          <Sliders class="w-4 h-4" />
          <span>Configuration</span>
          <span v-if="config.enabled" class="w-2 h-2 rounded-full bg-emerald-500 inline-block" />
        </button>

        <button
          type="button"
          class="pb-3 text-sm font-semibold transition-colors relative flex items-center gap-2"
          :class="activeTab === 'chat' ? 'text-primary-600 dark:text-primary-400 border-b-2 border-primary-600 dark:border-primary-400' : 'text-secondary-500 hover:text-secondary-800 dark:text-secondary-400 dark:hover:text-secondary-200'"
          @click="activeTab = 'chat'"
        >
          <MessageSquareMore class="w-4 h-4" />
          <span>Members & Chat</span>
          <span v-if="membersTotal > 0" class="px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-secondary-200 dark:bg-secondary-700 text-secondary-700 dark:text-secondary-300">
            {{ membersTotal }}
          </span>
        </button>
      </div>

      <!-- Tab 1: Configuration -->
      <div v-show="activeTab === 'config'" class="app-page-scroll p-4 md:p-6 space-y-6 flex-1">
        <!-- Turn On / Off Switch Box -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl border border-secondary-200 dark:border-secondary-700 p-5 bg-secondary-50/70 dark:bg-secondary-900/40">
          <div>
            <div class="flex items-center gap-2">
              <h4 class="text-base font-semibold text-secondary-900 dark:text-white">
                WhatsApp / GoWA Integration
              </h4>
              <span
                class="px-2 py-0.5 text-[11px] font-bold rounded-full uppercase tracking-wider"
                :class="config.enabled ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-secondary-200 text-secondary-700 dark:bg-secondary-800 dark:text-secondary-400'"
              >
                {{ config.enabled ? 'Enabled' : 'Disabled' }}
              </span>
            </div>
            <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-1 max-w-xl">
              Turn on to connect to a GoWA (Go WhatsApp Web Multi-Device) server or custom WhatsApp provider. This enables member live chat, message history, and direct WhatsApp communication.
            </p>
          </div>

          <div class="shrink-0 flex items-center gap-3">
            <AppFormSwitch
              id="whatsapp-enabled-switch"
              :model-value="config.enabled"
              true-label="Enabled"
              false-label="Disabled"
              @update:model-value="val => { config.enabled = Boolean(val); }"
            />
          </div>
        </div>

        <!-- GoWA Configuration Fields (Shown when enabled) -->
        <div v-if="config.enabled" class="space-y-6">
          <div class="rounded-2xl border border-secondary-200 dark:border-secondary-700 p-5 space-y-5 bg-white dark:bg-secondary-900/60 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-secondary-100 dark:border-secondary-800">
              <div>
                <h4 class="text-sm font-bold text-secondary-900 dark:text-white flex items-center gap-2">
                  <Radio class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                  GoWA Server Settings
                </h4>
                <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">
                  Configure your Go WhatsApp Web instance endpoint and authentication details.
                </p>
              </div>

              <button
                type="button"
                class="inline-flex items-center justify-center gap-2 px-3.5 py-2 rounded-xl border border-secondary-300 dark:border-secondary-600 text-xs font-semibold text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-800 transition-colors disabled:opacity-50"
                :disabled="testingConnection"
                @click="testConnection"
              >
                <RefreshCw v-if="testingConnection" class="w-3.5 h-3.5 animate-spin text-primary-600" />
                <Plug v-else class="w-3.5 h-3.5 text-primary-600 dark:text-primary-400" />
                <span>{{ testingConnection ? 'Testing Connection...' : 'Test Connection' }}</span>
              </button>
            </div>

            <!-- Connection Status Banner -->
            <div
              v-if="connectionStatus"
              class="rounded-xl p-4 text-xs space-y-1 transition-all"
              :class="connectionStatus.success ? 'bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200' : 'bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-200'"
            >
              <div class="flex items-start gap-2.5">
                <CheckCircle2 v-if="connectionStatus.success" class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" />
                <AlertCircle v-else class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                <div class="flex-1">
                  <p class="font-semibold">
                    {{ connectionStatus.message }}
                  </p>
                  <p v-if="connectionStatus.device_state" class="mt-1 opacity-90">
                    Device State: <span class="font-mono font-bold">{{ connectionStatus.device_state }}</span>
                  </p>
                  <button
                    v-if="!connectionStatus.success || connectionStatus.device_state !== 'connected'"
                    type="button"
                    class="mt-2 inline-flex items-center gap-1.5 px-3 py-1 bg-white/20 hover:bg-white/30 text-white rounded-lg text-xs font-semibold backdrop-blur-xs transition-colors"
                    @click="fetchQrCode"
                  >
                    <QrCode class="w-3.5 h-3.5" />
                    <span>View QR Code to Pair Phone</span>
                  </button>
                </div>
              </div>
            </div>

            <!-- QR Code Pairing Modal/Panel -->
            <div v-if="qrModalOpen" class="rounded-2xl border border-primary-200 dark:border-primary-800 bg-primary-50/50 dark:bg-primary-950/30 p-5 space-y-4">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <QrCode class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                  <h4 class="text-sm font-bold text-secondary-900 dark:text-white">
                    Scan QR Code to Pair WhatsApp
                  </h4>
                </div>
                <button type="button" class="text-secondary-400 hover:text-secondary-600 text-xs font-semibold" @click="qrModalOpen = false">
                  ✕ Close
                </button>
              </div>

              <div v-if="loadingQr" class="py-8 text-center space-y-2">
                <RefreshCw class="w-6 h-6 animate-spin mx-auto text-primary-600" />
                <p class="text-xs text-secondary-500">
                  Generating pairing QR code from GoWA...
                </p>
              </div>

              <div v-else-if="qrLink" class="flex flex-col sm:flex-row items-center gap-6 bg-white dark:bg-secondary-900 p-4 rounded-xl border border-secondary-200 dark:border-secondary-700">
                <div class="p-2 bg-white rounded-xl shadow-xs border border-secondary-100 shrink-0">
                  <img :src="qrLink" alt="WhatsApp QR Code" class="w-48 h-48 object-contain rounded-lg" />
                </div>
                <div class="space-y-2 text-xs text-secondary-600 dark:text-secondary-300">
                  <p class="font-bold text-secondary-900 dark:text-white text-sm">
                    How to Link:
                  </p>
                  <ol class="list-decimal list-inside space-y-1 text-xs">
                    <li>Open WhatsApp on your phone</li>
                    <li>Tap <b>Linked Devices</b> in Settings</li>
                    <li>Tap <b>Link a Device</b> and point your camera at this QR code</li>
                  </ol>
                  <p class="text-[11px] text-secondary-400 pt-1">
                    QR code expires in ~30s. Click refresh if needed.
                  </p>
                  <button
                    type="button"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-secondary-300 dark:border-secondary-600 text-xs font-semibold hover:bg-secondary-50 dark:hover:bg-secondary-800 transition-colors"
                    @click="fetchQrCode"
                  >
                    <RefreshCw class="w-3.5 h-3.5" />
                    <span>Refresh QR Code</span>
                  </button>
                </div>
              </div>

              <div v-else class="py-4 text-center text-xs text-red-600 dark:text-red-400">
                {{ qrError || 'Could not load QR code. Ensure your GoWA server URL is correct and reachable.' }}
              </div>
            </div>

            <!-- Inputs Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <AppFormField label="GoWA Server URL" for-id="gowa-url" required>
                <AppFormInput
                  id="gowa-url"
                  v-model="config.url"
                  type="url"
                  placeholder="https://gowa.srv1835569.hstgr.cloud"
                  maxlength="500"
                />
              </AppFormField>

              <AppFormField label="API Key / Auth Token" for-id="gowa-api-key" optional>
                <div class="relative">
                  <AppFormInput
                    id="gowa-api-key"
                    v-model="config.api_key"
                    :type="showApiKey ? 'text' : 'password'"
                    placeholder="admin:password or Token"
                    maxlength="255"
                  />
                  <button
                    type="button"
                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-200"
                    @click="showApiKey = !showApiKey"
                  >
                    <EyeOff v-if="showApiKey" class="w-4 h-4" />
                    <Eye v-else class="w-4 h-4" />
                  </button>
                </div>
              </AppFormField>

              <AppFormField label="Device ID / Session ID" for-id="gowa-session-id" optional>
                <AppFormInput
                  id="gowa-session-id"
                  v-model="config.session_id"
                  type="text"
                  placeholder="Optional Device ID (e.g. device_1)"
                  maxlength="255"
                />
              </AppFormField>
            </div>
          </div>

          <!-- Save Button -->
          <div class="flex items-center justify-end pt-2">
            <button
              type="button"
              class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl text-sm font-semibold shadow-sm hover:shadow transition-all disabled:opacity-50"
              :disabled="savingConfig"
              @click="saveConfig"
            >
              <RefreshCw v-if="savingConfig" class="w-4 h-4 animate-spin" />
              <span>{{ savingConfig ? 'Saving Settings...' : 'Save Configuration' }}</span>
            </button>
          </div>
        </div>

        <!-- Disabled Inactive State Message -->
        <div v-else class="rounded-2xl border border-dashed border-secondary-300 dark:border-secondary-700 p-10 text-center space-y-3 bg-secondary-50/30 dark:bg-secondary-900/20">
          <div class="w-12 h-12 rounded-2xl bg-secondary-100 dark:bg-secondary-800 text-secondary-400 flex items-center justify-center mx-auto">
            <PowerOff class="w-6 h-6" />
          </div>
          <h4 class="text-base font-semibold text-secondary-900 dark:text-white">
            WhatsApp Integration is Currently Turned Off
          </h4>
          <p class="text-xs text-secondary-500 dark:text-secondary-400 max-w-md mx-auto">
            Enable the toggle above to configure your GoWA server credentials and start communicating with members via WhatsApp.
          </p>
          <button
            type="button"
            class="mt-2 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-xs font-semibold transition-colors"
            @click="config.enabled = true"
          >
            <Power class="w-3.5 h-3.5" />
            Enable WhatsApp Now
          </button>
        </div>
      </div>

      <!-- Tab 2: Members & Chat -->
      <div v-show="activeTab === 'chat'" class="flex-1 flex flex-col min-h-0 overflow-hidden">
        <!-- Banner if WhatsApp is not enabled -->
        <div v-if="!config.enabled" class="p-3 bg-amber-50 dark:bg-amber-950/40 border-b border-amber-200 dark:border-amber-800 text-xs text-amber-800 dark:text-amber-200 flex items-center justify-between shrink-0 px-4">
          <div class="flex items-center gap-2">
            <AlertTriangle class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0" />
            <span>WhatsApp integration is currently turned off. To send messages and sync chats, enable it in Configuration.</span>
          </div>
          <button
            type="button"
            class="text-xs font-bold text-amber-700 dark:text-amber-300 underline hover:no-underline ml-2 shrink-0"
            @click="activeTab = 'config'"
          >
            Go to Configuration →
          </button>
        </div>

        <!-- Master-Detail Chat Layout -->
        <div class="flex-1 flex flex-col md:flex-row min-h-0 overflow-hidden">
          <!-- Left Column: Members List -->
          <div
            class="w-full md:w-80 lg:w-96 flex flex-col border-r border-secondary-200 dark:border-secondary-700 shrink-0 bg-secondary-50/30 dark:bg-secondary-900/20"
            :class="selectedMember && mobileChatOpen ? 'hidden md:flex' : 'flex'"
          >
            <!-- Search & Filters -->
            <div class="p-3 space-y-2 border-b border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900">
              <div class="relative">
                <Search class="w-4 h-4 text-secondary-400 absolute left-3 top-1/2 -translate-y-1/2" />
                <input
                  v-model="memberSearch"
                  type="text"
                  placeholder="Search by name, phone, MEM-..."
                  class="w-full pl-9 pr-8 py-2 rounded-xl text-xs bg-secondary-100/70 dark:bg-secondary-800/60 border border-transparent focus:border-primary-500 focus:bg-white dark:focus:bg-secondary-900 focus:outline-none transition-all"
                  @input="debouncedSearch"
                />
                <button
                  v-if="memberSearch"
                  type="button"
                  class="absolute right-2.5 top-1/2 -translate-y-1/2 text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-200"
                  @click="memberSearch = ''; loadMembers(1)"
                >
                  <X class="w-3.5 h-3.5" />
                </button>
              </div>

              <!-- Quick Filter Chips -->
              <div class="flex items-center gap-1.5 overflow-x-auto pb-0.5 scrollbar-none text-[11px]">
                <button
                  type="button"
                  class="px-2.5 py-1 rounded-lg font-medium transition-colors whitespace-nowrap"
                  :class="memberFilter === 'all' ? 'bg-primary-600 text-white font-semibold' : 'bg-secondary-100 dark:bg-secondary-800 text-secondary-600 dark:text-secondary-400 hover:bg-secondary-200 dark:hover:bg-secondary-700'"
                  @click="setMemberFilter('all')"
                >
                  All ({{ membersTotal }})
                </button>
                <button
                  type="button"
                  class="px-2.5 py-1 rounded-lg font-medium transition-colors whitespace-nowrap"
                  :class="memberFilter === 'whatsapp' ? 'bg-primary-600 text-white font-semibold' : 'bg-secondary-100 dark:bg-secondary-800 text-secondary-600 dark:text-secondary-400 hover:bg-secondary-200 dark:hover:bg-secondary-700'"
                  @click="setMemberFilter('whatsapp')"
                >
                  Has WhatsApp
                </button>
                <button
                  type="button"
                  class="px-2.5 py-1 rounded-lg font-medium transition-colors whitespace-nowrap"
                  :class="memberFilter === 'active' ? 'bg-primary-600 text-white font-semibold' : 'bg-secondary-100 dark:bg-secondary-800 text-secondary-600 dark:text-secondary-400 hover:bg-secondary-200 dark:hover:bg-secondary-700'"
                  @click="setMemberFilter('active')"
                >
                  Active Only
                </button>
              </div>
            </div>

            <!-- Members List Scroll Container -->
            <div class="flex-1 overflow-y-auto divide-y divide-secondary-100 dark:divide-secondary-800/60">
              <div v-if="loadingMembers" class="p-8 text-center text-xs text-secondary-500 dark:text-secondary-400 space-y-2">
                <RefreshCw class="w-5 h-5 animate-spin mx-auto text-primary-500" />
                <p>Loading members...</p>
              </div>

              <template v-else>
                <button
                  v-for="member in members"
                  :key="member.id"
                  type="button"
                  class="w-full text-left p-3 flex items-start gap-3 transition-colors relative group"
                  :class="selectedMember?.id === member.id ? 'bg-primary-50/80 dark:bg-primary-950/30' : 'hover:bg-secondary-100/50 dark:hover:bg-secondary-800/40'"
                  @click="selectMember(member)"
                >
                  <!-- Active indicator strip -->
                  <span
                    v-if="selectedMember?.id === member.id"
                    class="absolute left-0 top-0 bottom-0 w-1 bg-primary-600 dark:bg-primary-400 rounded-r"
                  />

                  <!-- Avatar -->
                  <MemberAvatar
                    :src="member.profile_photo_url"
                    :initials="getInitials(member.name)"
                    size="sm"
                    class="shrink-0 mt-0.5"
                  />

                  <!-- Member Details -->
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-1">
                      <p class="text-xs font-bold text-secondary-900 dark:text-white truncate">
                        {{ member.name }}
                      </p>
                      <span
                        v-if="member.allow_whatsapp"
                        class="px-1.5 py-0.2 text-[9px] font-bold rounded bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 shrink-0"
                      >
                        WA
                      </span>
                    </div>

                    <p class="text-[11px] text-secondary-500 dark:text-secondary-400 truncate mt-0.5">
                      {{ getMemberPhone(member) || 'No phone number' }}
                    </p>

                    <div class="flex items-center justify-between gap-2 mt-1">
                      <span class="text-[10px] text-secondary-400 font-mono">
                        {{ member.member_id || ('#' + member.id) }}
                      </span>
                      <span
                        class="text-[10px] px-1.5 py-0.5 rounded-full font-medium"
                        :class="member.is_active ? 'bg-green-50 dark:bg-green-950/50 text-green-700 dark:text-green-300' : 'bg-secondary-100 dark:bg-secondary-800 text-secondary-500'"
                      >
                        {{ member.is_active ? 'Active' : 'Inactive' }}
                      </span>
                    </div>
                  </div>
                </button>

                <div v-if="members.length === 0" class="p-8 text-center text-xs text-secondary-400">
                  No members found matching your search.
                </div>
              </template>
            </div>

            <!-- Member List Pagination -->
            <div v-if="membersLastPage > 1" class="p-2 border-t border-secondary-200 dark:border-secondary-700 flex items-center justify-between text-xs bg-white dark:bg-secondary-900">
              <button
                type="button"
                class="px-2.5 py-1 rounded border border-secondary-300 dark:border-secondary-700 disabled:opacity-40"
                :disabled="membersPage <= 1 || loadingMembers"
                @click="loadMembers(membersPage - 1)"
              >
                ← Prev
              </button>
              <span class="text-[11px] text-secondary-500">
                Page {{ membersPage }} / {{ membersLastPage }}
              </span>
              <button
                type="button"
                class="px-2.5 py-1 rounded border border-secondary-300 dark:border-secondary-700 disabled:opacity-40"
                :disabled="membersPage >= membersLastPage || loadingMembers"
                @click="loadMembers(membersPage + 1)"
              >
                Next →
              </button>
            </div>
          </div>

          <!-- Right Column: WhatsApp Chat Pane -->
          <div
            class="flex-1 flex flex-col min-h-0 bg-secondary-50/30 dark:bg-secondary-950/40 relative overflow-hidden"
            :class="!selectedMember && !mobileChatOpen ? 'hidden md:flex' : 'flex'"
          >
            <!-- When a member is selected -->
            <template v-if="selectedMember">
              <!-- Chat Header -->
              <div class="px-4 py-3 border-b border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 flex items-center justify-between shrink-0 shadow-xs">
                <div class="flex items-center gap-3 min-w-0">
                  <!-- Mobile Back Button -->
                  <button
                    type="button"
                    class="md:hidden p-1 rounded-lg text-secondary-500 hover:bg-secondary-100 dark:hover:bg-secondary-800"
                    @click="mobileChatOpen = false"
                  >
                    <ArrowLeft class="w-5 h-5" />
                  </button>

                  <MemberAvatar
                    :src="selectedMember.profile_photo_url"
                    :initials="getInitials(selectedMember.name)"
                    size="sm"
                    class="shrink-0"
                  />

                  <div class="min-w-0">
                    <div class="flex items-center gap-2">
                      <h3 class="text-sm font-bold text-secondary-900 dark:text-white truncate">
                        {{ selectedMember.name }}
                      </h3>
                      <span
                        v-if="selectedMember.allow_whatsapp"
                        class="px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/50 dark:border-emerald-800/40 shrink-0"
                      >
                        WhatsApp Opt-In
                      </span>
                    </div>

                    <div class="flex items-center gap-2 text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">
                      <span class="font-mono text-[11px]">{{ getMemberPhone(selectedMember) || 'No phone' }}</span>
                      <span>•</span>
                      <span>{{ selectedMember.member_id || ('#' + selectedMember.id) }}</span>
                    </div>
                  </div>
                </div>

                <!-- Chat Header Actions -->
                <div class="flex items-center gap-2 shrink-0">
                  <button
                    type="button"
                    class="p-2 rounded-xl border border-secondary-200 dark:border-secondary-700 text-secondary-600 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-800 transition-colors"
                    title="Refresh Messages"
                    :disabled="loadingMessages"
                    @click="loadChatMessages(selectedMember)"
                  >
                    <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loadingMessages }" />
                  </button>

                  <a
                    v-if="getMemberPhone(selectedMember)"
                    :href="`https://wa.me/${cleanPhoneForWa(getMemberPhone(selectedMember))}`"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-secondary-200 dark:border-secondary-700 text-xs font-semibold text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-800 transition-colors"
                    title="Open in WhatsApp Web"
                  >
                    <ExternalLink class="w-3.5 h-3.5" />
                    <span>WhatsApp Web</span>
                  </a>
                </div>
              </div>

              <!-- Chat Message Stream -->
              <div
                ref="chatStreamRef"
                class="flex-1 overflow-y-auto p-4 md:p-6 space-y-3 scroll-smooth"
              >
                <!-- Loading State -->
                <div v-if="loadingMessages" class="p-8 text-center text-xs text-secondary-400 space-y-2">
                  <RefreshCw class="w-6 h-6 animate-spin mx-auto text-primary-500" />
                  <p>Fetching WhatsApp conversation...</p>
                </div>

                <!-- Empty Chat State -->
                <div
                  v-else-if="chatMessages.length === 0"
                  class="py-16 text-center space-y-3 max-w-sm mx-auto"
                >
                  <div class="w-12 h-12 rounded-2xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto shadow-sm">
                    <MessageSquare class="w-6 h-6" />
                  </div>
                  <h4 class="text-sm font-bold text-secondary-900 dark:text-white">
                    No Message History Found
                  </h4>
                  <p class="text-xs text-secondary-500 dark:text-secondary-400 leading-relaxed">
                    There are no previous messages recorded for {{ selectedMember.name }}. Type a message below to start a WhatsApp conversation.
                  </p>
                </div>

                <!-- Message Bubbles -->
                <template v-else>
                  <div
                    v-for="msg in chatMessages"
                    :key="msg.id"
                    class="flex flex-col max-w-[85%] md:max-w-[70%]"
                    :class="msg.from_me ? 'ml-auto items-end' : 'mr-auto items-start'"
                  >
                    <!-- Bubble Content -->
                    <div
                      class="px-4 py-2.5 rounded-2xl text-xs leading-relaxed break-words shadow-xs relative"
                      :class="msg.from_me
                        ? 'bg-gradient-to-br from-emerald-600 to-teal-700 text-white rounded-tr-none'
                        : 'bg-white dark:bg-secondary-800 text-secondary-900 dark:text-white border border-secondary-200/80 dark:border-secondary-700 rounded-tl-none'"
                    >
                      <!-- Sender display name if present -->
                      <span
                        v-if="!msg.from_me && msg.sender_display_name"
                        class="block text-[10px] font-bold text-emerald-600 dark:text-emerald-400 mb-1"
                      >
                        {{ msg.sender_display_name }}
                      </span>

                      <!-- Media Image Attachment -->
                      <div v-if="msg.media_url && (msg.type === 'image' || isImageUrl(msg.media_url))" class="mb-2 rounded-xl overflow-hidden max-w-xs">
                        <img :src="msg.media_url" :alt="msg.filename || 'WhatsApp Image'" class="w-full h-auto object-cover rounded-lg" />
                      </div>

                      <!-- Document / File Attachment -->
                      <div v-else-if="msg.media_url" class="mb-2 flex items-center gap-2 p-2 rounded-xl bg-black/10 dark:bg-white/10">
                        <FileText class="w-4 h-4 shrink-0" />
                        <a
                          :href="msg.media_url"
                          target="_blank"
                          rel="noopener noreferrer"
                          class="truncate text-xs underline font-medium"
                        >
                          {{ msg.filename || 'Download Attachment' }}
                        </a>
                      </div>

                      <!-- Message Text -->
                      <p v-if="msg.message" class="whitespace-pre-wrap select-text">
                        {{ msg.message }}
                      </p>

                      <!-- Timestamp & Status -->
                      <div
                        class="flex items-center justify-end gap-1 mt-1 text-[10px]"
                        :class="msg.from_me ? 'text-emerald-100/80' : 'text-secondary-400'"
                      >
                        <span>{{ formatMessageTime(msg.timestamp) }}</span>
                        <CheckCheck v-if="msg.from_me" class="w-3.5 h-3.5 text-emerald-200 inline" />
                      </div>

                      <!-- Emoji Reactions -->
                      <div
                        v-if="msg.reactions && msg.reactions.length > 0"
                        class="absolute -bottom-2.5 right-2 flex items-center gap-0.5 px-1.5 py-0.5 rounded-full bg-white dark:bg-secondary-900 border border-secondary-200 dark:border-secondary-700 shadow-xs text-[11px]"
                      >
                        <span v-for="(rx, idx) in msg.reactions" :key="idx" :title="rx.sender_display_name || rx.sender_jid">
                          {{ rx.emoji }}
                        </span>
                      </div>
                    </div>
                  </div>
                </template>
              </div>

              <!-- Chat Error Alert -->
              <div v-if="sendError" class="mx-4 mb-2 p-2.5 rounded-xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 text-xs text-red-700 dark:text-red-300 flex items-center justify-between">
                <span>{{ sendError }}</span>
                <button type="button" class="text-red-500 font-bold ml-2" @click="sendError = ''">
                  ×
                </button>
              </div>

              <!-- Quick Template Snippets -->
              <div class="px-4 pt-2 flex items-center gap-1.5 overflow-x-auto scrollbar-none text-[11px] shrink-0 bg-white/70 dark:bg-secondary-900/70 border-t border-secondary-100 dark:border-secondary-800">
                <span class="text-secondary-400 text-[10px] font-semibold uppercase shrink-0 mr-1">Quick:</span>
                <button
                  type="button"
                  class="px-2.5 py-1 rounded-full bg-secondary-100 dark:bg-secondary-800 text-secondary-600 dark:text-secondary-300 hover:bg-secondary-200 dark:hover:bg-secondary-700 transition-colors shrink-0"
                  @click="insertSnippet(`Hello ${selectedMember.name}, `)"
                >
                  "Hello {{ selectedMember.name }}"
                </button>
                <button
                  type="button"
                  class="px-2.5 py-1 rounded-full bg-secondary-100 dark:bg-secondary-800 text-secondary-600 dark:text-secondary-300 hover:bg-secondary-200 dark:hover:bg-secondary-700 transition-colors shrink-0"
                  @click="insertSnippet('Your gym membership reminder: ')"
                >
                  Membership Reminder
                </button>
                <button
                  type="button"
                  class="px-2.5 py-1 rounded-full bg-secondary-100 dark:bg-secondary-800 text-secondary-600 dark:text-secondary-300 hover:bg-secondary-200 dark:hover:bg-secondary-700 transition-colors shrink-0"
                  @click="insertSnippet('Thank you for visiting us today!')"
                >
                  Thank You
                </button>
              </div>

              <!-- Chat Input Box -->
              <div class="p-3 md:p-4 bg-white dark:bg-secondary-900 border-t border-secondary-200 dark:border-secondary-700 shrink-0">
                <form class="flex items-end gap-2" @submit.prevent="sendMessage">
                  <div class="flex-1 min-w-0 relative">
                    <textarea
                      ref="messageInputRef"
                      v-model="messageDraft"
                      rows="2"
                      placeholder="Type a WhatsApp message... (Enter to send, Shift+Enter for new line)"
                      class="w-full px-3.5 py-2.5 rounded-xl text-xs bg-secondary-50 dark:bg-secondary-800 border border-secondary-200 dark:border-secondary-700 focus:border-primary-500 focus:bg-white dark:focus:bg-secondary-900 focus:outline-none resize-none transition-all leading-relaxed"
                      :disabled="sendingMessage"
                      @keydown.enter.exact.prevent="sendMessage"
                    />
                  </div>

                  <button
                    type="submit"
                    class="inline-flex items-center justify-center p-3 rounded-xl bg-primary-600 hover:bg-primary-700 text-white shadow-sm hover:shadow transition-all disabled:opacity-50 shrink-0"
                    :disabled="sendingMessage || !messageDraft.trim()"
                  >
                    <RefreshCw v-if="sendingMessage" class="w-4 h-4 animate-spin" />
                    <Send v-else class="w-4 h-4" />
                  </button>
                </form>
              </div>
            </template>

            <!-- No Member Selected Placeholder -->
            <div v-else class="flex-1 flex flex-col items-center justify-center p-8 text-center space-y-4">
              <div class="w-16 h-16 rounded-3xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shadow-md">
                <MessageSquareMore class="w-8 h-8" />
              </div>
              <div class="max-w-md space-y-1">
                <h3 class="text-base font-bold text-secondary-900 dark:text-white">
                  WhatsApp Member Chat
                </h3>
                <p class="text-xs text-secondary-500 dark:text-secondary-400 leading-relaxed">
                  Select a member from the left list to load their chat history, inspect WhatsApp status, and send instant messages.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { nextTick, onMounted, ref } from 'vue';
import AppPageHeader from '../components/AppPageHeader.vue';
import MemberAvatar from '../../components/ui/MemberAvatar.vue';
import AppFormField from '../components/forms/AppFormField.vue';
import AppFormInput from '../components/forms/AppFormInput.vue';
import AppFormSwitch from '../components/forms/AppFormSwitch.vue';
import { apiRequest } from '../composables/useApiClient';
import {
    AlertCircle,
    AlertTriangle,
    ArrowLeft,
    CheckCircle2,
    CheckCheck,
    ExternalLink,
    Eye,
    EyeOff,
    FileText,
    MessageSquare,
    MessageSquareMore,
    Plug,
    Power,
    PowerOff,
    QrCode,
    Radio,
    RefreshCw,
    Search,
    Send,
    Sliders,
    X,
} from 'lucide-vue-next';

// State
const activeTab = ref('config');
const pageError = ref('');
const pageSuccess = ref('');

// Config tab state
const config = ref({
    enabled: false,
    url: '',
    api_key: '',
    session_id: '',
    driver: 'gowa',
});
const showApiKey = ref(false);
const savingConfig = ref(false);
const testingConnection = ref(false);
const connectionStatus = ref(null);
const qrModalOpen = ref(false);
const loadingQr = ref(false);
const qrLink = ref('');
const qrError = ref('');

// Chat & Members tab state
const members = ref([]);
const membersTotal = ref(0);
const membersPage = ref(1);
const membersLastPage = ref(1);
const loadingMembers = ref(false);
const memberSearch = ref('');
const memberFilter = ref('all');
let searchDebounceTimeout = null;

const selectedMember = ref(null);
const mobileChatOpen = ref(false);
const chatMessages = ref([]);
const loadingMessages = ref(false);
const messageDraft = ref('');
const sendingMessage = ref(false);
const sendError = ref('');

const chatStreamRef = ref(null);
const messageInputRef = ref(null);

// Load configuration
async function loadConfig() {
    try {
        const res = await apiRequest('/api/settings/whatsapp/config');
        if (res.data) {
            config.value = {
                enabled: Boolean(res.data.enabled),
                url: res.data.url || '',
                api_key: res.data.api_key || '',
                session_id: res.data.session_id || '',
                driver: res.data.driver || 'gowa',
            };
        }
    } catch {
        pageError.value = 'Failed to load WhatsApp configuration.';
    }
}

// Save configuration
async function saveConfig() {
    savingConfig.value = true;
    pageError.value = '';
    pageSuccess.value = '';
    try {
        await apiRequest('/api/settings/whatsapp/config', {
            method: 'put',
            data: {
                enabled: config.value.enabled,
                url: config.value.url || '',
                api_key: config.value.api_key || '',
                session_id: config.value.session_id || '',
            },
        });
        pageSuccess.value = 'WhatsApp configuration saved successfully.';
        setTimeout(() => { pageSuccess.value = ''; }, 3000);
    } catch (err) {
        pageError.value = err?.response?.data?.message || err?.message || 'Failed to save configuration.';
    } finally {
        savingConfig.value = false;
    }
}

// Test connection
async function testConnection() {
    testingConnection.value = true;
    connectionStatus.value = null;
    try {
        const res = await apiRequest('/api/settings/whatsapp/test-connection', {
            method: 'post',
            data: {
                url: config.value.url,
                api_key: config.value.api_key,
                session_id: config.value.session_id,
            },
        });
        connectionStatus.value = {
            success: true,
            message: res.message || 'Connected to GoWA server successfully.',
            device_state: res.device_state || 'connected',
        };
    } catch (err) {
        connectionStatus.value = {
            success: false,
            message: err?.response?.data?.message || err?.message || 'Failed to connect to GoWA server.',
            device_state: err?.response?.data?.device_state || 'error',
        };
    } finally {
        testingConnection.value = false;
    }
}

// Fetch QR Code for pairing
async function fetchQrCode() {
    qrModalOpen.value = true;
    loadingQr.value = true;
    qrError.value = '';
    qrLink.value = '';
    try {
        const res = await apiRequest('/api/settings/whatsapp/qr-code', {
            params: {
                url: config.value.url,
                api_key: config.value.api_key,
                session_id: config.value.session_id,
            },
        });
        if (res.qr_link) {
            qrLink.value = res.qr_link;
        } else {
            qrError.value = res.message || 'QR code not returned by GoWA server.';
        }
    } catch (err) {
        qrError.value = err?.response?.data?.message || err?.message || 'Failed to fetch pairing QR code.';
    } finally {
        loadingQr.value = false;
    }
}

function isImageUrl(url) {
    if (!url || typeof url !== 'string') return false;
    return /\.(jpeg|jpg|gif|png|webp|svg)(\?.*)?$/i.test(url);
}

// Load members list
async function loadMembers(page = 1) {
    loadingMembers.value = true;
    membersPage.value = page;
    try {
        const params = {
            page,
            per_page: 25,
            search: memberSearch.value || undefined,
        };

        if (memberFilter.value === 'active') {
            params.active = '1';
        }

        const res = await apiRequest('/api/members', { params });
        let rawList = res.data || [];

        if (memberFilter.value === 'whatsapp') {
            rawList = rawList.filter(m => Boolean(m.allow_whatsapp) || Boolean(m.whatsapp_number));
        }

        members.value = rawList;
        membersTotal.value = res.meta?.total ?? rawList.length;
        membersLastPage.value = res.meta?.last_page ?? 1;

        // Automatically select first member if none selected on desktop
        if (!selectedMember.value && members.value.length > 0 && window.innerWidth >= 768) {
            selectMember(members.value[0]);
        }
    } catch {
        pageError.value = 'Failed to load members.';
    } finally {
        loadingMembers.value = false;
    }
}

function debouncedSearch() {
    clearTimeout(searchDebounceTimeout);
    searchDebounceTimeout = setTimeout(() => {
        loadMembers(1);
    }, 300);
}

function setMemberFilter(filter) {
    memberFilter.value = filter;
    loadMembers(1);
}

// Select a member & load their chat
function selectMember(member) {
    selectedMember.value = member;
    mobileChatOpen.value = true;
    loadChatMessages(member);
}

// Load chat messages for selected member
async function loadChatMessages(member) {
    if (!member) return;
    loadingMessages.value = true;
    sendError.value = '';
    const phone = getMemberPhone(member);

    try {
        const res = await apiRequest('/api/settings/whatsapp/messages', {
            params: {
                phone,
                member_id: member.id,
            },
        });
        chatMessages.value = Array.isArray(res.messages) ? res.messages : [];
        scrollToBottom();
    } catch (err) {
        sendError.value = err?.response?.data?.message || 'Could not fetch message history from GoWA server.';
        chatMessages.value = [];
    } finally {
        loadingMessages.value = false;
    }
}

// Send a chat message
async function sendMessage() {
    const text = messageDraft.value.trim();
    if (!text || !selectedMember.value || sendingMessage.value) return;

    sendingMessage.value = true;
    sendError.value = '';
    const phone = getMemberPhone(selectedMember.value);

    // Optimistic message append
    const tempId = 'temp_' + Date.now();
    const optimisticMsg = {
        id: tempId,
        from_me: true,
        message: text,
        timestamp: Math.floor(Date.now() / 1000),
        status: 'sending',
    };
    chatMessages.value.push(optimisticMsg);
    messageDraft.value = '';
    scrollToBottom();

    try {
        const res = await apiRequest('/api/settings/whatsapp/send', {
            method: 'post',
            data: {
                phone,
                member_id: selectedMember.value.id,
                message: text,
            },
        });

        // Update optimistic message status
        const idx = chatMessages.value.findIndex(m => m.id === tempId);
        if (idx !== -1) {
            chatMessages.value[idx].id = res.data?.id || tempId;
            chatMessages.value[idx].status = 'sent';
        }
    } catch (err) {
        sendError.value = err?.response?.data?.message || err?.message || 'Failed to send WhatsApp message.';
        // Remove optimistic message on failure
        chatMessages.value = chatMessages.value.filter(m => m.id !== tempId);
        messageDraft.value = text;
    } finally {
        sendingMessage.value = false;
        nextTick(() => {
            messageInputRef.value?.focus();
        });
    }
}

function insertSnippet(text) {
    messageDraft.value = (messageDraft.value ? messageDraft.value + ' ' : '') + text;
    nextTick(() => {
        messageInputRef.value?.focus();
    });
}

function scrollToBottom() {
    nextTick(() => {
        if (chatStreamRef.value) {
            chatStreamRef.value.scrollTop = chatStreamRef.value.scrollHeight;
        }
    });
}

// Helpers
function getMemberPhone(member) {
    if (!member) return '';
    return member.whatsapp_number || member.phone_number || '';
}

function cleanPhoneForWa(number) {
    if (!number) return '';
    let digits = String(number).replace(/\D/g, '');
    if (digits.startsWith('0')) {
        digits = '94' + digits.slice(1);
    }
    return digits;
}

function getInitials(name) {
    if (!name) return '?';
    const parts = name.trim().split(/\s+/);
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

function formatMessageTime(timestamp) {
    if (!timestamp) return '';
    const date = new Date(Number(timestamp) * 1000);
    if (isNaN(date.getTime())) return '';
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

onMounted(() => {
    loadConfig();
    loadMembers(1);
});
</script>
