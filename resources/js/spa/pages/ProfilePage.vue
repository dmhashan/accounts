<template>
    <section class="max-w-6xl mx-auto space-y-4 md:space-y-5">
        <div class="app-surface rounded-2xl p-4 sm:p-5 md:p-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.12em] text-secondary-500 dark:text-secondary-400">Profile</p>
                    <h2 class="text-xl md:text-2xl font-bold text-secondary-900 dark:text-white">Account Profile</h2>
                    <p class="text-sm text-secondary-500 dark:text-secondary-400">View your account and linked member details.</p>
                </div>

                <button
                    type="button"
                    class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-gradient-to-r from-primary-500 to-primary-700 text-white font-semibold transition-all hover:brightness-110 disabled:opacity-60 disabled:cursor-not-allowed"
                    :disabled="loading"
                    @click="loadProfile"
                >
                    {{ loading ? 'Refreshing...' : 'Refresh Profile' }}
                </button>
            </div>
        </div>

        <div v-if="errorMessage" class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ errorMessage }}
        </div>

        <article class="app-surface rounded-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-primary-500 to-primary-700 px-4 py-5 md:px-6 md:py-6">
                <div class="flex flex-col sm:flex-row sm:items-center gap-4 md:gap-5">
                    <MemberAvatar
                            :src="member?.profile_photo_url"
                            :initials="initials"
                            size="xl"
                            shape="circle"
                            variant="glass"
                        />

                    <div class="min-w-0">
                        <h2 class="text-xl md:text-2xl font-bold text-white truncate">{{ fullName }}</h2>
                        <p class="text-sm text-primary-100 truncate">{{ member?.member_id || 'No linked member ID' }}</p>

                        <div class="mt-2 flex flex-wrap gap-2">
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-white/20 text-white border border-white/30">
                                {{ account.role || 'Account' }}
                            </span>

                            <span
                                v-if="member"
                                class="px-2.5 py-1 text-xs font-semibold rounded-full"
                                :class="member.is_active
                                    ? 'bg-green-100 text-green-800 border border-green-200'
                                    : 'bg-red-100 text-red-800 border border-red-200'"
                            >
                                {{ member.is_active ? 'Active' : 'Inactive' }}
                            </span>

                            <span
                                v-if="member"
                                class="px-2.5 py-1 text-xs font-semibold rounded-full"
                                :class="member.is_verified
                                    ? 'bg-blue-100 text-blue-800 border border-blue-200'
                                    : 'bg-yellow-100 text-yellow-800 border border-yellow-200'"
                            >
                                {{ member.is_verified ? 'Verified' : 'Pending Verification' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-4 md:p-6">
                <div v-if="loading" class="text-sm text-secondary-500 dark:text-secondary-400">Loading profile details...</div>

                <template v-else>
                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 md:gap-5">
                        <article class="app-surface-soft rounded-xl p-4 md:p-5">
                            <h3 class="text-sm font-semibold text-secondary-900 dark:text-white mb-3">Personal Information</h3>
                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                                <div>
                                    <dt class="text-secondary-500 dark:text-secondary-400">Email</dt>
                                    <dd class="mt-1 text-secondary-900 dark:text-white break-all">{{ member?.email || account.email || 'Not provided' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-secondary-500 dark:text-secondary-400">Username</dt>
                                    <dd class="mt-1 text-secondary-900 dark:text-white">{{ member?.username || account.username || 'Not provided' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-secondary-500 dark:text-secondary-400">Phone Number</dt>
                                    <dd class="mt-1 text-secondary-900 dark:text-white">{{ member?.phone_number || 'Not provided' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-secondary-500 dark:text-secondary-400">Gender</dt>
                                    <dd class="mt-1 text-secondary-900 dark:text-white">{{ normalizedGender }}</dd>
                                </div>
                                <div>
                                    <dt class="text-secondary-500 dark:text-secondary-400">NIC</dt>
                                    <dd class="mt-1 text-secondary-900 dark:text-white">{{ member?.nic || 'Not provided' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-secondary-500 dark:text-secondary-400">Age</dt>
                                    <dd class="mt-1 text-secondary-900 dark:text-white">{{ member?.age ?? 'Not provided' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-secondary-500 dark:text-secondary-400">Date of Birth</dt>
                                    <dd class="mt-1 text-secondary-900 dark:text-white">{{ formatDate(member?.date_of_birth) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-secondary-500 dark:text-secondary-400">Address</dt>
                                    <dd class="mt-1 text-secondary-900 dark:text-white">{{ member?.address || 'Not provided' }}</dd>
                                </div>
                            </dl>
                        </article>

                        <article class="app-surface-soft rounded-xl p-4 md:p-5">
                            <h3 class="text-sm font-semibold text-secondary-900 dark:text-white mb-3">Membership Details</h3>
                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                                <div>
                                    <dt class="text-secondary-500 dark:text-secondary-400">Member ID</dt>
                                    <dd class="mt-1 text-secondary-900 dark:text-white">{{ member?.member_id || 'Not provided' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-secondary-500 dark:text-secondary-400">Member Role</dt>
                                    <dd class="mt-1 text-secondary-900 dark:text-white">{{ member?.member_role || 'Not provided' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-secondary-500 dark:text-secondary-400">Payment Plan</dt>
                                    <dd class="mt-1 text-secondary-900 dark:text-white">{{ member?.payment_plan || 'Not provided' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-secondary-500 dark:text-secondary-400">Price</dt>
                                    <dd class="mt-1 text-secondary-900 dark:text-white">{{ formatMoney(member?.price) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-secondary-500 dark:text-secondary-400">Admission Fee</dt>
                                    <dd class="mt-1 text-secondary-900 dark:text-white">{{ formatMoney(member?.admission_fee) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-secondary-500 dark:text-secondary-400">Current Balance</dt>
                                    <dd class="mt-1 text-secondary-900 dark:text-white">{{ formatMoney(member?.current_balance) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-secondary-500 dark:text-secondary-400">Joined Date</dt>
                                    <dd class="mt-1 text-secondary-900 dark:text-white">{{ formatDate(member?.joined_date) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-secondary-500 dark:text-secondary-400">Member Since</dt>
                                    <dd class="mt-1 text-secondary-900 dark:text-white">{{ formatDate(member?.created_at) }}</dd>
                                </div>
                            </dl>
                        </article>

                        <article v-if="member?.comment" class="app-surface-soft rounded-xl p-4 md:p-5 xl:col-span-2">
                            <h3 class="text-sm font-semibold text-secondary-900 dark:text-white mb-2">Notes</h3>
                            <p class="text-sm text-secondary-700 dark:text-secondary-300 whitespace-pre-line">{{ member.comment }}</p>
                        </article>
                    </div>

                    <p v-if="!member" class="mt-4 text-sm text-amber-700 dark:text-amber-300 rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 px-4 py-3">
                        No member profile is linked to this account yet. Basic account details are shown above.
                    </p>
                </template>
            </div>
        </article>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { apiRequest } from '../composables/useApiClient';
import MemberAvatar from '../../components/ui/MemberAvatar.vue';

const loading = ref(false);
const errorMessage = ref('');
const profile = ref({
    account: {},
    member: null,
});

const moneyFormatter = new Intl.NumberFormat(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

const account = computed(() => profile.value.account || {});
const member = computed(() => profile.value.member);

const fullName = computed(() => member.value?.name || account.value.name || 'Profile');

const initials = computed(() => {
    const value = fullName.value
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');

    return value || 'PR';
});

const normalizedGender = computed(() => {
    if (!member.value?.gender) {
        return 'Not provided';
    }

    return member.value.gender.charAt(0).toUpperCase() + member.value.gender.slice(1);
});

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

async function loadProfile() {
    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await apiRequest('/api/profile');
        profile.value = {
            account: response?.data?.account || {},
            member: response?.data?.member || null,
        };
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load profile details.';
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    loadProfile();
});
</script>
