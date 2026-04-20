<script setup>
import { ref, watch, computed, nextTick } from 'vue'
import axios from 'axios'

const props = defineProps({
  modelValue: Boolean,
  initialStep: { type: String, default: 'ean' },
  initialForm: { type: Object, default: () => ({ barcode: '', name: '', image_url: '', rating: '' }) },
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

// Ref per l'input EAN
const eanInputRef = ref(null)

const ratingOptions = [
  { value: 'gnuf', label: '😋 Gnuf' },
  { value: 'ok', label: '😊 Ok' },
  { value: 'meh', label: '😐 Meh' },
  { value: 'bleah', label: '🤮 Bleah' },
]

const placeholder = '/img/gnuff-placeholder-192.png';


// Quando la modale viene aperta, resetta lo stato e prova a recuperare immagine se mancante
watch(() => props.modelValue, async (v) => {
  if (v) {
    manualStep.value = props.initialStep
    manualForm.value = { ...props.initialForm }
    manualFormError.value = ''
    showImageInput.value = false
    newImageUrl.value = ''
    await nextTick()
    if (manualStep.value === 'ean' && eanInputRef.value) {
      eanInputRef.value.focus()
    }
    // LOGICA AGGIUNTA: se siamo in step 'dati', abbiamo barcode e manca image_url, prova fetch
    if (
      manualStep.value === 'dati' &&
      manualForm.value.barcode &&
      !manualForm.value.image_url
    ) {
      try {
        const res = await axios.get(`/product/${manualForm.value.barcode}`)
        if (res.data && res.data.product && res.data.product.image_url) {
          manualForm.value.image_url = res.data.product.image_url
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
    await axios.put(`/product/${manualForm.value.barcode}`, {
      name: manualForm.value.name,
      image: newImageUrl.value,
    })
    manualForm.value.image_url = newImageUrl.value
    showImageInput.value = false
    newImageUrl.value = ''
  } catch (e) {
    manualFormError.value = 'Errore durante l\'aggiornamento immagine.'
  } finally {
    manualFormLoading.value = false
  }
}

async function submitManualForm() {
  manualFormError.value = ''
  manualFormLoading.value = true
  try {
    const prodRes = await axios.post('/product', {
      barcode: manualForm.value.barcode,
      name: manualForm.value.name,
      image_url: manualForm.value.image_url || null,
    })
    await axios.post('/rate', {
      barcode: manualForm.value.barcode,
      value: manualForm.value.rating,
    })
    emit('saved')
    show.value = false
  } catch (e) {
    manualFormError.value = 'Errore durante il salvataggio. Controlla i dati.'
  } finally {
    manualFormLoading.value = false
  }
}
</script>
<template>
  <div v-if="show" class="fixed inset-0 flex items-center justify-center bg-black/40 z-50">
    <div class="bg-white dark:bg-zinc-900 p-6 rounded shadow-lg w-full max-w-md relative">
      <button @click="show = false" class="absolute top-2 right-2 text-gray-400 hover:text-black">✖</button>
      <h3 class="text-lg font-bold mb-4">Aggiungi/modifica prodotto tramite EAN</h3>
      <form v-if="manualStep === 'ean'" @submit.prevent="cercaEAN" class="flex flex-col gap-3">
        <input
          v-model="manualForm.barcode"
          type="text"
          placeholder="EAN (barcode)"
          class="border rounded px-3 py-2"
          required
          autofocus
          ref="eanInputRef"
        />
        <button type="submit" :disabled="manualFormLoading || !manualForm.barcode" class="bg-indigo-600 text-white rounded px-4 py-2 mt-2 hover:bg-indigo-700">
          Cerca prodotto
        </button>
        <p v-if="manualFormError" class="text-red-500">{{ manualFormError }}</p>
      </form>
      <div v-else-if="manualStep === 'dati'" class="flex flex-col gap-3">
        <div class="flex items-center gap-3 mb-2">
          <div class="flex flex-col items-center">
            <img :src="manualForm.image_url || placeholder" alt="Immagine prodotto" class="w-16 h-16 object-cover rounded cursor-pointer border-2 border-transparent hover:border-indigo-400" @click="showImageInput = true; newImageUrl = manualForm.image_url || ''" />
            <button type="button" @click="showImageInput = true; newImageUrl = manualForm.image_url || ''" class="mt-1 w-16 bg-white text-xs px-2 py-1 rounded shadow border border-gray-200 opacity-90 hover:opacity-100 text-center">Cambia</button>
          </div>
          <div>
            <div class="font-bold">{{ manualForm.name || 'Nome non disponibile' }}</div>
            <div class="text-xs text-gray-500">EAN: {{ manualForm.barcode }}</div>
          </div>
        </div>
        <div v-if="showImageInput" class="flex flex-col gap-2 mb-2">
          <input v-model="newImageUrl" type="url" placeholder="Nuovo URL immagine" class="border rounded px-3 py-2" />
          <div class="flex gap-2">
            <button @click="updateImageUrl" type="button" class="bg-indigo-600 text-white rounded px-3 py-1 hover:bg-indigo-700">Salva immagine</button>
            <button @click="showImageInput = false" type="button" class="bg-gray-300 rounded px-3 py-1 hover:bg-gray-400">Annulla</button>
          </div>
        </div>
        <form @submit.prevent="submitManualForm" class="flex flex-col gap-3">
          <input v-model="manualForm.name" type="text" placeholder="Nome prodotto" class="border rounded px-3 py-2" required />
          <select v-model="manualForm.rating" class="border rounded px-3 py-2" required>
            <option value="" disabled>Valutazione</option>
            <option v-for="opt in ratingOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
          <button type="submit" :disabled="manualFormLoading" class="bg-indigo-600 text-white rounded px-4 py-2 mt-2 hover:bg-indigo-700">
            Salva valutazione
          </button>
          <p v-if="manualFormError" class="text-red-500">{{ manualFormError }}</p>
        </form>
      </div>
      <div v-else-if="manualStep === 'errore'" class="flex flex-col gap-3">
        <p class="text-red-500">Prodotto non trovato su OpenFoodFacts. Inserisci i dati manualmente.</p>
        <form @submit.prevent="submitManualForm" class="flex flex-col gap-3">
          <input v-model="manualForm.name" type="text" placeholder="Nome prodotto" class="border rounded px-3 py-2" required />
          <select v-model="manualForm.rating" class="border rounded px-3 py-2" required>
            <option value="" disabled>Valutazione</option>
            <option v-for="opt in ratingOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
          <button type="submit" :disabled="manualFormLoading" class="bg-indigo-600 text-white rounded px-4 py-2 mt-2 hover:bg-indigo-700">
            Salva valutazione
          </button>
          <p v-if="manualFormError" class="text-red-500">{{ manualFormError }}</p>
        </form>
      </div>
    </div>
  </div>
</template>
