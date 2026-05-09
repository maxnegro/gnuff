<template>
    <div class="scanner-container">
      <StreamBarcodeReader
        @decode="onDecode"
        @loaded="onLoaded"
        torch
        no-front-cameras
      />
    </div>
  </template>
  
  <script setup>
  import { StreamBarcodeReader } from '@teckel/vue-barcode-reader';

  const emit = defineEmits(['scanned']);
  
  const onLoaded = () => {
    console.log('Scanner pronto');
  };
  
  const onDecode = (result) => {
    console.log('Codice scansionato:', result);
    emit('scanned', result);
  };
  </script>
  
  <style scoped>
  .scanner-container {
    width: 100%;
    max-width: 400px;
    margin: auto;
  }
  </style>
  