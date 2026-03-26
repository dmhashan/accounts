<template>
    <section>
        <div class="app-surface rounded-2xl p-4 sm:p-5 md:p-6 mb-4 md:mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.12em] text-secondary-500 dark:text-secondary-400">Users</p>
                    <h2 class="text-xl md:text-2xl font-bold text-secondary-900 dark:text-white">{{ isEdit ? 'Edit User' : 'Add User' }}</h2>
                    <p class="text-sm text-secondary-500 dark:text-secondary-400">Manage system user accounts and role assignments.</p>
                </div>
                <RouterLink to="/users" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-secondary-300 dark:border-secondary-700 text-secondary-700 dark:text-secondary-300 text-sm font-semibold transition-all hover:bg-secondary-50 dark:hover:bg-secondary-800">
                    ← Back to Users
                </RouterLink>
            </div>
        </div>

        <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ errorMessage }}
        </div>

        <form class="app-surface rounded-2xl p-5 md:p-6 space-y-4" @submit.prevent="submit">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-1 text-secondary-700 dark:text-secondary-300">Name</label>
                    <input v-model="form.name" type="text" required class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-1 text-secondary-700 dark:text-secondary-300">Email</label>
                    <input v-model="form.email" type="email" required class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1 text-secondary-700 dark:text-secondary-300">Role</label>
                    <select v-model="form.role_id" required class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800">
                        <option value="">Select role</option>
                        <option v-for="role in roles" :key="role.id" :value="String(role.id)">{{ role.name }}</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1 text-secondary-700 dark:text-secondary-300">Password {{ isEdit ? '(optional)' : '' }}</label>
                    <input v-model="form.password" type="password" :required="!isEdit" class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-secondary-700 dark:text-secondary-300">Confirm Password</label>
                    <input v-model="form.password_confirmation" type="password" :required="!isEdit" class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800">
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg" :disabled="submitting">
                    {{ submitting ? 'Saving...' : (isEdit ? 'Update User' : 'Create User') }}
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

const roles = ref([]);
const submitting = ref(false);
const errorMessage = ref('');
const form = ref({
    name: '',
    email: '',
    role_id: '',
    password: '',
    password_confirmation: '',
});

async function loadMeta() {
    const response = await apiRequest('/api/users/meta');
    roles.value = response.roles || [];
}

async function loadUser() {
    if (!isEdit.value) return;
    const response = await apiRequest(`/api/users/${route.params.id}`);
    form.value.name = response.data?.name || '';
    form.value.email = response.data?.email || '';
    form.value.role_id = String(response.data?.role_id || '');
}

async function submit() {
    submitting.value = true;
    errorMessage.value = '';

    try {
        const payload = {
            name: form.value.name,
            email: form.value.email,
            role_id: Number(form.value.role_id),
            password: form.value.password || undefined,
            password_confirmation: form.value.password_confirmation || undefined,
        };

        if (isEdit.value) {
            await apiRequest(`/api/users/${route.params.id}`, { method: 'put', data: payload });
        } else {
            await apiRequest('/api/users', { method: 'post', data: payload });
        }

        router.push('/users');
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to save user.';
    } finally {
        submitting.value = false;
    }
}

onMounted(async () => {
    try {
        await loadMeta();
        await loadUser();
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load form data.';
    }
});
</script>
