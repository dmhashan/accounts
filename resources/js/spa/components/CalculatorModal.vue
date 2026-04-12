<template>
    <div class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4" @keydown.escape.window="$emit('close')">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/45" @click="$emit('close')"></div>

        <!-- Panel -->
        <div class="relative z-10 w-full max-w-xs rounded-t-2xl sm:rounded-2xl app-surface shadow-xl overflow-hidden">
            <!-- Header -->
            <div class="flex items-center justify-between px-4 py-3 border-b border-secondary-200 dark:border-secondary-700">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span class="text-sm font-semibold text-secondary-900 dark:text-white">Calculator</span>
                </div>
                <button
                    type="button"
                    class="text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-200 transition-colors"
                    aria-label="Close calculator"
                    @click="$emit('close')"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
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
                <button class="calc-btn calc-btn-func" @click="clear">C</button>
                <button class="calc-btn calc-btn-func" @click="toggleSign">+/−</button>
                <button class="calc-btn calc-btn-func" @click="percent">%</button>
                <button class="calc-btn calc-btn-op" :class="{ 'calc-btn-op-active': pendingOp === '/' }" @click="setOp('/')">÷</button>

                <!-- Row 2 -->
                <button class="calc-btn calc-btn-num" @click="inputDigit('7')">7</button>
                <button class="calc-btn calc-btn-num" @click="inputDigit('8')">8</button>
                <button class="calc-btn calc-btn-num" @click="inputDigit('9')">9</button>
                <button class="calc-btn calc-btn-op" :class="{ 'calc-btn-op-active': pendingOp === '*' }" @click="setOp('*')">×</button>

                <!-- Row 3 -->
                <button class="calc-btn calc-btn-num" @click="inputDigit('4')">4</button>
                <button class="calc-btn calc-btn-num" @click="inputDigit('5')">5</button>
                <button class="calc-btn calc-btn-num" @click="inputDigit('6')">6</button>
                <button class="calc-btn calc-btn-op" :class="{ 'calc-btn-op-active': pendingOp === '-' }" @click="setOp('-')">−</button>

                <!-- Row 4 -->
                <button class="calc-btn calc-btn-num" @click="inputDigit('1')">1</button>
                <button class="calc-btn calc-btn-num" @click="inputDigit('2')">2</button>
                <button class="calc-btn calc-btn-num" @click="inputDigit('3')">3</button>
                <button class="calc-btn calc-btn-op" :class="{ 'calc-btn-op-active': pendingOp === '+' }" @click="setOp('+')">+</button>

                <!-- Row 5 -->
                <button class="calc-btn calc-btn-num col-span-2" @click="inputDigit('0')">0</button>
                <button class="calc-btn calc-btn-num" @click="inputDot">.</button>
                <button class="calc-btn calc-btn-eq" @click="calculate">=</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

defineEmits(['close']);

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
        if (currentInput.value.replace(/[.\-]/g, '').length >= 12) return;
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
