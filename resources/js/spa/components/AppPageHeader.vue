<template>
    <div class="app-page-header-compact">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <!-- Back button -->
                <button
                    v-if="showBack"
                    type="button"
                    @click="goBack"
                    class="shrink-0 inline-flex items-center justify-center w-9 h-9 rounded-full border border-secondary-300 dark:border-secondary-700 text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-800 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                <!-- Title -->
                <h2 class="text-xl md:text-2xl font-bold text-secondary-900 dark:text-white truncate">
                    {{ resolvedTitle }}
                </h2>
            </div>

            <!-- CTAs slot -->
            <div v-if="$slots['cta-slot']" class="flex items-center gap-2 shrink-0">
                <slot name="cta-slot" />
            </div>
        </div>

        <!-- Extra slot: search bar, tabs, etc. -->
        <div v-if="$slots['extra-slot']" class="mt-3">
            <slot name="extra-slot" />
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const props = defineProps({
    /** Override the route-derived title when needed (e.g. dynamic "Edit X" vs "New X"). */
    title: { type: String, default: null },
    /** Show/hide the round back icon button before the title. */
    showBack: { type: Boolean, default: false },
});

const route = useRoute();
const router = useRouter();

const resolvedTitle = computed(() => {
    if (props.title) return props.title;
    if (route.meta?.title) return route.meta.title;
    // Derive from the last meaningful path segment as a fallback.
    const segment = route.path.split('/').filter(Boolean).pop() || '';
    return segment.replace(/-/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()) || 'Page';
});

function goBack() {
    router.back();
}
</script>
