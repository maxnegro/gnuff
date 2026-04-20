<script setup>







import { router, usePage } from '@inertiajs/vue3'


import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/vue3'

import { ref, onMounted, watch } from 'vue'
import axios from 'axios'
import ProductRatingModal from '@/Components/ProductRatingModal.vue'

const page = usePage()
// Stato per la modale prodotto/valutazione
const showManualForm = ref(false)
const manualStep = ref('ean')
const manualForm = ref({ barcode: '', name: '', image_url: '', rating: '' })

async function openEditModal(rating) {
  // Se manca l'immagine o è il placeholder, prova a recuperare da OpenFoodFacts
  let name = rating.product.name || ''
  let image_url = rating.product.image_url || ''
  if (!image_url || image_url === placeholder) {
    try {
      const res = await axios.get(`/product/${rating.product.barcode}`)
      if (res.data && res.data.product) {
        name = res.data.product.name || name
        image_url = res.data.product.image_url || image_url
      }
    } catch (e) {
      // fallback: lascia i dati originali
    }
  }
  manualForm.value = {
    barcode: rating.product.barcode,
    name,
    image_url,
    rating: rating.rating || '',
  }
  manualStep.value = 'dati'
  showManualForm.value = true
}

function openNewModal() {
  manualForm.value = { barcode: '', name: '', image_url: '', rating: '' }
  manualStep.value = 'ean'
  showManualForm.value = true
}

defineOptions({
  layout: AuthenticatedLayout,
})


const ratings = ref(page.props.ratings || [])

const emojiMap = {
  gnuf: '😋',
  ok: '😊',
  meh: '😐',
  bleah: '🤮',
}

const placeholder = '/img/gnuff-placeholder-192.png';


// Aggiorna ratings quando cambiano le props di Inertia
watch(
  () => page.props.ratings,
  (newRatings) => {
    ratings.value = newRatings || []
  }
)



// Nessun fetch manuale: tutto da Inertia



</script>

<template>
  <div class="bg-gray-50 text-black dark:bg-black dark:text-white">
    <div
      class="relative flex min-h-screen flex-col items-center justify-center selection:bg-[#FF2D20] selection:text-white">
      <div class="relative w-full max-w-2xl px-6 lg:max-w-7xl">
        <header class="grid grid-cols-2 items-center gap-2 py-10 lg:grid-cols-3">
          <div class="flex lg:justify-center items-center gap-4 w-full">
            <img src="/img/icon-192.png" class="flex-shrink-0" />
          </div>
          <div class="flex flex-col gap-2 w-full ml-auto">
            <button @click="$inertia.visit('/scanner')"
              class="w-full px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
              📷 Scan
            </button>
            <button @click="openNewModal"
              class="w-full px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
              ➕ EAN
            </button>
          </div>
        </header>
        <main class="mt-6">
          <div class="grid items-center">
            <div
              class="flex flex-col gap-4 rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] lg:pb-10 dark:bg-zinc-900 dark:ring-zinc-800 text-gray-600">
              <ProductRatingModal v-model="showManualForm" :initial-step="manualStep" :initial-form="manualForm"
                @saved="() => router.reload({ only: ['ratings'] })" />

              <template v-if="ratings.length">
                <section style="width: 100%;">
                  <h2 class="text-xl font-semibold mb-4">Le tue valutazioni recenti</h2>
                  <ul class="space-y-2 w-full">
                    <li v-for="rating in ratings" :key="rating.id"
                      class="bg-white p-4 mb-2 rounded shadow w-full cursor-pointer hover:bg-gray-100 transition"
                      @click="openEditModal(rating)">
                      <div class="flex items-center w-full">
                        <img :src="rating.product.image_url || placeholder" alt="Immagine prodotto"
                          class="min-w-16 min-h-16 w-16 h-16 object-cover rounded mr-4" />
                        <div>
                          <h2 class="text-lg font-semibold">{{ rating.product.name }}</h2>
                          <p class="text-2xl">{{ emojiMap[rating.rating] }} ({{ rating.rating }})</p>
                          <p class="text-xs">
                            Valutato il {{ new Date(rating.updated_at).toLocaleString() }}
                            <span v-if="rating.product_list && rating.product_list.name">
                              in {{ rating.product_list.name }}
                            </span>
                          </p>
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
        <footer class="py-16 text-center text-sm text-black dark:text-white">Wonderful app by MN
        </footer>
      </div>
    </div>
  </div>


</template>