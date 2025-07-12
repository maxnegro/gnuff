<template>
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

    <div v-if="product" class="mt-4 border-t pt-4">
      <h2 class="text-lg font-semibold">{{ product.name }}</h2>
      <img :src="product.image_url" alt="" v-if="product.image_url" class="w-32 h-auto mt-2" />

      <div v-if="alreadyRated">
        <p>Questo prodotto è:</p>
        <p class="text-2xl">
          {{ selectedRating.emoji }} ({{ selectedRating.label }})
        </p>
      </div>


      <div class="mt-4 space-x-2">
        <button v-for="option in ratingOptions" :key="option.value" @click="submitRating(option.value)"
          class="text-2xl">
          {{ option.emoji }} {{ option.label }}
        </button>
      </div>
    </div>

    <div v-if="message" class="mt-4 text-green-700 font-semibold">
      {{ message }}
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
const rating = ref('');
const alreadyRated = ref(false);
const selectedRating = computed(() => ratingOptions.find(opt => opt.value === rating.value));

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
    message.value = '';
    try {
      const response = await axios.get(`/product/${result.text}`);
      if (response.data.rating) {
        rating.value = response.data.rating;
        alreadyRated.value = true;
      } else {
        rating.value = null;
        alreadyRated.value = false;
      }
      product.value = response.data.product;
    } catch (error) {
      message.value = `Prodotto non trovato (${error})`;
      product.value = null;
    }
  }
}

function onError(err) {
  console.error('Errore scanner:', err);
}

async function submitRating(value) {
  if (!product.value) return;

  try {
    const response = await axios.post('/rate', {
      barcode: product.value.barcode,
      value,
    });
    message.value = response.data.message || 'Valutazione salvata!';
  } catch (error) {
    message.value = 'Errore durante il salvataggio.';
  }
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

