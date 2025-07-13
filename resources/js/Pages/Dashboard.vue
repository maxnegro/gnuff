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
  <div class="bg-gray-50 text-black/50 dark:bg-black dark:text-white/50">
    <div
      class="relative flex min-h-screen flex-col items-center justify-center selection:bg-[#FF2D20] selection:text-white">
      <div class="relative w-full max-w-2xl px-6 lg:max-w-7xl">
        <header class="grid grid-cols-2 items-center gap-2 py-10 lg:grid-cols-3">
          <div class="flex lg:col-start-2 lg:justify-center">
            <p>
              <img src="/img/icon-192.png">

              <Link href="/scanner" class="text-indigo-600 hover:underline text-lg">
              👉 Vai allo scanner
              </Link>
            </p>
          </div>
        </header>
        <main class="mt-6">
          <div class="grid items-center">
            <div
              class="flex items-start gap-4 rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] lg:pb-10 dark:bg-zinc-900 dark:ring-zinc-800">


              <section v-if="ratings.length" class="" style="width: 100%;">
                <h2 class="text-xl font-semibold mb-4">Le tue valutazioni recenti</h2>
                <ul class="space-y-2">
                  <li v-for="rating in ratings" :key="rating.id" class="border p-3 rounded">
                    <div class="flex justify-between items-center">
                      <div>
                        <strong>{{ rating.product.name }}</strong>
                        <div class="text-sm text-gray-600">Barcode: {{ rating.product.barcode }}</div>
                        <div class="text-xs text-gray-500 mt-1">
                          Valutato il {{ new Date(rating.updated_at).toLocaleString() }}
                        </div>
                      </div>
                      <div class="text-2xl">
                        {{ emojiMap[rating.rating] || '❓' }}
                        {{ rating.rating }}
                      </div>
                    </div>
                  </li>
                </ul>
              </section>

              <p v-else class="text-gray-500">
                Non hai ancora fatto nessuna valutazione.
              </p>



            </div>
          </div>
        </main>
        <footer class="py-16 text-center text-sm text-black dark:text-white/70">Wonderful app by MN
        </footer>
      </div>
    </div>
  </div>


</template>