<template>
    <section class="max-w-6xl mx-auto space-y-4 md:space-y-5">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <RouterLink to="/members" class="text-sm text-primary-600 dark:text-primary-400">Back to Members</RouterLink>
                <h2 class="mt-1 text-xl md:text-2xl font-bold text-secondary-900 dark:text-white">Member Details</h2>
                <p class="text-sm text-secondary-500 dark:text-secondary-400">Review the full member record and manage account actions from one screen.</p>
            </div>

            <div v-if="member" class="flex flex-col sm:flex-row sm:flex-wrap gap-2 w-full lg:w-auto">
                <RouterLink
                    v-if="permissions.edit"
                    :to="`/members/${member.id}/edit`"
                    class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-primary-600 hover:bg-primary-700 text-white transition-colors lg:min-w-[8.5rem]"
                >
                    Edit Member
                </RouterLink>

                <button
                    v-if="permissions.edit"
                    type="button"
                    class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-secondary-300 dark:border-secondary-700 text-secondary-700 dark:text-secondary-200 hover:bg-secondary-50 dark:hover:bg-secondary-800 transition-colors disabled:opacity-60 disabled:cursor-not-allowed lg:min-w-[8.5rem]"
                    :disabled="Boolean(actionInProgress)"
                    @click="toggleStatus"
                >
                    {{ actionInProgress === 'status' ? 'Updating...' : activeActionLabel }}
                </button>

                <button
                    v-if="permissions.edit"
                    type="button"
                    class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-blue-200 dark:border-blue-900/60 text-blue-700 dark:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors disabled:opacity-60 disabled:cursor-not-allowed lg:min-w-[8.5rem]"
                    :disabled="Boolean(actionInProgress)"
                    @click="toggleVerification"
                >
                    {{ actionInProgress === 'verification' ? 'Updating...' : verificationActionLabel }}
                </button>

                <button
                    v-if="permissions.delete"
                    type="button"
                    class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-red-200 dark:border-red-900/60 text-red-700 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors disabled:opacity-60 disabled:cursor-not-allowed lg:min-w-[8.5rem]"
                    :disabled="Boolean(actionInProgress)"
                    @click="removeMember"
                >
                    {{ actionInProgress === 'delete' ? 'Deleting...' : 'Delete Member' }}
                </button>
            </div>
        </div>

        <div v-if="errorMessage" class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ errorMessage }}
        </div>

        <div v-if="successMessage" class="rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 px-4 py-3 text-sm text-green-700 dark:text-green-200">
            {{ successMessage }}
        </div>

        <article class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-700 shadow-sm overflow-hidden">
            <div v-if="loading" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">Loading member details...</div>

            <template v-else-if="member">
                <div class="bg-gradient-to-r from-primary-500 to-primary-700 px-4 py-5 md:px-6 md:py-6">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-4 md:gap-5">
                        <div class="h-16 w-16 md:h-20 md:w-20 rounded-full bg-white/20 border border-white/30 flex items-center justify-center">
                            <span class="text-lg md:text-2xl font-bold text-white">{{ initials }}</span>
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-xl md:text-2xl font-bold text-white truncate">{{ fullName }}</h3>
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-white/15 border border-white/20 text-white">
                                    {{ normalizedGender }}
                                </span>
                            </div>

                            <p class="mt-1 text-sm text-primary-100 break-all">
                                {{ member.member_id }}
                                <span v-if="member.username">• @{{ member.username }}</span>
                            </p>

                            <div class="mt-3 flex flex-wrap gap-2">
                                <span
                                    class="px-2.5 py-1 text-xs font-semibold rounded-full border"
                                    :class="member.is_active
                                        ? 'bg-green-100 text-green-800 border-green-200'
                                        : 'bg-red-100 text-red-800 border-red-200'"
                                >
                                    {{ member.is_active ? 'Active' : 'Inactive' }}
                                </span>

                                <span
                                    class="px-2.5 py-1 text-xs font-semibold rounded-full border"
                                    :class="member.is_verified
                                        ? 'bg-blue-100 text-blue-800 border-blue-200'
                                        : 'bg-yellow-100 text-yellow-800 border-yellow-200'"
                                >
                                    {{ member.is_verified ? 'Verified' : 'Unverified' }}
                                </span>

                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-white/15 border border-white/20 text-white">
                                    {{ displayValue(member.member_role) }}
                                </span>

                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-white/15 border border-white/20 text-white">
                                    {{ displayValue(member.payment_plan) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-4 md:p-6 space-y-4 md:space-y-5">
                    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.25fr)_minmax(18rem,0.75fr)] gap-4 md:gap-5">
                        <article class="rounded-xl border border-secondary-200 dark:border-secondary-700 p-4 md:p-5">
                            <h3 class="text-sm font-semibold text-secondary-900 dark:text-white mb-3">Personal Information</h3>
                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                                <div>
                                    <dt class="text-secondary-500 dark:text-secondary-400">First Name</dt>
                                    <dd class="mt-1 text-secondary-900 dark:text-white">{{ displayValue(member.first_name) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-secondary-500 dark:text-secondary-400">Last Name</dt>
                                    <dd class="mt-1 text-secondary-900 dark:text-white">{{ displayValue(member.last_name) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-secondary-500 dark:text-secondary-400">Gender</dt>
                                    <dd class="mt-1 text-secondary-900 dark:text-white">{{ normalizedGender }}</dd>
                                </div>
                                <div>
                                    <dt class="text-secondary-500 dark:text-secondary-400">Age</dt>
                                    <dd class="mt-1 text-secondary-900 dark:text-white">{{ displayValue(member.age) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-secondary-500 dark:text-secondary-400">Date of Birth</dt>
                                    <dd class="mt-1 text-secondary-900 dark:text-white">{{ formatDate(member.date_of_birth) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-secondary-500 dark:text-secondary-400">NIC</dt>
                                    <dd class="mt-1 text-secondary-900 dark:text-white">{{ displayValue(member.nic) }}</dd>
                                </div>
                            </dl>
                        </article>

                        <article class="rounded-xl border border-secondary-200 dark:border-secondary-700 p-4 md:p-5">
                            <h3 class="text-sm font-semibold text-secondary-900 dark:text-white mb-3">Membership Summary</h3>
                            <dl class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-1 gap-3 text-sm">
                                <div>
                                    <dt class="text-secondary-500 dark:text-secondary-400">Member ID</dt>
                                    <dd class="mt-1 text-secondary-900 dark:text-white">{{ displayValue(member.member_id) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-secondary-500 dark:text-secondary-400">Member Since</dt>
                                    <dd class="mt-1 text-secondary-900 dark:text-white">{{ formatDate(member.created_at) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-secondary-500 dark:text-secondary-400">Joined Date</dt>
                                    <dd class="mt-1 text-secondary-900 dark:text-white">{{ formatDate(member.joined_date) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-secondary-500 dark:text-secondary-400">Current Balance</dt>
                                    <dd class="mt-1 text-secondary-900 dark:text-white">{{ formatMoney(member.current_balance) }}</dd>
                                </div>
                            </dl>
                        </article>
                    </div>

                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 md:gap-5">
                        <article class="rounded-xl border border-secondary-200 dark:border-secondary-700 p-4 md:p-5">
                            <h3 class="text-sm font-semibold text-secondary-900 dark:text-white mb-3">Contact and Access</h3>
                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                                <div>
                                    <dt class="text-secondary-500 dark:text-secondary-400">Email</dt>
                                    <dd class="mt-1 text-secondary-900 dark:text-white break-all">{{ displayValue(member.email) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-secondary-500 dark:text-secondary-400">Phone Number</dt>
                                    <dd class="mt-1 text-secondary-900 dark:text-white">{{ displayValue(member.phone_number) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-secondary-500 dark:text-secondary-400">Username</dt>
                                    <dd class="mt-1 text-secondary-900 dark:text-white">{{ displayValue(member.username) }}</dd>
                                </div>
                                <div class="sm:col-span-2">
                                    <dt class="text-secondary-500 dark:text-secondary-400">Address</dt>
                                    <dd class="mt-1 text-secondary-900 dark:text-white whitespace-pre-line">{{ displayValue(member.address) }}</dd>
                                </div>
                            </dl>
                        </article>

                        <article class="rounded-xl border border-secondary-200 dark:border-secondary-700 p-4 md:p-5">
                            <h3 class="text-sm font-semibold text-secondary-900 dark:text-white mb-3">Plan and Billing</h3>
                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                                <div>
                                    <dt class="text-secondary-500 dark:text-secondary-400">Member Role</dt>
                                    <dd class="mt-1 text-secondary-900 dark:text-white">{{ displayValue(member.member_role) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-secondary-500 dark:text-secondary-400">Payment Plan</dt>
                                    <dd class="mt-1 text-secondary-900 dark:text-white">{{ displayValue(member.payment_plan) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-secondary-500 dark:text-secondary-400">Price</dt>
                                    <dd class="mt-1 text-secondary-900 dark:text-white">{{ formatMoney(member.price) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-secondary-500 dark:text-secondary-400">Admission Fee</dt>
                                    <dd class="mt-1 text-secondary-900 dark:text-white">{{ formatMoney(member.admission_fee) }}</dd>
                                </div>
                            </dl>
                        </article>
                    </div>

                    <article class="rounded-xl border border-secondary-200 dark:border-secondary-700 p-4 md:p-5">
                        <h3 class="text-sm font-semibold text-secondary-900 dark:text-white mb-2">Notes</h3>
                        <p class="text-sm text-secondary-700 dark:text-secondary-300 whitespace-pre-line">
                            {{ member.comment || 'No notes added for this member.' }}
                        </p>
                    </article>
                </div>
            </template>

            <div v-else class="p-6 text-sm text-secondary-500 dark:text-secondary-400">Member details are unavailable.</div>
        </article>
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