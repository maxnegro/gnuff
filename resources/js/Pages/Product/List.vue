<template>
  <div class="p-2 sm:p-4 max-w-lg mx-auto">
    <h1 class="text-xl font-bold mb-4 text-center">Prodotti Registrati</h1>
    <div class="flex flex-col gap-2 mb-4">
      <input v-model="search" type="text" placeholder="Cerca per nome o barcode..." class="input input-bordered w-full" />
      <button @click="toggleShowAll" class="mt-2 px-2 py-1 rounded bg-green-200 font-bold text-xs self-end">
        {{ showAll ? 'solo con valutazione' : 'mostra tutti' }}
      </button>
    </div>
    <div class="overflow-x-auto rounded shadow bg-white">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="bg-gray-100">
            <th v-for="field in tableFields" :key="field" class="py-2 px-2 text-left cursor-pointer select-none"
                @click="sortableFields.includes(field) && sortBy(field)"
                :class="sortableFields.includes(field) ? 'hover:bg-blue-100' : ''">
              {{ fieldLabels[field] }}
              <span v-if="sort.field === field">
                {{ sort.direction === 'asc' ? '▲' : '▼' }}
              </span>
            </th>
            <th class="py-2 px-2 text-left cursor-pointer select-none hover:bg-blue-100"
                @click="sortBy('rating')">
              Val
              <span v-if="sort.field === 'rating'">
                {{ sort.direction === 'asc' ? '▲' : '▼' }}
              </span>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="product in filteredProducts" :key="product.id" class="border-b">
            <td class="py-2 px-2">{{ product.name }}</td>
            <td class="py-2 px-2 font-mono">{{ product.barcode }}</td>
            <td class="py-2 px-2">
              <img :src="product.image_url || placeholder" @error="e => e.target.src = placeholder" alt="img" class="w-12 h-12 object-cover rounded" />
            </td>
            <td class="py-2 px-2">
              <select v-model="ratings[product.id]" class="select select-xs" @change="rateProduct(product)">
                <option value="">-</option>
                <option v-for="val in ratingOptions" :key="val" :value="val">
                  {{ ratingEmojis[val] || '' }} {{ val.charAt(0).toUpperCase() + val.slice(1) }}
                </option>
              </select>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="filteredProducts.length === 0" class="text-center text-gray-400 py-8">Nessun prodotto trovato.</div>
    </div>
  </div>
</template>

<script setup>
const ratingEmojis = { gnuf: '😋', ok: '😊', meh: '😐', bleah: '🤮' };
const placeholder = '/img/gnuff-placeholder-192.png';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
defineOptions({ layout: AuthenticatedLayout });
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';

const products = ref([]);
const search = ref('');
const sort = ref({ field: 'name', direction: 'asc' });
const ratings = ref({});
const showAll = ref(false); // default: solo con valutazione

const tableFields = ['name', 'barcode', 'image_url'];
const sortableFields = ['name', 'barcode'];
const fieldLabels = { name: 'Nome', barcode: 'Barcode', image_url: 'Immagine' };
const ratingOptions = ['gnuf', 'ok', 'meh', 'bleah'];

const fetchProducts = async () => {
  // Carica tutti i prodotti
  const pres = await axios.get('/api/products?per_page=100');
  products.value = pres.data.data;
  // Carica tutti i rating dell'utente
  const res = await axios.get('/api/ratings?per_page=100');
  res.data.data.forEach(r => {
    if (r.product && r.rating) ratings.value[r.product.id] = r.rating;
  });
};

const filteredProducts = computed(() => {
  let list = products.value.filter(p =>
    (!search.value ||
      p.name?.toLowerCase().includes(search.value.toLowerCase()) ||
      p.barcode?.includes(search.value))
  );
  // Se showAll è false, mostra solo prodotti con rating
  if (!showAll.value) {
    list = list.filter(p => ratings.value[p.id]);
  }
  if (sort.value.field) {
    list = [...list].sort((a, b) => {
      if (sort.value.field === 'rating') {
        const order = { gnuf: 4, ok: 3, meh: 2, bleah: 1, '': 0, null: 0, undefined: 0 };
        const ra = order[ratings.value[a.id]] ?? 0;
        const rb = order[ratings.value[b.id]] ?? 0;
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

function sortButtonClass(field) {
  return [
    'px-2 py-1 rounded',
    sort.value.field === field ? 'bg-blue-200 font-bold' : 'bg-gray-200',
  ];
}

async function rateProduct(product) {
  const value = ratings.value[product.id];
  if (!value) return;
  await axios.post('/api/rate', { barcode: product.barcode, value });
}

onMounted(fetchProducts);
</script>

<style scoped>
/* Migliora aspetto input e select */
.input {
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  padding: 0.5rem;
}
.select {
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  padding: 0.25rem 1.5rem 0.25rem 0.5rem;
  min-width: 80px;
  font-size: 1rem;
  font-family: inherit;
  background-color: #fff;
  appearance: none;
  background-image: url('data:image/svg+xml;utf8,<svg fill="gray" height="16" viewBox="0 0 20 20" width="16" xmlns="http://www.w3.org/2000/svg"><path d="M7.293 7.293a1 1 0 011.414 0L10 8.586l1.293-1.293a1 1 0 111.414 1.414l-2 2a1 1 0 01-1.414 0l-2-2a1 1 0 010-1.414z"/></svg>');
  background-repeat: no-repeat;
  background-position: right 0.5rem center;
  background-size: 1rem;
}
/* Fix per select su Safari/iOS */
select.select::-ms-expand {
  display: none;
}
@media (max-width: 640px) {
  table, thead, tbody, th, td, tr { display: block; }
  th, td { padding: 0.5rem 0.25rem; }
  tr { margin-bottom: 1rem; border-bottom: 1px solid #eee; }
  th { background: #f3f4f6; }
}
</style>
