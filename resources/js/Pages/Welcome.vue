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
    <div class="app-shell flex min-h-screen flex-col justify-between">
        <main class="app-frame flex flex-1 items-center py-10 sm:py-16">
            <section class="mx-auto flex w-full max-w-md flex-col items-center gap-6">
                <h1 class="app-page-title text-center">Benvenuto su Gnuff</h1>

                <div class="app-panel w-full p-8 text-center">
                    <div class="flex flex-col items-center">
                        <div class="app-logo-badge mb-6">
                            <img src="/img/icon-192.png" alt="Logo" class="h-28 w-28 rounded-[28px] object-cover" />
                        </div>
                    </div>
                    <p class="text-base font-medium" :style="{ color: 'var(--app-text-soft)' }">Valuta le tue preferenze in fatto di cibarie.</p>
                    <div class="mt-8 flex flex-col gap-3">
                        <Link v-if="canLogin && !$page.props.auth.user" :href="route('login')"
                            class="app-button-primary w-full text-center">
                            Accedi</Link>
                        <Link v-if="canRegister && !$page.props.auth.user" :href="route('register')"
                            class="app-button-secondary w-full text-center">
                            Registrati</Link>
                        <Link v-if="$page.props.auth.user" :href="route('dashboard')"
                            class="app-button-primary w-full text-center">
                            Vai alla Dashboard</Link>
                    </div>
                </div>
            </section>
        </main>
        <footer class="app-frame py-6 text-center text-xs" :style="{ color: 'var(--app-text-soft)' }">
            Wonderful app by MN
        </footer>
    </div>
</template>
