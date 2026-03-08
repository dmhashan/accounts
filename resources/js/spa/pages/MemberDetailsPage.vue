<template>
    <section class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-xl md:text-2xl font-bold text-secondary-900 dark:text-white">Member Details</h2>
                <p class="text-sm text-secondary-500 dark:text-secondary-400">{{ memberTitle }}</p>
            </div>
            <RouterLink to="/members" class="text-sm text-primary-600 dark:text-primary-400">Back to Members</RouterLink>
        </div>

        <div v-if="errorMessage" class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ errorMessage }}
        </div>

        <div v-if="successMessage" class="rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 px-4 py-3 text-sm text-green-700 dark:text-green-300">
            {{ successMessage }}
        </div>

        <div class="inline-flex w-full overflow-x-auto rounded-lg border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900">
            <button
                type="button"
                class="px-4 py-2.5 text-sm font-medium whitespace-nowrap transition-colors"
                :class="activeTab === 'profile' ? 'bg-primary-600 text-white' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-800'"
                @click="activeTab = 'profile'"
            >
                Profile
            </button>
            <button
                type="button"
                class="px-4 py-2.5 text-sm font-medium whitespace-nowrap transition-colors"
                :class="activeTab === 'wallet' ? 'bg-primary-600 text-white' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-800'"
                @click="activeTab = 'wallet'"
            >
                Wallet
            </button>
        </div>

        <div v-if="loadingMember" class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-700 p-6 text-sm text-secondary-500 dark:text-secondary-400">
            Loading member details...
        </div>

        <template v-else-if="member">
            <div v-if="activeTab === 'profile'" class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-700 p-4 md:p-6 space-y-5">
                <div class="flex flex-wrap gap-2">
                    <RouterLink
                        v-if="permissions.edit"
                        :to="`/members/${member.id}/edit`"
                        class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-primary-600 hover:bg-primary-700 text-white transition-colors"
                    >
                        Edit
                    </RouterLink>
                    <button
                        v-if="permissions.edit"
                        type="button"
                        class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-secondary-300 dark:border-secondary-700 text-secondary-700 dark:text-secondary-200 hover:bg-secondary-50 dark:hover:bg-secondary-800 transition-colors"
                        :disabled="statusProcessing"
                        @click="toggleStatus"
                    >
                        {{ statusProcessing ? 'Updating...' : (member.is_active ? 'Deactivate' : 'Activate') }}
                    </button>
                    <button
                        v-if="permissions.edit"
                        type="button"
                        class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-blue-300 dark:border-blue-700 text-blue-700 dark:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
                        :disabled="verificationProcessing"
                        @click="toggleVerification"
                    >
                        {{ verificationProcessing ? 'Updating...' : (member.is_verified ? 'Unverify' : 'Verify') }}
                    </button>
                    <button
                        v-if="permissions.delete"
                        type="button"
                        class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-red-300 dark:border-red-700 text-red-700 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                        :disabled="deleteProcessing"
                        @click="deleteMember"
                    >
                        {{ deleteProcessing ? 'Deleting...' : 'Delete' }}
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="rounded-lg border border-secondary-200 dark:border-secondary-700 p-3">
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">Member ID</p>
                        <p class="text-sm text-secondary-900 dark:text-white">{{ displayValue(member.member_id) }}</p>
                    </div>
                    <div class="rounded-lg border border-secondary-200 dark:border-secondary-700 p-3">
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">Full Name</p>
                        <p class="text-sm text-secondary-900 dark:text-white">{{ memberFullName }}</p>
                    </div>
                    <div class="rounded-lg border border-secondary-200 dark:border-secondary-700 p-3">
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">Username</p>
                        <p class="text-sm text-secondary-900 dark:text-white">{{ displayValue(member.username) }}</p>
                    </div>
                    <div class="rounded-lg border border-secondary-200 dark:border-secondary-700 p-3">
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">Gender</p>
                        <p class="text-sm text-secondary-900 dark:text-white">{{ displayValue(capitalize(member.gender)) }}</p>
                    </div>
                    <div class="rounded-lg border border-secondary-200 dark:border-secondary-700 p-3">
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">Email</p>
                        <p class="text-sm text-secondary-900 dark:text-white break-all">{{ displayValue(member.email) }}</p>
                    </div>
                    <div class="rounded-lg border border-secondary-200 dark:border-secondary-700 p-3">
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">Phone Number</p>
                        <p class="text-sm text-secondary-900 dark:text-white">{{ displayValue(member.phone_number) }}</p>
                    </div>
                    <div class="rounded-lg border border-secondary-200 dark:border-secondary-700 p-3">
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">NIC</p>
                        <p class="text-sm text-secondary-900 dark:text-white">{{ displayValue(member.nic) }}</p>
                    </div>
                    <div class="rounded-lg border border-secondary-200 dark:border-secondary-700 p-3">
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">Date of Birth</p>
                        <p class="text-sm text-secondary-900 dark:text-white">{{ displayValue(member.date_of_birth) }}</p>
                    </div>
                    <div class="rounded-lg border border-secondary-200 dark:border-secondary-700 p-3">
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">Age</p>
                        <p class="text-sm text-secondary-900 dark:text-white">{{ displayValue(member.age) }}</p>
                    </div>
                    <div class="rounded-lg border border-secondary-200 dark:border-secondary-700 p-3">
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">Member Role</p>
                        <p class="text-sm text-secondary-900 dark:text-white">{{ displayValue(member.member_role) }}</p>
                    </div>
                    <div class="rounded-lg border border-secondary-200 dark:border-secondary-700 p-3">
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">Payment Plan</p>
                        <p class="text-sm text-secondary-900 dark:text-white">{{ displayValue(member.payment_plan) }}</p>
                    </div>
                    <div class="rounded-lg border border-secondary-200 dark:border-secondary-700 p-3">
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">Price</p>
                        <p class="text-sm text-secondary-900 dark:text-white">{{ money(member.price) }}</p>
                    </div>
                    <div class="rounded-lg border border-secondary-200 dark:border-secondary-700 p-3">
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">Admission Fee</p>
                        <p class="text-sm text-secondary-900 dark:text-white">{{ money(member.admission_fee) }}</p>
                    </div>
                    <div class="rounded-lg border border-secondary-200 dark:border-secondary-700 p-3">
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">Joined Date</p>
                        <p class="text-sm text-secondary-900 dark:text-white">{{ displayValue(member.joined_date) }}</p>
                    </div>
                    <div class="rounded-lg border border-secondary-200 dark:border-secondary-700 p-3">
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">Status</p>
                        <p class="text-sm">
                            <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full" :class="member.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'">
                                {{ member.is_active ? 'Active' : 'Inactive' }}
                            </span>
                            <span class="ml-2 px-2 py-0.5 text-[11px] font-semibold rounded-full" :class="member.is_verified ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300'">
                                {{ member.is_verified ? 'Verified' : 'Unverified' }}
                            </span>
                        </p>
                    </div>
                    <div class="md:col-span-2 rounded-lg border border-secondary-200 dark:border-secondary-700 p-3">
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">Address</p>
                        <p class="text-sm text-secondary-900 dark:text-white whitespace-pre-line">{{ displayValue(member.address) }}</p>
                    </div>
                    <div class="md:col-span-2 rounded-lg border border-secondary-200 dark:border-secondary-700 p-3">
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">Comment</p>
                        <p class="text-sm text-secondary-900 dark:text-white whitespace-pre-line">{{ displayValue(member.comment) }}</p>
                    </div>
                </div>
            </div>

            <div v-if="activeTab === 'wallet'" class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-700 p-4 md:p-6 space-y-4">
                <template v-if="!canUseWallet">
                    <p class="text-sm text-secondary-500 dark:text-secondary-400">You do not have permission to view wallet details.</p>
                </template>

                <template v-else>
                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                        <div class="rounded-lg border border-secondary-200 dark:border-secondary-700 p-4 bg-secondary-50 dark:bg-secondary-800/40">
                            <p class="text-sm font-semibold text-secondary-900 dark:text-white mb-3">Wallet Snapshot</p>

                            <p v-if="walletLoading" class="text-sm text-secondary-500 dark:text-secondary-400">Loading wallet details...</p>
                            <template v-else-if="walletInfo">
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-secondary-500 dark:text-secondary-400">Status</span>
                                        <span class="font-medium text-secondary-900 dark:text-white capitalize">{{ walletInfo.status }}</span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-secondary-500 dark:text-secondary-400">Current Balance</span>
                                        <span class="font-medium text-secondary-900 dark:text-white">{{ money(walletInfo.current_balance) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-secondary-500 dark:text-secondary-400">Credit Limit</span>
                                        <span class="font-medium text-secondary-900 dark:text-white">{{ money(walletInfo.credit_limit) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-secondary-500 dark:text-secondary-400">Available Spend</span>
                                        <span class="font-medium text-secondary-900 dark:text-white">{{ money(walletInfo.available_spend) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-secondary-500 dark:text-secondary-400">Updated</span>
                                        <span class="font-medium text-secondary-900 dark:text-white">{{ formatDate(walletInfo.updated_at) }}</span>
                                    </div>
                                </div>
                            </template>
                            <p v-else class="text-sm text-secondary-500 dark:text-secondary-400">Wallet details are not available.</p>
                        </div>

                        <div class="rounded-lg border border-secondary-200 dark:border-secondary-700 p-4 space-y-3">
                            <p class="text-sm font-semibold text-secondary-900 dark:text-white">Top Up Wallet</p>
                            <div>
                                <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Amount</label>
                                <input
                                    v-model.number="walletTopUpForm.amount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800 text-secondary-900 dark:text-white"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Description (optional)</label>
                                <input
                                    v-model="walletTopUpForm.description"
                                    type="text"
                                    placeholder="Wallet top-up note"
                                    class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800 text-secondary-900 dark:text-white"
                                >
                            </div>
                            <button
                                type="button"
                                class="inline-flex items-center justify-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="walletSubmitting"
                                @click="submitWalletTopUp"
                            >
                                {{ walletSubmitting ? 'Processing...' : 'Top Up Wallet' }}
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        <div v-else class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-700 p-6 text-sm text-secondary-500 dark:text-secondary-400">
            Member not found.
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAppContext } from '../composables/useAppContext';
import { apiRequest } from '../composables/useApiClient';

const route = useRoute();
const router = useRouter();
const context = useAppContext();

const memberId = computed(() => route.params.id);
const canUseWallet = computed(() => Boolean(context.permissions?.sales));

const activeTab = ref('profile');
const loadingMember = ref(false);
const member = ref(null);
const permissions = ref({ edit: false, delete: false });

const walletInfo = ref(null);
const walletLoading = ref(false);
const walletSubmitting = ref(false);
const walletTopUpForm = ref({
    amount: null,
    description: '',
});

const statusProcessing = ref(false);
const verificationProcessing = ref(false);
const deleteProcessing = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

const memberFullName = computed(() => {
    if (!member.value) {
        return 'Member';
    }

    const firstName = String(member.value.first_name || '').trim();
    const lastName = String(member.value.last_name || '').trim();

    if (firstName || lastName) {
        return `${firstName} ${lastName}`.trim();
    }

    return member.value.name || 'Member';
});

const memberTitle = computed(() => {
    if (!member.value) {
        return 'View member profile, status, and wallet information.';
    }

    return `${memberFullName.value} (${member.value.member_id || 'No ID'})`;
});

function setPageMessages({ error = '', success = '' } = {}) {
    errorMessage.value = error;
    successMessage.value = success;
}

function displayValue(value) {
    if (value === null || value === undefined || value === '') {
        return '--';
    }

    return value;
}

function capitalize(value = '') {
    return value ? value.charAt(0).toUpperCase() + value.slice(1) : '';
}

function money(value) {
    return Number(value || 0).toFixed(2);
}

function formatDate(value) {
    if (!value) {
        return '--';
    }

    return new Date(value).toLocaleString();
}

async function loadMember() {
    loadingMember.value = true;

    try {
        const response = await apiRequest(`/api/members/${memberId.value}`);
        member.value = response.data || null;
        permissions.value = response.permissions || permissions.value;
    } catch (error) {
        member.value = null;
        setPageMessages({ error: error?.response?.data?.message || 'Failed to load member details.' });
    } finally {
        loadingMember.value = false;
    }
}

async function loadWallet() {
    if (!canUseWallet.value || !memberId.value) {
        walletInfo.value = null;
        return;
    }

    walletLoading.value = true;

    try {
        walletInfo.value = await apiRequest(`/api/wallets/member/${memberId.value}`);
    } catch (error) {
        walletInfo.value = null;
        setPageMessages({ error: error?.response?.data?.message || 'Failed to load wallet details.' });
    } finally {
        walletLoading.value = false;
    }
}

async function toggleStatus() {
    if (!permissions.value.edit || !member.value) {
        return;
    }

    statusProcessing.value = true;
    setPageMessages();

    try {
        const response = await apiRequest(`/api/members/${memberId.value}/toggle-status`, {
            method: 'patch',
        });

        member.value.is_active = Boolean(response.is_active);
        setPageMessages({ success: response.message || 'Member status updated successfully.' });
    } catch (error) {
        setPageMessages({ error: error?.response?.data?.message || 'Failed to update member status.' });
    } finally {
        statusProcessing.value = false;
    }
}

async function toggleVerification() {
    if (!permissions.value.edit || !member.value) {
        return;
    }

    verificationProcessing.value = true;
    setPageMessages();

    try {
        const response = await apiRequest(`/api/members/${memberId.value}/toggle-verification`, {
            method: 'patch',
        });

        member.value.is_verified = Boolean(response.is_verified);
        setPageMessages({ success: response.message || 'Member verification updated successfully.' });
    } catch (error) {
        setPageMessages({ error: error?.response?.data?.message || 'Failed to update member verification.' });
    } finally {
        verificationProcessing.value = false;
    }
}

async function deleteMember() {
    if (!permissions.value.delete || !member.value) {
        return;
    }

    if (!window.confirm('Are you sure you want to delete this member?')) {
        return;
    }

    deleteProcessing.value = true;
    setPageMessages();

    try {
        await apiRequest(`/api/members/${memberId.value}`, { method: 'delete' });
        router.push('/members');
    } catch (error) {
        setPageMessages({ error: error?.response?.data?.message || 'Failed to delete member.' });
    } finally {
        deleteProcessing.value = false;
    }
}

async function submitWalletTopUp() {
    if (!canUseWallet.value) {
        setPageMessages({ error: 'You do not have permission to top up wallets.' });
        return;
    }

    if (Number(walletTopUpForm.value.amount || 0) <= 0) {
        setPageMessages({ error: 'Top-up amount must be greater than zero.' });
        return;
    }

    walletSubmitting.value = true;
    setPageMessages();

    try {
        await apiRequest(`/api/wallets/member/${memberId.value}/top-up`, {
            method: 'post',
            data: {
                amount: Number(walletTopUpForm.value.amount),
                description: walletTopUpForm.value.description || null,
            },
        });

        walletTopUpForm.value.amount = null;
        walletTopUpForm.value.description = '';
        await loadWallet();
        setPageMessages({ success: 'Wallet top-up completed successfully.' });
    } catch (error) {
        setPageMessages({ error: error?.response?.data?.message || 'Failed to top up wallet.' });
    } finally {
        walletSubmitting.value = false;
    }
}

watch(activeTab, (tab) => {
    if (tab === 'wallet') {
        loadWallet();
    }
});

watch(memberId, async () => {
    activeTab.value = 'profile';
    walletInfo.value = null;
    setPageMessages();
    await loadMember();
});

onMounted(async () => {
    await loadMember();
});
</script>
