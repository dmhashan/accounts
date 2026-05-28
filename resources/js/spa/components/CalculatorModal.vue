<template>
  <!-- Mobile-only backdrop -->
  <div
    v-if="!isDesktop"
    class="fixed inset-0 z-[49] bg-black/45"
    @click="$emit('close')"
  />

  <!-- Panel -->
  <div
    ref="panelRef"
    class="fixed z-50 w-full sm:w-80 rounded-t-2xl sm:rounded-2xl app-surface shadow-xl overflow-hidden"
    :class="{ 'select-none': isDragging }"
    :style="panelStyle"
  >
    <!-- Header -->
    <div
      class="flex items-center justify-between px-4 py-3 border-b border-secondary-200 dark:border-secondary-700"
      :class="isDesktop ? (isDragging ? 'cursor-grabbing' : 'cursor-grab') : ''"
      @mousedown="startDrag"
      @touchstart.passive="startTouchDrag"
    >
      <div class="flex items-center gap-2">
        <Calculator class="w-4 h-4 text-primary-500" />
        <span class="text-sm font-semibold text-secondary-900 dark:text-white">Calculator</span>
      </div>
      <button
        type="button"
        class="text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-200 transition-colors"
        aria-label="Close calculator"
        @click="$emit('close')"
      >
        <X class="w-5 h-5" />
      </button>
    </div>

    <!-- Display -->
    <div class="bg-secondary-950 dark:bg-black/60 px-4 pt-3 pb-4">
      <div class="text-secondary-400 dark:text-secondary-500 text-xs text-right h-4 font-mono truncate">
        {{ expression || '&nbsp;' }}
      </div>
      <div class="text-white text-3xl font-light text-right font-mono mt-1 leading-none truncate">
        {{ display }}
      </div>
    </div>

    <!-- Buttons -->
    <div class="grid grid-cols-4 gap-px bg-secondary-200 dark:bg-secondary-700 border-t border-secondary-200 dark:border-secondary-700">
      <!-- Row 1 -->
      <button class="calc-btn calc-btn-func" @click="clear">
        C
      </button>
      <button class="calc-btn calc-btn-func" @click="toggleSign">
        +/−
      </button>
      <button class="calc-btn calc-btn-func" @click="percent">
        %
      </button>
      <button class="calc-btn calc-btn-op" :class="{ 'calc-btn-op-active': pendingOp === '/' }" @click="setOp('/')">
        ÷
      </button>

      <!-- Row 2 -->
      <button class="calc-btn calc-btn-num" @click="inputDigit('7')">
        7
      </button>
      <button class="calc-btn calc-btn-num" @click="inputDigit('8')">
        8
      </button>
      <button class="calc-btn calc-btn-num" @click="inputDigit('9')">
        9
      </button>
      <button class="calc-btn calc-btn-op" :class="{ 'calc-btn-op-active': pendingOp === '*' }" @click="setOp('*')">
        ×
      </button>

      <!-- Row 3 -->
      <button class="calc-btn calc-btn-num" @click="inputDigit('4')">
        4
      </button>
      <button class="calc-btn calc-btn-num" @click="inputDigit('5')">
        5
      </button>
      <button class="calc-btn calc-btn-num" @click="inputDigit('6')">
        6
      </button>
      <button class="calc-btn calc-btn-op" :class="{ 'calc-btn-op-active': pendingOp === '-' }" @click="setOp('-')">
        −
      </button>

      <!-- Row 4 -->
      <button class="calc-btn calc-btn-num" @click="inputDigit('1')">
        1
      </button>
      <button class="calc-btn calc-btn-num" @click="inputDigit('2')">
        2
      </button>
      <button class="calc-btn calc-btn-num" @click="inputDigit('3')">
        3
      </button>
      <button class="calc-btn calc-btn-op" :class="{ 'calc-btn-op-active': pendingOp === '+' }" @click="setOp('+')">
        +
      </button>

      <!-- Row 5 -->
      <button class="calc-btn calc-btn-num col-span-2" @click="inputDigit('0')">
        0
      </button>
      <button class="calc-btn calc-btn-num" @click="inputDot">
        .
      </button>
      <button class="calc-btn calc-btn-eq" @click="calculate">
        =
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Calculator, X } from 'lucide-vue-next';

const emit = defineEmits(['close']);

// ── Draggable / position persistence ──────────────────────────────────────────
const STORAGE_KEY = 'calculator-position';
const panelRef = ref(null);
const isDragging = ref(false);
const isDesktop = ref(false);
const pos = ref({ x: 0, y: 0 });

const dragOffset = { x: 0, y: 0 };
let touchStartX = 0, touchStartY = 0, touchStartPosX = 0, touchStartPosY = 0;
let mq = null;

const panelStyle = computed(() => {
    if (!isDesktop.value) return { bottom: '0', left: '0', right: '0' };
    return { left: pos.value.x + 'px', top: pos.value.y + 'px' };
});

function clamp(x, y) {
    const w = panelRef.value?.offsetWidth || 320;
    const h = panelRef.value?.offsetHeight || 420;
    return {
        x: Math.max(0, Math.min(x, window.innerWidth - w)),
        y: Math.max(0, Math.min(y, window.innerHeight - h)),
    };
}

function loadPosition() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (raw) {
            const p = JSON.parse(raw);
            if (typeof p.x === 'number' && typeof p.y === 'number') return p;
        }
    } catch { /* invalid JSON — ignore */ }
    return null;
}

function savePosition() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(pos.value));
}

// Mouse drag
function startDrag(e) {
    if (!isDesktop.value) return;
    isDragging.value = true;
    dragOffset.x = e.clientX - pos.value.x;
    dragOffset.y = e.clientY - pos.value.y;
    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('mouseup', stopDrag);
}

function onMouseMove(e) {
    if (!isDragging.value) return;
    pos.value = clamp(e.clientX - dragOffset.x, e.clientY - dragOffset.y);
}

function stopDrag() {
    isDragging.value = false;
    savePosition();
    window.removeEventListener('mousemove', onMouseMove);
    window.removeEventListener('mouseup', stopDrag);
}

// Touch drag
function startTouchDrag(e) {
    if (!isDesktop.value) return;
    const t = e.touches[0];
    touchStartX = t.clientX;
    touchStartY = t.clientY;
    touchStartPosX = pos.value.x;
    touchStartPosY = pos.value.y;
    window.addEventListener('touchmove', onTouchMove, { passive: false });
    window.addEventListener('touchend', stopTouchDrag);
}

function onTouchMove(e) {
    e.preventDefault();
    const t = e.touches[0];
    pos.value = clamp(touchStartPosX + (t.clientX - touchStartX), touchStartPosY + (t.clientY - touchStartY));
}

function stopTouchDrag() {
    savePosition();
    window.removeEventListener('touchmove', onTouchMove);
    window.removeEventListener('touchend', stopTouchDrag);
}

function onKeyDown(e) {
    if (e.key === 'Escape') emit('close');
}

onMounted(() => {
    mq = window.matchMedia('(min-width: 640px)');
    isDesktop.value = mq.matches;
    mq.addEventListener('change', e => { isDesktop.value = e.matches; });

    const saved = loadPosition();
    pos.value = saved
        ? clamp(saved.x, saved.y)
        : clamp(window.innerWidth - 340, 80);

    window.addEventListener('keydown', onKeyDown);
});

onUnmounted(() => {
    window.removeEventListener('mousemove', onMouseMove);
    window.removeEventListener('mouseup', stopDrag);
    window.removeEventListener('touchmove', onTouchMove);
    window.removeEventListener('touchend', stopTouchDrag);
    window.removeEventListener('keydown', onKeyDown);
});
// ──────────────────────────────────────────────────────────────────────────────


const currentInput = ref('0');
const previousInput = ref(null);
const pendingOp = ref(null);
const waitingForOperand = ref(false);
const justCalculated = ref(false);
const expressionParts = ref([]);

const display = computed(() => {
    if (currentInput.value === 'Error') return 'Error';
    if (currentInput.value.endsWith('.')) return currentInput.value;
    const n = parseFloat(currentInput.value);
    if (isNaN(n)) return currentInput.value;
    return String(parseFloat(n.toPrecision(10)));
});

const expression = computed(() => expressionParts.value.join(' '));

function opSymbol(op) {
    return { '+': '+', '-': '−', '*': '×', '/': '÷' }[op] ?? op;
}

function formatNum(val) {
    return String(parseFloat(parseFloat(val).toPrecision(10)));
}

function compute(a, b, op) {
    switch (op) {
        case '+': return a + b;
        case '-': return a - b;
        case '*': return a * b;
        case '/': return b === 0 ? NaN : a / b;
        default: return b;
    }
}

function inputDigit(digit) {
    if (waitingForOperand.value) {
        currentInput.value = digit;
        waitingForOperand.value = false;
        justCalculated.value = false;
        return;
    }
    if (justCalculated.value) {
        currentInput.value = digit;
        justCalculated.value = false;
        expressionParts.value = [];
        pendingOp.value = null;
        previousInput.value = null;
        return;
    }
    if (currentInput.value === '0') {
        currentInput.value = digit;
    } else {
        if (currentInput.value.replace(/[.-]/g, '').length >= 12) return;
        currentInput.value += digit;
    }
}

function inputDot() {
    if (waitingForOperand.value) {
        currentInput.value = '0.';
        waitingForOperand.value = false;
        return;
    }
    if (justCalculated.value) {
        currentInput.value = '0.';
        justCalculated.value = false;
        expressionParts.value = [];
        return;
    }
    if (!currentInput.value.includes('.')) {
        currentInput.value += '.';
    }
}

function setOp(op) {
    if (pendingOp.value && !waitingForOperand.value && !justCalculated.value) {
        const result = compute(parseFloat(previousInput.value), parseFloat(currentInput.value), pendingOp.value);
        const resultStr = isNaN(result) ? 'Error' : formatNum(result);
        expressionParts.value = [resultStr, opSymbol(op)];
        previousInput.value = resultStr;
        currentInput.value = resultStr;
    } else {
        expressionParts.value = [formatNum(currentInput.value), opSymbol(op)];
        previousInput.value = currentInput.value;
    }
    pendingOp.value = op;
    justCalculated.value = false;
    waitingForOperand.value = true;
}

function calculate() {
    if (!pendingOp.value || previousInput.value === null) return;
    const a = parseFloat(previousInput.value);
    const b = parseFloat(currentInput.value);
    const result = compute(a, b, pendingOp.value);

    expressionParts.value = [
        formatNum(previousInput.value),
        opSymbol(pendingOp.value),
        formatNum(currentInput.value),
        '=',
    ];

    const resultStr = isNaN(result) ? 'Error' : String(parseFloat(result.toPrecision(10)));
    currentInput.value = resultStr;
    previousInput.value = null;
    pendingOp.value = null;
    justCalculated.value = true;
    waitingForOperand.value = false;
}

function clear() {
    currentInput.value = '0';
    previousInput.value = null;
    pendingOp.value = null;
    justCalculated.value = false;
    waitingForOperand.value = false;
    expressionParts.value = [];
}

function toggleSign() {
    if (currentInput.value === '0' || currentInput.value === 'Error') return;
    currentInput.value = currentInput.value.startsWith('-')
        ? currentInput.value.slice(1)
        : '-' + currentInput.value;
}

function percent() {
    const val = parseFloat(currentInput.value);
    if (isNaN(val)) return;
    currentInput.value = String(val / 100);
}
</script>

<style scoped>
@reference "../../../css/app.css";

.calc-btn {
    @apply flex items-center justify-center text-lg font-medium h-14 select-none cursor-pointer transition-opacity active:opacity-60;
}
.calc-btn-num {
    @apply bg-secondary-100 dark:bg-secondary-800 text-secondary-900 dark:text-white hover:bg-secondary-200 dark:hover:bg-secondary-700;
}
.calc-btn-func {
    @apply bg-secondary-200 dark:bg-secondary-700 text-secondary-700 dark:text-secondary-200 hover:bg-secondary-300 dark:hover:bg-secondary-600;
}
.calc-btn-op {
    @apply bg-primary-500 text-white hover:bg-primary-600 text-xl;
}
.calc-btn-op-active {
    @apply bg-white text-primary-500 hover:bg-secondary-100;
}
.calc-btn-eq {
    @apply bg-primary-500 text-white hover:bg-primary-600 text-xl;
}
</style>
