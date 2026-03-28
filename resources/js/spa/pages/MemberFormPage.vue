<template>
    <section>
        <div class="app-surface app-page-header-compact">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.12em] text-secondary-500 dark:text-secondary-400">Members</p>
                    <h2 class="text-xl md:text-2xl font-bold text-secondary-900 dark:text-white">{{ isEdit ? 'Edit Member' : 'Add Member' }}</h2>
                    <p class="text-sm text-secondary-500 dark:text-secondary-400">Create and update member profile and billing details.</p>
                </div>
                <RouterLink :to="backRoute" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-secondary-300 dark:border-secondary-700 text-secondary-700 dark:text-secondary-300 text-sm font-semibold transition-all hover:bg-secondary-50 dark:hover:bg-secondary-800">
                    ← {{ backLabel }}
                </RouterLink>
            </div>
        </div>

        <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ errorMessage }}
        </div>

        <form class="app-surface rounded-2xl p-5 md:p-6 space-y-4" @submit.prevent="submit">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm mb-1">First Name</label>
                    <input v-model="form.first_name" required class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800">
                </div>
                <div>
                    <label class="block text-sm mb-1">Last Name</label>
                    <input v-model="form.last_name" required class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800">
                </div>
                <div>
                    <label class="block text-sm mb-1">Username</label>
                    <input v-model="form.username" required class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800">
                </div>
                <div>
                    <label class="block text-sm mb-1">Gender</label>
                    <select v-model="form.gender" required class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800">
                        <option value="">Select gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm mb-1">Email</label>
                    <input v-model="form.email" type="email" required class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800">
                </div>
                <div>
                    <label class="block text-sm mb-1">Phone Number</label>
                    <input v-model="form.phone_number" required class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800">
                </div>
                <div>
                    <label class="block text-sm mb-1">NIC</label>
                    <input v-model="form.nic" class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800">
                </div>
                <div>
                    <label class="block text-sm mb-1">Date of Birth</label>
                    <input v-model="form.date_of_birth" type="date" required class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800">
                </div>
                <div>
                    <label class="block text-sm mb-1">Age</label>
                    <input v-model="form.age" type="number" min="1" max="120" required class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800">
                </div>
                <div>
                    <label class="block text-sm mb-1">Member Role</label>
                    <input v-model="form.member_role" required class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800">
                </div>
                <div>
                    <label class="block text-sm mb-1">Payment Plan</label>
                    <input v-model="form.payment_plan" required class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800">
                </div>
                <div>
                    <label class="block text-sm mb-1">Price</label>
                    <input v-model="form.price" type="number" step="0.01" min="0" required class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800">
                </div>
                <div>
                    <label class="block text-sm mb-1">Admission Fee</label>
                    <input v-model="form.admission_fee" type="number" step="0.01" min="0" class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800">
                </div>
                <div>
                    <label class="block text-sm mb-1">Joined Date</label>
                    <input v-model="form.joined_date" type="date" required class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm mb-1">Address</label>
                    <textarea v-model="form.address" rows="2" class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800"></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm mb-1">Comment</label>
                    <textarea v-model="form.comment" rows="2" class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800"></textarea>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg" :disabled="submitting">
                    {{ submitting ? 'Saving...' : (isEdit ? 'Update Member' : 'Create Member') }}
                </button>
            </div>
        </form>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiRequest } from '../composables/useApiClient';

const route = useRoute();
const router = useRouter();
const isEdit = computed(() => Boolean(route.params.id));
const backRoute = computed(() => (isEdit.value ? `/members/${route.params.id}` : '/members'));
const backLabel = computed(() => (isEdit.value ? 'Back to Member' : 'Back to Members'));
const submitting = ref(false);
const errorMessage = ref('');

const form = ref({
    first_name: '',
    last_name: '',
    username: '',
    gender: '',
    email: '',
    phone_number: '',
    nic: '',
    date_of_birth: '',
    age: '',
    address: '',
    member_role: '',
    admission_fee: '',
    payment_plan: '',
    price: '',
    joined_date: '',
    comment: '',
});

async function loadMember() {
    if (!isEdit.value) return;
    const response = await apiRequest(`/api/members/${route.params.id}`);
    form.value = {
        ...form.value,
        ...response.data,
        age: response.data?.age ? String(response.data.age) : '',
        admission_fee: response.data?.admission_fee ?? '',
        price: response.data?.price ?? '',
    };
}

async function submit() {
    submitting.value = true;
    errorMessage.value = '';

    try {
        let memberId = route.params.id;

        if (isEdit.value) {
            await apiRequest(`/api/members/${route.params.id}`, { method: 'put', data: form.value });
        } else {
            const response = await apiRequest('/api/members', { method: 'post', data: form.value });
            memberId = response?.data?.id;
        }

        router.push(memberId ? `/members/${memberId}` : '/members');
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to save member.';
    } finally {
        submitting.value = false;
    }
}

onMounted(async () => {
    try {
        await loadMember();
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load member.';
    }
});
</script>
