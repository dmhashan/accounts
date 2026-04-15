<template>
    <section class="app-page-frame">
        <AppPageHeader :show-back="true" :title="notification?.name || 'Notification'">
            <template v-if="notification?.status === 'draft'" #cta-slot>
                <RouterLink :to="`/notifications/${notification.id}/edit`" class="px-4 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg text-sm text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-800 transition-colors">
                    Edit Draft
                </RouterLink>
                <button
                    type="button"
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-semibold disabled:opacity-50 transition-colors"
                    :disabled="deleting"
                    @click="removeNotification"
                >
                    {{ deleting ? 'Deleting...' : 'Delete' }}
                </button>
            </template>
        </AppPageHeader>

        <div class="app-page-scroll">
            <div v-if="loading" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">Loading...</div>

            <template v-else-if="notification">
                <!-- Status banner -->
                <div v-if="notification.status === 'sent'" class="mb-4 rounded-xl border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 px-4 py-3 flex items-center gap-2">
                    <CheckCircle class="h-4 w-4 text-green-600 dark:text-green-400 shrink-0" />
                    <span class="text-sm text-green-700 dark:text-green-300">
                        Sent on {{ formatDate(notification.sent_at) }} &bull; {{ notification.recipients.length }} recipient{{ notification.recipients.length === 1 ? '' : 's' }}
                    </span>
                </div>

                <div v-if="notification.status === 'draft'" class="mb-4 rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 px-4 py-3 flex items-center gap-2">
                    <Clock class="h-4 w-4 text-amber-600 dark:text-amber-400 shrink-0" />
                    <span class="text-sm text-amber-700 dark:text-amber-300">Draft — not yet sent</span>
                </div>

                <!-- Error / success messages -->
                <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
                    {{ errorMessage }}
                </div>
                <div v-if="successMessage" class="mb-4 rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 px-4 py-3 text-sm text-green-700 dark:text-green-200">
                    {{ successMessage }}
                </div>

                <!-- Message card -->
                <div class="app-surface rounded-2xl p-4 md:p-6 mb-4">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div class="space-y-1 min-w-0">
                            <p class="text-xs text-secondary-400 dark:text-secondary-500">Name</p>
                            <p class="text-base font-semibold text-secondary-900 dark:text-white">{{ notification.name }}</p>
                        </div>
                        <div class="flex gap-2 shrink-0">
                            <button
                                v-if="notification.status === 'draft'"
                                type="button"
                                class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-semibold disabled:opacity-50 transition-colors flex items-center gap-2"
                                :disabled="sending || notification.recipients.length === 0"
                                @click="confirmSend"
                            >
                                <Send class="h-4 w-4" />
                                {{ sending ? 'Sending...' : 'Finalize & Send' }}
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 space-y-1">
                        <p class="text-xs text-secondary-400 dark:text-secondary-500">Message</p>
                        <div class="rounded-xl bg-secondary-50 dark:bg-secondary-800/50 px-4 py-3 text-sm text-secondary-800 dark:text-secondary-200 whitespace-pre-wrap leading-relaxed">{{ notification.message }}</div>
                    </div>

                    <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
                        <div>
                            <p class="text-xs text-secondary-400 dark:text-secondary-500">Status</p>
                            <span class="mt-1 inline-block px-2 py-0.5 text-xs font-semibold rounded-full" :class="statusClass(notification.status)">{{ capitalize(notification.status) }}</span>
                        </div>
                        <div>
                            <p class="text-xs text-secondary-400 dark:text-secondary-500">Created by</p>
                            <p class="mt-1 text-secondary-800 dark:text-secondary-200">{{ notification.created_by_name || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-secondary-400 dark:text-secondary-500">Created</p>
                            <p class="mt-1 text-secondary-800 dark:text-secondary-200">{{ formatDate(notification.created_at) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Recipients list -->
                <div class="app-surface rounded-2xl overflow-hidden">
                    <div class="px-4 md:px-6 py-4 border-b border-secondary-200 dark:border-secondary-700 flex items-center justify-between">
                        <h3 class="text-base font-semibold text-secondary-900 dark:text-white">Recipients</h3>
                        <span class="text-sm text-secondary-500 dark:text-secondary-400">{{ notification.recipients.length }} total</span>
                    </div>

                    <div v-if="notification.recipients.length === 0" class="px-4 py-8 text-center text-sm text-secondary-400 dark:text-secondary-500">
                        No recipients selected.
                    </div>

                    <template v-else>
                        <!-- Mobile -->
                        <div class="md:hidden divide-y divide-secondary-100 dark:divide-secondary-800">
                            <div v-for="r in notification.recipients" :key="r.id" class="px-4 py-3">
                                <p class="text-sm font-medium text-secondary-900 dark:text-white">{{ r.member_name || '—' }}</p>
                                <p class="text-xs text-secondary-500 dark:text-secondary-400">{{ r.phone_number }}</p>
                            </div>
                        </div>

                        <!-- Desktop -->
                        <table class="hidden md:table w-full text-sm">
                            <thead class="bg-secondary-50 dark:bg-secondary-800/50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-secondary-700 dark:text-secondary-300">#</th>
                                    <th class="px-4 py-3 text-left font-semibold text-secondary-700 dark:text-secondary-300">Name</th>
                                    <th class="px-4 py-3 text-left font-semibold text-secondary-700 dark:text-secondary-300">Phone Number</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-secondary-100 dark:divide-secondary-800">
                                <tr v-for="(r, i) in notification.recipients" :key="r.id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/30">
                                    <td class="px-4 py-3 text-secondary-500 dark:text-secondary-400">{{ i + 1 }}</td>
                                    <td class="px-4 py-3 font-medium text-secondary-900 dark:text-white">{{ r.member_name || '—' }}</td>
                                    <td class="px-4 py-3 text-secondary-600 dark:text-secondary-400">{{ r.phone_number }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </template>
                </div>
            </template>
        </div>

        <!-- Confirm send modal -->
        <div v-if="showConfirm" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-4">
            <div class="w-full sm:max-w-md app-surface rounded-2xl p-6 space-y-4 shadow-xl">
                <h3 class="text-lg font-bold text-secondary-900 dark:text-white">Finalize & Send</h3>
                <p class="text-sm text-secondary-600 dark:text-secondary-400">
                    You are about to send this SMS to <strong>{{ notification.recipients.length }}</strong> recipient{{ notification.recipients.length === 1 ? '' : 's' }}.
                    Once sent, this notification <strong>cannot be edited or deleted</strong>.
                </p>
                <div class="flex gap-2 justify-end">
                    <button type="button" class="px-4 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg text-sm text-secondary-700 dark:text-secondary-300" @click="showConfirm = false">Cancel</button>
                    <button type="button" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-semibold disabled:opacity-50 flex items-center gap-2" :disabled="sending" @click="doSend">
                        <Send class="h-4 w-4" />
                        {{ sending ? 'Sending...' : 'Confirm & Send' }}
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { RouterLink } from 'vue-router';
import { CheckCircle, Clock, Send } from 'lucide-vue-next';
import { apiRequest } from '../composables/useApiClient';
import AppPageHeader from '../components/AppPageHeader.vue';

const route = useRoute();
const router = useRouter();
const loading = ref(false);
const sending = ref(false);
const deleting = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const notification = ref(null);
const showConfirm = ref(false);

async function load() {
    loading.value = true;
    errorMessage.value = '';
    try {
        notification.value = await apiRequest(`/api/notifications/${route.params.id}`);
    } catch {
        errorMessage.value = 'Failed to load notification.';
    } finally {
        loading.value = false;
    }
}

function confirmSend() {
    errorMessage.value = '';
    successMessage.value = '';
    showConfirm.value = true;
}

async function removeNotification() {
    if (!window.confirm('Delete this draft notification?')) return;
    deleting.value = true;
    errorMessage.value = '';
    try {
        await apiRequest(`/api/notifications/${route.params.id}`, { method: 'delete' });
        router.push('/notifications');
    } catch (e) {
        errorMessage.value = e?.response?.data?.message || 'Failed to delete notification.';
    } finally {
        deleting.value = false;
    }
}

async function doSend() {
    sending.value = true;
    errorMessage.value = '';
    try {
        const res = await apiRequest(`/api/notifications/${route.params.id}/send`, { method: 'POST' });
        successMessage.value = res.message || 'Notification sent successfully.';
        showConfirm.value = false;
        await load();
    } catch (e) {
        showConfirm.value = false;
        errorMessage.value = e?.message || 'Failed to send notification.';
    } finally {
        sending.value = false;
    }
}

function statusClass(status) {
    return status === 'sent'
        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
        : 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300';
}

function capitalize(str) {
    return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
}

function formatDate(str) {
    if (!str) return '';
    return new Date(str).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

onMounted(() => load());
</script>
