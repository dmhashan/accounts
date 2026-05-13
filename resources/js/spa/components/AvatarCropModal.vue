<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-3 bg-black/75" @mousedown.self="cancel">
            <div
                class="bg-white dark:bg-secondary-900 rounded-2xl shadow-2xl w-full max-w-[360px] overflow-hidden flex flex-col">

                <!-- Header -->
                <div
                    class="px-5 py-3.5 border-b border-secondary-100 dark:border-secondary-800 flex items-center justify-between shrink-0">
                    <h3 class="text-sm font-semibold text-secondary-900 dark:text-white">Crop Photo</h3>
                    <button type="button"
                        class="text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-200 transition-colors"
                        @click="cancel">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Crop area -->
                <div class="px-4 pt-4 pb-2 flex flex-col items-center">
                    <div ref="cropEl" class="relative overflow-hidden rounded-xl select-none touch-none bg-black"
                        :class="loaded ? 'cursor-grab active:cursor-grabbing' : 'cursor-default'"
                        :style="{ width: `${SIZE}px`, height: `${SIZE}px` }" @mousedown="onMouseDown"
                        @touchstart.prevent="onTouchStart" @wheel.prevent="onWheel">
                        <img v-if="imageSrc" ref="imgEl" :src="imageSrc" draggable="false"
                            class="absolute pointer-events-none" :style="imgStyle" @load="onLoad" />
                        <!-- Rule-of-thirds grid overlay -->
                        <div class="absolute inset-0 pointer-events-none rounded-xl" style="
                            background:
                                linear-gradient(to right,  rgba(255,255,255,.18) 1px, transparent 1px) 0 0 / 33.333% 100%,
                                linear-gradient(to bottom, rgba(255,255,255,.18) 1px, transparent 1px) 0 0 / 100% 33.333%;
                            box-shadow: inset 0 0 0 2px rgba(255,255,255,.55);
                        "></div>
                        <!-- Loading placeholder -->
                        <div v-if="!loaded"
                            class="absolute inset-0 flex items-center justify-center text-white/40 text-sm">Loading…
                        </div>
                    </div>

                    <!-- Zoom slider -->
                    <div class="mt-3 w-full flex items-center gap-2.5">
                        <svg class="w-4 h-4 shrink-0 text-secondary-400 dark:text-secondary-500" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7" />
                        </svg>
                        <input type="range" class="flex-1 accent-primary-600" :min="minScale" :max="maxScale"
                            :step="0.001" :value="scale" :disabled="!loaded" @input="onZoomSlider" />
                        <svg class="w-4 h-4 shrink-0 text-secondary-400 dark:text-secondary-500" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                        </svg>
                    </div>
                    <p class="mt-1 text-[11px] text-secondary-400 dark:text-secondary-500">Drag to reposition · Scroll
                        or pinch to zoom</p>
                </div>

                <!-- Footer -->
                <div class="px-5 py-4 flex justify-end gap-3 shrink-0">
                    <button type="button"
                        class="px-4 py-2 text-sm font-medium rounded-lg bg-secondary-100 dark:bg-secondary-800 text-secondary-700 dark:text-secondary-300 hover:bg-secondary-200 dark:hover:bg-secondary-700 transition-colors"
                        @click="cancel">Cancel</button>
                    <button type="button"
                        class="px-4 py-2 text-sm font-semibold rounded-lg bg-primary-600 hover:bg-primary-700 text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="!loaded" @click="confirm">Crop & Save</button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, computed } from 'vue';

// Square crop display size in px — fits on 375px+ screens with padding
const SIZE = 300;

const props = defineProps({
    imageSrc: { type: String, required: true },
});

const emit = defineEmits(['confirm', 'cancel']);

const cropEl = ref(null);
const imgEl = ref(null);
const loaded = ref(false);

let naturalW = 0;
let naturalH = 0;

const scale = ref(1);
const dragX = ref(0);
const dragY = ref(0);
const minScale = ref(1);
const maxScale = ref(4);

const imgStyle = computed(() => ({
    width: `${naturalW * scale.value}px`,
    height: `${naturalH * scale.value}px`,
    left: `${dragX.value}px`,
    top: `${dragY.value}px`,
    maxWidth: 'none',   // override Tailwind preflight max-width: 100%
    maxHeight: 'none',   // override Tailwind preflight height: auto
}));

// ── Image load ──────────────────────────────────────────────────────────────

function onLoad() {
    naturalW = imgEl.value.naturalWidth;
    naturalH = imgEl.value.naturalHeight;

    const cover = Math.max(SIZE / naturalW, SIZE / naturalH);
    minScale.value = cover;
    maxScale.value = cover * 4;
    scale.value = cover;

    // Center image in crop box
    dragX.value = (SIZE - naturalW * cover) / 2;
    dragY.value = (SIZE - naturalH * cover) / 2;

    loaded.value = true;
}

// ── Clamp drag so image always fills the crop box ───────────────────────────

function clamp() {
    const minX = SIZE - naturalW * scale.value;
    const minY = SIZE - naturalH * scale.value;
    dragX.value = Math.min(0, Math.max(minX, dragX.value));
    dragY.value = Math.min(0, Math.max(minY, dragY.value));
}

// ── Mouse drag ──────────────────────────────────────────────────────────────

let lastMX = 0;
let lastMY = 0;

function onMouseDown(e) {
    if (e.button !== 0 || !loaded.value) return;
    lastMX = e.clientX;
    lastMY = e.clientY;
    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('mouseup', onMouseUp);
}

function onMouseMove(e) {
    dragX.value += e.clientX - lastMX;
    dragY.value += e.clientY - lastMY;
    lastMX = e.clientX;
    lastMY = e.clientY;
    clamp();
}

function onMouseUp() {
    window.removeEventListener('mousemove', onMouseMove);
    window.removeEventListener('mouseup', onMouseUp);
}

// ── Touch: pan + pinch-to-zoom ───────────────────────────────────────────────

let lastTouches = [];

function onTouchStart(e) {
    if (!loaded.value) return;
    lastTouches = touchList(e.touches);
    window.addEventListener('touchmove', onTouchMove, { passive: false });
    window.addEventListener('touchend', onTouchEnd);
}

function onTouchMove(e) {
    e.preventDefault();
    const touches = touchList(e.touches);

    if (touches.length === 1 && lastTouches.length >= 1) {
        // Pan
        dragX.value += touches[0].x - lastTouches[0].x;
        dragY.value += touches[0].y - lastTouches[0].y;
        clamp();
    } else if (touches.length === 2 && lastTouches.length === 2) {
        // Pinch-zoom + pan — handled in one pass so there's no double-counting.
        // All client coordinates are converted to crop-box-local before use.
        const rect = cropEl.value.getBoundingClientRect();
        const prevMid = midpoint(lastTouches[0], lastTouches[1]);
        const newMid = midpoint(touches[0], touches[1]);
        const prevDist = dist(lastTouches[0], lastTouches[1]);
        const newDist = dist(touches[0], touches[1]);

        const ratio = prevDist > 0 ? newDist / prevDist : 1;
        const newScale = Math.min(maxScale.value, Math.max(minScale.value, scale.value * ratio));
        const actualRatio = newScale / scale.value;

        // Convert midpoints to crop-box-local coordinates
        const prevLocal = { x: prevMid.x - rect.left, y: prevMid.y - rect.top };
        const newLocal = { x: newMid.x - rect.left, y: newMid.y - rect.top };

        // Zoom around prevLocal then translate so that point lands at newLocal
        dragX.value = newLocal.x - (prevLocal.x - dragX.value) * actualRatio;
        dragY.value = newLocal.y - (prevLocal.y - dragY.value) * actualRatio;
        scale.value = newScale;
        clamp();
    }

    lastTouches = touches;
}

function onTouchEnd(e) {
    lastTouches = touchList(e.touches);
    if (e.touches.length === 0) {
        window.removeEventListener('touchmove', onTouchMove);
        window.removeEventListener('touchend', onTouchEnd);
    }
}

// ── Wheel zoom ───────────────────────────────────────────────────────────────

function onWheel(e) {
    const factor = e.deltaY > 0 ? 0.92 : 1.08;
    const rect = cropEl.value.getBoundingClientRect();
    applyZoom(factor, { x: e.clientX - rect.left, y: e.clientY - rect.top });
}

// ── Zoom slider ──────────────────────────────────────────────────────────────

function onZoomSlider(e) {
    const newScale = parseFloat(e.target.value);
    const scaleDiff = newScale / scale.value;
    // Zoom around center of crop box
    dragX.value = SIZE / 2 - (SIZE / 2 - dragX.value) * scaleDiff;
    dragY.value = SIZE / 2 - (SIZE / 2 - dragY.value) * scaleDiff;
    scale.value = newScale;
    clamp();
}

// ── Shared zoom helper (point is relative to the crop box) ───────────────────

function applyZoom(ratio, localPoint) {
    const newScale = Math.min(maxScale.value, Math.max(minScale.value, scale.value * ratio));
    const scaleDiff = newScale / scale.value;
    dragX.value = localPoint.x - (localPoint.x - dragX.value) * scaleDiff;
    dragY.value = localPoint.y - (localPoint.y - dragY.value) * scaleDiff;
    scale.value = newScale;
    clamp();
}

// ── Confirm: draw crop to 320×320 canvas and emit JPEG blob ──────────────────

function confirm() {
    const canvas = document.createElement('canvas');
    canvas.width = 320;
    canvas.height = 320;
    const ctx = canvas.getContext('2d');

    // Region of the original image that maps to the 300×300 preview crop box
    const srcX = -dragX.value / scale.value;
    const srcY = -dragY.value / scale.value;
    const srcW = SIZE / scale.value;
    const srcH = SIZE / scale.value;

    ctx.drawImage(imgEl.value, srcX, srcY, srcW, srcH, 0, 0, 320, 320);

    canvas.toBlob(blob => emit('confirm', blob), 'image/jpeg', 0.92);
}

function cancel() {
    cleanup();
    emit('cancel');
}

function cleanup() {
    window.removeEventListener('mousemove', onMouseMove);
    window.removeEventListener('mouseup', onMouseUp);
    window.removeEventListener('touchmove', onTouchMove);
    window.removeEventListener('touchend', onTouchEnd);
}

// ── Utils ────────────────────────────────────────────────────────────────────

function touchList(list) {
    return Array.from(list).map(t => ({ x: t.clientX, y: t.clientY }));
}
function dist(a, b) {
    return Math.hypot(b.x - a.x, b.y - a.y);
}
function midpoint(a, b) {
    return { x: (a.x + b.x) / 2, y: (a.y + b.y) / 2 };
}
</script>
