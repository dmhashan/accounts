import { mount, flushPromises } from '@vue/test-utils';
import { vi, describe, it, beforeEach, expect } from 'vitest';
import MemberPaymentsTab from '../../spa/components/member/MemberPaymentsTab.vue';
import { apiRequest } from '../../spa/composables/useApiClient';

vi.mock('vue-router', () => ({
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

describe('MemberPaymentsTab', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders empty message when there are no payments', async () => {
        apiRequest.mockResolvedValueOnce({
            data: [],
            meta: { current_page: 1, last_page: 1, per_page: 15, total: 0 },
        });

        const wrapper = mount(MemberPaymentsTab, {
            props: { memberId: 1 },
            global: {
                provide: {
                    appContext: {
                        settings: { dateFormat: 'YYYY-MM-DD' },
                    },
                },
            },
        });

        await wrapper.vm.loadMemberPayments();
        await flushPromises();

        expect(wrapper.text()).toContain('No payments recorded for this member.');
    });

    it('renders membership payment valid from and to dates and plan name', async () => {
        apiRequest.mockResolvedValueOnce({
            data: [
                {
                    id: 101,
                    member_id: 1,
                    amount: 2500,
                    payment_date: '2026-08-01',
                    start_date: '2026-08-01',
                    end_date: '2026-08-31',
                    payment_plan_name: 'Monthly Gold',
                    payment_method: 'cash',
                    payment_method_name: 'Cash',
                    payment_method_color: 'emerald',
                    payment_method_icon: 'Banknote',
                    is_paid: true,
                    reference_number: 'REC-001',
                },
            ],
            meta: { current_page: 1, last_page: 1, per_page: 15, total: 1 },
        });

        const wrapper = mount(MemberPaymentsTab, {
            props: { memberId: 1 },
            global: {
                provide: {
                    appContext: {
                        settings: { dateFormat: 'YYYY-MM-DD' },
                    },
                },
            },
        });

        await wrapper.vm.loadMemberPayments();
        await flushPromises();

        expect(wrapper.text()).toContain('Monthly Gold');
        expect(wrapper.text()).toContain('2,500.00');
        expect(wrapper.text()).toContain('Valid:');
        expect(wrapper.text()).toContain('2026-08-01 – 2026-08-31');
        expect(wrapper.text()).toContain('Ref: REC-001');
        expect(wrapper.text()).toContain('Cash');
    });

    it('renders outstanding badge when payment is unpaid', async () => {
        apiRequest.mockResolvedValueOnce({
            data: [
                {
                    id: 102,
                    member_id: 1,
                    amount: 1500,
                    payment_date: '2026-08-05',
                    start_date: '2026-08-05',
                    end_date: '2026-09-04',
                    payment_plan_name: 'Standard Monthly',
                    payment_method: 'cash',
                    is_paid: false,
                    balance: 1500,
                },
            ],
            meta: { current_page: 1, last_page: 1, per_page: 15, total: 1 },
        });

        const wrapper = mount(MemberPaymentsTab, {
            props: { memberId: 1 },
            global: {
                provide: {
                    appContext: {
                        settings: { dateFormat: 'YYYY-MM-DD' },
                    },
                },
            },
        });

        await wrapper.vm.loadMemberPayments();
        await flushPromises();

        expect(wrapper.text()).toContain('Outstanding');
        expect(wrapper.text()).toContain('Bal: 1,500.00');
        expect(wrapper.text()).toContain('Valid:');
        expect(wrapper.text()).toContain('2026-08-05 – 2026-09-04');
    });
});
