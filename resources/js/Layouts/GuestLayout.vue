<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="app-shell flex min-h-screen flex-col items-center justify-center px-4 py-8 sm:px-6">
        <div class="flex w-full max-w-md flex-col items-center gap-8">
            <Link href="/">
                <div class="app-logo-badge">
                    <img src="/img/icon-192.png" alt="Gnuff" title="Gnuff" class="h-24 w-24 rounded-2xl object-cover" />
                </div>
            </Link>
            <div class="app-panel w-full p-6 sm:p-8">
                <slot />
            </div>
        </div>
    </div>
</template>
