<script setup>
import { ref, watch, computed, nextTick, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'
import ImageCropModal from './ImageCropModal.vue'
import { validateImageFile } from '@/utils/imageFileValidation'

const props = defineProps({
  modelValue: Boolean,
  initialStep: { type: String, default: 'ean' },
  initialForm: { type: Object, default: () => ({ barcode: '', name: '', image_url: '', rating: '' }) },
  ratingId: { type: Number, default: null },
})
const emit = defineEmits(['update:modelValue', 'saved'])

const show = computed({
  get: () => props.modelValue,
  set: v => emit('update:modelValue', v)
})


const manualStep = ref(props.initialStep)
const manualForm = ref({ ...props.initialForm })
const manualFormError = ref('')
const manualFormLoading = ref(false)
const manualProductFound = ref(false)
const showImageInput = ref(false)
const newImageUrl = ref('')
const showImageCropModal = ref(false)
const imageCropModalRef = ref(null)
const fileInputRef = ref(null)
const cameraInputRef = ref(null)
const imageUploadLoading = ref(false)

// Ref per l'input EAN
const eanInputRef = ref(null)

const ratingOptions = [
  { value: 'gnuf', label: '😋 Gnuf' },
  { value: 'ok', label: '😊 Ok' },
  { value: 'meh', label: '😐 Meh' },
  { value: 'bleah', label: '🤮 Bleah' },
]

const placeholder = '/img/gnuff-placeholder-192.png';


// Gestione chiusura con ESC
function handleEscClose(e) {
  if (e.key === 'Escape') {
    show.value = false
  }
}

onMounted(() => {
  watch(
    () => props.modelValue,
    (v) => {
      if (v) {
        window.addEventListener('keydown', handleEscClose)
      } else {
        window.removeEventListener('keydown', handleEscClose)
      }
    },
    { immediate: true }
  )
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleEscClose)
})


// Quando la modale viene aperta, resetta lo stato e prova a recuperare immagine se mancante
watch(() => props.modelValue, async (v) => {
  if (v) {
    manualStep.value = props.initialStep
    // Usa sempre i dati dal DB come base
    manualForm.value = { ...props.initialForm }
    manualFormError.value = ''
    showImageInput.value = false
    newImageUrl.value = ''
    await nextTick()
    if (manualStep.value === 'ean' && eanInputRef.value) {
      eanInputRef.value.focus()
    }
    // La fetch viene fatta solo se i campi sono vuoti nel DB (props.initialForm)
    if (
      manualStep.value === 'dati' &&
      manualForm.value.barcode &&
      (!props.initialForm.image_url || !props.initialForm.name)
    ) {
      try {
        const res = await axios.get(`/product/${manualForm.value.barcode}`)
        if (res.data && res.data.product) {
          // Aggiorna solo i campi che erano vuoti nel DB
          if (!props.initialForm.image_url && res.data.product.image_url) {
            manualForm.value.image_url = res.data.product.image_url
          }
          if (!props.initialForm.name && res.data.product.name) {
            manualForm.value.name = res.data.product.name
          }
        }
      } catch (e) {
        // Silenzio errori, fallback su placeholder
      }
    }
  }
})

async function cercaEAN() {
  manualFormError.value = ''
  manualFormLoading.value = true
  manualProductFound.value = false
  try {
    const res = await axios.get(`/product/${manualForm.value.barcode}`)
    if (res.data && res.data.product) {
      manualForm.value.name = res.data.product.name || ''
      manualForm.value.image_url = res.data.product.image_url || ''
      manualForm.value.rating = res.data.rating || ''
      manualProductFound.value = true
      manualStep.value = 'dati'
    } else {
      manualStep.value = 'errore'
    }
  } catch (e) {
    manualStep.value = 'errore'
  } finally {
    manualFormLoading.value = false
  }
}

async function updateImageUrl() {
  manualFormError.value = ''
  manualFormLoading.value = true
  try {
    const response = await axios.put(`/product/${manualForm.value.barcode}`, {
      name: manualForm.value.name,
      image_url: newImageUrl.value === '' ? null : newImageUrl.value,
    })
    manualForm.value.image_url = response.data?.product?.image_url ?? null
    showImageInput.value = false
    newImageUrl.value = ''
  } catch (e) {
    if (e.response && e.response.data && (e.response.data.message || e.response.data.error)) {
      manualFormError.value = e.response.data.message || e.response.data.error
    } else {
      manualFormError.value = "Errore durante l'aggiornamento immagine."
    }
  } finally {
    manualFormLoading.value = false
  }
}

/**
 * Handle image file selection and open crop modal
 */
async function handleImageFileSelected(event) {
  const target = event.target
  const file = target.files?.[0]
  if (!file) return

  const validation = validateImageFile(file)
  if (!validation.isValid) {
    manualFormError.value = validation.error
    if (fileInputRef.value) {
      fileInputRef.value.value = ''
    }
    return
  }

  try {
    await imageCropModalRef.value?.loadImageFromFile(file)
    showImageCropModal.value = true
  } catch (e) {
    manualFormError.value = 'Errore nel caricamento dell\'immagine'
  }

  // Reset input
  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
}

/**
 * Handle camera capture and open crop modal
 */
async function handleCameraCapture(event) {
  const target = event.target
  const file = target.files?.[0]
  if (!file) return

  const validation = validateImageFile(file)
  if (!validation.isValid) {
    manualFormError.value = validation.error
    if (cameraInputRef.value) {
      cameraInputRef.value.value = ''
    }
    return
  }

  try {
    await imageCropModalRef.value?.loadImageFromFile(file)
    showImageCropModal.value = true
  } catch (e) {
    manualFormError.value = 'Errore nel caricamento della foto'
  }

  // Reset input
  if (cameraInputRef.value) {
    cameraInputRef.value.value = ''
  }
}

/**
 * Handle cropped image confirmation
 */
async function handleImageCropConfirm(base64) {
  imageUploadLoading.value = true
  manualFormError.value = ''

  try {
    const response = await axios.post(`/product/${manualForm.value.barcode}/image`, {
      image_base64: base64,
    })

    if (response.data.success) {
      manualForm.value.image_url = response.data.image_url
      showImageInput.value = false
      newImageUrl.value = ''
    } else {
      manualFormError.value = response.data.message || 'Errore durante l\'upload'
    }
  } catch (e) {
    if (e.response?.data?.message) {
      manualFormError.value = e.response.data.message
    } else if (e.response?.data?.error) {
      manualFormError.value = e.response.data.error
    } else {
      manualFormError.value = 'Errore durante l\'upload dell\'immagine'
    }
  } finally {
    imageUploadLoading.value = false
    showImageCropModal.value = false
  }
}

async function removeRating() {
  manualFormError.value = ''
  manualFormLoading.value = true
  try {
    await axios.delete(`/api/rate/${props.ratingId}`)
    emit('saved')
    show.value = false
  } catch (e) {
    if (e.response && e.response.data && (e.response.data.message || e.response.data.error)) {
      manualFormError.value = e.response.data.message || e.response.data.error
    } else {
      manualFormError.value = 'Errore durante la rimozione della valutazione.'
    }
  } finally {
    manualFormLoading.value = false
  }
}

async function submitManualForm() {
  manualFormError.value = ''
  manualFormLoading.value = true
  try {
    // Confronta con i dati originali dal DB
    const original = props.initialForm || {}
    const updates = {}
    updates.name = manualForm.value.name
    updates.image_url = manualForm.value.image_url === '' ? null : manualForm.value.image_url
    let productExists = !!manualForm.value.barcode
    if (productExists) {
      await axios.put(`/product/${manualForm.value.barcode}`, updates)
    } else {
      await axios.post('/product', {
        barcode: manualForm.value.barcode,
        name: manualForm.value.name,
        image_url: manualForm.value.image_url === '' ? null : manualForm.value.image_url,
      })
    }
    await axios.post('/rate', {
      barcode: manualForm.value.barcode,
      value: manualForm.value.rating,
    })
    emit('saved')
    show.value = false
  } catch (e) {
    // Mostra messaggio dettagliato se disponibile
    if (e.response && e.response.data && (e.response.data.message || e.response.data.error)) {
      manualFormError.value = e.response.data.message || e.response.data.error
    } else if (e.response && e.response.data && typeof e.response.data === 'string') {
      manualFormError.value = e.response.data
    } else {
      manualFormError.value = 'Errore durante il salvataggio. Controlla i dati.'
    }
  } finally {
    manualFormLoading.value = false
  }
}
</script>
<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/55 px-4 backdrop-blur-sm">
    <div class="app-panel relative w-full max-w-lg p-6 sm:p-7">
      <button @click="show = false" class="absolute right-4 top-4 inline-flex h-10 w-10 items-center justify-center rounded-full text-secondary-500 transition hover:bg-secondary-100 hover:text-secondary-700 dark:text-slate-300 dark:hover:bg-secondary-800/70 dark:hover:text-white">✖</button>
      <div class="mb-5 pr-10">
        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-primary-600 dark:text-primary-300">Valutazione prodotto</p>
      </div>
      <form v-if="manualStep === 'ean'" @submit.prevent="cercaEAN" class="flex flex-col gap-3">
        <input
          v-model="manualForm.barcode"
          type="text"
          placeholder="EAN (barcode)"
          class="app-input"
          required
          autofocus
          ref="eanInputRef"
        />
<button type="submit" :disabled="manualFormLoading || !manualForm.barcode" class="app-button-primary mt-2 w-full disabled:cursor-not-allowed disabled:opacity-60">
  Cerca prodotto
</button>
        <p v-if="manualFormError" class="text-red-500 dark:text-red-400">{{ manualFormError }}</p>
      </form>
      <div v-else-if="manualStep === 'dati'" class="flex flex-col gap-4">
        <div class="flex items-start gap-4 rounded-3xl p-4" :style="{ background: 'color-mix(in srgb, var(--app-bg-muted) 100%, transparent)' }">
          <div class="flex flex-shrink-0 flex-col items-center">
            <img :src="manualForm.image_url || placeholder" alt="Immagine prodotto" class="h-20 w-20 cursor-pointer rounded-2xl object-cover border-2 border-transparent hover:border-indigo-400" @click="showImageInput = true; newImageUrl = manualForm.image_url || ''" />
            <button type="button" @click="showImageInput = true; newImageUrl = manualForm.image_url || ''" class="app-button-secondary mt-2 px-3 py-1.5 text-xs">Cambia</button>
          </div>
          <div class="flex flex-col gap-1 overflow-hidden">
            <div class="text-base font-bold leading-snug">{{ manualForm.name || 'Nome non disponibile' }}</div>
            <div class="text-xs font-medium" :style="{ color: 'var(--app-text-soft)' }">EAN: {{ manualForm.barcode }}</div>
          </div>
        </div>
        <div v-if="showImageInput" class="flex flex-col gap-2 mb-2">
          <input v-model="newImageUrl" type="url" placeholder="Nuovo URL immagine" class="app-input" />
          <div class="flex gap-2 flex-wrap">
            <button
              @click="updateImageUrl"
              type="button"
              :disabled="imageUploadLoading"
              class="flex-1 app-button-primary disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Salva URL
            </button>
            <button
              @click="fileInputRef?.click()"
              type="button"
              :disabled="imageUploadLoading"
              class="flex-1 app-button-secondary disabled:opacity-50 disabled:cursor-not-allowed"
            >
              📁 File
            </button>
            <button
              @click="cameraInputRef?.click()"
              type="button"
              :disabled="imageUploadLoading"
              class="flex-1 app-button-secondary disabled:opacity-50 disabled:cursor-not-allowed"
            >
              📷 Foto
            </button>
            <button @click="showImageInput = false" type="button" class="flex-1 app-button-secondary">
              Annulla
            </button>
          </div>
          <!-- Hidden file inputs -->
          <input
            ref="fileInputRef"
            type="file"
            accept="image/*"
            class="hidden"
            @change="handleImageFileSelected"
          />
          <input
            ref="cameraInputRef"
            type="file"
            accept="image/*"
            capture="environment"
            class="hidden"
            @change="handleCameraCapture"
          />
        </div>
        <form @submit.prevent="submitManualForm" class="flex flex-col gap-3">
          <input v-model="manualForm.name" type="text" placeholder="Nome prodotto" class="app-input" required />
          <select v-model="manualForm.rating" class="app-select" required>
            <option value="" disabled>Valutazione</option>
            <option v-for="opt in ratingOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
          <button type="submit" :disabled="manualFormLoading" class="app-button-primary mt-2 w-full disabled:cursor-not-allowed disabled:opacity-60">
            Salva valutazione
          </button>
          <button v-if="ratingId" type="button" @click="removeRating" :disabled="manualFormLoading" data-test="remove-rating" class="app-button-secondary w-full disabled:cursor-not-allowed disabled:opacity-60">
            Rimuovi valutazione
          </button>
          <p v-if="manualFormError" class="text-red-500 dark:text-red-400">{{ manualFormError }}</p>
        </form>
      </div>
      <div v-else-if="manualStep === 'errore'" class="flex flex-col gap-3">
        <p class="text-red-500 dark:text-red-400">Prodotto non trovato su OpenFoodFacts. Inserisci i dati manualmente.</p>
        <form @submit.prevent="submitManualForm" class="flex flex-col gap-3">
          <input v-model="manualForm.name" type="text" placeholder="Nome prodotto" class="app-input" required />
          <select v-model="manualForm.rating" class="app-select" required>
            <option value="" disabled>Valutazione</option>
            <option v-for="opt in ratingOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
          <button type="submit" :disabled="manualFormLoading" class="app-button-primary mt-2 w-full disabled:cursor-not-allowed disabled:opacity-60">
            Salva valutazione
          </button>
          <button v-if="ratingId" type="button" @click="removeRating" :disabled="manualFormLoading" data-test="remove-rating" class="app-button-secondary w-full disabled:cursor-not-allowed disabled:opacity-60">
            Rimuovi valutazione
          </button>
          <p v-if="manualFormError" class="text-red-500 dark:text-red-400">{{ manualFormError }}</p>
        </form>
      </div>
    </div>
  </div>
  <!-- Image Crop Modal -->
  <ImageCropModal
    ref="imageCropModalRef"
    :is-open="showImageCropModal"
    @close="showImageCropModal = false"
    @confirm="handleImageCropConfirm"
  />
</template>
