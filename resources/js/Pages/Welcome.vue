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
            <section class="grid w-full gap-10 lg:grid-cols-[1.15fr_0.85fr] lg:items-center">
                <div class="space-y-6">
                    <div class="inline-flex items-center gap-3 rounded-full px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-primary-700 dark:text-primary-200" :style="{ background: 'color-mix(in srgb, var(--app-surface) 82%, transparent)', border: '1px solid var(--app-border)' }">
                        <span class="h-2.5 w-2.5 rounded-full bg-primary-500"></span>
                        Scanner + liste condivise + rating
                    </div>
                    <div class="space-y-4">
                        <h1 class="app-page-title max-w-2xl text-center lg:text-left">Benvenuto su Gnuff</h1>
                        <p class="app-page-subtitle mx-auto max-w-xl text-center lg:mx-0 lg:text-left">Gestisci, valuta e condividi le tue liste di prodotti con un'interfaccia più leggibile, armonizzata e coerente con il tema di sistema.</p>
                    </div>
                    <div class="grid gap-3 text-sm sm:grid-cols-3">
                        <div class="app-surface-soft rounded-3xl p-4">
                            <p class="font-semibold">Scansione rapida</p>
                            <p class="mt-1" :style="{ color: 'var(--app-text-soft)' }">Apri la fotocamera e collega il barcode alla lista attiva.</p>
                        </div>
                        <div class="app-surface-soft rounded-3xl p-4">
                            <p class="font-semibold">Valutazione immediata</p>
                            <p class="mt-1" :style="{ color: 'var(--app-text-soft)' }">Rivedi nome, immagine e rating nello stesso flusso.</p>
                        </div>
                        <div class="app-surface-soft rounded-3xl p-4">
                            <p class="font-semibold">Collaborazione</p>
                            <p class="mt-1" :style="{ color: 'var(--app-text-soft)' }">Condividi liste e mantieni allineate le preferenze di gruppo.</p>
                        </div>
                    </div>
                </div>

                <div class="app-panel mx-auto w-full max-w-md p-8 text-center lg:mx-0">
                    <div class="flex flex-col items-center">
                        <div class="app-logo-badge mb-6">
                            <img src="/img/icon-192.png" alt="Logo" class="h-28 w-28 rounded-[28px] object-cover" />
                        </div>
                    </div>
                    <h2 class="text-2xl font-semibold tracking-tight">Un unico punto di accesso</h2>
                    <p class="mt-3 text-sm leading-6" :style="{ color: 'var(--app-text-soft)' }">Entra nel flusso di scansione, gestione liste e valutazioni con una UI più stabile su desktop e mobile.</p>
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
