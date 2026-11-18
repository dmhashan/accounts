import { mount, flushPromises } from '@vue/test-utils';
import { reactive } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import InventoryPage from '../../spa/pages/InventoryPage.vue';
import InventoryCategoryFilters from '../../spa/components/InventoryCategoryFilters.vue';
import { apiRequest } from '../../spa/composables/useApiClient';

const route = reactive({ path: '/inventory' });
const permissions = reactive({});
vi.mock('vue-router', () => ({ useRoute: () => route, useRouter: () => ({ push: vi.fn() }) }));
vi.mock('../../spa/composables/useApiClient', () => ({ apiRequest: vi.fn() }));
vi.mock('../../spa/composables/useAppContext', () => ({ useAppContext: () => ({ permissions }) }));

const categories = [
    { id: 7, name: 'Drinks', color: 'blue', icon: 'Coffee' },
    { id: 8, name: 'Snacks', color: 'unknown', icon: 'unknown' },
];
const meta = (page = 1, perPage = 10) => ({ current_page: page, last_page: 4, per_page: perPage, total: 32 });
const deferred = () => {
    let resolve, reject;
    const promise = new Promise((res, rej) => { resolve = res; reject = rej; });
    return { promise, resolve, reject };
};
const filterButton = (wrapper, label) => wrapper.getComponent(InventoryCategoryFilters).findAll('button').find((button) => button.text() === label);
const requestFor = (path) => apiRequest.mock.calls.filter(([url]) => url === `/api/inventory/${path}`).at(-1)?.[1]?.params;
const stubs = {
    AppPageHeader: { template: '<div><slot name="cta-slot" /><slot name="extra-slot" /></div>' },
    AppHeaderAction: true,
    AppSearchField: true,
    AppPagination: { props: ['currentPage', 'perPage', 'disabled'], emits: ['page-change', 'limit-change'], template: '<div><button @click="$emit(\'page-change\', 3)">Page 3</button><button @click="$emit(\'limit-change\', 25)">25 per page</button></div>' },
    ProductCategoryManager: { emits: ['close', 'changed', 'deleted'], template: '<div class="category-manager" />' },
};

function mountPage() { return mount(InventoryPage, { global: { stubs } }); }

describe('inventory product filters', () => {
    beforeEach(() => {
        vi.resetAllMocks();
        route.path = '/inventory';
        for (const key of Object.keys(permissions)) delete permissions[key];
        Object.assign(permissions, { inventoryProducts: true, inventoryStock: true, inventoryDisplay: true });
        apiRequest.mockImplementation(async (url, options = {}) => url.endsWith('/category-filters')
            ? { data: categories }
            : { data: [], meta: meta(options.params?.page, options.params?.per_page) });
    });

    it('renders all choices with safe category styles, icons, and accessible selection', async () => {
        const wrapper = mount(InventoryCategoryFilters, { props: { categories, modelValue: 'all' } });
        const buttons = wrapper.findAll('button');
        expect(buttons.map((button) => button.text())).toEqual(['All', 'Drinks', 'Snacks', 'None', 'Disabled']);
        expect(buttons[0].attributes('aria-pressed')).toBe('true');
        expect(buttons[1].classes()).toContain('bg-blue-100');
        expect(buttons[2].classes()).toContain('bg-slate-100');
        expect(buttons[1].find('svg').classes()).toContain('lucide-coffee');
        expect(buttons[2].find('svg').classes()).toContain('lucide-tag');
        await buttons[1].trigger('click');
        expect(wrapper.emitted('update:modelValue')[0]).toEqual(['category:7']);
        expect(wrapper.text()).toContain('Disabled shows inactive products');
        wrapper.unmount();
    });

    it.each(['products', 'stock', 'display'])('sends each filter and resets pagination on %s', async (tab) => {
        route.path = tab === 'products' ? '/inventory' : `/inventory/${tab}`;
        const wrapper = mountPage(); await flushPromises();
        expect(requestFor(tab)).toEqual({ page: 1, per_page: 10, product_filter: 'all' });
        await wrapper.findAll('button').find((button) => button.text() === '25 per page').trigger('click'); await flushPromises();
        await wrapper.findAll('button').find((button) => button.text() === 'Page 3').trigger('click'); await flushPromises();
        expect(requestFor(tab)).toEqual({ page: 3, per_page: 25, product_filter: 'all' });
        for (const [label, value] of [['Drinks', 'category:7'], ['None', 'none'], ['Disabled', 'disabled'], ['All', 'all']]) {
            await filterButton(wrapper, label).trigger('click'); await flushPromises();
            expect(requestFor(tab)).toEqual({ page: 1, per_page: 25, product_filter: value });
            expect(filterButton(wrapper, label).attributes('aria-pressed')).toBe('true');
        }
        wrapper.unmount();
    });

    it('retains the selected filter across tabs and reloads even previously visited tabs', async () => {
        const wrapper = mountPage(); await flushPromises();
        await filterButton(wrapper, 'Drinks').trigger('click'); await flushPromises();
        route.path = '/inventory/stock'; await flushPromises();
        expect(requestFor('stock').product_filter).toBe('category:7');
        await filterButton(wrapper, 'Disabled').trigger('click'); await flushPromises();
        route.path = '/inventory/display'; await flushPromises();
        expect(requestFor('display')).toMatchObject({ page: 1, product_filter: 'disabled' });
        route.path = '/inventory'; await flushPromises();
        expect(requestFor('products')).toMatchObject({ page: 1, product_filter: 'disabled' });
        expect(filterButton(wrapper, 'Disabled').attributes('aria-pressed')).toBe('true');
        route.path = '/inventory/audit'; await flushPromises();
        expect(wrapper.findComponent(InventoryCategoryFilters).exists()).toBe(false);
        wrapper.unmount();
    });

    it.each([['stock', 'inventoryStock'], ['display', 'inventoryDisplay']])('loads categories for a %s-only user without product management permission', async (tab, permission) => {
        for (const key of Object.keys(permissions)) delete permissions[key];
        permissions[permission] = true;
        route.path = `/inventory/${tab}`;
        const wrapper = mountPage(); await flushPromises();
        expect(filterButton(wrapper, 'Drinks')).toBeTruthy();
        expect(apiRequest.mock.calls.some(([url]) => url === '/api/inventory/categories')).toBe(false);
        expect(apiRequest.mock.calls.some(([url]) => url === '/api/inventory/products')).toBe(false);
        expect(wrapper.text()).not.toContain('Manage categories');
        await filterButton(wrapper, 'None').trigger('click'); await flushPromises();
        expect(requestFor(tab).product_filter).toBe('none');
        wrapper.unmount();
    });

    it('does not request inventory data or categories for an audit-only user', async () => {
        for (const key of Object.keys(permissions)) delete permissions[key];
        permissions.inventoryAudit = true; route.path = '/inventory/audit';
        const wrapper = mountPage(); await flushPromises();
        expect(wrapper.findComponent(InventoryCategoryFilters).exists()).toBe(false);
        expect(apiRequest.mock.calls.map(([url]) => url)).toEqual(['/api/inventory/audit-logs']);
        wrapper.unmount();
    });

    it('refreshes tags and records on category changes and falls back after deletion', async () => {
        let currentCategories = [...categories];
        apiRequest.mockImplementation(async (url, options = {}) => url.endsWith('/category-filters')
            ? { data: currentCategories }
            : { data: [], meta: meta(options.params?.page, options.params?.per_page) });
        const wrapper = mountPage(); await flushPromises();
        await filterButton(wrapper, 'Drinks').trigger('click'); await flushPromises();
        await wrapper.findAll('button').find((button) => button.text() === 'Manage categories').trigger('click');
        currentCategories = [{ ...categories[0], name: 'Beverages' }, categories[1]];
        wrapper.findComponent(stubs.ProductCategoryManager).vm.$emit('changed'); await flushPromises();
        expect(filterButton(wrapper, 'Beverages').attributes('aria-pressed')).toBe('true');
        expect(requestFor('products').product_filter).toBe('category:7');
        currentCategories = [categories[1]];
        wrapper.findComponent(stubs.ProductCategoryManager).vm.$emit('deleted', 7); await flushPromises();
        expect(filterButton(wrapper, 'Beverages')).toBeUndefined();
        expect(filterButton(wrapper, 'All').attributes('aria-pressed')).toBe('true');
        expect(requestFor('products')).toMatchObject({ page: 1, product_filter: 'all' });
        wrapper.unmount();
    });

    it('refreshes on manager close and resets a selected category that no longer exists', async () => {
        let currentCategories = [...categories];
        apiRequest.mockImplementation(async (url, options = {}) => url.endsWith('/category-filters')
            ? { data: currentCategories }
            : { data: [], meta: meta(options.params?.page, options.params?.per_page) });
        const wrapper = mountPage(); await flushPromises();
        await filterButton(wrapper, 'Drinks').trigger('click'); await flushPromises();
        await wrapper.findAll('button').find((button) => button.text() === 'Manage categories').trigger('click');
        currentCategories = [categories[1]];
        wrapper.findComponent(stubs.ProductCategoryManager).vm.$emit('close'); await flushPromises();
        expect(filterButton(wrapper, 'All').attributes('aria-pressed')).toBe('true');
        expect(requestFor('products')).toMatchObject({ page: 1, product_filter: 'all' });
        wrapper.unmount();
    });

    it('ignores stale list responses and stale failures while keeping loading accurate', async () => {
        const old = deferred(); const latest = deferred();
        apiRequest.mockImplementation((url, options = {}) => {
            if (url.endsWith('/category-filters')) return Promise.resolve({ data: categories });
            if (options.params.product_filter === 'none') return old.promise;
            if (options.params.product_filter === 'disabled') return latest.promise;
            return Promise.resolve({ data: [], meta: meta() });
        });
        const wrapper = mountPage(); await flushPromises();
        await filterButton(wrapper, 'None').trigger('click');
        await filterButton(wrapper, 'Disabled').trigger('click');
        expect(wrapper.text()).toContain('Loading inventory');
        old.reject({ response: { data: { message: 'Old failure' } } }); await flushPromises();
        expect(wrapper.text()).toContain('Loading inventory');
        expect(wrapper.text()).not.toContain('Old failure');
        latest.resolve({ data: [{ id: 2, name: 'Latest', is_active: false }], meta: meta() }); await flushPromises();
        expect(wrapper.text()).toContain('Latest');
        expect(wrapper.text()).not.toContain('Loading inventory');
        wrapper.unmount();
    });

    it('prevents older successful list and category requests from overwriting newer results', async () => {
        const oldList = deferred(); const oldCategories = deferred(); const newCategories = deferred();
        let categoryCalls = 0;
        apiRequest.mockImplementation((url, options = {}) => {
            if (url.endsWith('/category-filters')) return ++categoryCalls === 1 ? oldCategories.promise : newCategories.promise;
            if (options.params.product_filter === 'none') return oldList.promise;
            return Promise.resolve({ data: [{ id: 1, name: options.params.product_filter }], meta: meta() });
        });
        const wrapper = mountPage(); await flushPromises();
        await filterButton(wrapper, 'None').trigger('click');
        await filterButton(wrapper, 'Disabled').trigger('click'); await flushPromises();
        oldList.resolve({ data: [{ id: 2, name: 'Stale product' }], meta: meta() }); await flushPromises();
        expect(wrapper.text()).not.toContain('Stale product');
        await wrapper.findAll('button').find((button) => button.text() === 'Manage categories').trigger('click');
        wrapper.findComponent(stubs.ProductCategoryManager).vm.$emit('changed');
        newCategories.resolve({ data: [categories[1]] }); await flushPromises();
        oldCategories.resolve({ data: [categories[0]] }); await flushPromises();
        expect(filterButton(wrapper, 'Snacks')).toBeTruthy();
        expect(filterButton(wrapper, 'Drinks')).toBeUndefined();
        wrapper.unmount();
    });

    it('shows errors and retries list and category requests', async () => {
        apiRequest.mockImplementationOnce(() => Promise.reject(new Error('offline')))
            .mockImplementationOnce(() => Promise.reject(new Error('offline')));
        const wrapper = mountPage(); await flushPromises();
        expect(wrapper.text()).toContain('Failed to load categories.');
        expect(wrapper.text()).toContain('Failed to load inventory data.');
        expect(wrapper.text()).not.toContain('No products found.');
        await wrapper.getComponent(InventoryCategoryFilters).findAll('button').find((button) => button.text() === 'Retry').trigger('click'); await flushPromises();
        expect(filterButton(wrapper, 'Drinks')).toBeTruthy();
        await wrapper.findAll('button').find((button) => button.text() === 'Retry' && !button.element.closest('[aria-label="Inventory product filters"]')).trigger('click'); await flushPromises();
        expect(wrapper.text()).not.toContain('Failed to load inventory data.');
        expect(wrapper.text()).toContain('No products found.');
        wrapper.unmount();
    });
});