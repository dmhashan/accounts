<template>
  <section class="app-page-frame">
    <AppPageHeader show-back />
    <div class="app-page-scroll">
      <div v-if="errorMessage" role="alert" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-200">
        {{ errorMessage }}
      </div>
      <p v-if="loading" class="p-4 text-sm text-secondary-500">
        Loading product…
      </p>
      <form v-else class="app-surface rounded-2xl p-4 md:p-6" @submit.prevent="submit">
        <fieldset :disabled="submitting || !!reconcileRows || blocked" class="space-y-5 disabled:opacity-70">
          <AppFormField label="Product Name" required>
            <AppFormInput
              v-model="form.name"
              type="text"
              required
              maxlength="255"
            />
          </AppFormField>
          <div>
            <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
              <label for="product-category" class="text-sm font-medium text-secondary-700 dark:text-secondary-300">Category</label>
              <button type="button" class="text-sm font-medium text-primary-600 dark:text-primary-400" @click="showCategories = true">
                Manage categories
              </button>
            </div>
            <select id="product-category" v-model="form.category_id" class="w-full rounded-lg border border-secondary-300 bg-white px-3 py-2 text-sm text-secondary-900 dark:border-secondary-700 dark:bg-secondary-800 dark:text-white">
              <option :value="null">
                Uncategorized
              </option>
              <option v-if="form.category_id !== null && !categories.some((c) => String(c.id) === String(form.category_id))" :value="form.category_id">
                Current category unavailable
              </option>
              <option v-for="category in categories" :key="category.id" :value="category.id">
                {{ category.name }}
              </option>
            </select>
            <p v-if="categoryError" role="alert" class="mt-1 text-xs text-red-600 dark:text-red-400">
              {{ categoryError }} <button type="button" class="underline" @click="loadCategories">
                Retry
              </button>
            </p>
          </div>
          <div class="grid gap-3 sm:grid-cols-2">
            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-secondary-200 p-4 dark:border-secondary-700">
              <input v-model="form.track_stock" type="checkbox" class="mt-1 h-4 w-4 accent-primary-600" />
              <span>
                <span class="block text-sm font-semibold text-secondary-900 dark:text-white">Track stock</span>
                <span class="mt-1 block text-xs text-secondary-500 dark:text-secondary-400">Limit sales to available inventory. Turn off for unlimited products or services.</span>
              </span>
            </label>
            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-secondary-200 p-4 dark:border-secondary-700">
              <input v-model="form.is_active" type="checkbox" class="mt-1 h-4 w-4 accent-primary-600" />
              <span>
                <span class="block text-sm font-semibold text-secondary-900 dark:text-white">Active for sale</span>
                <span class="mt-1 block text-xs text-secondary-500 dark:text-secondary-400">Turn off to hide this product from new sales. Existing sales remain in history.</span>
              </span>
            </label>
          </div>
          <div>
            <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
              <div>
                <h2 class="text-sm font-semibold text-secondary-700 dark:text-secondary-300">
                  Variations
                </h2>
                <p class="text-xs text-secondary-500">
                  Images are cropped to 100 × 100 and uploaded after saving.
                </p>
              </div>
              <button type="button" class="rounded-lg border border-secondary-300 px-3 py-1.5 text-sm dark:border-secondary-700" @click="addVariation()">
                Add Variation
              </button>
            </div>
            <div class="space-y-3">
              <div v-for="(variation, index) in form.variations" :key="variation.key" class="flex flex-wrap items-start gap-3 rounded-xl border border-secondary-200 p-3 dark:border-secondary-700 sm:flex-nowrap">
                <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-secondary-100 dark:bg-secondary-800">
                  <img
                    v-if="variation.preview || (!variation.removeImage && variation.image_url)"
                    :src="variation.preview || variation.image_url"
                    :alt="`${variation.name || 'Variation'} image`"
                    class="h-full w-full object-cover"
                  />
                  <ImageIcon v-else class="h-6 w-6 text-secondary-400" />
                </div>
                <div class="min-w-[12rem] flex-1 space-y-2">
                  <AppFormInput
                    v-model="variation.name"
                    type="text"
                    maxlength="255"
                    placeholder="Variation name"
                    :aria-label="`Variation ${index + 1} name`"
                  />
                  <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                    <label class="cursor-pointer font-medium text-primary-600 dark:text-primary-400">
                      {{ variation.preview || (!variation.removeImage && variation.image_url) ? 'Change image' : 'Choose image' }}
                      <input
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        class="sr-only"
                        :aria-label="`Choose image for variation ${index + 1}`"
                        @change="selectImage($event, variation)"
                      />
                    </label>
                    <button
                      v-if="variation.preview || (!variation.removeImage && variation.image_url)"
                      type="button"
                      class="text-red-600 dark:text-red-400"
                      @click="removeImage(variation)"
                    >
                      Remove image
                    </button>
                    <span v-if="variation.pendingBlob || variation.removeImage" class="text-amber-700 dark:text-amber-400">Pending save</span>
                  </div>
                  <p v-if="variation.imageError" role="alert" class="text-xs text-red-600 dark:text-red-400">
                    {{ variation.imageError }}
                  </p>
                  <div v-if="!form.track_stock" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300">
                      Local selling price
                      <AppFormInput
                        v-model="variation.untracked_local_price"
                        type="number"
                        min="0"
                        step="0.01"
                        :aria-label="`Local selling price for variation ${index + 1}`"
                      />
                    </label>
                    <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300">
                      Foreign selling price
                      <AppFormInput
                        v-model="variation.untracked_foreign_price"
                        type="number"
                        min="0"
                        step="0.01"
                        :aria-label="`Foreign selling price for variation ${index + 1}`"
                      />
                    </label>
                    <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300">
                      Unit cost (optional)
                      <AppFormInput
                        v-model="variation.untracked_unit_cost"
                        type="number"
                        min="0"
                        step="0.0001"
                        :aria-label="`Unit cost for variation ${index + 1}`"
                      />
                    </label>
                  </div>
                </div>
                <button type="button" class="rounded-lg border border-red-200 px-3 py-2 text-sm text-red-600 dark:border-red-700 dark:text-red-400" @click="removeVariation(index)">
                  Remove variation
                </button>
              </div>
            </div>
            <p class="mt-2 text-xs text-secondary-500">
              Empty variation rows are ignored. Variation names must be unique.
            </p>
          </div>
        </fieldset>
        <div class="mt-5 flex flex-wrap items-center justify-end gap-2">
          <RouterLink to="/inventory" class="rounded-lg border border-secondary-300 px-4 py-2 text-sm dark:border-secondary-700">
            Cancel
          </RouterLink>
          <button
            v-if="reconcileRows && productId"
            type="button"
            class="rounded-lg bg-primary-600 px-4 py-2 text-sm text-white disabled:opacity-50"
            :disabled="submitting"
            @click="retryReconcile"
          >
            {{ submitting ? 'Checking…' : 'Retry finding saved variations' }}
          </button>
          <button
            v-else
            type="submit"
            class="rounded-lg bg-primary-600 px-4 py-2 text-sm text-white disabled:opacity-50"
            :disabled="submitting || blocked || !!reconcileRows"
          >
            {{ submitting ? 'Saving…' : (productId ? 'Update Product' : 'Create Product') }}
          </button>
        </div>
      </form>
    </div>
    <ProductCategoryManager
      v-if="showCategories"
      @close="showCategories = false"
      @changed="categoriesChanged"
      @deleted="categoryDeleted"
    />
    <AvatarCropModal
      v-if="cropSrc"
      :image-src="cropSrc"
      title="Crop variation image"
      confirm-label="Use image"
      :output-size="100"
      @confirm="confirmCrop"
      @cancel="cropSrc = null"
    />
  </section>
</template>

<script setup>
import { onMounted, onBeforeUnmount, ref } from 'vue';
import { onBeforeRouteLeave, useRoute, useRouter } from 'vue-router';
import { ImageIcon } from 'lucide-vue-next';
import { apiRequest } from '../composables/useApiClient';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppFormField from '../components/forms/AppFormField.vue';
import AppFormInput from '../components/forms/AppFormInput.vue';
import AvatarCropModal from '../components/AvatarCropModal.vue';
import ProductCategoryManager from '../components/ProductCategoryManager.vue';

const route = useRoute();
const router = useRouter();
const productId = ref(route.params.id || null);
const submitting = ref(false);
const loading = ref(Boolean(productId.value));
const blocked = ref(false);
const errorMessage = ref('');
const categoryError = ref('');
const categories = ref([]);
const showCategories = ref(false);
const cropSrc = ref(null);
const cropVariation = ref(null);
const reconcileRows = ref(null);
const form = ref({ name: '', category_id: null, track_stock: true, is_active: true, variations: [] });
let variationKey = 0;
let allowLeave = false;

function addVariation(name = '', id = null, imageUrl = null, prices = {}) {
  form.value.variations.push({ key: ++variationKey, id, name, image_url: imageUrl, untracked_local_price: prices.untracked_local_price ?? '', untracked_foreign_price: prices.untracked_foreign_price ?? '', untracked_unit_cost: prices.untracked_unit_cost ?? '', pendingBlob: null, preview: null, removeImage: false, imageError: '' });
}
function removeVariation(index) { form.value.variations.splice(index, 1); }
function categoriesChanged(list) { categories.value = list; categoryError.value = ''; }
function categoryDeleted(id) { if (String(form.value.category_id) === String(id)) form.value.category_id = null; }
async function loadCategories() {
    try { const response = await apiRequest('/api/inventory/categories'); categoriesChanged(response.data || []); }
    catch (error) { categoryError.value = error?.response?.data?.message || 'Failed to load categories.'; }
}
async function loadProduct() {
    if (!productId.value) { addVariation(); return; }
    try {
        const response = await apiRequest(`/api/inventory/products/${productId.value}`);
        const product = response.data;
        form.value.name = product.name || '';
        form.value.category_id = product.category_id ?? null;
        form.value.track_stock = product.track_stock ?? true;
        form.value.is_active = product.is_active ?? true;
        (product.variations || []).forEach((variation) => addVariation(variation.name, variation.id, variation.image_url, variation));
        if (!form.value.variations.length) addVariation();
    } catch (error) { errorMessage.value = error?.response?.data?.message || 'Failed to load product.'; blocked.value = true; }
    finally { loading.value = false; }
}
function selectImage(event, variation) {
    const file = event.target.files?.[0]; event.target.value = '';
    if (!file) return;
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) { variation.imageError = 'Choose a JPEG, PNG or WebP image.'; return; }
    variation.imageError = '';
    const reader = new FileReader();
    reader.onload = () => { cropVariation.value = variation; cropSrc.value = reader.result; };
    reader.onerror = () => { variation.imageError = 'Could not read the image.'; };
    reader.readAsDataURL(file);
}
function confirmCrop(blob) {
    const variation = cropVariation.value;
    cropSrc.value = null;
    if (!blob || !variation) return;
    variation.pendingBlob = blob; variation.removeImage = false; variation.imageError = '';
    const reader = new FileReader();
    reader.onload = () => { if (variation.pendingBlob === blob) variation.preview = reader.result; };
    reader.readAsDataURL(blob);
}
function removeImage(variation) {
    variation.pendingBlob = null; variation.preview = null; variation.removeImage = Boolean(variation.image_url); variation.imageError = '';
}
function hasPending() { return Boolean(reconcileRows.value || blocked.value || form.value.variations.some((v) => v.pendingBlob || v.removeImage)); }
onBeforeRouteLeave(() => allowLeave || !hasPending() || window.confirm('There are unsaved image changes. Leave this page?'));
function beforeUnload(event) { if (hasPending()) { event.preventDefault(); event.returnValue = ''; } }
onBeforeUnmount(() => window.removeEventListener('beforeunload', beforeUnload));

// Match new rows by unique submitted name, never by response order.
function reconcile(product, rows) {
    if (!Array.isArray(product?.variations)) throw new Error('Saved variations were not returned.');
    const usedIds = new Set(rows.filter((row) => row.id !== null && row.id !== undefined).map((row) => String(row.id)));
    const assignments = [];
    for (const row of rows.filter((item) => item.id === null || item.id === undefined)) {
      const matches = product.variations.filter((v) => v.id !== null && v.id !== undefined && !usedIds.has(String(v.id)) && v.name === row.name);
        if (matches.length !== 1) throw new Error(`Could not uniquely identify variation "${row.name}".`);
        usedIds.add(String(matches[0].id)); assignments.push([row.key, matches[0].id]);
    }
    for (const [key, id] of assignments) form.value.variations.find((v) => v.key === key).id = id;
}
async function resolveSavedRows(product = null) {
    if (!Array.isArray(product?.variations)) product = (await apiRequest(`/api/inventory/products/${productId.value}`)).data;
    reconcile(product, reconcileRows.value);
    reconcileRows.value = null;
}
async function uploadImages() {
    let failed = false;
    for (const variation of form.value.variations) {
        if (!variation.pendingBlob && !variation.removeImage) continue;
        if (!variation.id) { variation.imageError = 'Save a named variation before uploading its image.'; failed = true; continue; }
        try {
            let response;
            if (variation.pendingBlob) {
                const data = new FormData(); data.append('image', variation.pendingBlob, 'variation.jpg');
                response = await apiRequest(`/api/inventory/variations/${variation.id}/image`, { method: 'post', data });
            } else response = await apiRequest(`/api/inventory/variations/${variation.id}/image`, { method: 'delete' });
            variation.image_url = response.data?.image_url ?? null; variation.pendingBlob = null; variation.preview = null; variation.removeImage = false; variation.imageError = '';
        } catch (error) { variation.imageError = error?.response?.data?.message || 'Image update failed. Save again to retry.'; failed = true; }
    }
    if (failed) throw new Error('Product saved, but some images could not be updated. Your pending images are retained; save again to retry.');
}
async function retryReconcile() {
    submitting.value = true; errorMessage.value = '';
    try { await resolveSavedRows(); await uploadImages(); allowLeave = true; await router.push('/inventory'); }
    catch (error) { errorMessage.value = `Product may already be saved. ${error.message || 'Could not retrieve saved variations.'} Retry without creating it again.`; }
    finally { submitting.value = false; }
}
async function submit() {
    if (submitting.value || blocked.value || reconcileRows.value) return;
    const rows = form.value.variations.filter((v) => v.name.trim()).map((v) => ({ key: v.key, id: v.id, name: v.name.trim(), untracked_local_price: v.untracked_local_price, untracked_foreign_price: v.untracked_foreign_price, untracked_unit_cost: v.untracked_unit_cost }));
    if (form.value.variations.some((v) => !v.name.trim() && (v.pendingBlob || v.removeImage))) { errorMessage.value = 'Enter a variation name for each pending image.'; return; }
    if (new Set(rows.map((v) => v.name.toLocaleLowerCase())).size !== rows.length) { errorMessage.value = 'Variation names must be unique.'; return; }
    const priceFields = ['untracked_local_price', 'untracked_foreign_price', 'untracked_unit_cost'];
    if (rows.some((row) => priceFields.some((field) => row[field] !== '' && row[field] !== null && row[field] !== undefined && (!Number.isFinite(Number(row[field])) || Number(row[field]) < 0)))) { errorMessage.value = 'Variation prices and costs must be non-negative numbers.'; return; }
    submitting.value = true; errorMessage.value = '';
    const payload = { name: form.value.name, category_id: form.value.category_id, track_stock: form.value.track_stock, is_active: form.value.is_active, variations: rows.map(({ id, name, ...row }) => ({ id: id ?? undefined, name, ...Object.fromEntries(priceFields.map((field) => [field, row[field] === '' || row[field] === null || row[field] === undefined ? null : Number(row[field])])) })) };
    let saved = false;
    try {
        const response = await apiRequest(productId.value ? `/api/inventory/products/${productId.value}` : '/api/inventory/products', { method: productId.value ? 'put' : 'post', data: payload });
        saved = true;
        productId.value = productId.value || response.data?.id || null;
        if (!productId.value) { blocked.value = true; throw new Error('Product was saved but its ID was not returned. Check inventory before retrying to avoid duplicates.'); }
        if (rows.some((v) => v.id === null || v.id === undefined)) { reconcileRows.value = rows; await resolveSavedRows(response.data); }
        await uploadImages();
        allowLeave = true; await router.push('/inventory');
    } catch (error) {
        if (!saved && (!error?.response || error.response.status >= 500)) {
          blocked.value = true;
            errorMessage.value = 'The save result is uncertain. Check inventory before trying again to avoid duplicate products or variations.';
        } else errorMessage.value = error?.response?.data?.message || error.message || 'Failed to save product.';
    } finally { submitting.value = false; }
}
onMounted(() => { window.addEventListener('beforeunload', beforeUnload); loadCategories(); loadProduct(); });
</script>
