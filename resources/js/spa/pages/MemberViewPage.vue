<template>
    <section class="app-page-frame">
        <div class="app-page-scroll">
        <div class="max-w-4xl mx-auto px-0 pb-8 space-y-4">

        <!-- Alerts -->
        <div v-if="errorMessage" class="mx-4 mt-4 rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ errorMessage }}
        </div>
        <div v-if="successMessage" class="mx-4 mt-4 rounded-xl border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 px-4 py-3 text-sm text-green-700 dark:text-green-200">
            {{ successMessage }}
        </div>

        <div v-if="loading" class="mt-8 p-8 text-center text-sm text-secondary-400 dark:text-secondary-500">Loading...</div>

        <template v-else-if="member">

            <!-- ── Hero Card ── -->
            <div class="bg-gradient-to-br from-primary-600 via-primary-500 to-primary-700 rounded-2xl shadow-lg overflow-hidden mx-0">

                <!-- Top bar: back + actions -->
                <div class="flex items-center justify-between px-4 pt-4 pb-0 gap-2">
                    <RouterLink to="/members" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-white/15 hover:bg-white/25 border border-white/20 text-white transition-colors" title="Back to Members">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                    </RouterLink>

                    <div v-if="permissions.edit || permissions.delete" class="flex flex-wrap items-center justify-end gap-1.5">
                        <RouterLink
                            v-if="permissions.edit"
                            :to="`/members/${member.id}/edit`"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-white/20 hover:bg-white/30 border border-white/25 text-white transition-colors"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Edit
                        </RouterLink>
                        <button
                            v-if="permissions.edit"
                            type="button"
                            class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg bg-white/20 hover:bg-white/30 border border-white/25 text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="Boolean(actionInProgress)"
                            @click="toggleStatus"
                        >{{ actionInProgress === 'status' ? '...' : activeActionLabel }}</button>
                        <button
                            v-if="permissions.edit"
                            type="button"
                            class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg bg-white/20 hover:bg-white/30 border border-white/25 text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="Boolean(actionInProgress)"
                            @click="toggleVerification"
                        >{{ actionInProgress === 'verification' ? '...' : verificationActionLabel }}</button>
                        <button
                            v-if="permissions.delete"
                            type="button"
                            class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg bg-red-500/30 hover:bg-red-500/50 border border-red-300/30 text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="Boolean(actionInProgress)"
                            @click="removeMember"
                        >{{ actionInProgress === 'delete' ? 'Deleting...' : 'Delete' }}</button>
                    </div>
                </div>

                <!-- Profile section -->
                <div class="px-5 py-5 flex flex-col sm:flex-row sm:items-end gap-4">
                    <!-- Avatar -->
                    <div class="h-20 w-20 shrink-0 rounded-2xl bg-white/20 border-2 border-white/30 flex items-center justify-center shadow-lg">
                        <span class="text-2xl font-bold text-white">{{ initials }}</span>
                    </div>

                    <!-- Name + badges -->
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-2xl font-bold text-white leading-tight">{{ fullName }}</h1>
                            <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full bg-white/15 border border-white/20 text-white">{{ normalizedGender }}</span>
                        </div>
                        <p class="mt-1 text-sm text-primary-100">
                            {{ member.member_id }}<span v-if="member.username" class="ml-2 opacity-75">@{{ member.username }}</span>
                        </p>
                        <div class="mt-2.5 flex flex-wrap gap-1.5">
                            <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full border"
                                :class="member.is_active ? 'bg-green-400/20 text-green-100 border-green-300/30' : 'bg-red-400/20 text-red-100 border-red-300/30'">
                                {{ member.is_active ? '● Active' : '● Inactive' }}
                            </span>
                            <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full border"
                                :class="member.is_verified ? 'bg-blue-400/20 text-blue-100 border-blue-300/30' : 'bg-yellow-400/20 text-yellow-100 border-yellow-300/30'">
                                {{ member.is_verified ? '✓ Verified' : '! Unverified' }}
                            </span>
                            <span v-if="member.is_temp" class="px-2 py-0.5 text-[11px] font-semibold rounded-full bg-orange-400/20 border border-orange-300/30 text-orange-100">Temp</span>
                            <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full bg-white/10 border border-white/15 text-white">{{ displayValue(member.member_role) }}</span>
                            <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full bg-white/10 border border-white/15 text-white">{{ displayValue(member.payment_plan) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Quick stats strip -->
                <div class="grid grid-cols-2 sm:grid-cols-4 border-t border-white/15">
                    <div class="px-5 py-3 text-center border-r border-white/15">
                        <p class="text-[11px] text-primary-200 uppercase tracking-wide">Balance</p>
                        <p class="mt-0.5 text-base font-bold text-white">{{ formatMoney(member.current_balance) }}</p>
                    </div>
                    <div class="px-5 py-3 text-center sm:border-r border-white/15">
                        <p class="text-[11px] text-primary-200 uppercase tracking-wide">Plan</p>
                        <p class="mt-0.5 text-base font-bold text-white">{{ formatMoney(member.price) }}<span class="text-xs font-normal text-primary-200">/mo</span></p>
                    </div>
                    <div class="px-5 py-3 text-center border-t sm:border-t-0 border-r border-white/15">
                        <p class="text-[11px] text-primary-200 uppercase tracking-wide">Joined</p>
                        <p class="mt-0.5 text-sm font-semibold text-white">{{ formatDate(member.joined_date) }}</p>
                    </div>
                    <div class="px-5 py-3 text-center border-t sm:border-t-0 border-white/15">
                        <p class="text-[11px] text-primary-200 uppercase tracking-wide">Member Since</p>
                        <p class="mt-0.5 text-sm font-semibold text-white">{{ formatDate(member.created_at) }}</p>
                    </div>
                </div>
            </div>

            <!-- ── Detail Cards ── -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <!-- Personal Info -->
                <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-700 shadow-sm overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-secondary-100 dark:border-secondary-800">
                        <h2 class="text-xs font-semibold uppercase tracking-widest text-secondary-400 dark:text-secondary-500">Personal</h2>
                    </div>
                    <dl class="divide-y divide-secondary-100 dark:divide-secondary-800">
                        <div class="flex items-center justify-between px-5 py-3">
                            <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">First Name</dt>
                            <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right">{{ displayValue(member.first_name) }}</dd>
                        </div>
                        <div class="flex items-center justify-between px-5 py-3">
                            <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">Last Name</dt>
                            <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right">{{ displayValue(member.last_name) }}</dd>
                        </div>
                        <div class="flex items-center justify-between px-5 py-3">
                            <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">Gender</dt>
                            <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right">{{ normalizedGender }}</dd>
                        </div>
                        <div class="flex items-center justify-between px-5 py-3">
                            <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">Date of Birth</dt>
                            <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right">{{ formatDate(member.date_of_birth) }}</dd>
                        </div>
                        <div class="flex items-center justify-between px-5 py-3">
                            <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">Age</dt>
                            <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right">{{ displayValue(member.age) }}</dd>
                        </div>
                        <div class="flex items-center justify-between px-5 py-3">
                            <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">NIC</dt>
                            <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right">{{ displayValue(member.nic) }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Contact & Access -->
                <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-700 shadow-sm overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-secondary-100 dark:border-secondary-800">
                        <h2 class="text-xs font-semibold uppercase tracking-widest text-secondary-400 dark:text-secondary-500">Contact & Access</h2>
                    </div>
                    <dl class="divide-y divide-secondary-100 dark:divide-secondary-800">
                        <div class="flex items-center justify-between px-5 py-3 gap-3">
                            <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">Email</dt>
                            <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right break-all">{{ displayValue(member.email) }}</dd>
                        </div>
                        <div class="flex items-center justify-between px-5 py-3">
                            <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">Phone</dt>
                            <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right">{{ displayValue(member.phone_number) }}</dd>
                        </div>
                        <div class="flex items-center justify-between px-5 py-3">
                            <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">Username</dt>
                            <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right">{{ displayValue(member.username) }}</dd>
                        </div>
                        <div class="flex items-start justify-between px-5 py-3 gap-3">
                            <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28 pt-0.5">Address</dt>
                            <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right whitespace-pre-line">{{ displayValue(member.address) }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Plan & Billing -->
                <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-700 shadow-sm overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-secondary-100 dark:border-secondary-800">
                        <h2 class="text-xs font-semibold uppercase tracking-widest text-secondary-400 dark:text-secondary-500">Plan & Billing</h2>
                    </div>
                    <dl class="divide-y divide-secondary-100 dark:divide-secondary-800">
                        <div class="flex items-center justify-between px-5 py-3">
                            <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">Role</dt>
                            <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right">{{ displayValue(member.member_role) }}</dd>
                        </div>
                        <div class="flex items-center justify-between px-5 py-3">
                            <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">Payment Plan</dt>
                            <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right">{{ displayValue(member.payment_plan) }}</dd>
                        </div>
                        <div class="flex items-center justify-between px-5 py-3">
                            <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">Monthly Fee</dt>
                            <dd class="text-sm font-semibold text-secondary-900 dark:text-white text-right">{{ formatMoney(member.price) }}</dd>
                        </div>
                        <div class="flex items-center justify-between px-5 py-3">
                            <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">Admission Fee</dt>
                            <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right">{{ formatMoney(member.admission_fee) }}</dd>
                        </div>
                        <div class="flex items-center justify-between px-5 py-3">
                            <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">Balance</dt>
                            <dd class="text-sm font-semibold text-secondary-900 dark:text-white text-right">{{ formatMoney(member.current_balance) }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Notes -->
                <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-700 shadow-sm overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-secondary-100 dark:border-secondary-800">
                        <h2 class="text-xs font-semibold uppercase tracking-widest text-secondary-400 dark:text-secondary-500">Notes</h2>
                    </div>
                    <div class="px-5 py-4">
                        <p class="text-sm text-secondary-700 dark:text-secondary-300 whitespace-pre-line leading-relaxed">
                            {{ member.comment || 'No notes added for this member.' }}
                        </p>
                    </div>
                </div>

            </div>
        </template>

        <div v-else-if="!loading" class="p-8 text-center text-sm text-secondary-400 dark:text-secondary-500">Member details are unavailable.</div>

        </div><!-- max-w-4xl -->
        </div><!-- app-page-scroll -->
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiRequest } from '../composables/useApiClient';

const route = useRoute();
const router = useRouter();

const loading = ref(false);
const actionInProgress = ref('');
const errorMessage = ref('');
const successMessage = ref('');
const member = ref(null);
const permissions = ref({ edit: false, delete: false });

const moneyFormatter = new Intl.NumberFormat(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

const fullName = computed(() => {
    if (!member.value) {
        return 'Member';
    }

    const firstName = (member.value.first_name || '').trim();
    const lastName = (member.value.last_name || '').trim();

    if (firstName || lastName) {
        return `${firstName} ${lastName}`.trim();
    }

    return member.value.name || 'Member';
});

const initials = computed(() => {
    const value = fullName.value
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');

    return value || 'MB';
});

const normalizedGender = computed(() => {
    if (!member.value?.gender) {
        return 'Not provided';
    }

    return capitalize(member.value.gender);
});

const activeActionLabel = computed(() => (member.value?.is_active ? 'Deactivate' : 'Activate'));
const verificationActionLabel = computed(() => (member.value?.is_verified ? 'Unverify' : 'Verify'));

function capitalize(value = '') {
    return value ? value.charAt(0).toUpperCase() + value.slice(1) : '';
}

function displayValue(value) {
    return value === null || value === undefined || value === '' ? 'Not provided' : value;
}

function formatDate(value) {
    if (!value) {
        return 'Not provided';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

function formatMoney(value) {
    if (value === null || value === undefined || value === '') {
        return 'Not provided';
    }

    return moneyFormatter.format(Number(value));
}

async function loadMember() {
    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await apiRequest(`/api/members/${route.params.id}`);
        member.value = response.data || null;
        permissions.value = response.permissions || permissions.value;
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load member details.';
    } finally {
        loading.value = false;
    }
}

async function toggleStatus() {
    if (!member.value) {
        return;
    }

    actionInProgress.value = 'status';
    errorMessage.value = '';
    successMessage.value = '';

    try {
        const response = await apiRequest(`/api/members/${member.value.id}/toggle-status`, { method: 'patch' });
        member.value = {
            ...member.value,
            is_active: response.is_active,
        };
        successMessage.value = response.message || 'Member status updated successfully.';
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to update member status.';
    } finally {
        actionInProgress.value = '';
    }
}

async function toggleVerification() {
    if (!member.value) {
        return;
    }

    actionInProgress.value = 'verification';
    errorMessage.value = '';
    successMessage.value = '';

    try {
        const response = await apiRequest(`/api/members/${member.value.id}/toggle-verification`, { method: 'patch' });
        member.value = {
            ...member.value,
            is_verified: response.is_verified,
        };
        successMessage.value = response.message || 'Member verification updated successfully.';
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to update member verification.';
    } finally {
        actionInProgress.value = '';
    }
}

async function removeMember() {
    if (!member.value || !window.confirm('Are you sure you want to delete this member?')) {
        return;
    }

    actionInProgress.value = 'delete';
    errorMessage.value = '';
    successMessage.value = '';

    try {
        await apiRequest(`/api/members/${member.value.id}`, { method: 'delete' });
        router.push('/members');
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to delete member.';
    } finally {
        actionInProgress.value = '';
    }
}

onMounted(() => {
    loadMember();
});
</script>