<template>
    <section class="app-page-frame">
        <AppPageHeader :show-back="true" />
        <div class="app-page-scroll">
            <div v-if="loadingEvent" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">Loading...</div>
            <form v-else class="space-y-6" @submit.prevent="submit">
                <!-- Basic info -->
                <div class="app-surface rounded-2xl p-4 md:p-6 space-y-4">
                    <h2 class="text-base font-semibold text-secondary-900 dark:text-white">Event Details</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Event Name <span class="text-red-500">*</span></label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                maxlength="255"
                                class="app-form-input w-full"
                                placeholder="e.g. Annual Fitness Championship 2026"
                                @blur="syncSlug"
                            />
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">
                                Slug (URL identifier)
                                <span class="text-xs text-secondary-400 font-normal ml-1">— auto-generated from name</span>
                            </label>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-secondary-400 shrink-0">/profile/event/</span>
                                <input
                                    v-model="form.slug"
                                    type="text"
                                    maxlength="150"
                                    class="app-form-input flex-1"
                                    placeholder="my-event-slug"
                                />
                            </div>
                            <p v-if="form.slug" class="mt-1 text-xs text-secondary-400">
                                Registration link: <span class="font-mono text-primary-600 dark:text-primary-400 select-all">{{ registrationLink }}</span>
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Start Date & Time <span class="text-red-500">*</span></label>
                            <input v-model="form.start_datetime" type="datetime-local" required class="app-form-input w-full" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">End Date & Time</label>
                            <input v-model="form.end_datetime" type="datetime-local" class="app-form-input w-full" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Venue</label>
                            <input v-model="form.venue" type="text" maxlength="255" class="app-form-input w-full" placeholder="e.g. Main Hall, City Sport Center" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Venue URL <span class="font-normal text-secondary-400">(optional)</span></label>
                            <input v-model="form.venue_url" type="url" maxlength="500" class="app-form-input w-full" placeholder="https://maps.google.com/..." />
                        </div>
                    </div>
                </div>

                <!-- Agenda & Registration process -->
                <div class="app-surface rounded-2xl p-4 md:p-6 space-y-4">
                    <h2 class="text-base font-semibold text-secondary-900 dark:text-white">Agenda & Process</h2>

                    <div>
                        <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Agenda</label>
                        <textarea
                            v-model="form.agenda"
                            rows="5"
                            class="app-form-input w-full resize-y"
                            placeholder="Describe the event schedule and agenda..."
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Registration Process</label>
                        <textarea
                            v-model="form.registration_process"
                            rows="3"
                            class="app-form-input w-full resize-y"
                            placeholder="Describe what registrants need to do, any requirements, deadlines, etc."
                        ></textarea>
                    </div>
                </div>

                <!-- Fees -->
                <div class="app-surface rounded-2xl p-4 md:p-6 space-y-4">
                    <h2 class="text-base font-semibold text-secondary-900 dark:text-white">Ticket Fees</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Ticket Fee per Person</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-secondary-400 text-sm">$</span>
                                <input
                                    v-model="form.ticket_fee"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="app-form-input w-full pl-7"
                                    placeholder="0.00"
                                />
                            </div>
                            <p class="mt-1 text-xs text-secondary-400">Main registrant ticket fee. Set to 0 for a free event.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Additional Family Member Fee</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-secondary-400 text-sm">$</span>
                                <input
                                    v-model="form.additional_ticket_fee"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="app-form-input w-full pl-7"
                                    placeholder="0.00"
                                />
                            </div>
                            <p class="mt-1 text-xs text-secondary-400">Fee charged per additional family member added to a registration.</p>
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div class="app-surface rounded-2xl p-4 md:p-6">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-secondary-300 dark:border-secondary-600 text-primary-600 focus:ring-primary-500" />
                        <div>
                            <span class="text-sm font-medium text-secondary-900 dark:text-white">Active (accepting registrations)</span>
                            <p class="text-xs text-secondary-400 mt-0.5">When inactive, the event page is hidden from the public registration link.</p>
                        </div>
                    </label>
                </div>

                <div v-if="errorMessage" class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
                    {{ errorMessage }}
                </div>

                <div class="flex items-center justify-end gap-3 pb-6">
                    <RouterLink to="/events" class="px-4 py-2 text-sm font-medium text-secondary-600 dark:text-secondary-400 hover:text-secondary-900 dark:hover:text-white transition-colors">
                        Cancel
                    </RouterLink>
                    <button
                        type="submit"
                        :disabled="submitting"
                        class="px-5 py-2 bg-primary-600 hover:bg-primary-700 disabled:opacity-60 text-white text-sm font-semibold rounded-xl transition-colors"
                    >
                        {{ submitting ? 'Saving...' : (isEdit ? 'Update Event' : 'Create Event') }}
                    </button>
                </div>
            </form>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiRequest } from '../composables/useApiClient';
import AppPageHeader from '../components/AppPageHeader.vue';

const route      = useRoute();
const router     = useRouter();
const isEdit     = computed(() => Boolean(route.params.id));
const submitting = ref(false);
const loadingEvent = ref(false);
const errorMessage = ref('');

const form = ref({
    name:                  '',
    slug:                  '',
    start_datetime:        '',
    end_datetime:          '',
    venue:                 '',
    venue_url:             '',
    agenda:                '',
    registration_process:  '',
    ticket_fee:            0,
    additional_ticket_fee: 0,
    is_active:             true,
});

const registrationLink = computed(() => {
    if (!form.value.slug) return '';
    const base = window.location.origin;
    return `${base}/profile/event/${form.value.slug}`;
});

function slugify(text) {
    return text.toLowerCase().trim()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-');
}

function syncSlug() {
    if (!form.value.slug && form.value.name) {
        form.value.slug = slugify(form.value.name);
    }
}

async function loadEvent() {
    if (!isEdit.value) return;
    loadingEvent.value = true;
    try {
        const data = await apiRequest(`/api/events/${route.params.id}`);
        form.value = {
            name:                  data.name,
            slug:                  data.slug,
            start_datetime:        toLocalDatetimeInput(data.start_datetime),
            end_datetime:          data.end_datetime ? toLocalDatetimeInput(data.end_datetime) : '',
            venue:                 data.venue || '',
            venue_url:             data.venue_url || '',
            agenda:                data.agenda || '',
            registration_process:  data.registration_process || '',
            ticket_fee:            data.ticket_fee,
            additional_ticket_fee: data.additional_ticket_fee,
            is_active:             data.is_active,
        };
    } catch {
        errorMessage.value = 'Failed to load event.';
    } finally {
        loadingEvent.value = false;
    }
}

function toLocalDatetimeInput(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    const pad = n => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

async function submit() {
    submitting.value = true;
    errorMessage.value = '';
    try {
        const payload = { ...form.value };
        if (!payload.end_datetime) payload.end_datetime = null;
        if (!payload.slug) payload.slug = slugify(payload.name);

        if (isEdit.value) {
            await apiRequest(`/api/events/${route.params.id}`, { method: 'put', data: payload });
        } else {
            await apiRequest('/api/events', { method: 'post', data: payload });
        }
        router.push('/events');
    } catch (e) {
        errorMessage.value = e?.response?.data?.message || Object.values(e?.response?.data?.errors || {})[0]?.[0] || 'Failed to save event.';
    } finally {
        submitting.value = false;
    }
}

onMounted(() => loadEvent());
</script>
