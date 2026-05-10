<template>
  <div class="app-page-stack space-y-6">
    <div v-if="notification" :class="['rounded-2xl px-4 py-3 text-center text-sm font-medium', notificationType === 'success' ? 'bg-primary-100 text-primary-900 dark:bg-primary-900/40 dark:text-primary-100' : 'bg-red-100 text-red-900 dark:bg-red-900/30 dark:text-red-100']">
      {{ notification }}
    </div>

    <section class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div><h1 class="app-page-title">Prodotti</h1></div>
      <div class="app-surface-soft flex flex-col gap-2 rounded-3xl p-2 sm:flex-row sm:items-center">
        <button @click="toggleShowAll" class="app-button-secondary w-full text-xs font-semibold uppercase tracking-[0.18em]">
          {{ showAll ? 'solo con valutazione' : 'mostra tutti' }}
        </button>
      </div>
    </section>

    <section class="app-panel app-panel-pad">
      <div class="mb-4 flex flex-col gap-3">
        <input v-model="search" type="text" placeholder="Cerca per nome o barcode..." class="app-input w-full" />
      </div>

      <div class="overflow-x-auto rounded-[24px] border" :style="{ borderColor: 'var(--app-border)', background: 'color-mix(in srgb, var(--app-surface-strong) 90%, transparent)' }">
      <table class="min-w-full text-sm">
        <thead>
          <tr :style="{ background: 'color-mix(in srgb, var(--app-bg-muted) 100%, transparent)' }">
            <th v-for="field in tableFields" :key="field" class="px-3 py-3 text-left cursor-pointer select-none text-xs font-semibold uppercase tracking-[0.18em]"
                @click="sortableFields.includes(field) && sortBy(field)"
                :class="sortableFields.includes(field) ? 'hover:bg-primary-50 dark:hover:bg-slate-800/80' : ''">
              {{ fieldLabels[field] }}
              <span v-if="sort.field === field">
                {{ sort.direction === 'asc' ? '▲' : '▼' }}
              </span>
            </th>
            <th class="px-3 py-3 text-left cursor-pointer select-none text-xs font-semibold uppercase tracking-[0.18em] hover:bg-primary-50 dark:hover:bg-slate-800/80"
                @click="sortBy('rating')">
              Valore
              <span v-if="sort.field === 'rating'">
                {{ sort.direction === 'asc' ? '▲' : '▼' }}
              </span>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="product in filteredProducts" :key="product.id" class="cursor-pointer border-b transition hover:bg-primary-50/70 dark:hover:bg-slate-800/70" :style="{ borderColor: 'var(--app-border)' }" @click="openEditModal(product)">
            <td class="px-3 py-3 font-medium">{{ product.name }}</td>
            <td class="px-3 py-3 font-mono text-xs sm:text-sm">{{ product.barcode }}</td>
            <td class="px-3 py-3">
              <img :src="product.image_url || placeholder" @error="e => e.target.src = placeholder" alt="img" class="h-12 w-12 rounded-2xl object-cover" />
            </td>
            <td class="px-3 py-3 whitespace-nowrap">
              <span class="text-xl">{{ ratingEmojis[ratings[product.id]?.value] || '' }}</span>
              <span v-if="ratings[product.id]?.value"> ({{ ratings[product.id].value }})</span>
            </td>
          </tr>
        </tbody>
      </table>
      </div>
      <div v-if="filteredProducts.length === 0" class="app-empty-state text-center" :style="{ color: 'var(--app-text-soft)' }">Nessun prodotto trovato.</div>
    </section>

    <ProductRatingModal
      v-model="showProductModal"
      :initial-step="modalStep"
      :initial-form="modalForm"
      :rating-id="modalRatingId"
      @saved="onModalSaved"
    />
  </div>
</template>

<script setup>
import ProductRatingModal from '@/Components/ProductRatingModal.vue';

const showProductModal = ref(false);
const modalStep = ref('ean');
const modalForm = ref({ barcode: '', name: '', image_url: '', rating: '' });
const modalRatingId = ref(null);

function openEditModal(product) {
  modalForm.value = {
    barcode: product.barcode,
    name: product.name,
    image_url: product.image_url,
    rating: ratings.value[product.id]?.value || '',
  };
  modalRatingId.value = ratings.value[product.id]?.id || null;
  modalStep.value = 'dati';
  showProductModal.value = true;
}

function onModalSaved() {
  showProductModal.value = false;
  fetchProducts();
}
const ratingEmojis = { gnuf: '😋', ok: '😊', meh: '😐', bleah: '🤮' };
const placeholder = '/img/gnuff-placeholder-192.png';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
defineOptions({ layout: AuthenticatedLayout });

import { ref, computed, watch } from 'vue';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';


const page = usePage();
const activeListId = computed(() => page.props.active_list ? page.props.active_list.id : null);

const products = ref([]);
const search = ref('');
const sort = ref({ field: 'name', direction: 'asc' });
const ratings = ref({});
const showAll = ref(false); // default: solo con valutazione

const tableFields = ['name', 'barcode', 'image_url'];
const sortableFields = ['name', 'barcode'];
const fieldLabels = { name: 'Nome', barcode: 'Barcode', image_url: 'Immagine' };
const fetchProducts = async () => {
  // console.log('fetchProducts chiamata, activeListId:', activeListId.value);
  if (!activeListId.value) {
    console.log('Nessuna lista attiva, esco');
    products.value = [];
    ratings.value = {};
    return;
  }
  try {
    // Carica tutti i prodotti (senza filtro lista)
    const pres = await axios.get(`/api/products?per_page=100`);
    // console.log('Risposta /api/products:', pres.data);
    // Adatta qui se la struttura non è pres.data.data
    if (Array.isArray(pres.data)) {
      products.value = pres.data;
    } else if (Array.isArray(pres.data.data)) {
      products.value = pres.data.data;
    } else if (pres.data && pres.data.products) {
      products.value = pres.data.products;
    } else {
      products.value = [];
    }
    // console.log('Prodotti caricati:', products.value);
    // Carica tutti i rating dell'utente per la lista attiva
    const res = await axios.get(`/api/ratings?per_page=100&list_id=${activeListId.value}`);
    ratings.value = {};
    res.data.data.forEach(r => {
      if (r.product && r.rating) ratings.value[r.product.id] = { id: r.id, value: r.rating };
    });
    // console.log('Ratings caricati:', ratings.value);
  } catch (e) {
    console.error('Errore in fetchProducts:', e);
  }
};

const filteredProducts = computed(() => {
  let list = products.value.filter(p =>
    (!search.value ||
      p.name?.toLowerCase().includes(search.value.toLowerCase()) ||
      p.barcode?.includes(search.value))
  );
  // Se showAll è false, mostra solo prodotti con rating
  if (!showAll.value) {
    list = list.filter(p => ratings.value[p.id]?.value);
  }
  if (sort.value.field) {
    list = [...list].sort((a, b) => {
      if (sort.value.field === 'rating') {
        const order = { gnuf: 4, ok: 3, meh: 2, bleah: 1, '': 0, null: 0, undefined: 0 };
        const ra = order[ratings.value[a.id]?.value] ?? 0;
        const rb = order[ratings.value[b.id]?.value] ?? 0;
        if (ra < rb) return sort.value.direction === 'asc' ? -1 : 1;
        if (ra > rb) return sort.value.direction === 'asc' ? 1 : -1;
        return 0;
      } else {
        const fa = a[sort.value.field]?.toString().toLowerCase() ?? '';
        const fb = b[sort.value.field]?.toString().toLowerCase() ?? '';
        if (fa < fb) return sort.value.direction === 'asc' ? -1 : 1;
        if (fa > fb) return sort.value.direction === 'asc' ? 1 : -1;
        return 0;
      }
    });
  }
  return list;
});
function toggleShowAll() {
  showAll.value = !showAll.value;
}

function sortBy(field) {
  if (sort.value.field === field) {
    sort.value.direction = sort.value.direction === 'asc' ? 'desc' : 'asc';
  } else {
    sort.value.field = field;
    sort.value.direction = 'asc';
  }
}

async function rateProduct(product) {
  const value = ratings.value[product.id];
  if (!value) return;
  await axios.post('/api/rate', { barcode: product.barcode, value });
}


// Aggiorna prodotti e rating quando cambia la lista attiva
watch(activeListId, () => {
  fetchProducts();
});

// Primo caricamento
fetchProducts();
</script>

<style scoped>
@media (max-width: 640px) {
  table, thead, tbody, th, td, tr { display: block; }
  th, td { padding: 0.5rem 0.25rem; }
  tr { margin-bottom: 1rem; }
}
</style>
