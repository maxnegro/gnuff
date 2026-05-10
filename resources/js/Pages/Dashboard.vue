<script setup>







import { router, usePage } from '@inertiajs/vue3'


import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, watch } from 'vue'
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
  <section class="app-page-stack space-y-6">
        <div class="app-panel app-panel-pad flex min-h-[10rem] items-center justify-between gap-5 sm:min-h-[12rem] sm:gap-6">
          <div class="flex min-w-0 items-center">
            <img src="/img/icon-1024.png" alt="Dashboard header" class="h-28 w-28 sm:h-36 sm:w-36 object-contain" />
          </div>
          <div class="flex flex-col gap-3 sm:flex-row">
            <button @click="$inertia.visit('/scanner')" class="app-button-primary flex items-center justify-center gap-2">📷 Scan</button>
            <button @click="openNewModal" class="app-button-secondary flex items-center justify-center gap-2">➕ EAN</button>
          </div>
        </div>

        <ProductRatingModal v-model="showManualForm" :initial-step="manualStep" :initial-form="manualForm"
          @saved="() => router.reload({ only: ['ratings'] })" />

        <div v-if="ratings.length" class="app-panel app-panel-pad">
          <h2 class="text-lg font-semibold mb-4">Valutazioni recenti</h2>
          <div class="space-y-3">
            <div v-for="rating in ratings" :key="rating.id"
              class="app-surface-soft rounded-3xl p-4 cursor-pointer transition hover:bg-primary-50/50 dark:hover:bg-slate-800/70"
              @click="openEditModal(rating)">
              <div class="flex items-center gap-4">
                <img :src="rating.product.image_url || placeholder" alt="Immagine prodotto"
                  class="h-16 w-16 flex-shrink-0 rounded-2xl object-cover" />
                <div class="flex-1 overflow-hidden">
                  <h3 class="font-semibold leading-snug">{{ rating.product.name }}</h3>
                  <p class="mt-1 text-xl">{{ emojiMap[rating.rating] }} <span class="text-sm font-medium">{{ rating.rating }}</span></p>
                  <p class="mt-1 text-xs" :style="{ color: 'var(--app-text-soft)' }">
                    Valutato il {{ new Date(rating.updated_at).toLocaleDateString('it-IT') }}
                    <span v-if="rating.product_list && rating.product_list.name">
                      in <span class="font-medium">{{ rating.product_list.name }}</span>
                    </span>
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="app-panel app-empty-state text-center" :style="{ color: 'var(--app-text-soft)' }">
          <p class="text-lg">Non hai ancora fatto nessuna valutazione.</p>
          <p class="mt-2 text-sm">Inizia scansionando un codice o aggiungendo un nuovo prodotto.</p>
        </div>
  </section>
</template>