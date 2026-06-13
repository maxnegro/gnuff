<template>
  <div
    class="flex items-start gap-4 rounded-3xl p-4"
    :style="{ background: 'color-mix(in srgb, var(--app-bg-muted) 100%, transparent)' }"
  >
    <div class="flex flex-shrink-0 flex-col items-center">
      <img
        :src="imageUrl || placeholder"
        alt="Immagine prodotto"
        class="h-20 w-20 cursor-pointer rounded-2xl object-cover border-2 border-transparent hover:border-indigo-400"
        @click="$emit('change-image')"
      />
      <button
        type="button"
        @click="$emit('change-image')"
        class="app-button-secondary mt-2 px-3 py-1.5 text-xs"
      >
        Cambia
      </button>
    </div>
    <div class="flex flex-col gap-1 overflow-hidden">
      <div class="text-base font-bold leading-snug">{{ name || 'Nome non disponibile' }}</div>
      <div class="text-xs font-medium" :style="{ color: 'var(--app-text-soft)' }">
        EAN: {{ barcode }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  name: string
  barcode: string
  imageUrl?: string | null
  placeholder?: string
}>()

defineEmits<{
  (e: 'change-image'): void
}>()
</script>
