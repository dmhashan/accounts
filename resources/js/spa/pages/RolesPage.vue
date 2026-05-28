<template>
  <section class="app-page-frame">
    <AppPageHeader>
      <template #cta-slot>
        <AppHeaderAction
          v-if="allowRoleCreate"
          to="/roles/new"
          :icon="ShieldPlus"
          label="Add Role"
        />
      </template>

      <template #extra-slot>
        <AppSearchField
          v-model="search"
          placeholder="Search roles by name, description, or slug"
          :disabled="loading"
          @search="loadRoles(1)"
        />
      </template>
    </AppPageHeader>

    <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
      {{ errorMessage }}
    </div>

    <div class="min-h-0 flex flex-1 flex-col">
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
          :current-page="meta.current_page"
          :last-page="meta.last_page"
          :per-page="perPage"
          :total="meta.total"
          :disabled="loading"
          @page-change="handlePageChange"
          @limit-change="handleLimitChange"
        />
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import AppPagination from '../components/AppPagination.vue';
import AppHeaderAction from '../components/AppHeaderAction.vue';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppSearchField from '../components/AppSearchField.vue';
import { ShieldPlus } from 'lucide-vue-next';
import { apiRequest } from '../composables/useApiClient';

const loading = ref(false);
const router = useRouter();
const roles = ref([]);
const errorMessage = ref('');
const allowRoleCreate = ref(false);
const search = ref('');
const meta = ref({ current_page: 1, last_page: 1, per_page: 12, total: 0 });
const perPage = ref(12);

const filteredRoles = computed(() => {
    const query = search.value.trim().toLowerCase();

    if (!query) {
        return roles.value;
    }

    return roles.value.filter((role) => {
        return [role.name, role.slug, role.description]
            .filter(Boolean)
            .some((value) => String(value).toLowerCase().includes(query));
    });
});

function pluralize(count, noun) {
    return count === 1 ? noun : `${noun}s`;
}

async function loadRoles(page = 1) {
    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await apiRequest('/api/roles', {
            params: {
                page,
                per_page: perPage.value,
            },
        });
        roles.value = response.data || [];
        meta.value = response.meta || meta.value;
        perPage.value = meta.value.per_page || perPage.value;
        allowRoleCreate.value = Boolean(response.permissions?.managePermissions);
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load roles.';
    } finally {
        loading.value = false;
    }
}

function handlePageChange(page) {
    loadRoles(page);
}

function handleLimitChange(limit) {
    perPage.value = Number(limit);
    loadRoles(1);
}

onMounted(() => {
    loadRoles();
});
</script>
