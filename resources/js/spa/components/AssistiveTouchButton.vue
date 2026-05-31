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
      @touchstart.prevent="onTouchStart"
      @click="onOrbClick"
      @mouseenter="resetIdle"
    >
      <!-- Glow halo (visible when menu is open) -->
      <span
        class="absolute inset-[-6px] rounded-full border-2 border-white/20 transition-opacity duration-300"
        :class="menuOpen ? 'opacity-100' : 'opacity-0'"
        aria-hidden="true"
      />
      <!-- Glass disc -->
      <span
        class="absolute inset-0 rounded-full bg-neutral-900/70 dark:bg-black/70 backdrop-blur-xl border border-white/20 shadow-[0_4px_24px_rgba(0,0,0,0.55)]"
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
    <Transition name="at-menu">
      <div
        v-if="menuOpen"
        class="assistive-menu absolute z-10"
        :class="placement.classes"
        :style="{ transformOrigin: placement.origin }"
        role="menu"
        aria-label="Assistive Touch menu"
      >
        <div
          class="relative overflow-hidden rounded-[24px] bg-neutral-900/88 dark:bg-black/88 backdrop-blur-2xl border border-white/10 shadow-[0_20px_70px_rgba(0,0,0,0.65)]"
          :class="isCompactMenu ? 'min-w-[10.5rem]' : 'min-w-[13.5rem]'"
        >
          <!-- Header -->
          <div v-if="!isCompactMenu" class="flex items-center justify-between px-4 pt-4 pb-2.5">
            <span class="text-white/40 text-[10px] font-bold uppercase tracking-[0.15em]">Quick Access</span>
            <button
              type="button"
              class="flex h-[22px] w-[22px] items-center justify-center rounded-full bg-white/10 hover:bg-white/18 active:scale-90 transition-all text-white/50 hover:text-white/80 focus:outline-none"
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
            class="absolute top-2 right-2 z-10 flex h-6 w-6 items-center justify-center rounded-full bg-white/10 hover:bg-white/18 active:scale-90 transition-all text-white/50 hover:text-white/80 focus:outline-none"
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
                class="relative flex items-center justify-center shadow-[0_4px_16px_rgba(0,0,0,0.4)] transition-all duration-150 group-hover:brightness-[1.15] group-hover:scale-105 group-active:scale-90 group-active:brightness-110 overflow-hidden"
                :class="[
                  isCompactMenu ? 'h-[68px] w-[68px] rounded-[20px]' : 'h-[62px] w-[62px] rounded-[18px]',
                  getTileGradient(item.color),
                ]"
              >
                <!-- Subtle inner gloss -->
                <span class="absolute inset-0 bg-gradient-to-b from-white/15 to-transparent" aria-hidden="true" />
                <component :is="item.icon" class="relative z-10 w-[26px] h-[26px] text-white drop-shadow" :class="item.loading ? 'animate-spin' : ''" />
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
    </Transition>
  </div>

  <!-- Backdrop -->
  <Transition name="at-backdrop">
    <div
      v-if="menuOpen"
      class="fixed inset-0 z-40 bg-black/25 backdrop-blur-[3px]"
      aria-hidden="true"
      @click="closeMenu"
    />
  </Transition>
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

// ─── Computed ────────────────────────────────────────────────────────────────

const floatStyle = computed(() => ({
  left: `${pos.value.x}px`,
  top: `${pos.value.y}px`,
  touchAction: 'none',
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
  window.addEventListener('touchend', onTouchEnd);
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
  if (isDragging.value) {
    isDragging.value = false;
    snapToEdge();
  }
  window.removeEventListener('touchmove', onTouchMove);
  window.removeEventListener('touchend', onTouchEnd);
}

// ─── Menu ────────────────────────────────────────────────────────────────────

function onOrbClick() {
  if (movedDuringPress) return;
  resetIdle();
  const next = !menuOpen.value;
  menuOpen.value = next;
  if (next) emit('menu-open');
}

function closeMenu() {
  menuOpen.value = false;
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
/* Snap transition after drag release */
.assistive-touch:not(.is-dragging) {
  transition: left 0.24s cubic-bezier(0.34, 1.56, 0.64, 1),
              top  0.24s cubic-bezier(0.34, 1.56, 0.64, 1);
}

/* Orb behavior */
.assistive-orb {
  opacity: 0.94;
  transform: scale(1);
  transition: transform 0.18s ease, opacity 0.2s ease;
}

.assistive-orb:hover,
.assistive-orb:focus-visible {
  opacity: 1;
  transform: scale(1.05);
}

.assistive-orb:active {
  transform: scale(0.95);
}

.assistive-orb.orb-open {
  opacity: 1;
  transform: scale(0.94);
}

.assistive-orb.orb-idle:not(.orb-open) {
  opacity: 0.62;
}

/* Menu enter/leave */
.at-menu-enter-active {
  transition: opacity 0.2s ease, transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), filter 0.2s ease;
}
.at-menu-leave-active {
  transition: opacity 0.14s ease, transform 0.14s ease, filter 0.14s ease;
}
.at-menu-enter-from,
.at-menu-leave-to {
  opacity: 0;
  transform: scale(0.84);
  filter: blur(2px);
}

/* Action stagger animation */
.assistive-action {
  animation: at-item-in 0.28s cubic-bezier(0.22, 1, 0.36, 1) both;
  animation-delay: var(--stagger, 0ms);
}

@keyframes at-item-in {
  from {
    opacity: 0;
    transform: translateY(6px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Backdrop fade */
.at-backdrop-enter-active,
.at-backdrop-leave-active {
  transition: opacity 0.15s ease;
}
.at-backdrop-enter-from,
.at-backdrop-leave-to {
  opacity: 0;
}
</style>
