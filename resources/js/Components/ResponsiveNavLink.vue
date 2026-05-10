<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
    ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-indigo-400 text-start text-base font-semibold text-indigo-800 bg-indigo-50 focus:outline-none focus:text-indigo-900 focus:bg-indigo-100 focus:border-indigo-700 transition duration-150 ease-in-out dark:border-emerald-400 dark:text-emerald-100 dark:bg-emerald-500/15 dark:focus:text-white dark:focus:bg-emerald-500/25 dark:focus:border-emerald-300'
    : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:text-gray-900 focus:bg-gray-50 focus:border-gray-300 transition duration-150 ease-in-out dark:text-slate-200 dark:hover:text-white dark:hover:bg-slate-800/80 dark:hover:border-slate-500 dark:focus:text-white dark:focus:bg-slate-800/80 dark:focus:border-slate-500',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
