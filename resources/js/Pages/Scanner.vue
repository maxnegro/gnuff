<template>
  <div class="bg-gray-50 text-black/50 dark:bg-black dark:text-white/50">
    <div
      class="relative flex min-h-screen flex-col items-center justify-top selection:bg-[#FF2D20] selection:text-white">
      <div class="relative w-full max-w-2xl px-6 lg:max-w-7xl">
        <header class="grid items-center">
          <div class="flex justify-center">
            <!-- Overlay prodotto -->
            <div v-if="showOverlay"
              class="absolute inset-0 bg-black bg-opacity-90 flex flex-col items-center justify-center p-6 z-50">
              <div class="flex flex-col items-center justify-center" v-if="message">
                <p class="text-gray-300">{{ message }}</p>
              </div>
              <h2 class="text-2xl font-bold mb-2 text-white">{{ product.name }}</h2>
              <div v-if="product && !product.name">
                <input v-model="productName" class="border p-2 my-2 w-full" placeholder="Nome prodotto" />
                <button @click="saveProduct" class="btn">Salva</button>
              </div>
              <img :src="product.image_url || placeholder" alt="Immagine prodotto" class="max-h-48 object-contain mb-4" />
              <p class="text-sm text-gray-300 mb-2">Barcode: {{ product.barcode }}</p>

              <div class="flex gap-4 my-4 text-white">
                <button v-for="opt in ratingOptions" :key="opt.value" :class="[
                  'text-4xl transition transform hover:scale-110',
                  productRating === opt.value ? 'opacity-100' : 'opacity-50'
                ]" @click="submitRating(opt.value)">
                  {{ opt.emoji }}
                </button>
              </div>

              <button @click="closeOverlay"
                class="mt-6 bg-white text-black px-4 py-2 rounded-full hover:bg-gray-200 transition">
                Chiudi
              </button>
            </div>
            <!-- Fine Overlay prodotto -->

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
const productName = ref('');
const placeholder = '/img/gnuff-placeholder-192.png';

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
  if ((result.format == 7) || (result.format == 14)) {
    scannerPaused.value = true;
    try {
      message.value = null;
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

const saveProduct = async () => {
  try {
    const response = await axios.post('/product', {
      barcode: product.value.barcode,
      name: productName.value 
    });
    product.value = response.data.product;
  } catch (error) {
    message.value = "Errore durante il salvataggio del nome.";
  }
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
