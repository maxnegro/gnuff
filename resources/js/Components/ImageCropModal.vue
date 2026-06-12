<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center">
    <!-- Overlay -->
    <div class="absolute inset-0 bg-black/50" @click="onCancel"></div>

    <!-- Modal -->
    <div class="relative bg-white rounded-lg shadow-lg p-6 max-w-2xl w-full mx-4">
      <!-- Header -->
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-semibold text-gray-900">Ritaglia Immagine</h2>
        <button
          type="button"
          @click="onCancel"
          class="text-gray-400 hover:text-gray-600 transition-colors"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="flex justify-center py-8">
        <div class="animate-spin">
          <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
        </div>
      </div>

      <!-- Error State -->
      <div v-if="error" class="mb-4 p-4 bg-red-50 rounded-lg border border-red-200">
        <p class="text-sm text-red-700">{{ error }}</p>
      </div>

      <!-- Canvas and Controls -->
      <div v-if="image && !isLoading" class="space-y-4">
        <!-- Canvas -->
        <div class="mx-auto w-full max-w-[400px] aspect-square bg-gray-100 rounded-lg overflow-hidden">
          <canvas
            :ref="setCanvasElement"
            class="block h-full w-full border border-gray-200"
          ></canvas>
        </div>

        <!-- Zoom Slider -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">Zoom: {{ zoomPercentage }}%</label>
          <input
            type="range"
            :value="zoom"
            :min="minZoom"
            :max="maxZoom"
            step="0.1"
            @input="handleZoomChange"
            class="w-full"
          />
        </div>

        <!-- Pan Controls -->
        <div class="grid grid-cols-3 gap-2">
          <button
            type="button"
            @click="() => imageCropper.setPan(panX, panY - 20)"
            class="p-2 bg-gray-200 hover:bg-gray-300 rounded transition-colors"
            title="Pan Up"
          >
            <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
            </svg>
          </button>

          <button
            type="button"
            @click="imageCropper.reset"
            class="p-2 bg-gray-200 hover:bg-gray-300 rounded transition-colors text-xs font-medium"
            title="Reset"
          >
            Reset
          </button>

          <button
            type="button"
            @click="() => imageCropper.setPan(panX, panY + 20)"
            class="p-2 bg-gray-200 hover:bg-gray-300 rounded transition-colors"
            title="Pan Down"
          >
            <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
          </button>

          <button
            type="button"
            @click="() => imageCropper.setPan(panX - 20, panY)"
            class="p-2 bg-gray-200 hover:bg-gray-300 rounded transition-colors"
            title="Pan Left"
          >
            <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
          </button>

          <div></div>

          <button
            type="button"
            @click="() => imageCropper.setPan(panX + 20, panY)"
            class="p-2 bg-gray-200 hover:bg-gray-300 rounded transition-colors"
            title="Pan Right"
          >
            <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
          </button>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex gap-3 mt-6">
        <button
          type="button"
          @click="onCancel"
          class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition-colors"
        >
          Annulla
        </button>
        <button
          type="button"
          @click="onConfirm"
          :disabled="!image || isLoading"
          class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Conferma
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
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
const { image, zoom, panX, panY, isLoading, error, maxZoom, minZoom } = imageCropper

const zoomPercentage = computed(() => Math.round(imageCropper.zoom.value * 100))

watch(
  () => imageCropper.image.value,
  () => {
    if (imageCropper.image.value && props.isOpen && canvasElement.value && !imageCropper.isLoading.value) {
      imageCropper.draw()
    }
  }
)

function setCanvasElement(element: HTMLCanvasElement | null) {
  canvasElement.value = element

  if (!element) {
    return
  }

  if (imageCropper.initCanvas(element) && imageCropper.image.value && !imageCropper.isLoading.value) {
    imageCropper.draw()
  }
}

const handleZoomChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  imageCropper.setZoom(parseFloat(target.value))
}

const onCancel = () => {
  imageCropper.reset()
  imageCropper.image.value = null
  imageCropper.error.value = null
  emit('close')
}

const onConfirm = async () => {
  const croppedBase64 = imageCropper.getCroppedImage('jpeg', 0.85)
  if (croppedBase64) {
    emit('confirm', croppedBase64)
    onCancel()
  }
}

defineExpose({
  loadImageFromBase64: imageCropper.loadImageFromBase64,
  loadImageFromFile: imageCropper.loadImageFromFile,
})
</script>
