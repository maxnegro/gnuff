<template>
  <div class="app-page-stack">
      <!-- Modale prodotto/valutazione riutilizzabile -->
      <ProductRatingModal
        v-model="showProductModal"
        :initial-step="modalStep"
        :initial-form="modalForm"
        :rating-id="modalRatingId"
        @saved="onModalSaved"
      />

      <section>
        <div class="app-panel app-panel-pad overflow-hidden">
          <div class="rounded-[24px] border border-dashed border-primary-300/60 p-4 dark:border-primary-500/30" :style="{ background: 'color-mix(in srgb, var(--app-surface-strong) 82%, transparent)' }">
            <div class="overflow-hidden rounded-[20px]" :style="{ minHeight: 'clamp(16rem, 56vh, 24rem)', background: 'color-mix(in srgb, var(--app-bg-muted) 100%, transparent)' }">
              <StreamBarcodeReader v-if="!scannerPaused" :facing-mode="'environment'" @result="onResult" @error="onError"
                :torch="torchEnabled" />
              <div v-else class="flex items-center justify-center px-4 text-center sm:px-6" :style="{ minHeight: 'clamp(16rem, 56vh, 24rem)' }">
                <div>
                  <p class="text-lg font-semibold">Scanner in pausa</p>
                  <p class="mt-2 text-sm" :style="{ color: 'var(--app-text-soft)' }">Riprendi la scansione dopo aver chiuso o salvato la modale prodotto.</p>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center">
            <button @click="toggleTorch" class="btn app-button-secondary flex-1">🔦 Torcia</button>
            <button @click="togglePause" class="btn app-button-primary flex-1">
              {{ scannerPaused ? '▶️ Riprendi' : '⏸️ Pausa' }}
            </button>
          </div>
        </div>
      </section>
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
// Stato per la modale prodotto/valutazione
const showProductModal = ref(false);
const modalStep = ref('ean');
const modalForm = ref({ barcode: '', name: '', image_url: '', rating: '' });
const modalRatingId = ref(null);
let lastScannedBarcode = '';
let barcodeLookupInFlight = false;


function toggleTorch() {
  torchEnabled.value = !torchEnabled.value;
}

function togglePause() {
  scannerPaused.value = !scannerPaused.value;
}

async function onResult(result) {
  if ((result.format == 7) || (result.format == 14)) {
    const barcode = result.text;
    if (barcode === lastScannedBarcode || barcodeLookupInFlight) {
      return;
    }

    lastScannedBarcode = barcode;
    barcodeLookupInFlight = true;
    scannerPaused.value = true;
    try {
      const response = await axios.get(`/product/${encodeURIComponent(barcode)}`);
      // Prepara dati per la modale
      modalForm.value = {
        barcode,
        name: response.data.product?.name || '',
        image_url: response.data.product?.image_url || '',
        rating: response.data.rating || '',
      };
      modalRatingId.value = response.data.rating_id || null;
      modalStep.value = 'dati';
      showProductModal.value = true;
    } catch (error) {
      // Prodotto non trovato o lookup temporaneamente non disponibile
      modalForm.value = { barcode, name: '', image_url: '', rating: '' };
      modalRatingId.value = null;
      modalStep.value = 'errore';
      showProductModal.value = true;
    } finally {
      barcodeLookupInFlight = false;
    }
  }
}

function onError(err) {
  console.error('Errore scanner:', err);
}

function onModalSaved() {
  showProductModal.value = false;
  scannerPaused.value = false;
  lastScannedBarcode = '';
}

</script>

<style scoped>
.btn {
  padding: 0.5rem 1rem;
  font-weight: bold;
}
</style>
