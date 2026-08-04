<template>
  <section class="app-page-frame">
    <AppPageHeader title="Configuration" />

    <div class="app-page-scroll">
      <div v-if="loadError" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
        {{ loadError }}
      </div>

      <div v-if="loading" class="py-12 text-center text-sm text-secondary-500 dark:text-secondary-400">
        Loading configuration…
      </div>

      <template v-else>
        <!-- Notifications card -->
        <div class="app-surface rounded-2xl overflow-hidden">
          <div class="px-4 md:px-6 py-4 border-b border-secondary-200/70 dark:border-secondary-700/70 flex items-center gap-3">
            <Bell class="w-5 h-5 text-primary-500 flex-shrink-0" :stroke-width="2" />
            <div>
              <h2 class="text-base font-semibold" style="color: var(--text-strong)">
                Notifications
              </h2>
              <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">
                Configure in-app, email and SMS notification channels
              </p>
            </div>
          </div>

          <div class="divide-y divide-secondary-100 dark:divide-secondary-800">
            <!-- In-App Notifications -->
            <div class="px-4 md:px-6 py-4">
              <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-xl bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center flex-shrink-0">
                    <BellRing class="w-4 h-4 text-indigo-600 dark:text-indigo-400" :stroke-width="2" />
                  </div>
                  <div>
                    <p class="text-sm font-medium" style="color: var(--text-strong)">
                      In-App Notifications
                    </p>
                    <p class="text-xs text-secondary-500 dark:text-secondary-400">
                      Show notifications inside the member portal
                    </p>
                  </div>
                </div>
                <button
                  type="button"
                  role="switch"
                  :aria-checked="form['notifications.inapp.enabled'] === '1'"
                  class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-400 flex-shrink-0"
                  :class="form['notifications.inapp.enabled'] === '1' ? 'bg-primary-600' : 'bg-secondary-300 dark:bg-secondary-600'"
                  @click="toggle('notifications.inapp.enabled')"
                >
                  <span
                    class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform"
                    :class="form['notifications.inapp.enabled'] === '1' ? 'translate-x-6' : 'translate-x-1'"
                  />
                </button>
              </div>
            </div>

            <!-- Email Notifications -->
            <div class="px-4 md:px-6 py-4 space-y-4">
              <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                    <Mail class="w-4 h-4 text-blue-600 dark:text-blue-400" :stroke-width="2" />
                  </div>
                  <div>
                    <p class="text-sm font-medium" style="color: var(--text-strong)">
                      Email Notifications
                    </p>
                    <p class="text-xs text-secondary-500 dark:text-secondary-400">
                      Send notifications via SMTP email &mdash; configure your SMTP server below
                    </p>
                  </div>
                </div>
                <button
                  type="button"
                  role="switch"
                  :aria-checked="form['notifications.email.enabled'] === '1'"
                  class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-400 flex-shrink-0"
                  :class="form['notifications.email.enabled'] === '1' ? 'bg-primary-600' : 'bg-secondary-300 dark:bg-secondary-600'"
                  @click="toggle('notifications.email.enabled')"
                >
                  <span
                    class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform"
                    :class="form['notifications.email.enabled'] === '1' ? 'translate-x-6' : 'translate-x-1'"
                  />
                </button>
              </div>

              <!-- SMTP fields (shown when email enabled) -->
              <Transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-1"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-1"
              >
                <div v-if="form['notifications.email.enabled'] === '1'" class="ml-0 md:ml-12 rounded-xl border border-secondary-200 dark:border-secondary-700 p-4 bg-secondary-50/50 dark:bg-secondary-800/30">
                  <p class="text-xs font-semibold text-secondary-500 dark:text-secondary-400 uppercase tracking-wide mb-3">
                    SMTP Configuration
                  </p>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <AppFormField label="SMTP Host">
                      <AppFormInput
                        v-model="form['notifications.email.smtp_host']"
                        type="text"
                        placeholder="smtp.example.com"
                        maxlength="255"
                      />
                    </AppFormField>

                    <AppFormField label="SMTP Port">
                      <AppFormInput
                        v-model="form['notifications.email.smtp_port']"
                        type="number"
                        placeholder="587"
                        min="1"
                        max="65535"
                      />
                    </AppFormField>

                    <AppFormField label="Username">
                      <AppFormInput
                        v-model="form['notifications.email.smtp_username']"
                        type="text"
                        placeholder="user@example.com"
                        maxlength="255"
                        autocomplete="off"
                      />
                    </AppFormField>

                    <AppFormField label="Password">
                      <AppFormInput
                        v-model="form['notifications.email.smtp_password']"
                        type="password"
                        placeholder="••••••••"
                        maxlength="255"
                        autocomplete="new-password"
                      />
                    </AppFormField>

                    <AppFormField label="Encryption">
                      <select
                        v-model="form['notifications.email.smtp_encryption']"
                        class="app-form-input w-full"
                      >
                        <option value="tls">
                          TLS
                        </option>
                        <option value="ssl">
                          SSL
                        </option>
                        <option value="none">
                          None
                        </option>
                      </select>
                    </AppFormField>

                    <AppFormField label="From Address">
                      <AppFormInput
                        v-model="form['notifications.email.from_address']"
                        type="email"
                        placeholder="no-reply@example.com"
                        maxlength="255"
                      />
                    </AppFormField>

                    <AppFormField label="From Name" class="sm:col-span-2">
                      <AppFormInput
                        v-model="form['notifications.email.from_name']"
                        type="text"
                        placeholder="My Fitness Center"
                        maxlength="255"
                      />
                    </AppFormField>
                  </div>
                </div>
              </Transition>
            </div>

            <!-- SMS Notifications -->
            <div class="px-4 md:px-6 py-4 space-y-4">
              <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center flex-shrink-0">
                    <MessageSquare class="w-4 h-4 text-green-600 dark:text-green-400" :stroke-width="2" />
                  </div>
                  <div>
                    <p class="text-sm font-medium" style="color: var(--text-strong)">
                      SMS Notifications
                    </p>
                    <p class="text-xs text-secondary-500 dark:text-secondary-400">
                      Send notifications via SMS &mdash; powered by <a
                        href="https://smslenz.lk"
                        target="_blank"
                        rel="noopener"
                        class="underline hover:text-secondary-700 dark:hover:text-secondary-300"
                      >SMSlenz</a>
                    </p>
                  </div>
                </div>
                <button
                  type="button"
                  role="switch"
                  :aria-checked="form['notifications.sms.enabled'] === '1'"
                  class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-400 flex-shrink-0"
                  :class="form['notifications.sms.enabled'] === '1' ? 'bg-primary-600' : 'bg-secondary-300 dark:bg-secondary-600'"
                  @click="toggle('notifications.sms.enabled')"
                >
                  <span
                    class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform"
                    :class="form['notifications.sms.enabled'] === '1' ? 'translate-x-6' : 'translate-x-1'"
                  />
                </button>
              </div>

              <!-- SMS fields -->
              <Transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-1"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-1"
              >
                <div v-if="form['notifications.sms.enabled'] === '1'" class="ml-0 md:ml-12 rounded-xl border border-secondary-200 dark:border-secondary-700 p-4 bg-secondary-50/50 dark:bg-secondary-800/30">
                  <p class="text-xs font-semibold text-secondary-500 dark:text-secondary-400 uppercase tracking-wide mb-3">
                    SMSlenz Configuration
                  </p>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <AppFormField label="User ID">
                      <AppFormInput
                        v-model="form['notifications.sms.user_id']"
                        type="text"
                        placeholder="Your SMSlenz User ID"
                        maxlength="255"
                        autocomplete="off"
                      />
                    </AppFormField>

                    <AppFormField label="API Key">
                      <AppFormInput
                        v-model="form['notifications.sms.api_key']"
                        type="password"
                        placeholder="••••••••"
                        maxlength="255"
                        autocomplete="new-password"
                      />
                    </AppFormField>

                    <AppFormField label="Sender ID" class="sm:col-span-2" help="The name or number shown as the SMS sender (max 11 chars for alphanumeric IDs)">
                      <AppFormInput
                        v-model="form['notifications.sms.sender_id']"
                        type="text"
                        placeholder="e.g. MyGym"
                        maxlength="50"
                      />
                    </AppFormField>
                  </div>
                </div>
              </Transition>
            </div>
          </div>

          <!-- Save button -->
          <div class="px-4 md:px-6 py-4 border-t border-secondary-200/70 dark:border-secondary-700/70 flex flex-col sm:flex-row items-start sm:items-center gap-3">
            <div v-if="saveError" class="flex-1 text-sm text-red-600 dark:text-red-400">
              {{ saveError }}
            </div>
            <div v-else-if="successMessage" class="flex-1 text-sm text-green-600 dark:text-green-400">
              {{ successMessage }}
            </div>
            <div v-else class="flex-1" />
            <button
              type="button"
              class="w-full sm:w-auto px-5 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium disabled:opacity-50 transition-colors"
              :disabled="submitting"
              @click="save"
            >
              {{ submitting ? 'Saving…' : 'Save Configuration' }}
            </button>
          </div>
        </div>

        <!-- Member & Biometric ID Format Card -->
        <div class="app-surface rounded-2xl overflow-hidden mt-4">
          <div class="px-4 md:px-6 py-4 border-b border-secondary-200/70 dark:border-secondary-700/70 flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
              <IdCard class="w-5 h-5 text-primary-500 flex-shrink-0" :stroke-width="2" />
              <div>
                <h2 class="text-base font-semibold" style="color: var(--text-strong)">
                  Member & Biometric ID Formatting
                </h2>
                <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">
                  Configure automatic member ID generation rules and device biometric IDs
                </p>
              </div>
            </div>

            <!-- Live Preview Badges -->
            <div class="flex flex-wrap items-center gap-2">
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-medium bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 border border-primary-200 dark:border-primary-800">
                <span class="text-secondary-500 dark:text-secondary-400">Next Member ID:</span>
                <span class="font-mono font-bold">{{ previewNextMemberId }}</span>
              </span>
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-medium bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                <span class="text-secondary-500 dark:text-secondary-400">Next Biometric ID:</span>
                <span class="font-mono font-bold">{{ previewNextBiometricId }}</span>
              </span>
            </div>
          </div>

          <div class="p-4 md:p-6 space-y-6">
            <!-- Member ID Format Section -->
            <div>
              <h3 class="text-xs font-semibold text-secondary-500 dark:text-secondary-400 uppercase tracking-wide mb-3">
                Member ID Configuration
              </h3>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <AppFormField label="ID Prefix" help="e.g. MEM-, GYM- (leave empty for none)">
                  <AppFormInput
                    v-model="form['member.id_prefix']"
                    type="text"
                    placeholder="MEM-"
                    maxlength="20"
                  />
                </AppFormField>

                <AppFormField label="Starting Sequence Number" help="Starting sequence number">
                  <AppFormInput
                    v-model="form['member.id_next_number']"
                    type="number"
                    min="1"
                    placeholder="1"
                  />
                </AppFormField>

                <AppFormField label="Zero Padding Digits" help="e.g. 4 digits → 0001">
                  <AppFormInput
                    v-model="form['member.id_padding']"
                    type="number"
                    min="0"
                    max="10"
                    placeholder="4"
                  />
                </AppFormField>
              </div>
            </div>

            <!-- Biometric ID Format Section -->
            <div class="border-t border-secondary-100 dark:border-secondary-800 pt-5">
              <div class="flex items-center justify-between gap-4 mb-4">
                <div>
                  <h3 class="text-xs font-semibold text-secondary-500 dark:text-secondary-400 uppercase tracking-wide">
                    Biometric Hardware ID Configuration
                  </h3>
                  <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">
                    How member IDs are mapped to physical attendance terminals
                  </p>
                </div>
                <div class="flex items-center gap-2">
                  <label class="text-xs font-medium text-secondary-700 dark:text-secondary-300">
                    Same as Member ID
                  </label>
                  <button
                    type="button"
                    role="switch"
                    :aria-checked="form['biometric.id_same_as_member_id'] === '1'"
                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-400 flex-shrink-0"
                    :class="form['biometric.id_same_as_member_id'] === '1' ? 'bg-primary-600' : 'bg-secondary-300 dark:bg-secondary-600'"
                    @click="toggle('biometric.id_same_as_member_id')"
                  >
                    <span
                      class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform"
                      :class="form['biometric.id_same_as_member_id'] === '1' ? 'translate-x-6' : 'translate-x-1'"
                    />
                  </button>
                </div>
              </div>

              <!-- Custom Biometric ID settings when same_as_member_id is '0' -->
              <Transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-1"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-1"
              >
                <div v-if="form['biometric.id_same_as_member_id'] !== '1'" class="grid grid-cols-1 sm:grid-cols-3 gap-4 rounded-xl border border-secondary-200 dark:border-secondary-700 p-4 bg-secondary-50/50 dark:bg-secondary-800/30">
                  <AppFormField label="Biometric ID Prefix">
                    <AppFormInput
                      v-model="form['biometric.id_prefix']"
                      type="text"
                      placeholder="BIO-"
                      maxlength="20"
                    />
                  </AppFormField>

                  <AppFormField label="Biometric Next Number">
                    <AppFormInput
                      v-model="form['biometric.id_next_number']"
                      type="number"
                      min="1"
                      placeholder="1"
                    />
                  </AppFormField>

                  <AppFormField label="Biometric Zero Padding">
                    <AppFormInput
                      v-model="form['biometric.id_padding']"
                      type="number"
                      min="0"
                      max="10"
                      placeholder="4"
                    />
                  </AppFormField>
                </div>
              </Transition>
            </div>
          </div>

          <!-- Save Button Footer -->
          <div class="px-4 md:px-6 py-4 border-t border-secondary-200/70 dark:border-secondary-700/70 flex flex-col sm:flex-row items-start sm:items-center gap-3">
            <div v-if="saveError" class="flex-1 text-sm text-red-600 dark:text-red-400">
              {{ saveError }}
            </div>
            <div v-else-if="successMessage" class="flex-1 text-sm text-green-600 dark:text-green-400">
              {{ successMessage }}
            </div>
            <div v-else class="flex-1" />
            <button
              type="button"
              class="w-full sm:w-auto px-5 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium disabled:opacity-50 transition-colors"
              :disabled="submitting"
              @click="save"
            >
              {{ submitting ? 'Saving…' : 'Save Configuration' }}
            </button>
          </div>
        </div>

        <!-- Body measurements card -->
        <div class="app-surface rounded-2xl overflow-hidden mt-4">
          <div class="px-4 md:px-6 py-4 border-b border-secondary-200/70 dark:border-secondary-700/70 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
              <Ruler class="w-5 h-5 text-primary-500 flex-shrink-0" :stroke-width="2" />
              <div>
                <h2 class="text-base font-semibold" style="color: var(--text-strong)">
                  Body Measurements
                </h2>
                <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">
                  Configure optional transformation tracker fields
                </p>
              </div>
            </div>
            <button
              type="button"
              class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-2 text-xs font-semibold text-white hover:bg-primary-700 disabled:opacity-50"
              :disabled="submitting"
              @click="addMeasurementField"
            >
              <Plus class="h-3.5 w-3.5" />
              <span>Add Field</span>
            </button>
          </div>

          <div class="divide-y divide-secondary-100 dark:divide-secondary-800">
            <div
              v-for="(field, index) in bodyMeasurementFields"
              :key="field.key"
              class="px-4 md:px-6 py-4 grid grid-cols-1 md:grid-cols-[auto_minmax(0,1fr)_7rem_auto] gap-3 md:items-end"
            >
              <div class="flex items-center justify-between md:block">
                <span class="md:hidden text-xs font-medium text-secondary-700 dark:text-secondary-300">Enabled</span>
                <button
                  type="button"
                  role="switch"
                  :aria-checked="field.enabled"
                  class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-400 flex-shrink-0"
                  :class="field.enabled ? 'bg-primary-600' : 'bg-secondary-300 dark:bg-secondary-600'"
                  @click="field.enabled = !field.enabled"
                >
                  <span
                    class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform"
                    :class="field.enabled ? 'translate-x-6' : 'translate-x-1'"
                  />
                </button>
              </div>

              <AppFormField label="Label">
                <AppFormInput
                  v-model.trim="field.label"
                  type="text"
                  maxlength="100"
                  placeholder="Measurement label"
                />
              </AppFormField>

              <AppFormField label="Order">
                <AppFormInput
                  v-model.number="field.sort_order"
                  type="number"
                  min="0"
                  max="999"
                  step="1"
                />
              </AppFormField>

              <div class="flex items-center justify-end gap-1">
                <button
                  type="button"
                  class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-secondary-300 dark:border-secondary-600 text-secondary-700 dark:text-secondary-200 hover:bg-secondary-100 dark:hover:bg-secondary-800 disabled:opacity-40"
                  :disabled="index === 0"
                  title="Move up"
                  @click="moveMeasurementField(index, -1)"
                >
                  <ChevronUp class="h-4 w-4" />
                </button>
                <button
                  type="button"
                  class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-secondary-300 dark:border-secondary-600 text-secondary-700 dark:text-secondary-200 hover:bg-secondary-100 dark:hover:bg-secondary-800 disabled:opacity-40"
                  :disabled="index === bodyMeasurementFields.length - 1"
                  title="Move down"
                  @click="moveMeasurementField(index, 1)"
                >
                  <ChevronDown class="h-4 w-4" />
                </button>
                <button
                  type="button"
                  class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-red-200 dark:border-red-800 text-red-600 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 disabled:opacity-40"
                  :disabled="field.built_in"
                  title="Remove custom field"
                  @click="removeMeasurementField(index)"
                >
                  <Trash2 class="h-4 w-4" />
                </button>
              </div>
            </div>
          </div>

          <div class="px-4 md:px-6 py-4 border-t border-secondary-200/70 dark:border-secondary-700/70 flex flex-col sm:flex-row items-start sm:items-center gap-3">
            <div v-if="saveError" class="flex-1 text-sm text-red-600 dark:text-red-400">
              {{ saveError }}
            </div>
            <div v-else-if="successMessage" class="flex-1 text-sm text-green-600 dark:text-green-400">
              {{ successMessage }}
            </div>
            <div v-else class="flex-1" />
            <button
              type="button"
              class="w-full sm:w-auto px-5 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium disabled:opacity-50 transition-colors"
              :disabled="submitting"
              @click="save"
            >
              {{ submitting ? 'Saving…' : 'Save Configuration' }}
            </button>
          </div>
        </div>
      </template>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { Bell, BellRing, ChevronDown, ChevronUp, IdCard, Mail, MessageSquare, Plus, Ruler, Trash2 } from 'lucide-vue-next';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppFormField from '../components/forms/AppFormField.vue';
import AppFormInput from '../components/forms/AppFormInput.vue';
import { apiRequest } from '../composables/useApiClient';

const loading = ref(true);
const loadError = ref('');
const submitting = ref(false);
const saveError = ref('');
const successMessage = ref('');

const form = ref({
    'notifications.inapp.enabled': '0',
    'notifications.email.enabled': '0',
    'notifications.email.smtp_host': '',
    'notifications.email.smtp_port': '587',
    'notifications.email.smtp_username': '',
    'notifications.email.smtp_password': '',
    'notifications.email.smtp_encryption': 'tls',
    'notifications.email.from_address': '',
    'notifications.email.from_name': '',
    'notifications.sms.enabled': '0',
    'notifications.sms.user_id': '',
    'notifications.sms.api_key': '',
    'notifications.sms.sender_id': '',
    'general.member_notifications': '{}',
    'body_measurements.fields': '[]',
    'member.id_prefix': '',
    'member.id_next_number': '1',
    'member.id_padding': '4',
    'member.id_auto_generate': '1',
    'biometric.id_prefix': '',
    'biometric.id_next_number': '1',
    'biometric.id_padding': '4',
    'biometric.id_same_as_member_id': '1',
});

const previewNextMemberId = computed(() => {
    const prefix = form.value['member.id_prefix'] || '';
    const num = parseInt(form.value['member.id_next_number'] || '1', 10) || 1;
    const padding = Math.max(0, Math.min(10, parseInt(form.value['member.id_padding'] || '4', 10) || 0));
    const padded = padding > 0 ? String(num).padStart(padding, '0') : String(num);
    return `${prefix}${padded}`;
});

const previewNextBiometricId = computed(() => {
    if (form.value['biometric.id_same_as_member_id'] === '1') {
        return previewNextMemberId.value;
    }
    const prefix = form.value['biometric.id_prefix'] || '';
    const num = parseInt(form.value['biometric.id_next_number'] || '1', 10) || 1;
    const padding = Math.max(0, Math.min(10, parseInt(form.value['biometric.id_padding'] || '4', 10) || 0));
    const padded = padding > 0 ? String(num).padStart(padding, '0') : String(num);
    return `${prefix}${padded}`;
});

const bodyMeasurementFields = ref([]);

const memberReachableConfig = ref({
    member_login_url: '',
    whatsapp_group_url: '',
    whatsapp_groups: [],
});

function toggle(key) {
    form.value[key] = form.value[key] === '1' ? '0' : '1';
}

async function load() {
    loading.value = true;
    loadError.value = '';
    try {
        const response = await apiRequest('/api/settings/configuration');
        const data = response.data || {};
        Object.keys(form.value).forEach((key) => {
            if (data[key] !== undefined) {
                form.value[key] = data[key];
            }
        });
        bodyMeasurementFields.value = parseBodyMeasurementFields(form.value['body_measurements.fields']);
        memberReachableConfig.value = parseMemberReachableConfig(form.value['general.member_notifications']);
    } catch {
        loadError.value = 'Failed to load configuration.';
    } finally {
        loading.value = false;
    }
}

async function save() {
    submitting.value = true;
    saveError.value = '';
    successMessage.value = '';
    try {
        form.value['general.member_notifications'] = serializeMemberNotificationConfig();
        form.value['body_measurements.fields'] = serializeBodyMeasurementFields();
        await apiRequest('/api/settings/configuration', { method: 'PUT', data: form.value });
        successMessage.value = 'Configuration saved successfully.';
        setTimeout(() => { successMessage.value = ''; }, 3000);
    } catch (err) {
        saveError.value = err?.response?.data?.message || 'Failed to save configuration.';
    } finally {
        submitting.value = false;
    }
}

function parseBodyMeasurementFields(raw) {
    try {
        const fields = JSON.parse(raw || '[]');

        if (!Array.isArray(fields)) return [];

        return fields.map((field, index) => ({
            key: field.key || `custom_field_${index + 1}`,
            label: field.label || 'Measurement',
            enabled: field.enabled !== false,
            sort_order: Number(field.sort_order ?? ((index + 1) * 10)),
            built_in: Boolean(field.built_in),
        }));
    } catch {
        return [];
    }
}

function serializeBodyMeasurementFields() {
    return JSON.stringify(bodyMeasurementFields.value.map((field, index) => ({
        key: field.key || `custom_field_${index + 1}`,
        label: field.label || 'Measurement',
        enabled: Boolean(field.enabled),
        sort_order: Number(field.sort_order ?? ((index + 1) * 10)),
        built_in: Boolean(field.built_in),
    })));
}

function addMeasurementField() {
    const next = bodyMeasurementFields.value.length + 1;
    bodyMeasurementFields.value.push({
        key: `custom_field_${Date.now()}`,
        label: 'Custom Field',
        enabled: true,
        sort_order: next * 10,
        built_in: false,
    });
}

function moveMeasurementField(index, direction) {
    const target = index + direction;

    if (target < 0 || target >= bodyMeasurementFields.value.length) return;

    const fields = [...bodyMeasurementFields.value];
    [fields[index], fields[target]] = [fields[target], fields[index]];
    bodyMeasurementFields.value = fields.map((field, fieldIndex) => ({
        ...field,
        sort_order: (fieldIndex + 1) * 10,
    }));
}

function removeMeasurementField(index) {
    const field = bodyMeasurementFields.value[index];

    if (!field || field.built_in) return;

    bodyMeasurementFields.value.splice(index, 1);
}

function parseMemberReachableConfig(raw) {
    try {
        const config = JSON.parse(raw || '{}') || {};

        return {
            member_login_url: config.whatsapp_group_url ? (config.member_login_url || '') : (config.member_login_url || ''),
            whatsapp_group_url: config.whatsapp_group_url || '',
            whatsapp_groups: Array.isArray(config.whatsapp_groups) ? config.whatsapp_groups : [],
        };
    } catch {
        return {
            member_login_url: '',
            whatsapp_group_url: '',
            whatsapp_groups: [],
        };
    }
}

function serializeMemberNotificationConfig() {
    return JSON.stringify({
        member_login_url: memberReachableConfig.value.member_login_url || '',
        whatsapp_group_url: memberReachableConfig.value.whatsapp_group_url || '',
        whatsapp_groups: Array.isArray(memberReachableConfig.value.whatsapp_groups)
            ? memberReachableConfig.value.whatsapp_groups
            : [],
    });
}

onMounted(load);
</script>
