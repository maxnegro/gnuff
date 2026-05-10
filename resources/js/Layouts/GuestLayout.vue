<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="app-shell flex min-h-screen flex-col items-center justify-center px-4 py-6 sm:px-6 sm:py-8">
        <div class="flex w-full max-w-md flex-col items-center gap-6 sm:gap-8">
            <Link href="/">
                <div class="app-logo-badge">
                    <img src="/img/icon-192.png" alt="Logo" title="Gnuff" class="h-24 w-24 rounded-2xl object-cover" />
                </div>
            </Link>
            <div class="app-panel app-panel-pad-lg w-full">
                <slot />
            </div>
        </div>
    </div>
</template>
