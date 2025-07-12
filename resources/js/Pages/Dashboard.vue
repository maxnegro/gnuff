<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'
import axios from 'axios'

defineOptions({
  layout: AuthenticatedLayout,
})

const ratings = ref([])

const emojiMap = {
  gnuf: '😋',
  ok: '😊',
  meh: '😐',
  bleah: '🤮',
}

async function fetchRatings() {
  try {
    const response = await axios.get('/user/ratings')
    ratings.value = response.data
  } catch (e) {
    console.error('Errore caricamento valutazioni:', e)
  }
}

onMounted(() => {
  fetchRatings()
})

</script>

<template>
    <div class="space-y-6">
      <h1 class="text-2xl font-bold">Benvenuto su Gnuff 🎉</h1>
      
      <p class="text-gray-700">
        Questa è la tua dashboard personale. Qui potrai visualizzare e valutare i prodotti alimentari semplicemente
        scansionando il loro codice a barre.
      </p>
  
      <p>
        <Link href="/scanner" class="text-indigo-600 hover:underline text-lg">
          👉 Vai allo scanner
        </Link>
      </p>

      <section v-if="ratings.length" class="mt-8">
      <h2 class="text-xl font-semibold mb-4">Le tue valutazioni recenti</h2>
        <ul class="space-y-2">
            <li v-for="rating in ratings" :key="rating.id" class="border p-3 rounded">
                <div class="flex justify-between items-center">
                    <div>
                        <strong>{{ rating.product.name }}</strong>
                        <div class="text-sm text-gray-600">Barcode: {{ rating.product.barcode }}</div>
                    </div>
                    <div class="text-2xl">
                      {{ emojiMap[rating.value] || '❓' }}
                    </div>
                </div>
                <div class="text-xs text-gray-500 mt-1">
                    Valutato il {{ new Date(rating.created_at).toLocaleString() }}
                </div>
            </li>
      </ul>
      </section>

      <p v-else class="mt-8 text-gray-500">
        Non hai ancora fatto nessuna valutazione.
      </p>

    </div>
</template>
  