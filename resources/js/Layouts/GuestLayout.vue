<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="app-shell flex min-h-screen flex-col items-center justify-center px-4 py-8 sm:px-6">
        <div class="flex w-full max-w-5xl flex-col items-center gap-8 lg:flex-row lg:items-stretch lg:justify-center">
            <div class="max-w-md space-y-4 text-center lg:flex lg:flex-1 lg:flex-col lg:justify-center lg:text-left">
                <p class="text-sm font-semibold uppercase tracking-[0.28em] text-primary-600 dark:text-primary-300">Gnuff</p>
                <h1 class="app-page-title">Liste, scanner e valutazioni in un flusso unico.</h1>
                <p class="app-page-subtitle">Accesso rapido, contrasto coerente e superfici più leggibili sia in light che in dark mode di sistema.</p>
            </div>
            <Link href="/">
                <div class="app-logo-badge">
                    <img src="/img/icon-192.png" alt="Logo" class="h-24 w-24 rounded-2xl object-cover" />
                </div>
            </Link>
            <div class="app-panel w-full max-w-md p-6 sm:p-8">
                <slot />
            </div>
        </div>
    </div>
</template>
