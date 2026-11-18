import { mount, flushPromises } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import SalesFormPage from '../../spa/pages/SalesFormPage.vue';
import { apiRequest } from '../../spa/composables/useApiClient';

const params = {};
vi.mock('vue-router', () => ({
    useRoute: () => ({ params, query: {} }),
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
        delete params.id;
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

    it('offers active untracked products without stock and allows unrestricted cart quantities', async () => {
        const data = meta();
        data.variations.push(
            { id: 5, label: 'Unlimited service', track_stock: false, is_active: true, available_stock: 0, prices: { local: 25 } },
            { id: 6, label: 'Disabled service', track_stock: false, is_active: false, available_stock: 50, prices: { local: 25 } },
        );
        await mountPage(data);
        expect(products().map((p) => p.text()).join(' ')).not.toContain('Disabled service');
        const service = products().find((p) => p.text().includes('Unlimited service'));
        expect(service.text()).toContain('Unlimited');
        await service.trigger('click');
        await service.trigger('click');
        const cart = wrapper.findAll('article').find((p) => p.text().includes('Unit: 25.00'));
        await cart.findAll('button').find((b) => b.text() === '+').trigger('click');
        expect(cart.text()).toMatch(/-\s*3\s*\+/);
        await cart.get('input[aria-label="Unit price for Unlimited service"]').setValue('35');
        expect(cart.text()).toContain('105.00');
        apiRequest.mockResolvedValueOnce({});
        apiRequest.mockResolvedValueOnce(data);
        await wrapper.findAll('button').find((b) => b.text() === 'Save').trigger('click');
        await flushPromises();
        expect(apiRequest).toHaveBeenCalledWith('/api/sales', expect.objectContaining({ data: expect.objectContaining({ items: [{ product_variation_id: 5, quantity: 3, unit_price: 35 }] }) }));
        expect(wrapper.text()).toContain('Stock not tracked');
    });

    it('updates configured untracked prices on customer type changes unless manually overridden', async () => {
        const data = meta();
        data.variations = [
            { id: 5, label: 'Service', track_stock: false, is_active: true, prices: { local: 25, foreign: 40 } },
            { id: 6, label: 'Free service', track_stock: false, is_active: true, prices: { local: 0, foreign: null } },
        ];
        await mountPage(data);
        await products()[0].trigger('click'); await products()[1].trigger('click');
        const servicePrice = wrapper.get('input[aria-label="Unit price for Service"]');
        const freePrice = wrapper.get('input[aria-label="Unit price for Free service"]');
        expect(servicePrice.element.value).toBe('25'); expect(freePrice.element.value).toBe('0');
        await wrapper.get('button[aria-label="Foreign"]').trigger('click');
        expect(servicePrice.element.value).toBe('40'); expect(freePrice.element.value).toBe('');
        expect(products()[1].text()).toContain('Price not set');
        await servicePrice.setValue('12');
        await wrapper.get('button[aria-label="Local"]').trigger('click');
        expect(servicePrice.element.value).toBe('12'); expect(freePrice.element.value).toBe('0');
        apiRequest.mockResolvedValueOnce({}).mockResolvedValueOnce(data);
        await wrapper.findAll('button').find((b) => b.text() === 'Save').trigger('click'); await flushPromises();
        expect(apiRequest).toHaveBeenCalledWith('/api/sales', expect.objectContaining({ data: expect.objectContaining({ items: [
            { product_variation_id: 5, quantity: 1, unit_price: 12 }, { product_variation_id: 6, quantity: 1, unit_price: 0 },
        ] }) }));
    });

    it('requires an explicitly entered price when metadata has no configured price and accepts explicit zero', async () => {
        const data = meta();
        data.variations = [{ id: 5, label: 'Service', track_stock: false, is_active: true, prices: null }];
        await mountPage(data);
        expect(products()[0].text()).toContain('Price not set');
        await products()[0].trigger('click');
        const price = wrapper.get('input[aria-label="Unit price for Service"]');
        expect(price.element.value).toBe(''); expect(wrapper.text()).toContain('Unit: Price required');
        await wrapper.findAll('button').find((b) => b.text() === 'Save').trigger('click');
        expect(wrapper.text()).toContain('Enter a valid non-negative unit price');
        await wrapper.findAll('button').find((b) => b.text() === 'Pay Now').trigger('click');
        expect(wrapper.text()).not.toContain('Select Company Account');
        expect(apiRequest.mock.calls.some(([url, opts]) => url === '/api/sales' && opts?.method)).toBe(false);
        await price.setValue('0');
        await wrapper.get('button[aria-label="Foreign"]').trigger('click');
        expect(price.element.value).toBe('0');
        apiRequest.mockResolvedValueOnce({}).mockResolvedValueOnce(data);
        await wrapper.findAll('button').find((b) => b.text() === 'Save').trigger('click'); await flushPromises();
        expect(apiRequest).toHaveBeenCalledWith('/api/sales', expect.objectContaining({ data: expect.objectContaining({ items: [{ product_variation_id: 5, quantity: 1, unit_price: 0 }] }) }));
    });

    it('preserves historical price overrides but switches historical lines matching the configured price', async () => {
        params.id = '8';
        const data = meta();
        data.variations = [
            { id: 5, label: 'Standard', track_stock: false, is_active: true, prices: { local: 25, foreign: 40 } },
            { id: 6, label: 'Discounted', track_stock: false, is_active: true, prices: { local: 25, foreign: 40 } },
        ];
        apiRequest.mockResolvedValueOnce(data).mockResolvedValueOnce({ data: {
            customer_type: 'local', payment_method: 'cash', is_paid: false, items: [
                { product_variation_id: 5, quantity: 1, unit_price: 25 }, { product_variation_id: 6, quantity: 1, unit_price: 10 },
            ],
        } });
        wrapper = mount(SalesFormPage, { global: { stubs } }); await flushPromises();
        await wrapper.get('button[aria-label="Foreign"]').trigger('click');
        expect(wrapper.get('input[aria-label="Unit price for Standard"]').element.value).toBe('40');
        expect(wrapper.get('input[aria-label="Unit price for Discounted"]').element.value).toBe('10');
        apiRequest.mockResolvedValueOnce({});
        await wrapper.find('form').trigger('submit'); await flushPromises();
        expect(apiRequest).toHaveBeenCalledWith('/api/sales/8', expect.objectContaining({ data: expect.objectContaining({ items: [
            { product_variation_id: 5, quantity: 1, unit_price: 40 }, { product_variation_id: 6, quantity: 1, unit_price: 10 },
        ] }) }));
    });

    it('retains disabled and missing historical items and accounts for reserved tracked stock when editing', async () => {
        params.id = '42';
        const data = meta();
        data.variations = [
            { id: 1, label: 'Tracked', available_stock: 1, track_stock: true, is_active: true, prices: { local: 10 } },
            { id: 2, label: 'Disabled', available_stock: 100, track_stock: false, is_active: false, prices: { local: 20 } },
        ];
        apiRequest.mockResolvedValueOnce(data).mockResolvedValueOnce({ data: {
            customer_type: 'local', payment_method: 'cash', is_paid: false, paid_amount: 0,
            items: [
                { product_variation_id: 1, quantity: 2, unit_price: 10 },
                { product_variation_id: 2, quantity: 2, unit_price: 20 },
                { product_variation_id: 3, quantity: 1, unit_price: 30 },
            ],
        } });
        wrapper = mount(SalesFormPage, { global: { stubs } });
        await flushPromises();
        expect(products()).toHaveLength(1);
        expect(products()[0].text()).toContain('Stock: 1 (+ 2 in this sale)');
        const carts = () => wrapper.findAll('article').filter((p) => p.text().includes('Unit:'));
        expect(carts()[1].text()).toContain('Disabled product');
        expect(carts()[2].text()).toContain('Product variation #3');
        expect(carts()[2].text()).toContain('Unit: 30.00');
        await carts()[0].findAll('button').find((b) => b.text() === '+').trigger('click');
        await carts()[0].findAll('button').find((b) => b.text() === '+').trigger('click');
        await carts()[1].findAll('button').find((b) => b.text() === '+').trigger('click');
        await carts()[2].findAll('button').find((b) => b.text() === '+').trigger('click');
        expect(carts()[0].text()).toMatch(/-\s*3\s*\+/);
        expect(carts()[1].text()).toMatch(/-\s*2\s*\+/);
        expect(carts()[2].text()).toMatch(/-\s*1\s*\+/);
        expect(carts()[1].findAll('button').find((b) => b.text() === '-').attributes('disabled')).toBeDefined();
        expect(wrapper.get('button[aria-label="Foreign"]').attributes('disabled')).toBeDefined();
        expect(carts()[1].text()).toMatch(/-\s*2\s*\+/);
        apiRequest.mockResolvedValueOnce({});
        await wrapper.find('form').trigger('submit');
        await flushPromises();
        expect(apiRequest).toHaveBeenCalledWith('/api/sales/42', expect.objectContaining({ data: expect.objectContaining({ items: [
            { product_variation_id: 1, quantity: 3 }, { product_variation_id: 2, quantity: 2 }, { product_variation_id: 3, quantity: 1 },
        ] }) }));
    });

    it('can re-add reserved stock in an edit without exceeding the shared limit across duplicate rows', async () => {
        params.id = '7';
        const data = meta();
        data.variations = [{ id: 1, label: 'Tracked', available_stock: 0, track_stock: true, is_active: true, prices: { local: 10 } }];
        apiRequest.mockResolvedValueOnce(data).mockResolvedValueOnce({ data: {
            customer_type: 'local', payment_method: 'cash', is_paid: false, items: [
                { product_variation_id: 1, quantity: 1, unit_price: 10 },
                { product_variation_id: 1, quantity: 1, unit_price: 10 },
            ],
        } });
        wrapper = mount(SalesFormPage, { global: { stubs } }); await flushPromises();
        expect(products()[0].text()).toContain('Stock: 0 (+ 2 in this sale)');
        const carts = () => wrapper.findAll('article').filter((p) => p.text().includes('Unit:'));
        await carts()[0].findAll('button').find((b) => b.text() === '+').trigger('click');
        expect(carts()[0].text()).toMatch(/-\s*1\s*\+/);
        await carts()[1].findAll('button').find((b) => b.text() === 'Remove').trigger('click');
        await products()[0].trigger('click');
        await products()[0].trigger('click');
        expect(carts()[0].text()).toMatch(/-\s*2\s*\+/);
    });

    it('uses the historical price for an existing untracked line rather than the current metadata price', async () => {
        params.id = '8';
        const data = meta();
        data.variations = [{ id: 5, label: 'Service', available_stock: null, track_stock: false, is_active: true, prices: { local: 99 } }];
        apiRequest.mockResolvedValueOnce(data).mockResolvedValueOnce({ data: {
            customer_type: 'local', payment_method: 'cash', is_paid: false, items: [{ product_variation_id: 5, quantity: 2, unit_price: 25 }],
        } });
        wrapper = mount(SalesFormPage, { global: { stubs } }); await flushPromises();
        expect(wrapper.get('input[aria-label="Unit price for Service"]').element.value).toBe('25');
        expect(wrapper.text()).toContain('50.00');
        apiRequest.mockResolvedValueOnce({});
        await wrapper.find('form').trigger('submit'); await flushPromises();
        expect(apiRequest).toHaveBeenCalledWith('/api/sales/8', expect.objectContaining({ data: expect.objectContaining({ items: [{ product_variation_id: 5, quantity: 2, unit_price: 25 }] }) }));
    });
});