<template>
    <section class="max-w-5xl">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl md:text-2xl font-bold text-secondary-900 dark:text-white">Edit Role</h2>
            <RouterLink to="/roles" class="text-sm text-primary-600 dark:text-primary-400">Back to Roles</RouterLink>
        </div>

        <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ errorMessage }}
        </div>

        <form class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-700 p-5 md:p-6 mb-4" @submit.prevent="saveRole">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm mb-1">Name</label>
                    <input v-model="role.name" :disabled="!role.is_editable" class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800">
                </div>
                <div>
                    <label class="block text-sm mb-1">Slug</label>
                    <input v-model="role.slug" :disabled="!role.is_editable" class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm mb-1">Description</label>
                    <textarea v-model="role.description" rows="2" :disabled="!role.is_editable" class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800"></textarea>
                </div>
            </div>
            <div class="mt-4 flex justify-end">
                <button type="submit" :disabled="!role.is_editable || savingRole" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg disabled:opacity-50">
                    {{ savingRole ? 'Saving...' : 'Save Role' }}
                </button>
            </div>
        </form>

        <form class="space-y-4" @submit.prevent="savePermissions">
            <article v-for="(group, feature) in permissionsByFeature" :key="feature" class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-700 overflow-hidden">
                <div class="px-4 py-3 bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                    <h3 class="font-semibold text-secondary-900 dark:text-white">{{ feature }}</h3>
                </div>
                <div class="p-4 space-y-3">
                    <label v-for="permission in group" :key="permission.id" class="flex items-start gap-3">
                        <input
                            type="checkbox"
                            :value="permission.id"
                            v-model="selectedPermissionIds"
                            :disabled="!role.is_editable"
                            class="mt-1"
                        >
                        <div>
                            <p class="text-sm font-medium text-secondary-800 dark:text-secondary-200">{{ permission.name }}</p>
                            <p v-if="permission.description" class="text-xs text-secondary-500 dark:text-secondary-400">{{ permission.description }}</p>
                        </div>
                    </label>
                </div>
            </article>

            <div class="flex justify-end">
                <button type="submit" :disabled="!role.is_editable || savingPermissions" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg disabled:opacity-50">
                    {{ savingPermissions ? 'Saving...' : 'Update Permissions' }}
                </button>
            </div>
        </form>
    </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiRequest } from '../composables/useApiClient';

const route = useRoute();
const router = useRouter();
const role = ref({ id: null, name: '', slug: '', description: '', is_editable: true });
const permissionsByFeature = ref({});
const selectedPermissionIds = ref([]);
const errorMessage = ref('');
const savingRole = ref(false);
const savingPermissions = ref(false);

async function loadRole() {
    const response = await apiRequest(`/api/roles/${route.params.id}`);
    role.value = response.role;
    permissionsByFeature.value = response.permissions || {};
    selectedPermissionIds.value = response.role?.permission_ids || [];
}

async function saveRole() {
    if (!role.value.is_editable) return;

    savingRole.value = true;
    errorMessage.value = '';

    try {
        await apiRequest(`/api/roles/${route.params.id}`, {
            method: 'put',
            data: {
                name: role.value.name,
                slug: role.value.slug,
                description: role.value.description,
            },
        });
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to update role.';
    } finally {
        savingRole.value = false;
    }
}

async function savePermissions() {
    if (!role.value.is_editable) return;

    savingPermissions.value = true;
    errorMessage.value = '';

    try {
        await apiRequest(`/api/roles/${route.params.id}/permissions`, {
            method: 'patch',
            data: {
                permissions: selectedPermissionIds.value,
            },
        });
        router.push('/roles');
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to update permissions.';
    } finally {
        savingPermissions.value = false;
    }
}

onMounted(async () => {
    try {
        await loadRole();
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load role data.';
    }
});
</script>
