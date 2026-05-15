<template>
    <div>
        <div class="relative rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white overflow-hidden" style="touch-action: none;">
            <canvas
                ref="canvasRef"
                style="display: block; width: 100%; height: auto; cursor: crosshair;"
            />
            <div
                v-if="isEmpty"
                class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center gap-1.5"
            >
                <PenLine class="w-5 h-5 text-secondary-300" />
                <span class="text-xs text-secondary-400 select-none">Draw your signature here</span>
            </div>
        </div>
        <button
            v-if="!isEmpty"
            type="button"
            class="mt-1.5 text-xs text-secondary-400 hover:text-red-500 dark:hover:text-red-400 transition-colors"
            @click="clear"
        >Clear signature</button>
    </div>
</template>

<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { PenLine } from 'lucide-vue-next';

const props = defineProps({
    modelValue: { type: String, default: '' },
    height:     { type: Number, default: 160 },
});

const emit = defineEmits(['update:modelValue']);

const canvasRef = ref(null);
const isEmpty   = ref(true);

let isDrawing = false;
let lastX = 0;
let lastY = 0;

// ── Helpers ────────────────────────────────────────────────────────────────

function getCtx() {
    const ctx = canvasRef.value.getContext('2d');
    ctx.strokeStyle = '#111827';
    ctx.lineWidth   = 2.2;
    ctx.lineCap     = 'round';
    ctx.lineJoin    = 'round';
    return ctx;
}

function scaledPos(clientX, clientY) {
    const canvas = canvasRef.value;
    const rect   = canvas.getBoundingClientRect();
    return {
        x: (clientX - rect.left) * (canvas.width  / rect.width),
        y: (clientY - rect.top)  * (canvas.height / rect.height),
    };
}

// ── Mouse handlers ────────────────────────────────────────────────────────

function onMouseDown(e) {
    isDrawing = true;
    const { x, y } = scaledPos(e.clientX, e.clientY);
    lastX = x; lastY = y;
    getCtx().beginPath();
    getCtx().moveTo(x, y);
}

function onMouseMove(e) {
    if (!isDrawing) return;
    const { x, y } = scaledPos(e.clientX, e.clientY);
    const ctx = getCtx();
    ctx.beginPath();
    ctx.moveTo(lastX, lastY);
    ctx.lineTo(x, y);
    ctx.stroke();
    lastX = x; lastY = y;
    isEmpty.value = false;
}

function onMouseUp()    { endStroke(); }
function onMouseLeave() { endStroke(); }

// ── Touch handlers ─────────────────────────────────────────────────────────

function onTouchStart(e) {
    e.preventDefault();
    const t = e.touches[0];
    const { x, y } = scaledPos(t.clientX, t.clientY);
    isDrawing = true;
    lastX = x; lastY = y;
    getCtx().beginPath();
    getCtx().moveTo(x, y);
}

function onTouchMove(e) {
    e.preventDefault();
    if (!isDrawing) return;
    const t = e.touches[0];
    const { x, y } = scaledPos(t.clientX, t.clientY);
    const ctx = getCtx();
    ctx.beginPath();
    ctx.moveTo(lastX, lastY);
    ctx.lineTo(x, y);
    ctx.stroke();
    lastX = x; lastY = y;
    isEmpty.value = false;
}

function onTouchEnd(e) {
    e.preventDefault();
    endStroke();
}

// ── Core ───────────────────────────────────────────────────────────────────

function endStroke() {
    if (!isDrawing) return;
    isDrawing = false;
    if (!isEmpty.value) {
        emit('update:modelValue', canvasRef.value.toDataURL('image/png'));
    }
}

function clear() {
    if (!canvasRef.value) return;
    const canvas = canvasRef.value;
    canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
    isEmpty.value = true;
    emit('update:modelValue', '');
}

function loadImage(src) {
    if (!src || !canvasRef.value) return;
    const img    = new Image();
    const canvas = canvasRef.value;
    img.onload   = () => {
        canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
        isEmpty.value = false;
    };
    img.src = src;
}

watch(() => props.modelValue, (val) => {
    if (!val) clear();
});

// ── Lifecycle ──────────────────────────────────────────────────────────────

onMounted(async () => {
    await nextTick();
    const canvas  = canvasRef.value;
    // Fix internal dimensions (600 logical width keeps coordinates predictable)
    canvas.width  = 600;
    canvas.height = props.height;

    // Attach with passive:false so touchmove can preventDefault (prevent scroll)
    canvas.addEventListener('mousedown',  onMouseDown);
    canvas.addEventListener('mousemove',  onMouseMove);
    canvas.addEventListener('mouseup',    onMouseUp);
    canvas.addEventListener('mouseleave', onMouseLeave);
    canvas.addEventListener('touchstart', onTouchStart, { passive: false });
    canvas.addEventListener('touchmove',  onTouchMove,  { passive: false });
    canvas.addEventListener('touchend',   onTouchEnd,   { passive: false });

    if (props.modelValue) loadImage(props.modelValue);
});

onBeforeUnmount(() => {
    const canvas = canvasRef.value;
    if (!canvas) return;
    canvas.removeEventListener('mousedown',  onMouseDown);
    canvas.removeEventListener('mousemove',  onMouseMove);
    canvas.removeEventListener('mouseup',    onMouseUp);
    canvas.removeEventListener('mouseleave', onMouseLeave);
    canvas.removeEventListener('touchstart', onTouchStart);
    canvas.removeEventListener('touchmove',  onTouchMove);
    canvas.removeEventListener('touchend',   onTouchEnd);
});
</script>
