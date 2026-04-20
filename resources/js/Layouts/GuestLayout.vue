<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex flex-col justify-center items-center bg-white dark:bg-black text-black dark:text-white px-4 py-8">
        <div class="flex flex-col items-center w-full">
            <Link href="/">
                <img src="/img/icon-192.png" alt="Logo" class="w-24 h-24 mb-4 rounded-xl bg-white dark:bg-gray-900" />
            </Link>
            <div class="w-full max-w-md bg-white dark:bg-gray-900 rounded-2xl shadow-lg p-6 mt-2">
                <slot />
            </div>
        </div>
    </div>
</template>
