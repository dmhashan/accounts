import { mount, flushPromises } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import SalesFormPage from '../../spa/pages/SalesFormPage.vue';
import { apiRequest } from '../../spa/composables/useApiClient';

vi.mock('vue-router', () => ({
    useRoute: () => ({ params: {}, query: {} }),
    useRouter: () => ({ push: vi.fn() }),
}));

vi.mock('../../spa/composables/useApiClient', () => ({ apiRequest: vi.fn() }));
vi.mock('../../spa/composables/useAppContext', () => ({
    useAppContext: () => ({ permissions: { salesCreate: true, salesEdit: true } }),
}));

const meta = () => ({
    categories: [
        { id: 1, name: 'Drinks', color: 'blue', icon: 'Coffee' },
        { id: 2, name: 'Food', color: 'invalid', icon: '__proto__' },
    ],
    variations: [
        { id: 1, label: 'Water bottle', category_id: 1, image_url: '/water.jpg', available_stock: 2, prices: { local: 100, foreign: 150 } },
        { id: 2, label: 'Protein bar', category_id: '2', image_url: null, available_stock: 3, prices: { local: 200, foreign: 250 } },
        { id: 3, label: 'Water snack', category_id: 2, available_stock: 0, prices: { local: 300 } },
        { id: 4, label: 'Uncategorised', available_stock: 1, prices: { local: 50 } },
    ],
    members: [], accounts: [], payment_methods: [],
});

const stubs = {
    AppPageHeader: { template: '<header><slot name="extra-slot" /></header>' },
    AppSearchableDropdown: true,
    AppPaymentMethodSelect: true,
};

let wrapper;

async function mountPage(data = meta()) {
    apiRequest.mockResolvedValueOnce(data);
    wrapper = mount(SalesFormPage, { global: { stubs } });
    await flushPromises();
    return wrapper;
}

function category(name) {
    return wrapper.findAll('[aria-label="Product categories"] button').find((button) => button.text() === name);
}

function products() {
    return wrapper.findAll('article[role="button"]');
}

describe('SalesFormPage POS categories', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        HTMLElement.prototype.scrollIntoView = vi.fn();
    });

    afterEach(() => {
        wrapper?.unmount();
        vi.restoreAllMocks();
    });

    it('combines category and text filters with available stock and restores all products', async () => {
        await mountPage();
        expect(products().map((item) => item.text())).toEqual([
            expect.stringContaining('Water bottle'), expect.stringContaining('Protein bar'), expect.stringContaining('Uncategorised'),
        ]);
        expect(category('All').attributes('aria-pressed')).toBe('true');
        await category('Food').trigger('click');
        expect(category('Food').attributes('aria-pressed')).toBe('true');
        expect(products()).toHaveLength(1);
        expect(products()[0].text()).toContain('Protein bar');

        await wrapper.find('input[placeholder="Search product..."]').setValue(' WATER ');
        expect(products()).toHaveLength(0);
        expect(wrapper.text()).toContain('No products found.');
        await category('Drinks').trigger('click');
        expect(products()).toHaveLength(1);
        expect(products()[0].text()).toContain('Water bottle');
        await category('All').trigger('click');
        expect(products()).toHaveLength(1);
        await wrapper.find('input[placeholder="Search product..."]').setValue('');
        expect(products()).toHaveLength(3);
    });

    it('renders category icons and safe fallbacks and shows image thumbnails with an error fallback', async () => {
        await mountPage();
        expect(category('Drinks').find('svg').classes()).toContain('lucide-coffee');
        expect(category('Food').find('svg').classes()).toContain('lucide-tag');
        expect(category('Food').classes()).toContain('bg-slate-100');
        expect(products()[0].find('img').attributes('src')).toBe('/water.jpg');
        expect(products()[1].find('svg').classes()).toContain('lucide-package');
        await products()[0].find('img').trigger('error');
        expect(products()[0].find('img').exists()).toBe(false);
        expect(products()[0].find('svg').classes()).toContain('lucide-package');
    });

    it('retains cart selection, stock limits and customer pricing across filters', async () => {
        await mountPage();
        await category('Drinks').trigger('click');
        await products()[0].trigger('click');
        await products()[0].trigger('keydown.enter');
        await products()[0].trigger('click');
        await flushPromises();
        const cart = wrapper.findAll('article').find((item) => item.text().includes('Unit:'));
        expect(cart.text()).toContain('Unit: 100.00');
        expect(cart.text()).toContain('200.00');
        expect(cart.text()).toMatch(/-\s*2\s*\+/);
        await category('Food').trigger('click');
        expect(cart.text()).toContain('Water bottle');
        await wrapper.get('button[aria-label="Foreign"]').trigger('click');
        expect(cart.text()).toContain('Unit: 150.00');
        expect(cart.text()).toContain('300.00');
    });

    it('works with older metadata and resets category on a new sale', async () => {
        const oldMeta = meta();
        delete oldMeta.categories;
        await mountPage(oldMeta);
        expect(wrapper.findAll('[aria-label="Product categories"] button')).toHaveLength(1);
        expect(products()).toHaveLength(3);
        wrapper.unmount();

        await mountPage();
        await category('Drinks').trigger('click');
        await products()[0].trigger('click');
        apiRequest.mockResolvedValueOnce({});
        apiRequest.mockResolvedValueOnce(meta());
        await wrapper.findAll('button').find((button) => button.text() === 'Save').trigger('click');
        await flushPromises();
        expect(apiRequest).toHaveBeenCalledWith('/api/sales', expect.objectContaining({
            method: 'post',
            data: expect.objectContaining({ items: [{ product_variation_id: 1, quantity: 1 }] }),
        }));
        await wrapper.findAll('button').find((button) => button.text() === 'New Sale').trigger('click');
        expect(category('All').attributes('aria-pressed')).toBe('true');
        expect(products()).toHaveLength(3);
    });

    it('clears a selected category that disappears when stock metadata is refreshed', async () => {
        await mountPage();
        await category('Drinks').trigger('click');
        await products()[0].trigger('click');
        const refreshed = meta();
        refreshed.categories = [refreshed.categories[1]];
        apiRequest.mockResolvedValueOnce({});
        apiRequest.mockResolvedValueOnce(refreshed);
        await wrapper.findAll('button').find((button) => button.text() === 'Save').trigger('click');
        await flushPromises();
        expect(category('All').attributes('aria-pressed')).toBe('true');
        expect(products()).toHaveLength(3);
    });
});