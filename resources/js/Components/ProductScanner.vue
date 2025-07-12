<template>
    <div>
      <BarcodeScanner @scanned="barcode = $event" />
  
      <div v-if="barcode">
        <p>EAN: {{ barcode }}</p>
        <button @click="fetchProduct()">Scheda prodotto</button>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref } from 'vue';
  import BarcodeScanner from './BarcodeScanner.vue';
  import RatingSelector from './RatingSelector.vue'; // già fornito prima
  
  const barcode = ref('');
  const product = ref(null);
  
  async function fetchProduct() {
    const res = await fetch(`/api/product/${barcode.value}`);
    if (res.ok) {
      product.value = await res.json();
    }
  }
  
  // Quando hai prodotto e vuoi valutare
  </script>
  