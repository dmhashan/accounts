<template>
  <section class="app-page-frame">
    <AppPageHeader>
      <template #cta-slot>
        <AppHeaderAction
          v-if="activeTab === 'users' && userPermissions.create"
          to="/users/new"
          :icon="UserPlus"
          label="Add User"
        />
        <AppHeaderAction
          v-else-if="activeTab === 'roles' && allowRoleCreate"
          to="/roles/new"
          :icon="ShieldPlus"
          label="Add Role"
        />
      </template>

      <template #extra-slot>
        <AppSearchField
          v-model="search"
          :placeholder="activeTab === 'users' ? 'Search users by name or email' : 'Search roles by name or description'"
          :disabled="loading"
          @search="triggerSearch(1)"
        />
      </template>
    </AppPageHeader>

    <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
      {{ errorMessage }}
    </div>

    <!-- Users Tab -->
    <div v-if="activeTab === 'users'" class="min-h-0 flex flex-1 flex-col">
      <div class="app-page-scroll">
        <div class="app-surface rounded-2xl overflow-hidden">
          <div v-if="loading" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">
            Loading users...
          </div>

          <template v-else>
            <!-- Mobile -->
            <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
              <article
                v-for="user in users"
                :key="user.id"
                class="p-4 space-y-3 cursor-pointer hover:bg-secondary-50 dark:hover:bg-secondary-800/40 transition-colors"
                @click="router.push('/users/' + user.id)"
              >
                <div class="flex items-center gap-3">
                  <MemberAvatar :initials="initials(user.name)" size="sm" />
                  <div class="min-w-0">
                    <p class="text-sm font-semibold text-secondary-900 dark:text-white truncate">
                      {{ user.name }}
                    </p>
                    <p class="text-xs text-secondary-500 dark:text-secondary-400 truncate">
                      {{ user.email }}
                    </p>
                  </div>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-xs text-secondary-500 dark:text-secondary-400">Role</span>
                  <span
                    class="px-2.5 py-1 text-xs font-semibold rounded-full"
                    :class="user.role?.slug === 'admin' ? 'bg-primary-100 text-primary-800 dark:bg-primary-900/30 dark:text-primary-300' : 'bg-secondary-100 text-secondary-800 dark:bg-secondary-900/30 dark:text-secondary-300'"
                  >
                    {{ user.role?.name || 'No Role' }}
                  </span>
                </div>
              </article>
              <div v-if="users.length === 0" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">
                No users found.
              </div>
            </div>

            <!-- Desktop -->
            <div class="hidden md:block app-table-scroll">
              <table class="w-full">
                <thead class="app-table-head-sticky bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                      Name
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                      Email
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                      Role
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                  <tr
                    v-for="user in users"
                    :key="user.id"
                    class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50 cursor-pointer"
                    @click="router.push('/users/' + user.id)"
                  >
                    <td class="px-6 py-4">
                      <div class="flex items-center gap-3">
                        <MemberAvatar :initials="initials(user.name)" size="sm" />
                        <span class="text-sm font-medium text-secondary-900 dark:text-white">{{ user.name }}</span>
                      </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-secondary-900 dark:text-white">
                      {{ user.email }}
                    </td>
                    <td class="px-6 py-4">
                      <span
                        class="px-3 py-1 inline-flex text-xs font-semibold rounded-full"
                        :class="user.role?.slug === 'admin' ? 'bg-primary-100 text-primary-800 dark:bg-primary-900/30 dark:text-primary-300' : 'bg-secondary-100 text-secondary-800 dark:bg-secondary-900/30 dark:text-secondary-300'"
                      >
                        {{ user.role?.name || 'No Role' }}
                      </span>
                    </td>
                  </tr>
                  <tr v-if="users.length === 0">
                    <td colspan="3" class="px-6 py-10 text-center text-sm text-secondary-500 dark:text-secondary-400">
                      No users found.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </template>
        </div>
      </div>

      <div class="app-page-pagination">
        <AppPagination
          :current-page="userMeta.current_page"
          :last-page="userMeta.last_page"
          :per-page="userPerPage"
          :total="userMeta.total"
          :disabled="loading"
          @page-change="loadUsers"
          @limit-change="v => { userPerPage = Number(v); loadUsers(1); }"
        />
      </div>
    </div>

    <!-- Roles Tab -->
    <div v-else-if="activeTab === 'roles'" class="min-h-0 flex flex-1 flex-col">
      <div class="app-page-scroll">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 md:gap-6">
          <article
            v-for="role in filteredRoles"
            :key="role.id"
            class="bg-white dark:bg-secondary-900 rounded-xl shadow-sm border border-secondary-200 dark:border-secondary-700 p-5 md:p-6 cursor-pointer hover:shadow-md transition-shadow"
            @click="router.push('/roles/' + role.id)"
          >
            <div class="flex items-start justify-between gap-2">
              <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">
                {{ role.name }}
              </h3>
              <span v-if="!role.is_editable" class="px-2 py-1 text-xs bg-secondary-100 dark:bg-secondary-700 text-secondary-600 dark:text-secondary-300 rounded">Predefined</span>
            </div>
            <p v-if="role.description" class="mt-1 text-sm text-secondary-600 dark:text-secondary-400">
              {{ role.description }}
            </p>
            <div class="mt-4 space-y-2 text-sm text-secondary-700 dark:text-secondary-300">
              <p>{{ role.users_count }} {{ pluralize(role.users_count, 'user') }}</p>
              <p>{{ role.permissions_count }} {{ pluralize(role.permissions_count, 'permission') }}</p>
            </div>
          </article>
        </div>

        <div v-if="!loading && filteredRoles.length === 0" class="mt-4 text-sm text-secondary-500 dark:text-secondary-400">
          No roles found.
        </div>
      </div>

      <div class="app-page-pagination">
        <AppPagination
          :current-page="roleMeta.current_page"
          :last-page="roleMeta.last_page"
          :per-page="rolePerPage"
          :total="roleMeta.total"
          :disabled="loading"
          @page-change="loadRoles"
          @limit-change="v => { rolePerPage = Number(v); loadRoles(1); }"
        />
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { UserPlus, ShieldPlus } from 'lucide-vue-next';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppHeaderAction from '../components/AppHeaderAction.vue';
import AppSearchField from '../components/AppSearchField.vue';
import AppPagination from '../components/AppPagination.vue';
import MemberAvatar from '../../components/ui/MemberAvatar.vue';
import { apiRequest } from '../composables/useApiClient';
import { useAppContext } from '../composables/useAppContext';

const route = useRoute();
const router = useRouter();
const context = useAppContext();

const _defaultTab = (() => {
    if (route.path === '/settings/roles') return 'roles';
    if (route.path === '/settings' && !context.permissions?.users && context.permissions?.roles) return 'roles';
    return 'users';
})();
const activeTab = ref(_defaultTab);
const search = ref('');
const loading = ref(false);
const errorMessage = ref('');

// ── Users ─────────────────────────────────────────────────────────────────────
const users = ref([]);
const userPermissions = ref({ create: false, edit: false, delete: false });
const userMeta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
const userPerPage = ref(15);

function initials(name = '') {
    return name.split(' ').filter(Boolean).slice(0, 2).map(p => p.charAt(0).toUpperCase()).join('') || 'U';
}

async function loadUsers(page = 1) {
    loading.value = true;
    errorMessage.value = '';
    try {
        const response = await apiRequest('/api/users', { params: { page, per_page: userPerPage.value, search: search.value } });
        users.value = response.data || [];
        userPermissions.value = response.permissions || userPermissions.value;
        userMeta.value = response.meta || userMeta.value;
        userPerPage.value = userMeta.value.per_page || userPerPage.value;
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load users.';
    } finally {
        loading.value = false;
    }
}

// ── Roles ─────────────────────────────────────────────────────────────────────
const roles = ref([]);
const allowRoleCreate = ref(false);
const roleMeta = ref({ current_page: 1, last_page: 1, per_page: 12, total: 0 });
const rolePerPage = ref(12);

const filteredRoles = computed(() => {
    const query = search.value.trim().toLowerCase();
    if (!query) return roles.value;
    return roles.value.filter(role =>
        [role.name, role.slug, role.description].filter(Boolean).some(v => String(v).toLowerCase().includes(query))
    );
});

function pluralize(count, noun) {
    return count === 1 ? noun : `${noun}s`;
}

async function loadRoles(page = 1) {
    loading.value = true;
    errorMessage.value = '';
    try {
        const response = await apiRequest('/api/roles', { params: { page, per_page: rolePerPage.value } });
        roles.value = response.data || [];
        roleMeta.value = response.meta || roleMeta.value;
        rolePerPage.value = roleMeta.value.per_page || rolePerPage.value;
        allowRoleCreate.value = Boolean(response.permissions?.managePermissions);
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load roles.';
    } finally {
        loading.value = false;
    }
}

// ── Tab switching ─────────────────────────────────────────────────────────────
function switchTab(tab) {
    activeTab.value = tab;
    search.value = '';
    errorMessage.value = '';

    if (tab === 'users' && users.value.length === 0) loadUsers(1);
    if (tab === 'roles' && roles.value.length === 0) loadRoles(1);
}

function triggerSearch(page = 1) {
    if (activeTab.value === 'users') loadUsers(page);
    else loadRoles(page);
}

onMounted(() => {
    loadUsers();
    loadRoles();
});

watch(
    () => route.path,
    (path) => {
        const newTab = path === '/settings/roles' ? 'roles' : 'users';
        if (activeTab.value !== newTab) switchTab(newTab);
    }
);
</script>
