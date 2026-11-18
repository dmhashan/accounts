<template>
  <Teleport to="body">
    <div
      class="fixed inset-0 z-[70] flex items-center justify-center bg-black/60 p-3"
      @click.self="close"
    >
      <section
        role="dialog"
        aria-modal="true"
        aria-labelledby="category-title"
        class="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-secondary-900"
      >
        <header class="flex items-center justify-between border-b border-secondary-200 px-5 py-4 dark:border-secondary-700">
          <div>
            <h2
              id="category-title"
              class="font-semibold text-secondary-900 dark:text-white"
            >
              Product categories
            </h2>
            <p class="text-xs text-secondary-500">
              Organize products with a color and icon.
            </p>
          </div>
          <button
            type="button"
            class="rounded-lg px-2 py-1 text-sm text-secondary-600 dark:text-secondary-300"
            :disabled="saving"
            @click="close"
          >
            Close
          </button>
        </header>
        <div class="overflow-y-auto p-5 space-y-5">
          <p
            v-if="error"
            role="alert"
            class="rounded-lg bg-red-50 p-3 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-200"
          >
            {{ error }}
          </p>
          <p v-if="loading" class="text-sm text-secondary-500">
            Loading categories…
          </p>
          <div v-else class="space-y-2">
            <p
              v-if="!categories.length"
              class="text-sm text-secondary-500"
            >
              No categories yet. Add one below.
            </p>
            <div
              v-for="category in categories"
              :key="category.id"
              class="flex flex-wrap items-center gap-3 rounded-xl border border-secondary-200 p-3 dark:border-secondary-700"
            >
              <span
                class="flex h-9 w-9 items-center justify-center rounded-lg"
                :class="[getColorClasses(category.color).bg, getColorClasses(category.color).text]"
              >
                <component
                  :is="categoryIcon(category.icon)"
                  class="h-5 w-5"
                />
              </span>
              <span
                class="min-w-0 flex-1 break-words text-sm font-medium text-secondary-900 dark:text-white"
              >
                {{ category.name }}
                <span class="block text-xs font-normal text-secondary-500">
                  {{ category.color }} · {{ category.icon }}
                </span>
              </span>
              <button
                type="button"
                class="px-2 py-1 text-sm text-primary-600 dark:text-primary-400"
                :disabled="saving"
                @click="edit(category)"
              >
                Edit
              </button>
              <button
                type="button"
                class="px-2 py-1 text-sm text-red-600 dark:text-red-400"
                :disabled="saving"
                @click="deleting = category"
              >
                Delete
              </button>
            </div>
          </div>
          <form
            class="space-y-4 border-t border-secondary-200 pt-4 dark:border-secondary-700"
            @submit.prevent="save"
          >
            <h3 class="text-sm font-semibold text-secondary-900 dark:text-white">
              {{ draft.id ? 'Edit category' : 'Add category' }}
            </h3>
            <label class="block text-sm text-secondary-700 dark:text-secondary-300">
              Name
              <input
                v-model.trim="draft.name"
                required
                maxlength="255"
                class="mt-1 w-full rounded-lg border border-secondary-300 bg-white px-3 py-2 dark:border-secondary-600 dark:bg-secondary-800"
              />
            </label>
            <div class="grid gap-3 sm:grid-cols-2">
              <label class="text-sm text-secondary-700 dark:text-secondary-300">
                Color
                <select
                  v-model="draft.color"
                  class="mt-1 w-full rounded-lg border border-secondary-300 bg-white px-3 py-2 dark:border-secondary-600 dark:bg-secondary-800"
                >
                  <option
                    v-for="color in colors"
                    :key="color.key"
                    :value="color.key"
                  >
                    {{ color.label }}
                  </option>
                </select>
              </label>
              <label class="text-sm text-secondary-700 dark:text-secondary-300">
                Icon
                <select
                  v-model="draft.icon"
                  class="mt-1 w-full rounded-lg border border-secondary-300 bg-white px-3 py-2 dark:border-secondary-600 dark:bg-secondary-800"
                >
                  <option
                    v-for="icon in icons"
                    :key="icon.key"
                    :value="icon.key"
                  >
                    {{ icon.label }}
                  </option>
                </select>
              </label>
            </div>
            <div class="flex items-center gap-3">
              <span
                class="flex h-9 w-9 items-center justify-center rounded-lg"
                :class="[getColorClasses(draft.color).bg, getColorClasses(draft.color).text]"
              >
                <component
                  :is="categoryIcon(draft.icon)"
                  class="h-5 w-5"
                />
              </span>
              <span class="text-sm text-secondary-600 dark:text-secondary-300">
                {{ draft.name || 'Preview' }}
              </span>
            </div>
            <div class="flex flex-wrap justify-end gap-2">
              <button
                v-if="draft.id"
                type="button"
                class="rounded-lg border border-secondary-300 px-4 py-2 text-sm dark:border-secondary-700"
                :disabled="saving"
                @click="reset"
              >
                Cancel edit
              </button>
              <button
                type="submit"
                class="rounded-lg bg-primary-600 px-4 py-2 text-sm text-white disabled:opacity-50"
                :disabled="saving || loading"
              >
                {{ saving ? 'Saving…' : (draft.id ? 'Update category' : 'Add category') }}
              </button>
            </div>
          </form>
        </div>
      </section>
    </div>
    <AppConfirmModal
      v-if="deleting"
      title="Delete category"
      :message="`Delete category '${deleting.name}'? Its products will become uncategorized.`"
      :loading="saving"
      confirm-label="Delete"
      @cancel="deleting = null"
      @confirm="deleteCategory"
    />
  </Teleport>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { Tag, Package, Dumbbell, Shirt, Coffee, Apple, Droplets, Boxes } from 'lucide-vue-next';
import { apiRequest } from '../composables/useApiClient';
import { predefinedColors as colors, getColorClasses } from '../utils/paymentMethodHelper';
import AppConfirmModal from './AppConfirmModal.vue';

const emit = defineEmits(['close', 'changed', 'deleted']);
const icons = [
  { key: 'Tag', label: 'Tag', component: Tag },
  { key: 'Package', label: 'Package', component: Package },
  { key: 'Dumbbell', label: 'Dumbbell', component: Dumbbell },
  { key: 'Shirt', label: 'Shirt', component: Shirt },
  { key: 'Coffee', label: 'Coffee', component: Coffee },
  { key: 'Apple', label: 'Apple', component: Apple },
  { key: 'Droplets', label: 'Droplets', component: Droplets },
  { key: 'Boxes', label: 'Boxes', component: Boxes },
];
const categoryIcon = (key) => icons.find((item) => item.key === key)?.component || Tag;
const categories = ref([]);
const loading = ref(false);
const saving = ref(false);
const error = ref('');
const deleting = ref(null);
const draft = ref({ id: null, name: '', color: 'slate', icon: 'Tag' });
function reset() { draft.value = { id: null, name: '', color: 'slate', icon: 'Tag' }; }
function edit(category) { draft.value = { id: category.id, name: category.name, color: category.color, icon: category.icon }; }
function close() { if (!saving.value) emit('close'); }
async function load() {
    loading.value = true;
    try { const response = await apiRequest('/api/inventory/categories'); categories.value = response.data || []; emit('changed', categories.value); }
    catch (err) { error.value = err?.response?.data?.message || 'Failed to load categories.'; }
    finally { loading.value = false; }
}
async function save() {
    if (!draft.value.name) return;
    saving.value = true; error.value = '';
    try {
        const { id, name, color, icon } = draft.value;
        await apiRequest(id ? `/api/inventory/categories/${id}` : '/api/inventory/categories', { method: id ? 'put' : 'post', data: { name, color, icon } });
        reset(); await load();
    } catch (err) { error.value = err?.response?.data?.message || 'Failed to save category.'; }
    finally { saving.value = false; }
}
async function deleteCategory() {
    saving.value = true; error.value = '';
    try { await apiRequest(`/api/inventory/categories/${deleting.value.id}`, { method: 'delete' }); emit('deleted', deleting.value.id); if (draft.value.id === deleting.value.id) reset(); deleting.value = null; await load(); }
    catch (err) { deleting.value = null; error.value = err?.response?.data?.message || 'Failed to delete category.'; }
    finally { saving.value = false; }
}
onMounted(load);
</script>