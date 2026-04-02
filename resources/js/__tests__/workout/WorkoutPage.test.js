import { mount, flushPromises } from '@vue/test-utils';
import { vi, describe, it, beforeEach, afterEach, expect } from 'vitest';
import WorkoutPage from '../../spa/pages/WorkoutPage.vue';
import { apiRequest } from '../../spa/composables/useApiClient';

// ---------------------------------------------------------------------------
// Module mocks
// ---------------------------------------------------------------------------

const mockPush = vi.fn();
const mockQuery = {};

vi.mock('vue-router', () => ({
    useRoute: () => ({ params: {}, query: mockQuery }),
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
    Object.keys(mockQuery).forEach((k) => delete mockQuery[k]);
    Object.assign(mockQuery, query);

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

    it('calls DELETE /api/exercises/:id when user confirms exercise deletion', async () => {
        vi.spyOn(window, 'confirm').mockReturnValue(true);
        // After delete, reload exercises
        vi.mocked(apiRequest).mockImplementation((url, options) => {
            if (url.startsWith('/api/exercises') && options?.method === 'delete') {
                return Promise.resolve({});
            }
            return Promise.resolve(exercisesResponse);
        });

        const wrapper = mountWorkoutPage({ tab: 'exercises' });
        await flushPromises();

        // Click first Delete button in the exercises section
        const deleteButtons = wrapper.findAll('button').filter((b) => b.text().trim() === 'Delete');
        await deleteButtons[0].trigger('click');
        await flushPromises();

        expect(window.confirm).toHaveBeenCalledWith(expect.stringContaining('Bench Press'));
        expect(apiRequest).toHaveBeenCalledWith(
            '/api/exercises/1',
            expect.objectContaining({ method: 'delete' }),
        );
    });

    it('does not delete an exercise when the user cancels the confirm dialog', async () => {
        vi.spyOn(window, 'confirm').mockReturnValue(false);

        const wrapper = mountWorkoutPage({ tab: 'exercises' });
        await flushPromises();

        const deleteButtons = wrapper.findAll('button').filter((b) => b.text().trim() === 'Delete');
        await deleteButtons[0].trigger('click');
        await flushPromises();

        expect(apiRequest).not.toHaveBeenCalledWith(
            expect.stringContaining('/api/exercises/'),
            expect.objectContaining({ method: 'delete' }),
        );
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

    it('calls DELETE /api/workout-programs/:id when user confirms program deletion', async () => {
        vi.spyOn(window, 'confirm').mockReturnValue(true);
        vi.mocked(apiRequest).mockImplementation((url, options) => {
            if (url.startsWith('/api/workout-programs') && options?.method === 'delete') {
                return Promise.resolve({});
            }
            return Promise.resolve(programsResponse);
        });

        const wrapper = mountWorkoutPage();
        await flushPromises();

        const deleteButtons = wrapper.findAll('button').filter((b) => b.text().trim() === 'Delete');
        await deleteButtons[0].trigger('click');
        await flushPromises();

        expect(window.confirm).toHaveBeenCalledWith(expect.stringContaining('Strength Builder'));
        expect(apiRequest).toHaveBeenCalledWith(
            '/api/workout-programs/10',
            expect.objectContaining({ method: 'delete' }),
        );
    });

    it('navigates to exercise edit page when "Edit" is clicked', async () => {
        const wrapper = mountWorkoutPage({ tab: 'exercises' });
        await flushPromises();

        const editButtons = wrapper.findAll('button').filter((b) => b.text().trim() === 'Edit');
        await editButtons[0].trigger('click');

        expect(mockPush).toHaveBeenCalledWith('/workout/exercises/1/edit');
    });

    it('navigates to program manage page when "Manage" is clicked', async () => {
        const wrapper = mountWorkoutPage();
        await flushPromises();

        const manageButtons = wrapper.findAll('button').filter((b) => b.text().trim() === 'Manage');
        await manageButtons[0].trigger('click');

        expect(mockPush).toHaveBeenCalledWith('/workout/programs/10/edit');
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
