<template>
  <div class="bg-gray-50 text-black/50 dark:bg-black dark:text-white/50">
    <div class="relative flex min-h-screen flex-col items-center justify-top selection:bg-[#FF2D20] selection:text-white">
      <div class="relative w-full max-w-2xl px-6 lg:max-w-7xl">
        <header class="grid items-center">
          <div class="flex justify-center">
            <!-- Modale prodotto/valutazione riutilizzabile -->
            <ProductRatingModal
              v-model="showProductModal"
              :initial-step="modalStep"
              :initial-form="modalForm"
              @saved="onModalSaved"
            />

            <div class="p-6 space-y-4" style="min-height: 93vh;">
              <h1 class="text-xl font-bold">Scanner prodotto</h1>

              <StreamBarcodeReader v-if="!scannerPaused" :no-front-camera @result="onResult" @error="onError" 
                :torch="torchEnabled" />

              <div class="flex items-center gap-4">
                <button @click="toggleTorch" class="btn">🔦 Torcia</button>
                <button @click="togglePause" class="btn">
                  {{ scannerPaused ? '▶️ Riprendi' : '⏸️ Pausa' }}
                </button>
              </div>
            </div>
          </div>
        </header>
      </div>
    </div>
  </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

import { ref, computed } from 'vue';
import { StreamBarcodeReader } from '@teckel/vue-barcode-reader';
import axios from 'axios';
import ProductRatingModal from '@/Components/ProductRatingModal.vue';

defineOptions({
  layout: AuthenticatedLayout,
});


const torchEnabled = ref(false);
const scannerPaused = ref(false);
const placeholder = '/img/gnuff-placeholder-192.png';

// Stato per la modale prodotto/valutazione
const showProductModal = ref(false);
const modalStep = ref('ean');
const modalForm = ref({ barcode: '', name: '', image_url: '', rating: '' });


function toggleTorch() {
  torchEnabled.value = !torchEnabled.value;
}

function togglePause() {
  scannerPaused.value = !scannerPaused.value;
}

async function onResult(result) {
  if ((result.format == 7) || (result.format == 14)) {
    scannerPaused.value = true;
    try {
      const response = await axios.get(`/product/${result.text}`);
      // Prepara dati per la modale
      modalForm.value = {
        barcode: result.text,
        name: response.data.product?.name || '',
        image_url: response.data.product?.image_url || '',
        rating: response.data.rating || '',
      };
      modalStep.value = 'dati';
      showProductModal.value = true;
    } catch (error) {
      // Prodotto non trovato
      modalForm.value = { barcode: result.text, name: '', image_url: '', rating: '' };
      modalStep.value = 'errore';
      showProductModal.value = true;
    }
  }
}

function onError(err) {
  console.error('Errore scanner:', err);
}

function onModalSaved() {
  showProductModal.value = false;
  scannerPaused.value = false;
}

</script>

<style scoped>
.btn {
  background-color: #e2e8f0;
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  font-weight: bold;
}
</style>
