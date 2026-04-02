import { mount, flushPromises } from '@vue/test-utils';
import { vi, describe, it, beforeEach, expect } from 'vitest';
import WorkoutProgramFormPage from '../../spa/pages/WorkoutProgramFormPage.vue';
import { apiRequest } from '../../spa/composables/useApiClient';

// ---------------------------------------------------------------------------
// Module mocks
// ---------------------------------------------------------------------------

const mockPush = vi.fn();
const mockParams = {};

vi.mock('vue-router', () => ({
    useRoute: () => ({ params: mockParams, query: {} }),
    useRouter: () => ({ push: mockPush }),
    RouterLink: { name: 'RouterLink', template: '<a><slot /></a>', props: ['to'] },
}));

vi.mock('../../spa/composables/useApiClient', () => ({
    apiRequest: vi.fn(),
}));

// useAppContext returns a default object via inject — no need to mock it

// ---------------------------------------------------------------------------
// Global stubs
// ---------------------------------------------------------------------------

const globalStubs = {
    AppPageHeader: {
        name: 'AppPageHeader',
        template: '<header :data-title="title"><slot /><slot name="extra-slot" /><slot name="cta-slot" /></header>',
        props: ['title', 'showBack'],
    },
    AppFormField: {
        name: 'AppFormField',
        template: '<div><slot /></div>',
        props: ['label', 'required', 'optional'],
    },
    AppFormInput: {
        name: 'AppFormInput',
        template: '<input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
        props: ['modelValue', 'type', 'min', 'max', 'placeholder', 'required'],
        emits: ['update:modelValue'],
    },
    AppFormTextarea: {
        name: 'AppFormTextarea',
        template: '<textarea :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)"></textarea>',
        props: ['modelValue', 'rows', 'placeholder'],
        emits: ['update:modelValue'],
    },
    AppSearchableDropdown: {
        name: 'AppSearchableDropdown',
        template: '<select><slot /></select>',
        props: ['modelValue', 'options', 'optionLabel', 'optionKey', 'placeholder', 'disabled'],
        emits: ['update:modelValue'],
    },
    WorkoutProgramPreviewCard: {
        name: 'WorkoutProgramPreviewCard',
        template: '<div data-testid="preview-card">Preview</div>',
        props: ['program'],
    },
};

// ---------------------------------------------------------------------------
// Factory
// ---------------------------------------------------------------------------

function mountProgramForm(params = {}) {
    Object.keys(mockParams).forEach((k) => delete mockParams[k]);
    Object.assign(mockParams, params);

    return mount(WorkoutProgramFormPage, {
        global: { stubs: globalStubs },
    });
}

function findButtonByText(wrapper, text) {
    return wrapper.findAll('button').find((b) => b.text().trim() === text);
}

function findAllButtonsByText(wrapper, text) {
    return wrapper.findAll('button').filter((b) => b.text().trim() === text);
}

function paragraphsWithText(wrapper, regex) {
    return wrapper.findAll('p').filter((p) => regex.test(p.text().trim()));
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe('WorkoutProgramFormPage', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        // Exercises API call always returns empty list
        vi.mocked(apiRequest).mockResolvedValue({ data: { data: [] } });
    });

    // ── Create mode ──────────────────────────────────────────────────────────

    describe('create mode (no route id)', () => {
        it('shows "Create Workout Program" in the page header', () => {
            const wrapper = mountProgramForm();
            expect(wrapper.find('header').attributes('data-title')).toBe('Create Workout Program');
        });

        it('calls GET /api/exercises on mount to populate exercise selector', async () => {
            mountProgramForm();
            await flushPromises();
            expect(apiRequest).toHaveBeenCalledWith(
                '/api/exercises',
                expect.objectContaining({ params: { per_page: 100 } }),
            );
        });

        it('does not call GET /api/workout-programs in create mode', async () => {
            mountProgramForm();
            await flushPromises();
            // Only the exercises call should have been made
            expect(apiRequest).toHaveBeenCalledTimes(1);
        });

        it('starts with one workout day and one exercise row', async () => {
            const wrapper = mountProgramForm();
            await flushPromises();
            // One day → one "Add Exercise Row" button
            expect(findAllButtonsByText(wrapper, 'Add Exercise Row')).toHaveLength(1);
            // One exercise row in that day → "Exercise 1" label
            expect(paragraphsWithText(wrapper, /^Exercise 1$/)).toHaveLength(1);
        });

        it('"Add Day" button adds another workout day', async () => {
            const wrapper = mountProgramForm();
            await flushPromises();
            await findButtonByText(wrapper, 'Add Day').trigger('click');
            // Now 2 days → 2 "Add Exercise Row" buttons
            expect(findAllButtonsByText(wrapper, 'Add Exercise Row')).toHaveLength(2);
        });

        it('"Add Exercise Row" adds a second exercise row inside the day', async () => {
            const wrapper = mountProgramForm();
            await flushPromises();
            await findButtonByText(wrapper, 'Add Exercise Row').trigger('click');
            expect(paragraphsWithText(wrapper, /^Exercise [12]$/)).toHaveLength(2);
        });

        it('"Add Row" in the Core section adds a core extra row', async () => {
            const wrapper = mountProgramForm();
            await flushPromises();
            // No core rows initially
            expect(paragraphsWithText(wrapper, /^Core Row/)).toHaveLength(0);
            // Click the Core section's "Add Row" button
            const addCoreBtn = findAllButtonsByText(wrapper, 'Add Row')[0];
            await addCoreBtn.trigger('click');
            expect(paragraphsWithText(wrapper, /^Core Row 1$/)).toHaveLength(1);
        });

        it('"Add Row" in the Cardio section adds a cardio extra row', async () => {
            const wrapper = mountProgramForm();
            await flushPromises();
            expect(paragraphsWithText(wrapper, /^Cardio Row/)).toHaveLength(0);
            // Click the Cardio section's "Add Row" button (second "Add Row" button in page)
            const addCardioBtn = findAllButtonsByText(wrapper, 'Add Row')[1];
            await addCardioBtn.trigger('click');
            expect(paragraphsWithText(wrapper, /^Cardio Row 1$/)).toHaveLength(1);
        });

        it('"Preview Output" button switches to the preview tab', async () => {
            const wrapper = mountProgramForm();
            await flushPromises();
            // Preview card not visible in builder tab
            expect(wrapper.find('[data-testid="preview-card"]').exists()).toBe(false);
            await findButtonByText(wrapper, 'Preview Output').trigger('click');
            expect(wrapper.find('[data-testid="preview-card"]').exists()).toBe(true);
        });

        it('calls POST /api/workout-programs when "Save Program" is clicked', async () => {
            vi.mocked(apiRequest)
                .mockResolvedValueOnce({ data: { data: [] } }) // exercises
                .mockResolvedValue({ data: { id: 7 } });       // program POST + subsequent calls

            const wrapper = mountProgramForm();
            await flushPromises();
            await findButtonByText(wrapper, 'Save Program').trigger('click');
            await flushPromises();

            expect(apiRequest).toHaveBeenCalledWith(
                '/api/workout-programs',
                expect.objectContaining({ method: 'post' }),
            );
        });

        it('redirects to /workout?tab=programs after save', async () => {
            vi.mocked(apiRequest)
                .mockResolvedValueOnce({ data: { data: [] } }) // exercises
                .mockResolvedValue({ data: { id: 7 } });

            const wrapper = mountProgramForm();
            await flushPromises();
            await findButtonByText(wrapper, 'Save Program').trigger('click');
            await flushPromises();

            expect(mockPush).toHaveBeenCalledWith('/workout?tab=programs');
        });

        it('shows error message when save fails', async () => {
            vi.mocked(apiRequest)
                .mockResolvedValueOnce({ data: { data: [] } }) // exercises
                .mockRejectedValue({
                    response: { data: { message: 'Title required.' } },
                });

            const wrapper = mountProgramForm();
            await flushPromises();
            await findButtonByText(wrapper, 'Save Program').trigger('click');
            await flushPromises();

            expect(wrapper.text()).toContain('Title required.');
        });
    });

    // ── Edit mode ────────────────────────────────────────────────────────────

    describe('edit mode (route id present)', () => {
        const mockProgram = {
            id: 5,
            title: 'Strength Builder',
            description: 'Build total body strength.',
            duration_weeks: 12,
            days: [
                {
                    id: 10,
                    day_number: 1,
                    title: 'Push Day',
                    exercises: [
                        {
                            id: 100,
                            exercise_id: 1,
                            exercise_name: 'Bench Press',
                            w1_w3_exercise: '',
                            w2_w4_exercise: '',
                            sets: 4,
                            reps: '8',
                            tempo: '3-1-1',
                            rest_seconds: 90,
                            exercise_order: 1,
                        },
                    ],
                },
            ],
            extras: [],
        };

        beforeEach(() => {
            vi.mocked(apiRequest)
                .mockResolvedValueOnce({ data: { data: [] } })  // GET /api/exercises
                .mockResolvedValue({ data: mockProgram });       // GET program + any save calls
        });

        it('shows "Edit Workout Program" in the page header', () => {
            const wrapper = mountProgramForm({ id: '5' });
            expect(wrapper.find('header').attributes('data-title')).toBe('Edit Workout Program');
        });

        it('calls GET /api/workout-programs/:id on mount', async () => {
            mountProgramForm({ id: '5' });
            await flushPromises();
            expect(apiRequest).toHaveBeenCalledWith('/api/workout-programs/5');
        });

        it('hydrates builder with program days from API response', async () => {
            const wrapper = mountProgramForm({ id: '5' });
            await flushPromises();
            // Mock program has 1 day → 1 "Add Exercise Row" button
            expect(findAllButtonsByText(wrapper, 'Add Exercise Row')).toHaveLength(1);
        });

        it('calls PUT /api/workout-programs/:id when "Save Program" is clicked', async () => {
            vi.mocked(apiRequest)
                .mockReset()
                .mockResolvedValueOnce({ data: { data: [] } })  // exercises
                .mockResolvedValueOnce({ data: mockProgram })    // load program
                .mockResolvedValue({});                          // subsequent save calls

            const wrapper = mountProgramForm({ id: '5' });
            await flushPromises();
            await findButtonByText(wrapper, 'Save Program').trigger('click');
            await flushPromises();

            expect(apiRequest).toHaveBeenCalledWith(
                '/api/workout-programs/5',
                expect.objectContaining({ method: 'put' }),
            );
        });
    });
});
