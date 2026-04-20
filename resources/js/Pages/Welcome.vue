<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});

function handleImageError() {
    document.getElementById('screenshot-container')?.classList.add('!hidden');
    document.getElementById('docs-card')?.classList.add('!row-span-1');
    document.getElementById('docs-card-content')?.classList.add('!flex-row');
    document.getElementById('background')?.classList.add('!hidden');
}
</script>

<template>

    <Head title="Welcome" />
    <div class="min-h-screen flex flex-col justify-between bg-white dark:bg-black text-black dark:text-white">
        <main class="flex-1 flex flex-col items-center justify-center px-4 py-10">
            <img src="/img/icon-192.png" alt="Logo" class="w-28 h-28 mb-6 rounded-xl bg-white dark:bg-gray-900" />
            <h1 class="text-3xl font-bold mb-2 tracking-tight text-center">Benvenuto su Gnuff</h1>
            <p class="text-base text-gray-700 dark:text-gray-300 mb-8 text-center max-w-xs">Gestisci, valuta e condividi
                le tue liste di prodotti in modo semplice e veloce.</p>
            <div class="flex flex-col md:flex-row gap-4 w-full max-w-xs">
                <Link v-if="canLogin && !$page.props.auth.user" :href="route('login')"
                    class="w-full md:flex-1 py-3 px-4 rounded-lg bg-primary-600 text-white font-semibold text-lg shadow hover:bg-primary-700 transition text-center">
                    Accedi</Link>
                <Link v-if="canRegister && !$page.props.auth.user" :href="route('register')"
                    class="w-full md:flex-1 py-3 px-4 rounded-lg bg-primary-600 text-white font-semibold text-lg shadow hover:bg-primary-700 transition text-center">
                    Registrati</Link>
                <Link v-if="$page.props.auth.user" :href="route('dashboard')"
                    class="w-full md:flex-1 py-3 px-4 rounded-lg bg-primary-600 text-white font-semibold text-lg shadow hover:bg-primary-700 transition text-center">
                    Vai alla Dashboard</Link>
            </div>
        </main>
        <footer class="py-6 text-center text-xs text-gray-500 dark:text-gray-400">
            Wonderful app by MN
        </footer>
    </div>
</template>
