<template>
  <!-- AssistiveTouch floating button -->
  <div
    ref="touchRef"
    class="assistive-touch fixed z-50 select-none"
    :style="floatStyle"
    :class="{ 'is-dragging': isDragging }"
  >
    <!-- ── Orb ──────────────────────────────────────────────── -->
    <button
      type="button"
      aria-label="Assistive Touch"
      aria-haspopup="true"
      :aria-expanded="menuOpen"
      class="assistive-orb relative flex h-[52px] w-[52px] items-center justify-center rounded-full focus:outline-none"
      :class="{ 'orb-open': menuOpen, 'orb-idle': isIdle }"
      @mousedown.prevent="onMouseDown"
      @touchstart="onTouchStart"
      @touchend.stop.prevent="onTouchEnd"
      @touchcancel.stop.prevent="onTouchCancel"
      @click="onOrbClick"
      @mouseenter="resetIdle"
    >
      <!-- Glow halo (visible when menu is open) -->
      <span
        class="absolute inset-[-6px] rounded-full border-2 border-white/20"
        :class="menuOpen ? 'opacity-100' : 'opacity-0'"
        aria-hidden="true"
      />
      <!-- Glass disc -->
      <span
        class="absolute inset-0 rounded-full bg-neutral-900 dark:bg-black border border-white/20 shadow-[0_4px_24px_rgba(0,0,0,0.55)]"
        aria-hidden="true"
      />
      <!-- Concentric inner ring -->
      <span
        class="absolute inset-[7px] rounded-full border border-white/12"
        aria-hidden="true"
      />
      <!-- Center dot -->
      <span
        class="relative z-10 h-[13px] w-[13px] rounded-full bg-white/80 shadow-[0_0_8px_2px_rgba(255,255,255,0.3)]"
        aria-hidden="true"
      />
    </button>

    <!-- ── Menu panel ─────────────────────────────────────────── -->
    <transition name="assistive-panel">
      <div
        v-if="menuOpen"
        class="assistive-menu absolute z-10"
        :class="placement.classes"
        :style="{ transformOrigin: placement.origin }"
        role="menu"
        aria-label="Assistive Touch menu"
      >
        <div
          class="relative overflow-hidden rounded-[24px] bg-neutral-900 dark:bg-black border border-white/10 shadow-[0_20px_70px_rgba(0,0,0,0.65)]"
          :class="isCompactMenu ? 'min-w-[10.5rem]' : 'min-w-[13.5rem]'"
        >
          <!-- Header -->
          <div v-if="!isCompactMenu" class="flex items-center justify-between px-4 pt-4 pb-2.5">
            <span class="text-white/40 text-[10px] font-bold uppercase tracking-[0.15em]">Quick Access</span>
            <button
              type="button"
              class="flex h-[22px] w-[22px] items-center justify-center rounded-full bg-white/10 hover:bg-white/18 text-white/50 hover:text-white/80 focus:outline-none"
              aria-label="Close menu"
              @click.stop="closeMenu"
            >
              <svg
                viewBox="0 0 10 10"
                class="w-2.5 h-2.5"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
              >
                <path d="M2 2l6 6M8 2l-6 6" />
              </svg>
            </button>
          </div>

          <button
            v-else
            type="button"
            class="absolute top-2 right-2 z-10 flex h-6 w-6 items-center justify-center rounded-full bg-white/10 hover:bg-white/18 text-white/50 hover:text-white/80 focus:outline-none"
            aria-label="Close menu"
            @click.stop="closeMenu"
          >
            <svg
              viewBox="0 0 10 10"
              class="w-2.5 h-2.5"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
            >
              <path d="M2 2l6 6M8 2l-6 6" />
            </svg>
          </button>

          <!-- Separator -->
          <div v-if="!isCompactMenu" class="mx-4 mb-3 h-px bg-white/[0.07]" />

          <!-- Actions grid -->
          <div
            class="grid px-3 pb-4"
            :class="isCompactMenu ? 'gap-1 pt-3 pb-5 justify-items-center' : 'gap-2.5 pt-0'"
            :style="gridStyle"
          >
            <button
              v-for="(item, idx) in actions"
              :key="item.id"
              type="button"
              role="menuitem"
              class="assistive-action group flex flex-col items-center gap-1.5 focus:outline-none"
              :class="(item.disabled || item.loading) ? 'opacity-70 cursor-not-allowed' : ''"
              :disabled="item.disabled || item.loading"
              :style="{ '--stagger': `${idx * 45}ms` }"
              :aria-label="item.label"
              @click="handleAction(item)"
            >
              <!-- Tile -->
              <span
                class="relative flex items-center justify-center shadow-[0_4px_16px_rgba(0,0,0,0.4)] overflow-hidden"
                :class="[
                  isCompactMenu ? 'h-[68px] w-[68px] rounded-[20px]' : 'h-[62px] w-[62px] rounded-[18px]',
                  getTileGradient(item.color),
                ]"
              >
                <!-- Subtle inner gloss -->
                <span class="absolute inset-0 bg-gradient-to-b from-white/15 to-transparent" aria-hidden="true" />
                <component :is="item.icon" class="relative z-10 w-[26px] h-[26px] text-white drop-shadow" />
              </span>
              <!-- Label -->
              <span
                class="text-white/75 font-medium leading-tight text-center line-clamp-2"
                :class="isCompactMenu ? 'text-[11px] w-[72px]' : 'text-[10.5px] w-[62px]'"
              >
                {{ item.label }}
              </span>
            </button>
          </div>
        </div>
      </div>
    </transition>
  </div>

  <!-- Backdrop -->
  <transition name="assistive-backdrop">
    <div
      v-if="menuOpen"
      class="fixed inset-0 z-40 bg-black/25 backdrop-blur-sm"
      aria-hidden="true"
      @click="handleBackdropClick"
    />
  </transition>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

// ─── Props ──────────────────────────────────────────────────────────────────

const props = defineProps({
  /**
   * Array of action items to show in the menu.
   * Each item: { id, label, icon (Lucide component), handler, color? }
   * color: 'blue' | 'green' | 'orange' | 'red' | 'purple' | 'indigo' | 'amber' | 'primary'
   * Add more items here or pass them from App.vue — no internal changes needed.
   */
  actions: {
    type: Array,
    required: true,
    validator: (items) => items.every((i) => i.id && i.label && i.icon && typeof i.handler === 'function'),
  },
  /** Grid column count. Auto-sizes to sqrt of action count if not set. */
  columns: {
    type: Number,
    default: null,
  },
});

const emit = defineEmits(['menu-open']);

// ─── Tile color map ──────────────────────────────────────────────────────────

const TILE_GRADIENTS = {
  blue:    'bg-gradient-to-br from-blue-500 to-blue-700',
  green:   'bg-gradient-to-br from-emerald-500 to-emerald-700',
  orange:  'bg-gradient-to-br from-orange-500 to-orange-600',
  red:     'bg-gradient-to-br from-red-500 to-red-700',
  purple:  'bg-gradient-to-br from-purple-500 to-purple-700',
  indigo:  'bg-gradient-to-br from-indigo-500 to-indigo-700',
  amber:   'bg-gradient-to-br from-amber-400 to-amber-600',
  primary: 'bg-gradient-to-br from-primary-500 to-primary-700',
};
const TILE_DEFAULT = 'bg-gradient-to-br from-neutral-600 to-neutral-800';

function getTileGradient(color) {
  return TILE_GRADIENTS[color] ?? TILE_DEFAULT;
}

// ─── State ───────────────────────────────────────────────────────────────────

const touchRef = ref(null);
const menuOpen = ref(false);
const isDragging = ref(false);
const isIdle = ref(false);

const EDGE_MARGIN = 8;
const ORB_SIZE = 52; // matches h-[52px] w-[52px]

const pos = ref({ x: 0, y: 0 });
const dragStart = ref({ px: 0, py: 0, ox: 0, oy: 0 });
let movedDuringPress = false;
let idleTimer = null;
let suppressNextClick = false;

// ─── Computed ────────────────────────────────────────────────────────────────

const floatStyle = computed(() => ({
  left: `${pos.value.x}px`,
  top: `${pos.value.y}px`,
  touchAction: 'manipulation',
}));

/** Returns menu placement Tailwind classes + CSS transform-origin for animation */
const placement = computed(() => {
  const vw = window.innerWidth;
  const vh = window.innerHeight;
  const cx = pos.value.x + ORB_SIZE / 2;
  const cy = pos.value.y + ORB_SIZE / 2;
  const isRight = cx > vw / 2;
  const isBottom = cy > vh / 2;
  return {
    classes: `${isBottom ? 'bottom-full mb-3' : 'top-full mt-3'} ${isRight ? 'right-0' : 'left-0'}`,
    origin: `${isBottom ? 'bottom' : 'top'} ${isRight ? 'right' : 'left'}`,
  };
});

const isCompactMenu = computed(() => props.actions.length <= 2);

const gridStyle = computed(() => {
  const cols = isCompactMenu.value
    ? 1
    : (props.columns ?? Math.max(2, Math.ceil(Math.sqrt(props.actions.length))));
  return { gridTemplateColumns: `repeat(${cols}, minmax(0, 1fr))` };
});

// ─── Idle auto-fade ──────────────────────────────────────────────────────────

function resetIdle() {
  isIdle.value = false;
  clearTimeout(idleTimer);
  idleTimer = setTimeout(() => { isIdle.value = true; }, 4000);
}

// ─── Position helpers ────────────────────────────────────────────────────────

function initPosition() {
  const vw = window.innerWidth;
  const vh = window.innerHeight;
  pos.value = {
    x: vw - ORB_SIZE - EDGE_MARGIN,
    y: Math.round(vh * 0.70) - ORB_SIZE / 2,
  };
}

function clampToViewport(x, y) {
  const vw = window.innerWidth;
  const vh = window.innerHeight;
  return {
    x: Math.max(EDGE_MARGIN, Math.min(x, vw - ORB_SIZE - EDGE_MARGIN)),
    y: Math.max(EDGE_MARGIN, Math.min(y, vh - ORB_SIZE - EDGE_MARGIN)),
  };
}

function snapToEdge() {
  const vw = window.innerWidth;
  const cx = pos.value.x + ORB_SIZE / 2;
  const targetX = cx < vw / 2 ? EDGE_MARGIN : vw - ORB_SIZE - EDGE_MARGIN;
  pos.value = clampToViewport(targetX, pos.value.y);
}

// ─── Drag — mouse ────────────────────────────────────────────────────────────

function onMouseDown(e) {
  if (e.button !== 0) return;
  movedDuringPress = false;
  resetIdle();
  dragStart.value = { px: e.clientX, py: e.clientY, ox: pos.value.x, oy: pos.value.y };
  window.addEventListener('mousemove', onMouseMove);
  window.addEventListener('mouseup', onMouseUp);
}

function onMouseMove(e) {
  const dx = e.clientX - dragStart.value.px;
  const dy = e.clientY - dragStart.value.py;
  if (!isDragging.value && (Math.abs(dx) > 4 || Math.abs(dy) > 4)) {
    isDragging.value = true;
    movedDuringPress = true;
    closeMenu();
  }
  if (isDragging.value) {
    pos.value = clampToViewport(dragStart.value.ox + dx, dragStart.value.oy + dy);
  }
}

function onMouseUp() {
  if (isDragging.value) {
    isDragging.value = false;
    snapToEdge();
  }
  window.removeEventListener('mousemove', onMouseMove);
  window.removeEventListener('mouseup', onMouseUp);
}

// ─── Drag — touch ────────────────────────────────────────────────────────────

function onTouchStart(e) {
  const t = e.touches[0];
  movedDuringPress = false;
  resetIdle();
  dragStart.value = { px: t.clientX, py: t.clientY, ox: pos.value.x, oy: pos.value.y };
  window.addEventListener('touchmove', onTouchMove, { passive: false });
}

function onTouchMove(e) {
  e.preventDefault();
  const t = e.touches[0];
  const dx = t.clientX - dragStart.value.px;
  const dy = t.clientY - dragStart.value.py;
  if (!isDragging.value && (Math.abs(dx) > 4 || Math.abs(dy) > 4)) {
    isDragging.value = true;
    movedDuringPress = true;
    closeMenu();
  }
  if (isDragging.value) {
    pos.value = clampToViewport(dragStart.value.ox + dx, dragStart.value.oy + dy);
  }
}

function onTouchEnd() {
  if (!isDragging.value && !movedDuringPress) {
    resetIdle();
    const next = !menuOpen.value;
    menuOpen.value = next;
    if (next) emit('menu-open');

    suppressNextClick = true;
    setTimeout(() => { suppressNextClick = false; }, 350);
  }

  if (isDragging.value) {
    isDragging.value = false;
    snapToEdge();
  }
  window.removeEventListener('touchmove', onTouchMove);
}

function onTouchCancel() {
  isDragging.value = false;
  movedDuringPress = true;
  window.removeEventListener('touchmove', onTouchMove);
}

// ─── Menu ────────────────────────────────────────────────────────────────────

function onOrbClick() {
  if (suppressNextClick) return;
  if (movedDuringPress) return;
  resetIdle();
  const next = !menuOpen.value;
  menuOpen.value = next;
  if (next) emit('menu-open');
}

function closeMenu() {
  menuOpen.value = false;
}

function handleBackdropClick() {
  if (suppressNextClick) return;
  closeMenu();
}

function handleAction(item) {
  if (item.disabled || item.loading) return;
  closeMenu();
  item.handler();
}

// ─── Global listeners ────────────────────────────────────────────────────────

function onKeyDown(e) {
  if (e.key === 'Escape' && menuOpen.value) closeMenu();
}

function onResize() {
  pos.value = clampToViewport(pos.value.x, pos.value.y);
}

// ─── Lifecycle ───────────────────────────────────────────────────────────────

onMounted(() => {
  initPosition();
  resetIdle();
  window.addEventListener('keydown', onKeyDown);
  window.addEventListener('resize', onResize);
});

onUnmounted(() => {
  clearTimeout(idleTimer);
  window.removeEventListener('keydown', onKeyDown);
  window.removeEventListener('resize', onResize);
  window.removeEventListener('mousemove', onMouseMove);
  window.removeEventListener('mouseup', onMouseUp);
  window.removeEventListener('touchmove', onTouchMove);
  window.removeEventListener('touchend', onTouchEnd);
});
</script>

<style scoped>
/* Snap after drag release without transition */
.assistive-touch:not(.is-dragging) {
  transition: none;
}

/* Orb behavior */
.assistive-orb {
  opacity: 0.94;
}

.assistive-orb.orb-open {
  opacity: 1;
}

.assistive-orb.orb-idle:not(.orb-open) {
  opacity: 0.62;
  animation: assistive-orb-pulse 2.4s ease-in-out infinite;
}

.assistive-orb.orb-open {
  animation: assistive-orb-open 220ms cubic-bezier(0.2, 0.85, 0.2, 1) both;
}

.assistive-action {
  animation: assistive-action-in 320ms cubic-bezier(0.2, 0.85, 0.2, 1) both;
  animation-delay: var(--stagger);
}

.assistive-action:nth-child(1) { --stagger: 0ms; }
.assistive-action:nth-child(2) { --stagger: 45ms; }
.assistive-action:nth-child(3) { --stagger: 90ms; }
.assistive-action:nth-child(4) { --stagger: 135ms; }
.assistive-action:nth-child(5) { --stagger: 180ms; }
.assistive-action:nth-child(6) { --stagger: 225ms; }
.assistive-action:nth-child(7) { --stagger: 270ms; }
.assistive-action:nth-child(8) { --stagger: 315ms; }

.assistive-action > span:first-child,
.assistive-action > span:last-child {
  transition: transform 180ms ease, opacity 180ms ease;
}

.assistive-action:hover > span:first-child,
.assistive-action:focus-visible > span:first-child {
  transform: translateY(-2px) scale(1.03);
}

.assistive-action:hover > span:last-child,
.assistive-action:focus-visible > span:last-child {
  opacity: 1;
}

@keyframes assistive-orb-pulse {
  0%, 100% {
    transform: scale(1);
    box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.08);
  }
  50% {
    transform: scale(1.03);
    box-shadow: 0 0 0 10px rgba(255, 255, 255, 0);
  }
}

@keyframes assistive-orb-open {
  0% {
    transform: scale(0.92);
  }
  60% {
    transform: scale(1.06);
  }
  100% {
    transform: scale(1);
  }
}

@keyframes assistive-action-in {
  0% {
    opacity: 0;
    transform: translateY(10px) scale(0.92);
    filter: blur(4px);
  }
  70% {
    opacity: 1;
    transform: translateY(-1px) scale(1.02);
    filter: blur(0);
  }
  100% {
    opacity: 1;
    transform: translateY(0) scale(1);
    filter: blur(0);
  }
}

.assistive-panel-enter-active,
.assistive-panel-leave-active {
  transition: opacity 180ms ease, transform 220ms cubic-bezier(0.2, 0.85, 0.2, 1);
}

.assistive-panel-enter-from,
.assistive-panel-leave-to {
  opacity: 0;
  transform: scale(0.88) translateY(6px);
}

.assistive-panel-enter-to,
.assistive-panel-leave-from {
  opacity: 1;
  transform: scale(1) translateY(0);
}

.assistive-backdrop-enter-active,
.assistive-backdrop-leave-active {
  transition: opacity 160ms ease;
}

.assistive-backdrop-enter-from,
.assistive-backdrop-leave-to {
  opacity: 0;
}

.assistive-backdrop-enter-to,
.assistive-backdrop-leave-from {
  opacity: 1;
}
</style>
