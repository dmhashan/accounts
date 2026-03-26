<template>
    <section>
        <div class="app-surface rounded-2xl p-4 sm:p-5 md:p-6 mb-4 md:mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.12em] text-secondary-500 dark:text-secondary-400">Roles</p>
                    <h2 class="text-xl md:text-2xl font-bold text-secondary-900 dark:text-white">Create Role</h2>
                    <p class="text-sm text-secondary-500 dark:text-secondary-400">Define a new role with a name, slug, and description.</p>
                </div>
                <RouterLink to="/roles" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-secondary-300 dark:border-secondary-700 text-secondary-700 dark:text-secondary-300 text-sm font-semibold transition-all hover:bg-secondary-50 dark:hover:bg-secondary-800">
                    ← Back to Roles
                </RouterLink>
            </div>
        </div>

        <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ errorMessage }}
        </div>

        <form class="app-surface rounded-2xl p-5 md:p-6" @submit.prevent="createRole">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm mb-1 text-secondary-700 dark:text-secondary-300">Name</label>
                    <input v-model="form.name" type="text" required class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800">
                </div>
                <div>
                    <label class="block text-sm mb-1 text-secondary-700 dark:text-secondary-300">Slug</label>
                    <input v-model="form.slug" type="text" required class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm mb-1 text-secondary-700 dark:text-secondary-300">Description</label>
                    <textarea v-model="form.description" rows="3" class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800"></textarea>
                </div>
            </div>

            <div class="mt-4 flex flex-col sm:flex-row gap-2 sm:justify-end">
                <RouterLink to="/roles" class="inline-flex items-center justify-center px-4 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg text-sm text-secondary-700 dark:text-secondary-200">
                    Cancel
                </RouterLink>
                <button type="submit" :disabled="saving" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg disabled:opacity-50">
                    {{ saving ? 'Creating...' : 'Create Role' }}
                </button>
            </div>
        </form>
    </section>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { apiRequest } from '../composables/useApiClient';

const router = useRouter();
const saving = ref(false);
const errorMessage = ref('');
const form = ref({
    name: '',
    slug: '',
    description: '',
});

async function createRole() {
    saving.value = true;
    errorMessage.value = '';

    try {
        await apiRequest('/api/roles', {
            method: 'post',
            data: {
                name: form.value.name,
                slug: form.value.slug,
                description: form.value.description,
            },
        });

        router.push('/roles');
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to create role.';
    } finally {
        saving.value = false;
    }
}
</script>
