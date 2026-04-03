<template>
    <div class="flex justify-center min-h-screen bg-background-light dark:bg-background-dark py-6 px-2 sm:px-0">
        <div class="w-full max-w-2xl bg-white dark:bg-secondary-900 rounded-xl shadow-sm border border-secondary-200 dark:border-secondary-700 p-4 sm:p-8">
            <h2 class="text-xl sm:text-2xl font-bold text-secondary-900 dark:text-white mb-2 text-center">{{ meta.name }}</h2>
            <div class="flex justify-center mb-6">
                <span class="text-secondary-500 dark:text-secondary-400">Hi Welcome to {{ meta.tenant_name }}</span>
            </div>

            <!-- Tabs -->
            <div class="w-full">
                <div class="flex border-b border-secondary-200 dark:border-secondary-700 mb-4">
                    <button
                        v-for="tabItem in tabs"
                        :key="tabItem.key"
                        type="button"
                        class="flex-1 py-2 text-center text-sm font-semibold focus:outline-none transition-colors"
                        :class="activeTab === tabItem.key
                            ? 'border-b-2 border-primary-600 text-primary-700 dark:text-primary-300'
                            : 'text-secondary-700 dark:text-secondary-300'"
                        @click="activeTab = tabItem.key"
                    >{{ tabItem.label }}</button>
                </div>

                <!-- Profile Tab -->
                <div v-show="activeTab === 'profile'" class="space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <span class="block text-xs text-secondary-500 dark:text-secondary-400">Full Name</span>
                            <span class="block text-base font-medium text-secondary-900 dark:text-white">{{ meta.name }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-secondary-500 dark:text-secondary-400">Username</span>
                            <span class="block text-base font-medium text-secondary-900 dark:text-white">{{ meta.username }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-secondary-500 dark:text-secondary-400">Gender</span>
                            <span class="block text-base font-medium text-secondary-900 dark:text-white">{{ capitalize(meta.gender) }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-secondary-500 dark:text-secondary-400">Joined</span>
                            <span class="block text-base font-medium text-secondary-900 dark:text-white">{{ meta.joined_date ?? '-' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Workout Tab -->
                <div v-show="activeTab === 'workout'" class="space-y-2">
                    <div v-if="!workoutsData.length" class="text-secondary-500 dark:text-secondary-400 text-center py-6">
                        No assigned workout plans.
                    </div>
                    <div v-else class="divide-y divide-secondary-100 dark:divide-secondary-800">
                        <button
                            v-for="(workout, i) in workoutsData"
                            :key="i"
                            type="button"
                            class="w-full text-left py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 cursor-pointer hover:bg-secondary-50 dark:hover:bg-secondary-800/50 rounded px-2 -mx-2 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500"
                            @click="openWorkout(workout)"
                        >
                            <div>
                                <div class="font-semibold text-secondary-900 dark:text-white">{{ workout.title }}</div>
                                <div class="text-xs text-secondary-500 dark:text-secondary-400">Effective: {{ workout.effective_date || '-' }}</div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="text-xs text-secondary-500 dark:text-secondary-400">{{ workout.duration_weeks || '-' }} weeks</span>
                                <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Finance Tab -->
                <div v-show="activeTab === 'finance'" class="space-y-3">
                    <div class="flex justify-between items-center mb-2">
                        <span class="font-semibold text-secondary-900 dark:text-white">Total Outstanding:</span>
                        <span class="text-lg font-bold text-red-600">{{ meta.total_outstanding }}</span>
                    </div>
                    <div v-if="!salesData.length" class="text-secondary-500 dark:text-secondary-400 text-center py-6">
                        No finance records found.
                    </div>
                    <div v-else class="divide-y divide-secondary-100 dark:divide-secondary-800">
                        <button
                            v-for="(sale, i) in salesData"
                            :key="i"
                            type="button"
                            class="w-full text-left py-3 flex flex-col gap-1 rounded px-2 -mx-2 transition-colors cursor-pointer hover:bg-secondary-50 dark:hover:bg-secondary-800/50 focus:outline-none focus:ring-2 focus:ring-primary-500"
                            :class="!sale.is_paid ? 'bg-red-50 dark:bg-red-900/30 border-l-4 border-red-400' : ''"
                            @click="openSale(sale)"
                        >
                            <div class="flex flex-wrap justify-between items-center">
                                <span class="font-semibold text-secondary-900 dark:text-white">Invoice #{{ sale.id }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-secondary-500 dark:text-secondary-400">{{ sale.created_at }}</span>
                                    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </div>
                            </div>
                            <div class="flex flex-wrap justify-between items-center gap-x-4 text-sm">
                                <span>Total: <span class="font-semibold">{{ sale.total_amount }}</span></span>
                                <span>Paid: <span class="font-semibold">{{ sale.paid_amount }}</span></span>
                                <span>Outstanding: <span class="font-semibold" :class="!sale.is_paid ? 'text-red-600' : ''">{{ sale.balance }}</span></span>
                            </div>
                            <span v-if="!sale.is_paid" class="text-xs text-red-600 font-semibold">Unpaid</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Workout Preview Modal -->
        <Teleport to="body">
            <div
                v-if="activeWorkout"
                class="fixed inset-0 z-50 flex items-start justify-center p-3 sm:p-6 overflow-y-auto"
                @keydown.escape.window="closeWorkout"
            >
                <div class="fixed inset-0 bg-black/50" @click="closeWorkout"></div>
                <div class="relative w-full max-w-4xl my-4">
                    <div class="flex justify-end mb-2">
                        <button
                            type="button"
                            class="p-2 rounded-lg bg-white dark:bg-secondary-800 text-secondary-500 hover:text-secondary-700 dark:text-secondary-400 dark:hover:text-secondary-200 shadow transition-colors"
                            @click="closeWorkout"
                            aria-label="Close"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <WorkoutProgramPreviewCard :program="activeWorkout" />
                </div>
            </div>
        </Teleport>

        <!-- Sale Invoice Preview Modal -->
        <Teleport to="body">
            <div
                v-if="activeSale"
                class="fixed inset-0 z-50 flex items-start justify-center p-3 sm:p-6 overflow-y-auto"
                @keydown.escape.window="closeSale"
            >
                <div class="fixed inset-0 bg-black/50" @click="closeSale"></div>
                <div class="relative w-full max-w-2xl my-4">
                    <div class="flex justify-end mb-2">
                        <button
                            type="button"
                            class="p-2 rounded-lg bg-white dark:bg-secondary-800 text-secondary-500 hover:text-secondary-700 dark:text-secondary-400 dark:hover:text-secondary-200 shadow transition-colors"
                            @click="closeSale"
                            aria-label="Close"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <SaleInvoicePreviewCard :sale="activeSale" />
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import WorkoutProgramPreviewCard from './WorkoutProgramPreviewCard.vue';
import SaleInvoicePreviewCard from './SaleInvoicePreviewCard.vue';

const props = defineProps({
    workoutsData: { type: Array, default: () => [] },
    salesData: { type: Array, default: () => [] },
    meta: { type: Object, required: true },
});

const tabs = [
    { key: 'profile', label: 'Profile' },
    { key: 'workout', label: 'Workout' },
    { key: 'finance', label: 'Finance' },
];

const activeTab = ref('profile');
const activeWorkout = ref(null);
const activeSale = ref(null);

function openWorkout(workout) { activeWorkout.value = workout; }
function closeWorkout() { activeWorkout.value = null; }

function openSale(sale) { activeSale.value = sale; }
function closeSale() { activeSale.value = null; }

function capitalize(val) {
    if (!val) return '-';
    return val.charAt(0).toUpperCase() + val.slice(1);
}
</script>
