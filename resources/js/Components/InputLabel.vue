<script setup>
defineProps({
    value: {
        type: String,
    },
});
</script>

<template>
    <label class="block text-sm font-medium" :style="{ color: 'var(--app-text-soft)' }">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
    </label>
</template>
