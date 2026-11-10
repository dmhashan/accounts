<template>
  <section class="app-page-frame">
    <AppPageHeader title="Manager Notifications" />

    <div class="app-page-scroll space-y-5">
      <div v-if="loadError" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-200" role="alert">
        {{ loadError }}
      </div>
      <div v-if="successMessage" class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-200" role="status">
        {{ successMessage }}
      </div>

      <div v-if="loading" class="app-surface rounded-2xl py-12 text-center text-sm text-secondary-500 dark:text-secondary-400">
        Loading manager notification settings…
      </div>

      <template v-else>
        <p class="max-w-3xl text-sm text-secondary-600 dark:text-secondary-300">
          Choose which business changes managers should hear about and where alerts should be sent. These settings are separate from member notifications.
        </p>

        <section
          v-for="channel in channels"
          :key="channel.key"
          class="app-surface overflow-hidden rounded-2xl"
          :aria-labelledby="`${channel.key}-heading`"
        >
          <div class="flex flex-col gap-4 border-b border-secondary-200/70 px-4 py-4 dark:border-secondary-700/70 sm:flex-row sm:items-center sm:justify-between md:px-6">
            <div class="flex items-start gap-3">
              <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl" :class="channel.iconBg">
                <component
                  :is="channel.icon"
                  class="h-5 w-5"
                  :class="channel.iconColor"
                  :stroke-width="2"
                />
              </div>
              <div>
                <h2 :id="`${channel.key}-heading`" class="text-base font-semibold" style="color: var(--text-strong)">
                  {{ channel.title }} alerts
                </h2>
                <p class="mt-1 text-xs text-secondary-500 dark:text-secondary-400">
                  {{ channel.description }}
                </p>
              </div>
            </div>
            <button
              type="button"
              role="switch"
              :aria-label="`${channel.title} alerts`"
              :aria-checked="settings[`${channel.key}_enabled`]"
              class="relative inline-flex h-6 w-11 flex-shrink-0 items-center rounded-full transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-400"
              :class="settings[`${channel.key}_enabled`] ? 'bg-primary-600' : 'bg-secondary-300 dark:bg-secondary-600'"
              @click="settings[`${channel.key}_enabled`] = !settings[`${channel.key}_enabled`]"
            >
              <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform" :class="settings[`${channel.key}_enabled`] ? 'translate-x-6' : 'translate-x-1'" />
            </button>
          </div>

          <div class="space-y-6 p-4 md:p-6">
            <div v-if="settings[`${channel.key}_enabled`] && recipientsFor(channel.key).length === 0" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
              Add at least one recipient to receive {{ channel.title.toLowerCase() }} alerts.
            </div>

            <section aria-label="Recipients">
              <div class="mb-3 flex flex-wrap items-end justify-between gap-2">
                <div>
                  <h3 class="text-sm font-semibold" style="color: var(--text-strong)">
                    Recipient list
                  </h3>
                  <p class="mt-1 text-xs text-secondary-500 dark:text-secondary-400">
                    Only these addresses or numbers receive this channel's alerts.
                  </p>
                </div>
              </div>

              <form class="grid grid-cols-1 gap-3 rounded-xl border border-secondary-200 p-3 dark:border-secondary-700 sm:grid-cols-[minmax(0,1fr)_minmax(0,2fr)_auto] sm:items-end" @submit.prevent="addRecipient(channel.key)">
                <label class="block text-xs font-medium text-secondary-600 dark:text-secondary-300">
                  Name (optional)
                  <input
                    v-model="drafts[channel.key].label"
                    class="mt-1 w-full rounded-lg border border-secondary-300 bg-white px-3 py-2 text-sm text-secondary-900 outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-secondary-600 dark:bg-secondary-900 dark:text-white"
                    maxlength="100"
                    placeholder="Finance manager"
                  />
                </label>
                <label class="block text-xs font-medium text-secondary-600 dark:text-secondary-300">
                  {{ channel.destinationLabel }}
                  <input
                    v-model="drafts[channel.key].destination"
                    :type="channel.inputType"
                    required
                    maxlength="255"
                    :autocomplete="channel.autocomplete"
                    class="mt-1 w-full rounded-lg border border-secondary-300 bg-white px-3 py-2 text-sm text-secondary-900 outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-secondary-600 dark:bg-secondary-900 dark:text-white"
                    :placeholder="channel.placeholder"
                  />
                  <span v-if="channel.key === 'sms'" class="mt-1 block font-normal text-secondary-500">Use international format, e.g. +94771234567.</span>
                </label>
                <button type="submit" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-700 disabled:opacity-50" :disabled="recipientBusy === channel.key">
                  <Plus class="h-4 w-4" />
                  {{ recipientBusy === channel.key ? 'Adding…' : 'Add recipient' }}
                </button>
              </form>

              <p v-if="recipientErrors[channel.key]" class="mt-2 text-sm text-red-600 dark:text-red-300" role="alert">
                {{ recipientErrors[channel.key] }}
              </p>

              <div v-if="recipientsFor(channel.key).length" class="mt-3 divide-y divide-secondary-100 rounded-xl border border-secondary-200 dark:divide-secondary-800 dark:border-secondary-700">
                <div v-for="recipient in recipientsFor(channel.key)" :key="recipient.id" class="flex flex-col gap-3 p-3 sm:flex-row sm:items-center sm:justify-between">
                  <template v-if="editing?.id === recipient.id">
                    <div class="grid flex-1 grid-cols-1 gap-2 sm:grid-cols-2">
                      <input
                        v-model="editing.label"
                        aria-label="Recipient name"
                        maxlength="100"
                        class="rounded-lg border border-secondary-300 bg-white px-3 py-2 text-sm dark:border-secondary-600 dark:bg-secondary-900"
                        placeholder="Name (optional)"
                      />
                      <input
                        v-model="editing.destination"
                        :type="channel.inputType"
                        aria-label="Recipient destination"
                        maxlength="255"
                        class="rounded-lg border border-secondary-300 bg-white px-3 py-2 text-sm dark:border-secondary-600 dark:bg-secondary-900"
                        :placeholder="channel.destinationLabel"
                      />
                    </div>
                    <div class="flex gap-2">
                      <button
                        type="button"
                        class="rounded-lg bg-primary-600 px-3 py-2 text-sm font-medium text-white disabled:opacity-50"
                        :disabled="recipientBusy === recipient.id"
                        @click="saveRecipient(recipient)"
                      >
                        Save
                      </button>
                      <button type="button" class="rounded-lg border border-secondary-300 px-3 py-2 text-sm dark:border-secondary-600" @click="editing = null">
                        Cancel
                      </button>
                    </div>
                  </template>
                  <template v-else>
                    <div class="min-w-0">
                      <p class="truncate text-sm font-medium" style="color: var(--text-strong)">
                        {{ recipient.label || channel.defaultLabel }}
                      </p>
                      <p class="break-all text-sm text-secondary-600 dark:text-secondary-300">
                        {{ recipient.destination }}
                      </p>
                    </div>
                    <div class="flex flex-shrink-0 gap-2">
                      <button
                        type="button"
                        class="inline-flex min-h-9 items-center gap-1 rounded-lg border border-secondary-300 px-3 text-sm text-secondary-700 hover:bg-secondary-50 dark:border-secondary-600 dark:text-secondary-200 dark:hover:bg-secondary-800"
                        :aria-label="`Edit ${recipient.destination}`"
                        @click="startEdit(recipient)"
                      >
                        <Pencil class="h-4 w-4" /> <span>Edit</span>
                      </button>
                      <button
                        type="button"
                        class="inline-flex min-h-9 items-center gap-1 rounded-lg border border-red-200 px-3 text-sm text-red-700 hover:bg-red-50 dark:border-red-900 dark:text-red-300 dark:hover:bg-red-900/20"
                        :aria-label="`Remove ${recipient.destination}`"
                        :disabled="recipientBusy === recipient.id"
                        @click="removeRecipient(recipient)"
                      >
                        <Trash2 class="h-4 w-4" /> <span>Remove</span>
                      </button>
                    </div>
                  </template>
                </div>
              </div>
              <p v-else class="mt-3 rounded-xl bg-secondary-50 px-3 py-4 text-center text-sm text-secondary-500 dark:bg-secondary-800/50 dark:text-secondary-400">
                No {{ channel.title.toLowerCase() }} recipients added yet.
              </p>
            </section>

            <section class="border-t border-secondary-200 pt-5 dark:border-secondary-700" aria-label="Notification types">
              <div class="mb-3">
                <h3 class="text-sm font-semibold" style="color: var(--text-strong)">
                  Send alerts for
                </h3>
                <p class="mt-1 text-xs text-secondary-500 dark:text-secondary-400">
                  Choose the business actions that should trigger {{ channel.title.toLowerCase() }} alerts.
                </p>
              </div>
              <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                <fieldset v-for="group in eventGroups" :key="group.name" class="rounded-xl border border-secondary-200 p-3 dark:border-secondary-700">
                  <legend class="px-1 text-sm font-medium" style="color: var(--text-strong)">
                    {{ group.name }}
                  </legend>
                  <div class="mt-1 grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <label v-for="event in group.events" :key="event.key" class="flex min-h-10 cursor-pointer items-center gap-2 rounded-lg px-2 py-2 text-sm text-secondary-700 hover:bg-secondary-50 dark:text-secondary-200 dark:hover:bg-secondary-800/60">
                      <input
                        type="checkbox"
                        class="h-4 w-4 rounded border-secondary-300 text-primary-600 focus:ring-primary-500"
                        :checked="isSelected(channel.key, event.key)"
                        @change="toggleEvent(channel.key, event.key)"
                      />
                      <span>{{ event.label }}</span>
                    </label>
                  </div>
                </fieldset>
              </div>
            </section>
          </div>
        </section>

        <section class="app-surface overflow-hidden rounded-2xl">
          <div class="border-b border-secondary-200/70 px-4 py-4 dark:border-secondary-700/70 md:px-6">
            <h2 class="text-base font-semibold" style="color: var(--text-strong)">
              Recent delivery activity
            </h2>
            <p class="mt-1 text-xs text-secondary-500 dark:text-secondary-400">
              Shows the latest manager alerts queued to email and SMS.
            </p>
          </div>
          <div v-if="deliveries.length" class="divide-y divide-secondary-100 dark:divide-secondary-800">
            <div v-for="delivery in deliveries" :key="delivery.id" class="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:justify-between md:px-6">
              <div class="min-w-0">
                <p class="text-sm font-medium" style="color: var(--text-strong)">
                  {{ eventLabel(delivery.event_key) }}
                </p>
                <p class="break-words text-xs text-secondary-500 dark:text-secondary-400">
                  {{ delivery.summary }} · {{ delivery.destination }}
                </p>
                <p v-if="delivery.status === 'failed'" class="mt-1 text-xs text-red-600 dark:text-red-300">
                  {{ delivery.error_message || 'Delivery failed' }}
                </p>
              </div>
              <span class="inline-flex w-fit flex-shrink-0 rounded-full px-2.5 py-1 text-xs font-medium capitalize" :class="delivery.status === 'sent' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : delivery.status === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300'">
                {{ delivery.channel }} · {{ delivery.status }}
              </span>
            </div>
          </div>
          <p v-else class="px-4 py-8 text-center text-sm text-secondary-500 dark:text-secondary-400">
            No manager alerts have been delivered yet.
          </p>
        </section>

        <div class="sticky bottom-0 z-10 -mx-1 flex flex-col gap-3 border-t border-secondary-200 bg-white/95 px-1 py-3 backdrop-blur dark:border-secondary-700 dark:bg-secondary-950/95 sm:flex-row sm:items-center sm:justify-between">
          <p class="text-xs text-secondary-500 dark:text-secondary-400">
            Provider credentials are managed in Settings → Configuration.
          </p>
          <button
            type="button"
            class="min-h-11 rounded-lg bg-primary-600 px-5 py-2 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-50"
            :disabled="saving"
            @click="saveSettings"
          >
            {{ saving ? 'Saving…' : 'Save notification settings' }}
          </button>
        </div>
      </template>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { Mail, MessageSquareText, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import AppPageHeader from '../components/AppPageHeader.vue';
import { apiRequest } from '../composables/useApiClient';

const channels = [
    {
        key: 'email', title: 'Email', icon: Mail, iconBg: 'bg-blue-100 dark:bg-blue-900/30', iconColor: 'text-blue-600 dark:text-blue-400',
        description: 'Deliver business alerts to manager email addresses.', destinationLabel: 'Email address', inputType: 'email', autocomplete: 'email', placeholder: 'manager@example.com', defaultLabel: 'Email recipient',
    },
    {
        key: 'sms', title: 'SMS', icon: MessageSquareText, iconBg: 'bg-emerald-100 dark:bg-emerald-900/30', iconColor: 'text-emerald-600 dark:text-emerald-400',
        description: 'Send concise alerts to manager mobile numbers.', destinationLabel: 'Mobile number', inputType: 'tel', autocomplete: 'tel', placeholder: '+94771234567', defaultLabel: 'SMS recipient',
    },
];

const loading = ref(true);
const saving = ref(false);
const loadError = ref('');
const successMessage = ref('');
const settings = ref({ email_enabled: false, sms_enabled: false, email_events: [], sms_events: [] });
const events = ref([]);
const recipients = ref([]);
const deliveries = ref([]);
const drafts = ref({ email: { label: '', destination: '' }, sms: { label: '', destination: '' } });
const recipientErrors = ref({ email: '', sms: '' });
const recipientBusy = ref(null);
const editing = ref(null);

const eventGroups = computed(() => {
    const groups = new Map();
    for (const event of events.value) {
        if (!groups.has(event.group)) groups.set(event.group, []);
        groups.get(event.group).push(event);
    }
    return [...groups.entries()].map(([name, groupEvents]) => ({ name, events: groupEvents }));
});

function recipientsFor(channel) {
    return recipients.value.filter(recipient => recipient.channel === channel);
}

function eventLabel(key) {
    return events.value.find(event => event.key === key)?.label || key;
}

function isSelected(channel, eventKey) {
    return (settings.value[`${channel}_events`] || []).includes(eventKey);
}

function toggleEvent(channel, eventKey) {
    const key = `${channel}_events`;
    const selected = new Set(settings.value[key] || []);
    if (selected.has(eventKey)) selected.delete(eventKey);
    else selected.add(eventKey);
    settings.value[key] = [...selected];
}

function errorMessage(error, fallback) {
    const errors = error?.response?.data?.errors;
    if (errors && typeof errors === 'object') return Object.values(errors).flat()[0] || fallback;
    return error?.response?.data?.message || fallback;
}

async function load() {
    loading.value = true;
    loadError.value = '';
    try {
        const response = await apiRequest('/api/settings/manager-notifications');
        const data = response?.data || {};
        settings.value = {
            email_enabled: Boolean(data.settings?.email_enabled),
            sms_enabled: Boolean(data.settings?.sms_enabled),
            email_events: Array.isArray(data.settings?.email_events) ? data.settings.email_events : [],
            sms_events: Array.isArray(data.settings?.sms_events) ? data.settings.sms_events : [],
        };
        events.value = Array.isArray(data.events) ? data.events : [];
        recipients.value = Array.isArray(data.recipients) ? data.recipients : [];
        deliveries.value = Array.isArray(data.deliveries) ? data.deliveries : [];
    } catch (error) {
        loadError.value = errorMessage(error, 'Could not load manager notification settings.');
    } finally {
        loading.value = false;
    }
}

async function saveSettings() {
    saving.value = true;
    successMessage.value = '';
    try {
        await apiRequest('/api/settings/manager-notifications', {
            method: 'put',
            data: {
                email_enabled: settings.value.email_enabled,
                sms_enabled: settings.value.sms_enabled,
                email_events: settings.value.email_events,
                sms_events: settings.value.sms_events,
            },
        });
        successMessage.value = 'Manager notification settings saved.';
        window.setTimeout(() => { successMessage.value = ''; }, 3500);
    } catch (error) {
        loadError.value = errorMessage(error, 'Could not save notification settings.');
    } finally {
        saving.value = false;
    }
}

async function addRecipient(channel) {
    recipientBusy.value = channel;
    recipientErrors.value[channel] = '';
    try {
        const response = await apiRequest('/api/settings/manager-notifications/recipients', {
            method: 'post',
            data: { channel, ...drafts.value[channel] },
        });
        recipients.value.push(response.data);
        drafts.value[channel] = { label: '', destination: '' };
    } catch (error) {
        recipientErrors.value[channel] = errorMessage(error, 'Could not add this recipient.');
    } finally {
        recipientBusy.value = null;
    }
}

function startEdit(recipient) {
    editing.value = { id: recipient.id, label: recipient.label || '', destination: recipient.destination };
    recipientErrors.value[recipient.channel] = '';
}

async function saveRecipient(recipient) {
    recipientBusy.value = recipient.id;
    recipientErrors.value[recipient.channel] = '';
    try {
        const response = await apiRequest(`/api/settings/manager-notifications/recipients/${recipient.id}`, {
            method: 'put',
            data: { label: editing.value.label, destination: editing.value.destination },
        });
        const index = recipients.value.findIndex(item => item.id === recipient.id);
        if (index >= 0) recipients.value[index] = response.data;
        editing.value = null;
    } catch (error) {
        recipientErrors.value[recipient.channel] = errorMessage(error, 'Could not update this recipient.');
    } finally {
        recipientBusy.value = null;
    }
}

async function removeRecipient(recipient) {
  if (!window.confirm(`Remove ${recipient.destination} from the ${recipient.channel.toUpperCase()} recipient list?`)) return;
    recipientBusy.value = recipient.id;
    recipientErrors.value[recipient.channel] = '';
    try {
        await apiRequest(`/api/settings/manager-notifications/recipients/${recipient.id}`, { method: 'delete' });
        recipients.value = recipients.value.filter(item => item.id !== recipient.id);
    } catch (error) {
        recipientErrors.value[recipient.channel] = errorMessage(error, 'Could not remove this recipient.');
    } finally {
        recipientBusy.value = null;
    }
}

onMounted(load);
</script>
