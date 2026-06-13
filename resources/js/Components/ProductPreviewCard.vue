<template>
  <div
    class="flex items-start gap-4 rounded-3xl p-4"
    :style="{ background: 'color-mix(in srgb, var(--app-bg-muted) 100%, transparent)' }"
  >
    <div class="flex flex-shrink-0 flex-col items-center">
      <img
        :src="imageUrl || placeholder"
        :alt="name ? `Immagine di ${name}` : 'Placeholder prodotto'"
        loading="lazy"
        @error="handleImageError"
        class="h-20 w-20 cursor-pointer rounded-2xl object-cover border-2 border-transparent hover:border-indigo-400 focus:border-indigo-400 focus:outline-none"
        @click="$emit('change-image')"
        role="button"
        tabindex="0"
        @keydown.enter="$emit('change-image')"
        @keydown.space="$emit('change-image')"
      />
      <button
        type="button"
        @click="$emit('change-image')"
        class="app-button-secondary mt-2 px-3 py-1.5 text-xs"
        aria-label="Cambia immagine prodotto"
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

const emit = defineEmits<{
  (e: 'change-image'): void
}>()

function handleImageError(e: Event) {
  const target = e.target as HTMLImageElement;
  target.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"%3E%3Crect fill="%23ddd" width="100" height="100"/%3E%3Ctext fill="%23666" font-family="sans-serif" font-size="14" x="50" y="55" text-anchor="middle"%3ENo image%3C/text%3E%3C/svg%3E';
}
</script>
