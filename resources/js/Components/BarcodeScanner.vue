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
  import { ref } from 'vue';
  import { StreamBarcodeReader } from '@teckel/vue-barcode-reader';
  
  const onLoaded = () => {
    console.log('Scanner pronto');
  };
  
  const onDecode = (result) => {
    console.log('Codice scansionato:', result);
    // Qui puoi emettere un evento al parent con il codice letto
  };
  </script>
  
  <style scoped>
  .scanner-container {
    width: 100%;
    max-width: 400px;
    margin: auto;
  }
  </style>
  