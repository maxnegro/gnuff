<template>
  <div class="app-shell">
    <div class="app-frame py-6 sm:py-8">
      <!-- Modale prodotto/valutazione riutilizzabile -->
      <ProductRatingModal
        v-model="showProductModal"
        :initial-step="modalStep"
        :initial-form="modalForm"
        @saved="onModalSaved"
      />

      <section class="grid gap-6 lg:grid-cols-[0.95fr_1.05fr] lg:items-start">
        <div class="space-y-4">
          <p class="text-xs font-semibold uppercase tracking-[0.24em] text-primary-600 dark:text-primary-300">Scanner</p>
          <h1 class="app-page-title">Scanner prodotto</h1>
          <p class="app-page-subtitle">Inquadra un barcode, apri subito la scheda prodotto e completa la valutazione senza uscire dal flusso.</p>
          <div class="grid gap-3 text-sm sm:grid-cols-2">
            <div class="app-surface-soft rounded-3xl p-4">
              <p class="font-semibold">Modalità guidata</p>
              <p class="mt-1" :style="{ color: 'var(--app-text-soft)' }">Il reader mette in pausa la fotocamera quando trova un EAN supportato.</p>
            </div>
            <div class="app-surface-soft rounded-3xl p-4">
              <p class="font-semibold">Compatibile col tema</p>
              <p class="mt-1" :style="{ color: 'var(--app-text-soft)' }">Controlli, superfici e contrasto restano coerenti in light e dark mode.</p>
            </div>
          </div>
        </div>

        <div class="app-panel overflow-hidden p-5 sm:p-6">
          <div class="rounded-[24px] border border-dashed border-primary-300/60 p-4 dark:border-primary-500/30" :style="{ background: 'color-mix(in srgb, var(--app-surface-strong) 82%, transparent)' }">
            <div class="overflow-hidden rounded-[20px]" :style="{ minHeight: '24rem', background: 'color-mix(in srgb, var(--app-bg-muted) 100%, transparent)' }">
              <StreamBarcodeReader v-if="!scannerPaused" :facing-mode="'environment'" @result="onResult" @error="onError"
                :torch="torchEnabled" />
              <div v-else class="flex min-h-[24rem] items-center justify-center px-6 text-center">
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
  padding: 0.5rem 1rem;
  font-weight: bold;
}
</style>
