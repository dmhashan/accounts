import { mount, flushPromises } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import ProductForm from '../../spa/pages/InventoryProductFormPage.vue';
import CategoryManager from '../../spa/components/ProductCategoryManager.vue';
import { apiRequest } from '../../spa/composables/useApiClient';

const params = {};
const push = vi.fn();
vi.mock('vue-router', () => ({
    useRoute: () => ({ params }), useRouter: () => ({ push }), onBeforeRouteLeave: vi.fn(),
    RouterLink: { template: '<a><slot /></a>', props: ['to'] },
}));
vi.mock('../../spa/composables/useApiClient', () => ({ apiRequest: vi.fn() }));

const stubs = {
    AppPageHeader: true,
    AppFormField: { template: '<div><slot /></div>' },
    AppFormInput: { props: ['modelValue'], emits: ['update:modelValue'], template: '<input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />' },
    AvatarCropModal: true,
    ProductCategoryManager: true,
};
const button = (wrapper, text) => wrapper.findAll('button').find((item) => item.text().includes(text));

describe('product management', () => {
    beforeEach(() => { vi.clearAllMocks(); delete params.id; });

    it('matches reordered new variations by name and retries only failed image uploads without creating again', async () => {
        let failed = false;
        apiRequest.mockImplementation(async (url, options = {}) => {
            if (url.endsWith('/categories')) return { data: [{ id: 8, name: 'Drinks', color: 'blue', icon: 'Tag' }] };
            if (url === '/api/inventory/products' && options.method === 'post') return { data: { id: 15, variations: [{ id: 22, name: 'Large' }, { id: 21, name: 'Small' }] } };
            if (url.endsWith('/21/image') && !failed) { failed = true; throw { response: { status: 422, data: { message: 'Upload failed' } } }; }
            if (url.endsWith('/image')) return { data: { image_url: '/saved.jpg' } };
            if (url === '/api/inventory/products/15' && options.method === 'put') return { data: { id: 15 } };
            throw new Error(`Unexpected request ${url}`);
        });
        const wrapper = mount(ProductForm, { global: { stubs } });
        await flushPromises();
        wrapper.vm.form.name = 'Protein';
        wrapper.vm.form.category_id = 8;
        wrapper.vm.form.variations[0].name = 'Small';
        wrapper.vm.addVariation('Large');
        wrapper.vm.form.variations[0].pendingBlob = new Blob(['small'], { type: 'image/jpeg' });
        wrapper.vm.form.variations[1].pendingBlob = new Blob(['large'], { type: 'image/jpeg' });
        await wrapper.find('form').trigger('submit'); await flushPromises();
        expect(wrapper.text()).toContain('some images could not be updated');
        expect(wrapper.vm.form.variations.map((v) => v.id)).toEqual([21, 22]);
        expect(wrapper.vm.form.variations[0].pendingBlob).toBeTruthy();
        expect(wrapper.vm.form.variations[1].pendingBlob).toBeNull();
        const upload = apiRequest.mock.calls.find(([url]) => url.endsWith('/21/image'));
        expect(upload[1].data.get('image')).toBeInstanceOf(Blob);
        expect(apiRequest.mock.calls.find(([url]) => url === '/api/inventory/products')[1].data.category_id).toBe(8);
        await wrapper.find('form').trigger('submit'); await flushPromises();
        expect(apiRequest.mock.calls.filter(([url, opts]) => url === '/api/inventory/products' && opts?.method === 'post')).toHaveLength(1);
        expect(apiRequest.mock.calls.filter(([url]) => url.endsWith('/22/image'))).toHaveLength(1);
        expect(push).toHaveBeenCalledWith('/inventory');
        wrapper.unmount();
    });

    it('retains pending data and retries a failed ID lookup without resaving', async () => {
        let loads = 0;
        apiRequest.mockImplementation(async (url, options = {}) => {
            if (url.endsWith('/categories')) return { data: [] };
            if (url === '/api/inventory/products') return { data: { id: 11 } };
            if (url === '/api/inventory/products/11' && !options.method) {
                if (++loads === 1) throw new Error('Offline');
                return { data: { id: 11, variations: [{ id: 44, name: 'Vanilla' }] } };
            }
            if (url.endsWith('/44/image')) return { data: { image_url: '/image.jpg' } };
            throw new Error(`Unexpected request ${url}`);
        });
        const wrapper = mount(ProductForm, { global: { stubs } }); await flushPromises();
        wrapper.vm.form.name = 'Shake'; wrapper.vm.form.variations[0].name = 'Vanilla';
        wrapper.vm.form.variations[0].pendingBlob = new Blob(['x']);
        await wrapper.find('form').trigger('submit'); await flushPromises();
        expect(wrapper.vm.form.variations[0].pendingBlob).toBeTruthy();
        expect(button(wrapper, 'Retry finding saved variations')).toBeTruthy();
        await button(wrapper, 'Retry finding saved variations').trigger('click'); await flushPromises();
        expect(apiRequest.mock.calls.filter(([url]) => url === '/api/inventory/products')).toHaveLength(1);
        expect(apiRequest).toHaveBeenCalledWith('/api/inventory/variations/44/image', expect.objectContaining({ method: 'post' }));
        expect(push).toHaveBeenCalledWith('/inventory'); wrapper.unmount();
    });

    it('stages image removal for existing variations', async () => {
        params.id = '3';
        apiRequest.mockImplementation(async (url, opts = {}) => {
            if (url.endsWith('/categories')) return { data: [] };
            if (url.endsWith('/image')) return { data: { image_url: null } };
            if (opts.method === 'put') return { data: { id: 3 } };
            return { data: { name: 'Bar', category_id: null, variations: [{ id: 5, name: 'Chocolate', image_url: '/old.jpg' }] } };
        });
        const wrapper = mount(ProductForm, { global: { stubs } }); await flushPromises();
        await button(wrapper, 'Remove image').trigger('click');
        expect(apiRequest.mock.calls.some(([url]) => url.endsWith('/image'))).toBe(false);
        await wrapper.find('form').trigger('submit'); await flushPromises();
        expect(apiRequest).toHaveBeenCalledWith('/api/inventory/variations/5/image', { method: 'delete' }); wrapper.unmount();
    });

    it('matches a newly added variation on edit and requests a 100px crop', async () => {
        params.id = '3';
        apiRequest.mockImplementation(async (url, opts = {}) => {
            if (url.endsWith('/categories')) return { data: [] };
            if (url.endsWith('/image')) return { data: { image_url: '/new.jpg' } };
            if (opts.method === 'put') return { data: { id: 3, variations: [{ id: 9, name: 'New' }, { id: 5, name: 'Existing' }] } };
            return { data: { name: 'Bar', variations: [{ id: 5, name: 'Existing' }] } };
        });
        const wrapper = mount(ProductForm, { global: { stubs } }); await flushPromises();
        wrapper.vm.addVariation('New');
        wrapper.vm.cropSrc = 'data:image/jpeg;base64,eA==';
        wrapper.vm.cropVariation = wrapper.vm.form.variations[1];
        await wrapper.vm.$nextTick();
        expect(wrapper.findComponent({ name: 'AvatarCropModal' }).props('outputSize')).toBe(100);
        wrapper.vm.confirmCrop(new Blob(['image'], { type: 'image/jpeg' }));
        await wrapper.find('form').trigger('submit'); await flushPromises();
        expect(apiRequest).toHaveBeenCalledWith('/api/inventory/variations/9/image', expect.objectContaining({ method: 'post' }));
        wrapper.unmount();
    });

    it('does not upload or recreate when a saved variation cannot be identified uniquely', async () => {
        apiRequest.mockImplementation(async (url) => url.endsWith('/categories') ? { data: [] } : { data: { id: 6, variations: [{ id: 7, name: 'Regular' }, { id: 8, name: 'Regular' }] } });
        const wrapper = mount(ProductForm, { global: { stubs } }); await flushPromises();
        wrapper.vm.form.name = 'Snack'; wrapper.vm.form.variations[0].name = 'Regular';
        wrapper.vm.form.variations[0].pendingBlob = new Blob(['image']);
        await wrapper.find('form').trigger('submit'); await flushPromises();
        expect(wrapper.text()).toContain('Could not uniquely identify');
        expect(wrapper.vm.form.variations[0].pendingBlob).toBeTruthy();
        expect(apiRequest.mock.calls.some(([url]) => url.endsWith('/image'))).toBe(false);
        expect(button(wrapper, 'Retry finding saved variations')).toBeTruthy(); wrapper.unmount();
    });

    it('creates, edits and deletes categories with color and icon', async () => {
        let list = [];
        apiRequest.mockImplementation(async (url, opts = {}) => {
            if (!opts.method) return { data: list };
            if (opts.method === 'post') { list = [{ id: 1, ...opts.data }]; return { data: list[0] }; }
            if (opts.method === 'put') { list = [{ id: 1, ...opts.data }]; return { data: list[0] }; }
            if (opts.method === 'delete') { list = []; return {}; }
        });
        const wrapper = mount(CategoryManager, { global: { stubs: { teleport: true, AppConfirmModal: { template: '<button @click="$emit(\'confirm\')">Confirm delete</button>' } } } });
        await flushPromises();
        await wrapper.find('input').setValue('Food');
        const selects = wrapper.findAll('select'); await selects[0].setValue('blue'); await selects[1].setValue('Package');
        await wrapper.find('form').trigger('submit'); await flushPromises();
        expect(apiRequest).toHaveBeenCalledWith('/api/inventory/categories', { method: 'post', data: { name: 'Food', color: 'blue', icon: 'Package' } });
        await button(wrapper, 'Edit').trigger('click'); await wrapper.find('input').setValue('Snacks');
        await wrapper.find('form').trigger('submit'); await flushPromises();
        expect(apiRequest).toHaveBeenCalledWith('/api/inventory/categories/1', expect.objectContaining({ method: 'put', data: expect.objectContaining({ name: 'Snacks' }) }));
        await button(wrapper, 'Delete').trigger('click'); await button(wrapper, 'Confirm delete').trigger('click'); await flushPromises();
        expect(apiRequest).toHaveBeenCalledWith('/api/inventory/categories/1', { method: 'delete' });
        expect(wrapper.emitted('deleted')[0]).toEqual([1]); wrapper.unmount();
    });
});