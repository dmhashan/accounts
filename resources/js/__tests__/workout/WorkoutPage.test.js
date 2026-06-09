import { mount, flushPromises } from '@vue/test-utils';
import { vi, describe, it, beforeEach, afterEach, expect } from 'vitest';
import WorkoutPage from '../../spa/pages/WorkoutPage.vue';
import { apiRequest } from '../../spa/composables/useApiClient';

// ---------------------------------------------------------------------------
// Module mocks
// ---------------------------------------------------------------------------

const mockPush = vi.fn();
const mockRoute = { params: {}, query: {}, path: '/workout' };

vi.mock('vue-router', () => ({
    useRoute: () => mockRoute,
    useRouter: () => ({ push: mockPush }),
    RouterLink: { name: 'RouterLink', template: '<a><slot /></a>', props: ['to'] },
}));

vi.mock('../../spa/composables/useApiClient', () => ({
    apiRequest: vi.fn(),
}));

// ---------------------------------------------------------------------------
// Global stubs
// ---------------------------------------------------------------------------

const globalStubs = {
    AppPageHeader: {
        name: 'AppPageHeader',
        template: '<header :data-title="title"><slot /><slot name="extra-slot" /><slot name="cta-slot" /></header>',
        props: ['title', 'showBack'],
    },
    AppSearchField: {
        name: 'AppSearchField',
        template: '<input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
        props: ['modelValue', 'placeholder'],
        emits: ['update:modelValue'],
    },
};

// ---------------------------------------------------------------------------
// API response fixtures
// ---------------------------------------------------------------------------

const exercisesResponse = {
    data: {
        data: [
            { id: 1, name: 'Bench Press', status: 'active', variations: [] },
            { id: 2, name: 'Squat', status: 'active', variations: [] },
        ],
    },
};

const programsResponse = {
    data: {
        data: [
            { id: 10, title: 'Strength Builder', duration_weeks: 12 },
            { id: 11, title: 'Cardio Blast', duration_weeks: 8 },
        ],
    },
};

const assignmentsResponse = { data: { data: [] } };

// ---------------------------------------------------------------------------
// Factory
// ---------------------------------------------------------------------------

function mountWorkoutPage(query = {}) {
    mockRoute.query = query;
    mockRoute.path = query.tab === 'exercises'
        ? '/workout/exercises'
        : query.tab === 'assignments'
            ? '/workout/assignments'
            : '/workout';

    return mount(WorkoutPage, {
        global: { stubs: globalStubs },
    });
}

function setupDefaultApiMocks() {
    vi.mocked(apiRequest).mockImplementation((url) => {
        if (url === '/api/exercises') return Promise.resolve(exercisesResponse);
        if (url === '/api/workout-programs') return Promise.resolve(programsResponse);
        if (url === '/api/workout-program-assignments') return Promise.resolve(assignmentsResponse);
        return Promise.resolve({ data: {} });
    });
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe('WorkoutPage', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        setupDefaultApiMocks();
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    // ── Data loading ─────────────────────────────────────────────────────────

    it('calls all 3 API endpoints on mount', async () => {
        mountWorkoutPage();
        await flushPromises();

        expect(apiRequest).toHaveBeenCalledWith('/api/exercises', expect.anything());
        expect(apiRequest).toHaveBeenCalledWith('/api/workout-programs', expect.anything());
        expect(apiRequest).toHaveBeenCalledWith('/api/workout-program-assignments', expect.anything());
    });

    // ── Exercises tab ────────────────────────────────────────────────────────

    it('renders exercise names when the exercises tab is active', async () => {
        const wrapper = mountWorkoutPage({ tab: 'exercises' });
        await flushPromises();

        expect(wrapper.text()).toContain('Bench Press');
        expect(wrapper.text()).toContain('Squat');
    });

    it('filters exercises by search query', async () => {
        const wrapper = mountWorkoutPage({ tab: 'exercises' });
        await flushPromises();

        // Type "bench" into the search stub input
        const input = wrapper.find('input');
        await input.setValue('bench');
        await input.trigger('input');

        expect(wrapper.text()).toContain('Bench Press');
        expect(wrapper.text()).not.toContain('Squat');
    });

    it('shows "No exercises found." when search has no matches', async () => {
        const wrapper = mountWorkoutPage({ tab: 'exercises' });
        await flushPromises();

        const input = wrapper.find('input');
        await input.setValue('nonexistent');
        await input.trigger('input');

        expect(wrapper.text()).toContain('No exercises found.');
    });

    it('navigates to an exercise when its row is clicked', async () => {
        const wrapper = mountWorkoutPage({ tab: 'exercises' });
        await flushPromises();

        await wrapper.find('article').trigger('click');

        expect(mockPush).toHaveBeenCalledWith('/workout/exercises/1');
    });

    // ── Programs tab ─────────────────────────────────────────────────────────

    it('renders program titles when the programs tab is active (default tab)', async () => {
        const wrapper = mountWorkoutPage(); // default → programs
        await flushPromises();

        expect(wrapper.text()).toContain('Strength Builder');
        expect(wrapper.text()).toContain('Cardio Blast');
    });

    it('filters programs by title search query', async () => {
        const wrapper = mountWorkoutPage(); // programs tab
        await flushPromises();

        const input = wrapper.find('input');
        await input.setValue('strength');
        await input.trigger('input');

        expect(wrapper.text()).toContain('Strength Builder');
        expect(wrapper.text()).not.toContain('Cardio Blast');
    });

    it('navigates to a program when its row is clicked', async () => {
        const wrapper = mountWorkoutPage();
        await flushPromises();

        await wrapper.find('article').trigger('click');

        expect(mockPush).toHaveBeenCalledWith('/workout/programs/10');
    });

    it('navigates to an assignment when its row is clicked', async () => {
        vi.mocked(apiRequest).mockImplementation((url) => {
            if (url === '/api/workout-program-assignments') {
                return Promise.resolve({
                    data: {
                        data: [{
                            id: 20,
                            member_name: 'Member Tester',
                            member_code: '0042',
                            assigned_program_title: 'Strength Builder',
                            effective_date: '2026-06-09',
                            created_at: '2026-06-09',
                        }],
                    },
                });
            }

            if (url === '/api/exercises') return Promise.resolve(exercisesResponse);
            if (url === '/api/workout-programs') return Promise.resolve(programsResponse);

            return Promise.resolve({ data: {} });
        });

        const wrapper = mountWorkoutPage({ tab: 'assignments' });
        await flushPromises();

        await wrapper.find('article').trigger('click');

        expect(mockPush).toHaveBeenCalledWith('/workout/assignments/20');
    });

    // ── Tab switching ────────────────────────────────────────────────────────

    it('defaults to programs tab when no query param is given', async () => {
        const wrapper = mountWorkoutPage();
        await flushPromises();

        // Programs are shown; "No exercises found." text doesn't appear
        expect(wrapper.text()).toContain('Strength Builder');
        expect(wrapper.text()).not.toContain('Bench Press');
    });

    it('shows exercises tab content when tab=exercises query param is set', async () => {
        const wrapper = mountWorkoutPage({ tab: 'exercises' });
        await flushPromises();

        expect(wrapper.text()).toContain('Bench Press');
        expect(wrapper.text()).not.toContain('Strength Builder');
    });

    // ── Error handling ───────────────────────────────────────────────────────

    it('shows an error message when the initial data load fails', async () => {
        vi.mocked(apiRequest).mockRejectedValue({
            response: { data: { message: 'Server error.' } },
        });

        const wrapper = mountWorkoutPage();
        await flushPromises();

        expect(wrapper.text()).toContain('Server error.');
    });
});
