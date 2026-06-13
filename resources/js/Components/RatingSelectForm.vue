<template>
  <form @submit.prevent="$emit('submit')" class="flex flex-col gap-3">
    <input
      :value="name"
      @input="$emit('update:name', ($event.target as HTMLInputElement).value)"
      type="text"
      placeholder="Nome prodotto"
      class="app-input"
      required
    />
    <select
      :value="rating"
      @change="$emit('update:rating', ($event.target as HTMLSelectElement).value)"
      class="app-select"
      required
    >
      <option value="" disabled>Valutazione</option>
      <option v-for="opt in ratingOptions" :key="opt.value" :value="opt.value">
        {{ opt.label }}
      </option>
    </select>
    <button
      type="submit"
      :disabled="loading"
      class="app-button-primary mt-2 w-full disabled:cursor-not-allowed disabled:opacity-60"
    >
      Salva valutazione
    </button>
    <button
      v-if="ratingId"
      type="button"
      @click="$emit('delete')"
      :disabled="loading"
      data-test="remove-rating"
      class="app-button-secondary w-full disabled:cursor-not-allowed disabled:opacity-60"
    >
      Rimuovi valutazione
    </button>
    <p v-if="error" class="text-red-500 dark:text-red-400">{{ error }}</p>
  </form>
</template>

<script setup lang="ts">
export interface RatingOption {
  value: string
  label: string
}

defineProps<{
  name: string
  rating: string
  ratingId?: number | null
  loading: boolean
  error?: string | null
  ratingOptions: RatingOption[]
}>()

defineEmits<{
  (e: 'update:name', value: string): void
  (e: 'update:rating', value: string): void
  (e: 'submit'): void
  (e: 'delete'): void
}>()
</script>
