<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'
import axios from 'axios'

// Stato per il form manuale (esperienza tipo scanner)
const showManualForm = ref(false)
const manualStep = ref('ean') // 'ean' | 'dati' | 'errore'
const manualForm = ref({
  barcode: '',
  name: '',
  image_url: '',
  rating: '',
})

const manualFormError = ref('')
const manualFormLoading = ref(false)
const manualProductFound = ref(false)

// Stato per cambio immagine
const showImageInput = ref(false)
const newImageUrl = ref('')

async function updateImageUrl() {
  manualFormError.value = ''
  manualFormLoading.value = true
  try {
    // PATCH/PUT su /product/{barcode} (usiamo PUT per coerenza REST)
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

const ratingOptions = [
  { value: 'gnuf', label: '😋 Gnuf' },
  { value: 'ok', label: '😊 Ok' },
  { value: 'meh', label: '😐 Meh' },
  { value: 'bleah', label: '🤮 Bleah' },
]

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

async function submitManualForm() {
  manualFormError.value = ''
  manualFormLoading.value = true
  try {
    // 1. Crea/aggiorna prodotto (se non già esistente)
    const prodRes = await axios.post('/product', {
      barcode: manualForm.value.barcode,
      name: manualForm.value.name,
      image_url: manualForm.value.image_url || null,
    })
    const product = prodRes.data.product
    // 2. Salva rating
    await axios.post('/rate', {
      barcode: manualForm.value.barcode,
      value: manualForm.value.rating,
    })
    // 3. Aggiorna lista e chiudi form
    await fetchRatings()
    showManualForm.value = false
    manualForm.value = { barcode: '', name: '', image_url: '', rating: '' }
    manualStep.value = 'ean'
  } catch (e) {
    manualFormError.value = 'Errore durante il salvataggio. Controlla i dati.'
  } finally {
    manualFormLoading.value = false
  }
}

defineOptions({
  layout: AuthenticatedLayout,
})

const ratings = ref([])

const emojiMap = {
  gnuf: '😋',
  ok: '😊',
  meh: '😐',
  bleah: '🤮',
}

const placeholder = '/img/gnuff-placeholder-192.png';

async function fetchRatings() {
  try {
    const response = await axios.get('/user/ratings')
    ratings.value = response.data
  } catch (e) {
    console.error('Errore caricamento valutazioni:', e)
  }
}

onMounted(() => {
  fetchRatings()
})

// Funzione per aprire la modale in modalità modifica da valutazione recente
function openEditModal(rating) {
  manualForm.value = {
    barcode: rating.product.barcode,
    name: rating.product.name || '',
    image_url: rating.product.image_url || '',
    rating: rating.rating || '',
  }
  manualStep.value = 'dati'
  showManualForm.value = true
  showImageInput.value = false
  newImageUrl.value = ''
}

</script>

<template>
  <div class="bg-gray-50 text-black/50 dark:bg-black dark:text-white/50">
    <div
      class="relative flex min-h-screen flex-col items-center justify-center selection:bg-[#FF2D20] selection:text-white">
      <div class="relative w-full max-w-2xl px-6 lg:max-w-7xl">
        <header class="grid grid-cols-2 items-center gap-2 py-10 lg:grid-cols-3">
          <div class="flex lg:justify-center items-center gap-4 w-full">
            <img src="/img/icon-192.png" class="flex-shrink-0" />
          </div>
          <div class="flex lg:justify-center items-center gap-4 w-full">
            <div class="flex flex-col gap-2 w-full ml-auto">
              <button
                @click="$inertia.visit('/scanner')"
                class="w-full px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
              >
                📷 Scan
              </button>
              <button
                @click="showManualForm = true; manualStep = 'ean'; manualForm = { barcode: '', name: '', image_url: '', rating: '' }"
                class="w-full px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700"
              >
                ➕ EAN
              </button>
            </div>
          </div>
        </header>
        <main class="mt-6">
                  <div class="grid items-center">
                    <div class="flex flex-col gap-4 rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] lg:pb-10 dark:bg-zinc-900 dark:ring-zinc-800 text-gray-600">
                      <!-- Form inserimento manuale, esperienza tipo scanner -->
                      <div v-if="showManualForm" class="fixed inset-0 flex items-center justify-center bg-black/40 z-50">
                        <div class="bg-white dark:bg-zinc-900 p-6 rounded shadow-lg w-full max-w-md relative">
                          <button @click="showManualForm = false" class="absolute top-2 right-2 text-gray-400 hover:text-black">✖</button>
                          <h3 class="text-lg font-bold mb-4">Aggiungi prodotto tramite EAN</h3>
                          <form v-if="manualStep === 'ean'" @submit.prevent="cercaEAN" class="flex flex-col gap-3">
                            <input v-model="manualForm.barcode" type="text" placeholder="EAN (barcode)" class="border rounded px-3 py-2" required autofocus />
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

                      <template v-if="ratings.length">
                        <section style="width: 100%;">
                          <h2 class="text-xl font-semibold mb-4">Le tue valutazioni recenti</h2>
                          <ul class="space-y-2 w-full">
                            <li v-for="rating in ratings" :key="rating.id" class="bg-white p-4 mb-2 rounded shadow w-full cursor-pointer hover:bg-gray-100 transition"
                                @click="openEditModal(rating)">
                              <div class="flex items-center w-full">
                                <img :src="rating.product.image_url || placeholder" alt="Immagine prodotto" class="min-w-16 min-h-16 w-16 h-16 object-cover rounded mr-4" />
                                <div>
                                  <h2 class="text-lg font-semibold">{{ rating.product.name }}</h2>
                                  <p class="text-2xl">{{ emojiMap[rating.rating] }} ({{ rating.rating }})</p>
                                  <p class="text-xs">Valutato il {{ new Date(rating.updated_at).toLocaleString() }}</p>
                                </div>
                              </div>
                            </li>
                          </ul>
                        </section>
                      </template>
                      <p v-else class="text-gray-500">
                        Non hai ancora fatto nessuna valutazione.
                      </p>
            </div>
          </div>
        </main>
        <footer class="py-16 text-center text-sm text-black dark:text-white/70">Wonderful app by MN
        </footer>
      </div>
    </div>
  </div>


</template>