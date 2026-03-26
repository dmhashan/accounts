<template>
    <section>
        <div class="app-surface rounded-2xl p-4 sm:p-5 md:p-6 mb-4 md:mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.12em] text-secondary-500 dark:text-secondary-400">Inventory</p>
                    <h2 class="text-xl md:text-2xl font-bold text-secondary-900 dark:text-white">{{ isEdit ? 'Edit Product' : 'Add Product' }}</h2>
                    <p class="text-sm text-secondary-500 dark:text-secondary-400">Manage product name and variation list in one form.</p>
                </div>
                <RouterLink to="/inventory" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-secondary-300 dark:border-secondary-700 text-secondary-700 dark:text-secondary-300 text-sm font-semibold transition-all hover:bg-secondary-50 dark:hover:bg-secondary-800">
                    ← Back to Inventory
                </RouterLink>
            </div>
        </div>

        <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ errorMessage }}
        </div>

        <form class="app-surface rounded-2xl p-4 md:p-6" @submit.prevent="submit">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Product Name</label>
                    <input v-model="form.name" type="text" required maxlength="255" class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800">
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300">Variation List</label>
                        <button type="button" class="px-3 py-1.5 text-sm rounded-lg border border-secondary-300 dark:border-secondary-700" @click="addVariation">Add Variation</button>
                    </div>

                    <div class="space-y-2">
                        <div v-for="(variation, index) in form.variations" :key="variation.key" class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-2">
                            <input v-model="variation.name" type="text" maxlength="255" placeholder="Variation name" class="px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800">
                            <button type="button" class="px-3 py-2 text-sm text-red-600 dark:text-red-400 border border-red-200 dark:border-red-700 rounded-lg" @click="removeVariation(index)">Remove</button>
                        </div>
                    </div>

                    <p class="mt-2 text-xs text-secondary-500 dark:text-secondary-400">Empty variation rows are ignored on save.</p>
                </div>
            </div>

            <div class="mt-5 flex items-center justify-end gap-2">
                <RouterLink to="/inventory" class="px-4 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg text-sm">Cancel</RouterLink>
                <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm" :disabled="submitting">
                    {{ submitting ? 'Saving...' : (isEdit ? 'Update Product' : 'Create Product') }}
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
const submitting = ref(false);
const errorMessage = ref('');

let variationKey = 0;

const form = ref({
    name: '',
    variations: [],
});

function addVariation(name = '', id = null) {
    form.value.variations.push({ key: ++variationKey, id, name });
}

function removeVariation(index) {
    form.value.variations.splice(index, 1);
}

async function loadProduct() {
    if (!isEdit.value) {
        addVariation();
        return;
    }

    const response = await apiRequest(`/api/inventory/products/${route.params.id}`);
    form.value.name = response.data?.name || '';

    const variations = response.data?.variations || [];
    if (variations.length === 0) {
        addVariation();
        return;
    }

    form.value.variations = [];
    variations.forEach((variation) => {
        addVariation(variation.name, variation.id);
    });
}

async function submit() {
    submitting.value = true;
    errorMessage.value = '';

    try {
        const payload = {
            name: form.value.name,
            variations: form.value.variations.map((variation) => ({
                id: variation.id ?? undefined,
                name: variation.name,
            })),
        };

        if (isEdit.value) {
            await apiRequest(`/api/inventory/products/${route.params.id}`, {
                method: 'put',
                data: payload,
            });
        } else {
            await apiRequest('/api/inventory/products', {
                method: 'post',
                data: payload,
            });
        }

        router.push('/inventory');
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to save product.';
    } finally {
        submitting.value = false;
    }
}

onMounted(async () => {
    try {
        await loadProduct();
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load product data.';
    }
});
</script>
