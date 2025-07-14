<script setup>
import { useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    product: Object,
    layout: AuthenticatedLayout,
});

const form = useForm({
    name: props.product.name,
    image: props.product.image_url ?? '',
});

function submit() {
    form.put(route('product.update', props.product.id));
}

function destroy() {
    if (confirm('Sei sicuro di voler eliminare questo prodotto?')) {
        router.delete(route('product.destroy', props.product.id));
    }
}

function goBack() {
  window.history.back();
  // oppure: router.visit(route('dashboard'));
}

</script>

<template>
    <div class="max-w-md mx-auto p-6">
        <h1 class="text-2xl font-bold mb-6">Modifica prodotto</h1>

        <img :src="props.product.image_url || placeholder" alt="Immagine prodotto" class="min-w-16 min-h-16 w-16 h-16 object-cover rounded mr-4" />


        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Nome</label>
                <input v-model="form.name" type="text" class="w-full border rounded px-3 py-2" />
                <label class="block text-sm font-medium text-gray-700">URL Immagine</label>
                <input v-model="form.image_url" type="text" class="w-full border rounded px-3 py-2" />
                <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">
                    {{ form.errors.name }}
                </div>
            </div>
            <div class="flex justify-between mt-6">
                <!-- Pulsante Salva -->
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Salva
                </button>
                <!-- Pulsante elimina -->
                <button type="button" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700"
                    @click="router.delete(route('product.destroy', props.product.id))">
                    Elimina
                </button>
                <!-- Pulsante annulla -->
                <button type="button" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600"
                    @click="goBack">
                    Annulla
                </button>

            </div>
        </form>
    </div>
</template>
