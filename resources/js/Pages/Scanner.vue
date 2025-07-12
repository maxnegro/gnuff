<template>
  <div>
  <!-- Overlay prodotto -->
  <div
    v-if="showOverlay"
    class="absolute inset-0 bg-black bg-opacity-90 flex flex-col items-center justify-center p-6 z-50"
  >
    <h2 class="text-2xl font-bold mb-2 text-white">{{ product.name }}</h2>
    <img :src="product.image_url" alt="" class="max-h-48 object-contain mb-4" v-if="product.image_url" />
    <p class="text-sm text-gray-300 mb-2">Barcode: {{ product.barcode }}</p>

    <div class="flex gap-4 my-4">
      <button
        v-for="opt in ratingOptions"
        :key="opt.value"
        :class="[
          'text-4xl transition transform hover:scale-110',
          productRating === opt.value ? 'opacity-100' : 'opacity-50'
        ]"
        @click="submitRating(opt.value)"
      >
        {{ opt.emoji }}
      </button>
    </div>

    <button
      @click="closeOverlay"
      class="mt-6 bg-white text-black px-4 py-2 rounded-full hover:bg-gray-200 transition"
    >
      Chiudi
    </button>
  </div>
  <!-- Fine Overlay prodotto -->


  <div class="p-6 space-y-4">
    <h1 class="text-xl font-bold">Scanner prodotto</h1>

    <StreamBarcodeReader :no-front-camera @result="onResult" @error="onError" :paused="scannerPaused"
      :torch="torchEnabled" />

    <div class="flex items-center gap-4">
      <button @click="toggleTorch" class="btn">🔦 Torcia</button>
      <button @click="togglePause" class="btn">
        {{ scannerPaused ? '▶️ Riprendi' : '⏸️ Pausa' }}
      </button>
    </div>
  </div>
</div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed } from 'vue';
import { StreamBarcodeReader } from '@teckel/vue-barcode-reader';
import axios from 'axios';

defineOptions({
  layout: AuthenticatedLayout,
});

const torchEnabled = ref(false);
const scannerPaused = ref(false);
const product = ref(null);
const message = ref('');
const productRating = ref('');
const alreadyRated = ref(false);
const selectedRating = computed(() => ratingOptions.find(opt => opt.value === productRating.value));
const showOverlay = ref(false);

const ratingOptions = [
  { value: 'gnuf', emoji: '😋', label: 'Gnuf' },
  { value: 'ok', emoji: '😊', label: 'Ok' },
  { value: 'meh', emoji: '😐', label: 'Meh' },
  { value: 'bleah', emoji: '🤮', label: 'Bleah' },
];

function toggleTorch() {
  torchEnabled.value = !torchEnabled.value;
}

function togglePause() {
  scannerPaused.value = !scannerPaused.value;
}

async function onResult(result) {
  if (result.format == 7 ) {
    scannerPaused.value = true;
    try {
      const response = await axios.get(`/product/${result.text}`);
      if (response.data.rating) {
        productRating.value = response.data.rating;
        alreadyRated.value = true;
      } else {
        productRating.value = null;
        alreadyRated.value = false;
      }
      product.value = response.data.product;
      showOverlay.value = true;
    } catch (error) {
      console.error('Errore nella ricerca prodotto', error);
      product.value = null;
      productRating.value = null;
    }
  }
}

function onError(err) {
  console.error('Errore scanner:', err);
}

async function submitRating(value) {
  if (!product.value) return;
  const old = productRating.value;
  productRating.value = value;
  
  try {
    const response = await axios.post('/rate', {
      barcode: product.value.barcode,
      value,
    });
    message.value = response.data.message || 'Valutazione salvata!';
  } catch (error) {
    productRating.value = old;
    message.value = 'Errore durante il salvataggio.';
  }
}

const closeOverlay = () => {
  showOverlay.value = false;
  product.value = null;
  productRating.value = null;
  scannerPaused.value = false;
};

</script>

<style scoped>
.btn {
  background-color: #e2e8f0;
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  font-weight: bold;
}
</style>

