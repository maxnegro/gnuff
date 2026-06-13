<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <!-- Overlay with modern backdrop blur -->
    <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-md" @click="onCancel"></div>

    <!-- Immersive dark modal container -->
    <div class="relative bg-slate-900 border border-slate-800 text-white rounded-[28px] shadow-2xl p-6 sm:p-7 max-w-md w-full mx-auto overflow-hidden transition-all duration-300 scale-100">
      <!-- Header -->
      <div class="flex items-center justify-between mb-5">
        <h2 class="text-lg font-bold text-slate-100">Ritaglia Immagine</h2>
        <button
          type="button"
          @click="onCancel"
          class="text-slate-400 hover:text-white p-1 rounded-full hover:bg-slate-800 transition"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="flex flex-col items-center justify-center py-16 space-y-4">
        <div class="animate-spin rounded-full h-8 w-8 border-2 border-indigo-500 border-t-transparent"></div>
        <p class="text-sm text-slate-400">Caricamento immagine...</p>
      </div>

      <!-- Error State -->
      <div v-if="error" class="mb-4 p-4 bg-red-950/60 rounded-2xl border border-red-900/50">
        <p class="text-sm text-red-400">{{ error }}</p>
      </div>

      <!-- Canvas and Controls -->
      <div v-show="image && !isLoading" class="space-y-5">
        <!-- Interactive Canvas Wrapper -->
        <div class="relative mx-auto w-full aspect-square bg-slate-950 rounded-2xl overflow-hidden border border-slate-800 shadow-inner select-none cursor-move">
          <canvas
            :ref="setCanvasElement"
            @mousedown="onMousedown"
            @dblclick="handleDblClick"
            class="block h-full w-full"
          ></canvas>

          <!-- Gesture Instructions Badge -->
          <div class="absolute bottom-3 left-1/2 transform -translate-x-1/2 bg-slate-950/70 backdrop-blur-md text-[10px] text-slate-300 px-3.5 py-1.5 rounded-full pointer-events-none select-none uppercase tracking-wider font-semibold border border-slate-800/40">
            Trascina per spostare • Pizzica per zoomare
          </div>
        </div>

        <!-- Zoom Slider & Reset -->
        <div class="flex items-center gap-3 bg-slate-950/40 p-3 rounded-2xl border border-slate-800/80">
          <span class="text-xs font-semibold text-slate-500 select-none">-</span>
          <input
            type="range"
            :value="zoom"
            :min="minZoom"
            :max="maxZoom"
            step="0.05"
            @input="handleZoomChange"
            class="flex-1 accent-indigo-500 bg-slate-800 rounded-lg appearance-none h-1.5 cursor-pointer"
          />
          <span class="text-xs font-semibold text-slate-500 select-none">+</span>
          <span class="text-xs font-mono text-slate-400 min-w-[32px] text-right">{{ zoomPercentage }}%</span>
          <button
            type="button"
            @click="imageCropper.reset"
            class="px-3 py-1.5 text-xs font-semibold text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 rounded-lg transition"
          >
            Reset
          </button>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex gap-3 mt-6">
        <button
          type="button"
          @click="onCancel"
          class="flex-1 px-4 py-3 bg-slate-800 hover:bg-slate-700 border border-slate-700/50 text-slate-300 rounded-2xl font-semibold transition"
        >
          Annulla
        </button>
        <button
          type="button"
          @click="onConfirm"
          :disabled="!image || isLoading"
          class="flex-1 px-4 py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-2xl font-semibold shadow-lg shadow-indigo-600/20 transition disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Conferma
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onBeforeUnmount } from 'vue'
import { useImageCropper } from '@/composables/useImageCropper'

interface Props {
  isOpen: boolean
  initialImage?: string | File
}

interface Emits {
  (e: 'close'): void
  (e: 'confirm', base64: string): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const canvasElement = ref<HTMLCanvasElement | null>(null)
const imageCropper = useImageCropper(400, 400)
const { image, zoom, isLoading, error, maxZoom, minZoom } = imageCropper

const zoomPercentage = computed(() => Math.round(imageCropper.zoom.value * 100))

// Gesture state
const isDragging = ref(false)
const isZooming = ref(false)
const startX = ref(0)
const startY = ref(0)
const startPanX = ref(0)
const startPanY = ref(0)

const initialDistance = ref(0)
const startZoom = ref(1)
const lastTap = ref(0)

watch(
  () => imageCropper.image.value,
  () => {
    if (imageCropper.image.value && props.isOpen && canvasElement.value && !imageCropper.isLoading.value) {
      imageCropper.draw()
    }
  }
)

function setCanvasElement(element: HTMLCanvasElement | null) {
  // Clean up old listeners
  if (canvasElement.value) {
    canvasElement.value.removeEventListener('touchstart', onTouchStart)
    canvasElement.value.removeEventListener('touchmove', onTouchMove)
    canvasElement.value.removeEventListener('touchend', onTouchEnd)
  }

  canvasElement.value = element

  if (!element) {
    return
  }

  // Bind programmatic touch listeners with passive properties to allow preventDefault
  element.addEventListener('touchstart', onTouchStart, { passive: true })
  element.addEventListener('touchmove', onTouchMove, { passive: false })
  element.addEventListener('touchend', onTouchEnd, { passive: true })

  if (imageCropper.initCanvas(element) && imageCropper.image.value && !imageCropper.isLoading.value) {
    imageCropper.draw()
  }
}

// Mouse dragging handlers
const onMousedown = (e: MouseEvent) => {
  if (imageCropper.isLoading.value || !imageCropper.image.value) return
  isDragging.value = true
  startX.value = e.clientX
  startY.value = e.clientY
  startPanX.value = imageCropper.panX.value
  startPanY.value = imageCropper.panY.value

  window.addEventListener('mousemove', onMousemove)
  window.addEventListener('mouseup', onMouseup)
}

const onMousemove = (e: MouseEvent) => {
  if (!isDragging.value) return
  const dx = e.clientX - startX.value
  const dy = e.clientY - startY.value
  imageCropper.setPan(startPanX.value + dx, startPanY.value + dy)
}

const onMouseup = () => {
  isDragging.value = false
  window.removeEventListener('mousemove', onMousemove)
  window.removeEventListener('mouseup', onMouseup)
}

// Touch gesture handlers
const onTouchStart = (e: TouchEvent) => {
  if (imageCropper.isLoading.value || !imageCropper.image.value) return

  if (e.touches.length === 1) {
    isDragging.value = true
    isZooming.value = false
    startX.value = e.touches[0].clientX
    startY.value = e.touches[0].clientY
    startPanX.value = imageCropper.panX.value
    startPanY.value = imageCropper.panY.value

    // Double tap detection
    const now = Date.now()
    if (now - lastTap.value < 300) {
      imageCropper.reset()
    }
    lastTap.value = now
  } else if (e.touches.length === 2) {
    isDragging.value = false
    isZooming.value = true
    const t1 = e.touches[0]
    const t2 = e.touches[1]
    initialDistance.value = Math.hypot(t1.clientX - t2.clientX, t1.clientY - t2.clientY)
    startZoom.value = imageCropper.zoom.value
  }
}

const onTouchMove = (e: TouchEvent) => {
  if (imageCropper.isLoading.value || !imageCropper.image.value) return

  if (isDragging.value && e.touches.length === 1) {
    e.preventDefault()
    const dx = e.touches[0].clientX - startX.value
    const dy = e.touches[0].clientY - startY.value
    imageCropper.setPan(startPanX.value + dx, startPanY.value + dy)
  } else if (isZooming.value && e.touches.length === 2) {
    e.preventDefault()
    const t1 = e.touches[0]
    const t2 = e.touches[1]
    const dist = Math.hypot(t1.clientX - t2.clientX, t1.clientY - t2.clientY)
    if (initialDistance.value > 0) {
      const ratio = dist / initialDistance.value
      imageCropper.setZoom(startZoom.value * ratio)
    }
  }
}

const onTouchEnd = () => {
  isDragging.value = false
  isZooming.value = false
}

const handleZoomChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  imageCropper.setZoom(parseFloat(target.value))
}

const handleDblClick = () => {
  imageCropper.reset()
}

const onCancel = () => {
  imageCropper.reset()
  imageCropper.image.value = null
  imageCropper.error.value = null
  isDragging.value = false
  isZooming.value = false
  emit('close')
}

const onConfirm = async () => {
  const croppedBase64 = imageCropper.getCroppedImage('jpeg', 0.85)
  if (croppedBase64) {
    emit('confirm', croppedBase64)
    onCancel()
  }
}

onBeforeUnmount(() => {
  window.removeEventListener('mousemove', onMousemove)
  window.removeEventListener('mouseup', onMouseup)
  if (canvasElement.value) {
    canvasElement.value.removeEventListener('touchstart', onTouchStart)
    canvasElement.value.removeEventListener('touchmove', onTouchMove)
    canvasElement.value.removeEventListener('touchend', onTouchEnd)
  }
})

defineExpose({
  loadImageFromBase64: imageCropper.loadImageFromBase64,
  loadImageFromFile: imageCropper.loadImageFromFile,
})
</script>
