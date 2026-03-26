<template>
    <div
        v-if="show"
        class="flex w-full flex-col items-end gap-2 sm:flex-row sm:items-center sm:justify-end"
    >
        <div class="flex items-center justify-end gap-2 flex-nowrap overflow-x-auto max-w-full sm:w-auto">
            <button
                type="button"
                class="px-3 py-2 text-sm rounded-lg border border-secondary-300 dark:border-secondary-700 disabled:opacity-40"
                :disabled="disabled || currentPage <= 1"
                @click="$emit('page-change', currentPage - 1)"
            >
                ←
            </button>

            <div class="flex items-center gap-1 flex-nowrap">
                <button
                    v-for="page in availablePages"
                    :key="page"
                    type="button"
                    class="min-w-9 px-3 py-2 text-sm rounded-lg border"
                    :class="page === currentPage
                        ? 'bg-primary-600 border-primary-600 text-white'
                        : 'border-secondary-300 dark:border-secondary-700 text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-800'"
                    :disabled="disabled"
                    @click="$emit('page-change', page)"
                >
                    {{ page }}
                </button>
            </div>

            <button
                type="button"
                class="px-3 py-2 text-sm rounded-lg border border-secondary-300 dark:border-secondary-700 disabled:opacity-40"
                :disabled="disabled || currentPage >= lastPage"
                @click="$emit('page-change', currentPage + 1)"
            >
                →
            </button>
        </div>

        <div class="flex items-center justify-end gap-2 text-sm whitespace-nowrap overflow-x-auto max-w-full sm:w-auto sm:ml-2">
            <label class="text-secondary-500 dark:text-secondary-400">Go to</label>
            <input
                v-model.number="goToPage"
                type="number"
                min="1"
                :max="lastPage"
                class="w-20 px-2 py-1.5 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-900"
                :disabled="disabled"
                @keyup.enter="submitGoto"
            >
            <button
                type="button"
                class="px-3 py-1.5 rounded-lg border border-secondary-300 dark:border-secondary-700"
                :disabled="disabled"
                @click="submitGoto"
            >
                Go
            </button>

            <label class="ml-2 text-secondary-500 dark:text-secondary-400">Limit</label>
            <select
                v-model.number="selectedLimit"
                class="px-2 py-1.5 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-900"
                :disabled="disabled"
                @change="emitLimit"
            >
                <option v-for="option in pageSizeOptions" :key="option" :value="option">{{ option }}</option>
            </select>

            <span class="hidden sm:inline text-secondary-500 dark:text-secondary-400">Page {{ currentPage }} / {{ lastPage }} • {{ total }} total</span>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    currentPage: {
        type: Number,
        default: 1,
    },
    lastPage: {
        type: Number,
        default: 1,
    },
    perPage: {
        type: Number,
        default: 10,
    },
    total: {
        type: Number,
        default: 0,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    pageWindow: {
        type: Number,
        default: 2,
    },
    pageSizeOptions: {
        type: Array,
        default: () => [10, 15, 25, 50],
    },
});

const emit = defineEmits(['page-change', 'limit-change']);

const goToPage = ref(props.currentPage);
const selectedLimit = ref(props.perPage);

watch(
    () => props.currentPage,
    (value) => {
        goToPage.value = value;
    }
);

watch(
    () => props.perPage,
    (value) => {
        selectedLimit.value = value;
    }
);

const show = computed(() => props.total > 0);

const availablePages = computed(() => {
    const last = Math.max(1, props.lastPage);
    const current = Math.min(Math.max(1, props.currentPage), last);
    const windowSize = Math.max(1, props.pageWindow);

    const start = Math.max(1, current - windowSize);
    const end = Math.min(last, current + windowSize);

    const pages = [];
    for (let page = start; page <= end; page += 1) {
        pages.push(page);
    }

    if (!pages.includes(1)) {
        pages.unshift(1);
    }

    if (!pages.includes(last)) {
        pages.push(last);
    }

    return [...new Set(pages)].sort((a, b) => a - b);
});

function submitGoto() {
    const parsed = Number(goToPage.value || 1);
    const nextPage = Math.min(Math.max(1, parsed), Math.max(1, props.lastPage));
    goToPage.value = nextPage;

    if (nextPage !== props.currentPage) {
        emit('page-change', nextPage);
    }
}

function emitLimit() {
    const nextLimit = Number(selectedLimit.value || props.perPage);
    if (nextLimit !== props.perPage) {
        emit('limit-change', nextLimit);
    }
}
</script>
