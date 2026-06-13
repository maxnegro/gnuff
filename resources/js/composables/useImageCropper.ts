import { ref, computed, watch } from 'vue'
import { fileToDataUri } from '@/utils/imageConverter'

export interface ImageCropperState {
  canvas: HTMLCanvasElement | null
  ctx: CanvasRenderingContext2D | null
  image: HTMLImageElement | null
  zoom: number
  panX: number
  panY: number
  isLoading: boolean
  error: string | null
}

export interface CropArea {
  x: number
  y: number
  width: number
  height: number
}

/**
 * Composable for handling image cropping with zoom and pan
 */
export function useImageCropper(canvasWidth = 400, canvasHeight = 400) {
  const canvas = ref<HTMLCanvasElement | null>(null)
  const ctx = ref<CanvasRenderingContext2D | null>(null)
  const image = ref<HTMLImageElement | null>(null)
  const zoom = ref(1)
  const panX = ref(0)
  const panY = ref(0)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const maxZoom = computed(() => 3)
  const minZoom = computed(() => 0.5)

  /**
   * Initialize canvas context
   */
  const initCanvas = (canvasElement: HTMLCanvasElement) => {
    canvas.value = canvasElement
    canvas.value.width = canvasWidth
    canvas.value.height = canvasHeight
    ctx.value = canvas.value.getContext('2d')
    if (!ctx.value) {
      error.value = 'Failed to initialize canvas context'
      return false
    }
    return true
  }

  /**
   * Load image from Base64 data URI
   */
  const loadImageFromBase64 = (base64: string): Promise<void> => {
    return new Promise((resolve, reject) => {
      isLoading.value = true
      error.value = null

      const img = new Image()
      img.crossOrigin = 'anonymous'

      img.onload = () => {
        image.value = img
        zoom.value = 1
        panX.value = 0
        panY.value = 0
        isLoading.value = false
        draw()
        resolve()
      }

      img.onerror = () => {
        error.value = 'Failed to load image'
        isLoading.value = false
        reject(new Error('Failed to load image'))
      }

      img.src = base64
    })
  }

  /**
   * Load image from File object
   */
  const loadImageFromFile = (file: File): Promise<void> => {
    isLoading.value = true
    error.value = null
    return fileToDataUri(file)
      .then((base64) => loadImageFromBase64(base64))
      .catch((err) => {
        error.value = err instanceof Error ? err.message : 'Failed to read file'
        isLoading.value = false
        throw err
      })
  }

  /**
   * Draw image on canvas with current zoom and pan
   */
  const draw = () => {
    if (!canvas.value || !ctx.value || !image.value) {
      return
    }

    const context = ctx.value
    context.clearRect(0, 0, canvas.value.width, canvas.value.height)

    // Calculate scaling to fit image in canvas
    const scaleX = canvas.value.width / image.value.width
    const scaleY = canvas.value.height / image.value.height
    const scale = Math.min(scaleX, scaleY) * zoom.value

    const x = (canvas.value.width - image.value.width * scale) / 2 + panX.value
    const y = (canvas.value.height - image.value.height * scale) / 2 + panY.value

    // Draw background
    context.fillStyle = '#f0f0f0'
    context.fillRect(0, 0, canvas.value.width, canvas.value.height)

    // Draw image
    context.drawImage(image.value, x, y, image.value.width * scale, image.value.height * scale)

    // Draw crop area (center square)
    const cropSize = Math.min(canvas.value.width, canvas.value.height) * 0.8
    const cropX = (canvas.value.width - cropSize) / 2
    const cropY = (canvas.value.height - cropSize) / 2

    // Draw semi-transparent overlay
    context.fillStyle = 'rgba(0, 0, 0, 0.5)'
    context.fillRect(0, 0, cropX, canvas.value.height)
    context.fillRect(cropX + cropSize, 0, cropX, canvas.value.height)
    context.fillRect(cropX, 0, cropSize, cropY)
    context.fillRect(cropX, cropY + cropSize, cropSize, cropY)

    // Draw crop frame border
    context.strokeStyle = '#ffffff'
    context.lineWidth = 2
    context.strokeRect(cropX, cropY, cropSize, cropSize)

    // Draw corner handles
    const handleSize = 10
    context.fillStyle = '#ffffff'
    context.fillRect(cropX - handleSize / 2, cropY - handleSize / 2, handleSize, handleSize)
    context.fillRect(cropX + cropSize - handleSize / 2, cropY - handleSize / 2, handleSize, handleSize)
    context.fillRect(cropX - handleSize / 2, cropY + cropSize - handleSize / 2, handleSize, handleSize)
    context.fillRect(cropX + cropSize - handleSize / 2, cropY + cropSize - handleSize / 2, handleSize, handleSize)
  }

  /**
   * Update zoom level
   */
  const setZoom = (newZoom: number) => {
    zoom.value = Math.max(minZoom.value, Math.min(maxZoom.value, newZoom))
    draw()
  }

  /**
   * Update pan offset with dynamic limits to keep crop area filled
   */
  const setPan = (x: number, y: number) => {
    if (!canvas.value || !image.value) {
      panX.value = x
      panY.value = y
      draw()
      return
    }

    const scaleX = canvas.value.width / image.value.width
    const scaleY = canvas.value.height / image.value.height
    const scale = Math.min(scaleX, scaleY) * zoom.value
    const cropSize = Math.min(canvas.value.width, canvas.value.height) * 0.8

    const imgWidth = image.value.width * scale
    const imgHeight = image.value.height * scale

    // Max pan limits: allow panning until the image edge reaches the opposite edge of the crop frame
    const limitX = Math.max(0, (imgWidth - cropSize) / 2)
    const limitY = Math.max(0, (imgHeight - cropSize) / 2)

    panX.value = imgWidth >= cropSize ? Math.max(-limitX, Math.min(limitX, x)) : 0
    panY.value = imgHeight >= cropSize ? Math.max(-limitY, Math.min(limitY, y)) : 0
    draw()
  }

  /**
   * Reset zoom and pan
   */
  const reset = () => {
    zoom.value = 1
    panX.value = 0
    panY.value = 0
    draw()
  }

  /**
   * Get cropped image as Base64 data URI
   */
  const getCroppedImage = (outputFormat: 'jpeg' | 'png' | 'webp' = 'jpeg', quality = 0.85): string | null => {
    if (!canvas.value || !ctx.value || !image.value) {
      return null
    }

    try {
      // Create a new canvas for the cropped image
      const croppedCanvas = document.createElement('canvas')
      const cropSize = Math.min(canvas.value.width, canvas.value.height) * 0.8
      croppedCanvas.width = cropSize
      croppedCanvas.height = cropSize

      const croppedCtx = croppedCanvas.getContext('2d')
      if (!croppedCtx) {
        return null
      }

      // Calculate image position and scale on original canvas
      const scaleX = canvas.value.width / image.value.width
      const scaleY = canvas.value.height / image.value.height
      const scale = Math.min(scaleX, scaleY) * zoom.value

      const imgX = (canvas.value.width - image.value.width * scale) / 2 + panX.value
      const imgY = (canvas.value.height - image.value.height * scale) / 2 + panY.value

      // Crop coordinates relative to canvas
      const cropX = (canvas.value.width - cropSize) / 2
      const cropY = (canvas.value.height - cropSize) / 2

      // Calculate source coordinates from original image
      const sourceX = (cropX - imgX) / scale
      const sourceY = (cropY - imgY) / scale
      const sourceWidth = cropSize / scale
      const sourceHeight = cropSize / scale

      // Draw the cropped portion to the new canvas
      croppedCtx.drawImage(
        image.value,
        sourceX,
        sourceY,
        sourceWidth,
        sourceHeight,
        0,
        0,
        cropSize,
        cropSize
      )

      // Convert to data URI based on format
      const mimeType = `image/${outputFormat}`
      const dataUri = croppedCanvas.toDataURL(mimeType, quality)

      return dataUri
    } catch (err) {
      error.value = `Failed to crop image: ${err instanceof Error ? err.message : 'Unknown error'}`
      return null
    }
  }

  /**
   * Get image dimensions
   */
  const getImageDimensions = () => {
    if (!image.value) {
      return { width: 0, height: 0 }
    }
    return {
      width: image.value.width,
      height: image.value.height,
    }
  }

  return {
    // State
    canvas,
    ctx,
    image,
    zoom,
    panX,
    panY,
    isLoading,
    error,

    // Computed
    maxZoom,
    minZoom,

    // Methods
    initCanvas,
    loadImageFromBase64,
    loadImageFromFile,
    draw,
    setZoom,
    setPan,
    reset,
    getCroppedImage,
    getImageDimensions,
  }
}
