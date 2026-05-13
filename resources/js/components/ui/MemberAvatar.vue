<template>
    <div
        class="shrink-0 overflow-hidden flex items-center justify-center"
        :class="[sizeClass, shapeClass, bgClass]"
    >
        <img
            v-if="src && !imgFailed"
            :src="src"
            :alt="initials"
            class="w-full h-full object-cover"
            @error="imgFailed = true"
        />
        <span v-else :class="textClass" class="font-bold text-white select-none leading-none">
            {{ initials || '?' }}
        </span>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

/**
 * Shared member avatar component — works in both SPA and public profile.
 *
 * Props:
 *   src       — presigned photo URL (null = show initials)
 *   initials  — 1-2 letter fallback shown when no photo
 *   size      — 'xs' (32) | 'sm' (40) | 'md' (48) | 'lg' (64) | 'xl' (80)
 *   shape     — 'circle' | 'square'
 *   variant   — 'default'  gradient bg, for card/list surfaces
 *               'glass'    bg-white/20 + border, for colour-banner surfaces
 */
const props = defineProps({
    src:      { type: String,  default: null },
    initials: { type: String,  default: '?' },
    size:     { type: String,  default: 'md' },
    shape:    { type: String,  default: 'circle' },
    variant:  { type: String,  default: 'default' },
});

// Reset error state whenever the src URL changes (e.g. after a fresh upload)
const imgFailed = ref(false);
watch(() => props.src, () => { imgFailed.value = false; });

const SIZE_MAP = {
    xs:  'h-8 w-8',
    sm:  'h-10 w-10',
    md:  'h-12 w-12',
    lg:  'h-16 w-16',
    xl:  'h-20 w-20',
    '2xl': 'h-24 w-24',
};

const TEXT_MAP = {
    xs:  'text-xs',
    sm:  'text-xs',
    md:  'text-sm',
    lg:  'text-lg',
    xl:  'text-2xl',
    '2xl': 'text-3xl',
};

const sizeClass = computed(() => SIZE_MAP[props.size] ?? SIZE_MAP.md);
const textClass = computed(() => TEXT_MAP[props.size] ?? TEXT_MAP.md);

const shapeClass = computed(() =>
    props.shape === 'square' ? 'rounded-2xl' : 'rounded-full',
);

const bgClass = computed(() => {
    if (props.src && !imgFailed.value) return '';
    return props.variant === 'glass'
        ? 'bg-white/20 border border-white/40'
        : 'bg-gradient-to-br from-primary-500 to-primary-700 shadow-sm shadow-primary-500/20';
});
</script>
