import { mount, flushPromises } from '@vue/test-utils';
import { vi, describe, it, beforeEach, expect } from 'vitest';
import MemberWorkoutsTab from '../../spa/components/member/MemberWorkoutsTab.vue';
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

const mockSettings = { dateFormat: 'YYYY-MM-DD', whatsappEnabled: true };

vi.mock('../../spa/composables/useAppContext', () => ({
    useAppContext: () => ({
        permissions: { workout: true, workoutAssignments: true },
        settings: mockSettings,
    }),
}));

describe('MemberWorkoutsTab', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockSettings.whatsappEnabled = true;
    });

    it('renders empty message when no workouts are assigned', async () => {
        apiRequest.mockResolvedValueOnce({
            data: [],
            meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 },
        });

        const wrapper = mount(MemberWorkoutsTab, {
            props: { memberId: 1 },
        });

        await wrapper.vm.loadMemberWorkouts();
        await flushPromises();

        expect(wrapper.text()).toContain('No workout plans assigned');
    });

    it('renders assigned workouts of various types (program, pdf, text)', async () => {
        apiRequest.mockResolvedValueOnce({
            data: [
                {
                    id: 1,
                    type: 'program',
                    title: 'Strength Builder 4-Week',
                    assigned_program_title: 'Strength Builder 4-Week',
                    effective_date: '2026-08-01',
                    created_by_name: 'Coach John',
                },
                {
                    id: 2,
                    type: 'file',
                    title: 'Chest Day PDF Routine',
                    file_name: 'chest_routine.pdf',
                    mime_type: 'application/pdf',
                    effective_date: '2026-08-10',
                    created_by_name: 'Coach Alex',
                },
                {
                    id: 3,
                    type: 'text',
                    title: 'Core & Cardio Blast',
                    formatted_text: '<h3>Day 1</h3><p>Plank 3x60s</p>',
                    effective_date: '2026-08-12',
                    created_by_name: 'Coach Sam',
                },
            ],
            meta: { current_page: 1, last_page: 1, per_page: 10, total: 3 },
        });

        const wrapper = mount(MemberWorkoutsTab, {
            props: { memberId: 1 },
        });

        await wrapper.vm.loadMemberWorkouts();
        await flushPromises();

        expect(wrapper.text()).toContain('Strength Builder 4-Week');
        expect(wrapper.text()).toContain('Program');
        expect(wrapper.text()).toContain('Chest Day PDF Routine');
        expect(wrapper.text()).toContain('PDF Plan');
        expect(wrapper.text()).toContain('chest_routine.pdf');
        expect(wrapper.text()).toContain('Core & Cardio Blast');
        expect(wrapper.text()).toContain('Custom Routine');
    });

    it('shows Send via WhatsApp button and triggers API when clicked', async () => {
        apiRequest.mockResolvedValueOnce({
            data: [
                {
                    id: 10,
                    type: 'program',
                    title: 'Leg Day Annihilation',
                    effective_date: '2026-08-14',
                },
            ],
            meta: { current_page: 1, last_page: 1, per_page: 10, total: 1 },
        });

        const wrapper = mount(MemberWorkoutsTab, {
            props: { memberId: 42 },
        });

        await wrapper.vm.loadMemberWorkouts();
        await flushPromises();

        const sendWaBtn = wrapper.find('button[title="Send Workout via WhatsApp"]');
        expect(sendWaBtn.exists()).toBe(true);

        apiRequest.mockResolvedValueOnce({
            success: true,
            message: 'Workout plan sent to member via WhatsApp successfully.',
        });

        await sendWaBtn.trigger('click');
        await flushPromises();

        expect(apiRequest).toHaveBeenCalledWith('/api/members/42/workouts/10/send-whatsapp', {
            method: 'post',
        });
        expect(wrapper.text()).toContain('Workout plan sent to member via WhatsApp');
    });
});
