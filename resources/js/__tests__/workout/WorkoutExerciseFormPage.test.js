import { mount, flushPromises } from '@vue/test-utils';
import { vi, describe, it, beforeEach, expect } from 'vitest';
import WorkoutExerciseFormPage from '../../spa/pages/WorkoutExerciseFormPage.vue';
import { apiRequest } from '../../spa/composables/useApiClient';

// ---------------------------------------------------------------------------
// Module mocks (hoisted by Vitest)
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

// ---------------------------------------------------------------------------
// Global stubs — child components are replaced with minimal renders
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
        props: ['modelValue', 'type', 'min', 'placeholder', 'required'],
        emits: ['update:modelValue'],
    },
    AppSearchableDropdown: {
        name: 'AppSearchableDropdown',
        template: '<select><slot /></select>',
        props: ['modelValue', 'options', 'optionLabel', 'optionKey', 'placeholder', 'searchable', 'required'],
        emits: ['update:modelValue'],
    },
};

// ---------------------------------------------------------------------------
// Factory
// ---------------------------------------------------------------------------

function mountExerciseForm(params = {}) {
    Object.keys(mockParams).forEach((k) => delete mockParams[k]);
    Object.assign(mockParams, params);

    return mount(WorkoutExerciseFormPage, {
        global: { stubs: globalStubs },
    });
}

function findButtonByText(wrapper, text) {
    return wrapper.findAll('button').find((b) => b.text().trim() === text);
}

function removeButtons(wrapper) {
    return wrapper.findAll('button').filter((b) => b.text().trim() === 'Remove');
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe('WorkoutExerciseFormPage', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    // ── Create mode ──────────────────────────────────────────────────────────

    describe('create mode (no route id)', () => {
        it('shows "Add Exercise" in the page header title', () => {
            const wrapper = mountExerciseForm();
            expect(wrapper.find('header').attributes('data-title')).toBe('Add Exercise');
        });

        it('does not call apiRequest on mount', async () => {
            mountExerciseForm();
            await flushPromises();
            expect(apiRequest).not.toHaveBeenCalled();
        });

        it('renders exactly 1 variation row by default', () => {
            const wrapper = mountExerciseForm();
            expect(removeButtons(wrapper)).toHaveLength(1);
        });

        it('adds a variation row when "Add Variation" is clicked', async () => {
            const wrapper = mountExerciseForm();
            await findButtonByText(wrapper, 'Add Variation').trigger('click');
            expect(removeButtons(wrapper)).toHaveLength(2);
        });

        it('removes a variation row when "Remove" is clicked', async () => {
            const wrapper = mountExerciseForm();
            // Add one more so we have 2, then remove the first
            await findButtonByText(wrapper, 'Add Variation').trigger('click');
            expect(removeButtons(wrapper)).toHaveLength(2);
            await removeButtons(wrapper)[0].trigger('click');
            expect(removeButtons(wrapper)).toHaveLength(1);
        });

        it('calls POST /api/exercises when form is submitted', async () => {
            vi.mocked(apiRequest).mockResolvedValue({ data: { id: 99 } });
            const wrapper = mountExerciseForm();
            await wrapper.find('form').trigger('submit');
            await flushPromises();

            expect(apiRequest).toHaveBeenCalledWith(
                '/api/exercises',
                expect.objectContaining({ method: 'post' }),
            );
        });

        it('redirects to /workout?tab=exercises after successful save', async () => {
            vi.mocked(apiRequest).mockResolvedValue({ data: { id: 99 } });
            const wrapper = mountExerciseForm();
            await wrapper.find('form').trigger('submit');
            await flushPromises();

            expect(mockPush).toHaveBeenCalledWith('/workout?tab=exercises');
        });

        it('shows an error message when the API call fails', async () => {
            vi.mocked(apiRequest).mockRejectedValue({
                response: { data: { message: 'Exercise name already exists.' } },
            });
            const wrapper = mountExerciseForm();
            await wrapper.find('form').trigger('submit');
            await flushPromises();

            expect(wrapper.text()).toContain('Exercise name already exists.');
        });

        it('shows a generic fallback error when response has no message', async () => {
            vi.mocked(apiRequest).mockRejectedValue({});
            const wrapper = mountExerciseForm();
            await wrapper.find('form').trigger('submit');
            await flushPromises();

            expect(wrapper.text()).toContain('Failed to save exercise.');
        });
    });

    // ── Edit mode ────────────────────────────────────────────────────────────

    describe('edit mode (route id present)', () => {
        const mockExercise = {
            id: 42,
            name: 'Romanian Deadlift',
            status: 'active',
            default_sets: 4,
            default_reps: '8',
            default_tempo: '3-1-1',
            default_rest: 90,
            variations: [
                { id: 1, variation_name: 'Barbell RDL' },
                { id: 2, variation_name: 'Dumbbell RDL' },
            ],
        };

        beforeEach(() => {
            vi.mocked(apiRequest).mockResolvedValue({ data: mockExercise });
        });

        it('shows "Edit Exercise" in the page header title', () => {
            const wrapper = mountExerciseForm({ id: '42' });
            expect(wrapper.find('header').attributes('data-title')).toBe('Edit Exercise');
        });

        it('calls GET /api/exercises/:id on mount', async () => {
            mountExerciseForm({ id: '42' });
            await flushPromises();
            expect(apiRequest).toHaveBeenCalledWith('/api/exercises/42');
        });

        it('populates variation rows from loaded exercise data', async () => {
            const wrapper = mountExerciseForm({ id: '42' });
            await flushPromises();
            // mockExercise has 2 variations → 2 Remove buttons
            expect(removeButtons(wrapper)).toHaveLength(2);
        });

        it('calls PUT /api/exercises/:id when form is submitted', async () => {
            // First call → load, second call → save
            vi.mocked(apiRequest)
                .mockResolvedValueOnce({ data: mockExercise })
                .mockResolvedValueOnce({});

            const wrapper = mountExerciseForm({ id: '42' });
            await flushPromises();

            await wrapper.find('form').trigger('submit');
            await flushPromises();

            expect(apiRequest).toHaveBeenLastCalledWith(
                '/api/exercises/42',
                expect.objectContaining({ method: 'put' }),
            );
        });

        it('shows an error when load fails', async () => {
            vi.mocked(apiRequest).mockRejectedValue({
                response: { data: { message: 'Not found.' } },
            });
            const wrapper = mountExerciseForm({ id: '999' });
            await flushPromises();

            expect(wrapper.text()).toContain('Not found.');
        });
    });
});
