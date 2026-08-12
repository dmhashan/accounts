import { mount, flushPromises } from '@vue/test-utils';
import { vi, describe, it, beforeEach, expect } from 'vitest';
import PaymentViewPage from '../../spa/pages/PaymentViewPage.vue';
import { apiRequest } from '../../spa/composables/useApiClient';

const mockParams = { id: '1914' };

vi.mock('vue-router', () => ({
    useRoute: () => ({ params: mockParams, query: {} }),
    useRouter: () => ({ push: vi.fn() }),
    RouterLink: {
        name: 'RouterLink',
        template: '<a :href="to"><slot /></a>',
        props: ['to'],
    },
}));

vi.mock('../../spa/composables/useApiClient', () => ({
    apiRequest: vi.fn(),
}));

vi.mock('../../spa/composables/useAppContext', () => ({
    useAppContext: () => ({
        permissions: { paymentsManage: true },
        settings: { dateFormat: 'YYYY-MM-DD' },
    }),
}));

const globalStubs = {
    AppPageHeader: {
        name: 'AppPageHeader',
        template: '<header><slot /><slot name="cta-slot" /></header>',
        props: ['showBack'],
    },
    AppPaymentMethodSelect: {
        name: 'AppPaymentMethodSelect',
        template: '<div data-stub="payment-method-select" />',
        props: ['modelValue', 'methods', 'memberId', 'amount'],
    },
};

describe('PaymentViewPage', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders member link to member view when member_id is available', async () => {
        apiRequest.mockResolvedValueOnce({
            data: {
                id: 1914,
                member_id: 42,
                member_name: 'John Doe',
                member_phone: '0771234567',
                amount: 5000,
                payment_date: '2026-08-01',
                payment_method_name: 'Cash',
                is_paid: true,
            },
        });

        const wrapper = mount(PaymentViewPage, {
            global: {
                stubs: globalStubs,
            },
        });

        await flushPromises();

        const memberLink = wrapper.find('a[href="/members/42"]');
        expect(memberLink.exists()).toBe(true);
        expect(memberLink.text()).toContain('John Doe');
    });

    it('renders text without link when member_id is null', async () => {
        apiRequest.mockResolvedValueOnce({
            data: {
                id: 1915,
                member_id: null,
                member_name: null,
                amount: 3000,
                payment_date: '2026-08-02',
                payment_method_name: 'Cash',
                is_paid: true,
            },
        });

        const wrapper = mount(PaymentViewPage, {
            global: {
                stubs: globalStubs,
            },
        });

        await flushPromises();

        expect(wrapper.find('a[href^="/members/"]').exists()).toBe(false);
        expect(wrapper.text()).toContain('Walk-in / Unspecified');
    });
});
