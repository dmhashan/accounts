<template>
  <div>
    <div
      class="relative rounded-lg border bg-white overflow-hidden transition-colors"
      :class="locked
        ? 'border-secondary-200 dark:border-secondary-700'
        : 'border-primary-400 dark:border-primary-500 ring-1 ring-primary-300 dark:ring-primary-700'"
      style="touch-action: none;"
    >
      <canvas
        ref="canvasRef"
        :style="`display: block; width: 100%; height: 240px; cursor: ${locked ? 'default' : 'crosshair'};`"
      />
      <div
        v-if="isEmpty && !locked"
        class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center gap-1.5"
      >
        <PenLine class="w-6 h-6 text-secondary-300" />
        <span class="text-xs text-secondary-400 select-none">Draw your signature here</span>
      </div>
      <div
        v-if="locked && isEmpty"
        class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center gap-1.5"
      >
        <LockKeyhole class="w-6 h-6 text-secondary-300" />
        <span class="text-xs text-secondary-400 select-none">Signature locked</span>
      </div>
      <!-- lock overlay when locked with content -->
      <div
        v-if="locked && !isEmpty"
        class="pointer-events-none absolute top-2 right-2 bg-secondary-100 dark:bg-secondary-700 rounded px-1.5 py-0.5 flex items-center gap-1"
      >
        <LockKeyhole class="w-3 h-3 text-secondary-400" />
        <span class="text-[10px] text-secondary-400 select-none">Locked</span>
      </div>
    </div>

    <div class="mt-1.5 flex items-center gap-3">
      <button
        type="button"
        class="flex items-center gap-1.5 text-xs font-medium transition-colors"
        :class="locked
          ? 'text-secondary-400 hover:text-primary-600 dark:hover:text-primary-400'
          : 'text-primary-600 dark:text-primary-400 hover:text-primary-800'"
        @click="toggleLock"
      >
        <component :is="locked ? LockKeyhole : LockKeyholeOpen" class="w-3.5 h-3.5" />
        {{ locked ? 'Unlock to edit' : 'Lock signature' }}
      </button>

      <button
        v-if="!isEmpty && !locked"
        type="button"
        class="text-xs text-secondary-400 hover:text-red-500 dark:hover:text-red-400 transition-colors"
        @click="clear"
      >
        Clear
      </button>
    </div>
  </div>
</template>

<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { LockKeyhole, LockKeyholeOpen, PenLine } from 'lucide-vue-next';

const props = defineProps({
    modelValue:    { type: String, default: '' },
    height:        { type: Number, default: 240 },
    defaultLocked: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const canvasRef = ref(null);
const isEmpty   = ref(true);
const locked    = ref(false);

let isDrawing = false;
let lastX = 0;
let lastY = 0;

// ── Lock ───────────────────────────────────────────────────────────────────

function toggleLock() {
    locked.value = !locked.value;
}

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
    if (locked.value) return;
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
    if (locked.value) return;
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

    // Lock by default if a value already exists, or if defaultLocked prop is set
    if (props.modelValue || props.defaultLocked) {
        locked.value = true;
    }

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
