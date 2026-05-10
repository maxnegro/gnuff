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
    ? 'inline-flex items-center px-1 pt-1 border-b-2 border-indigo-500 text-sm font-semibold leading-5 text-slate-900 focus:outline-none focus:border-indigo-700 transition duration-150 ease-in-out dark:border-emerald-400 dark:text-white dark:focus:border-emerald-300'
    : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-slate-600 hover:text-slate-900 hover:border-gray-300 focus:outline-none focus:text-slate-900 focus:border-gray-300 transition duration-150 ease-in-out dark:text-slate-300 dark:hover:text-white dark:hover:border-slate-500 dark:focus:text-white dark:focus:border-slate-500',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
