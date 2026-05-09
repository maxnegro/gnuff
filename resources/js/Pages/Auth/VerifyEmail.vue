<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head title="Verifica Email" />
        <div class="mb-4 text-sm text-center" :style="{ color: 'var(--app-text-soft)' }">
            Grazie per la registrazione! Per continuare, verifica il tuo indirizzo email cliccando sul link che ti abbiamo inviato. Se non hai ricevuto l'email, te ne invieremo un'altra.
        </div>
        <div v-if="verificationLinkSent" class="mb-4 rounded-2xl px-4 py-3 text-center text-sm font-medium text-primary-900 dark:text-primary-100" :style="{ background: 'color-mix(in srgb, var(--app-bg-accent) 100%, transparent)' }">
            Un nuovo link di verifica è stato inviato all'indirizzo email fornito.
        </div>
        <form @submit.prevent="submit" class="space-y-4">
            <PrimaryButton class="w-full app-button-primary" :class="{ 'opacity-60': form.processing }" :disabled="form.processing">
                Rinvia Email di Verifica
            </PrimaryButton>
            <div class="text-center">
                <Link :href="route('logout')" method="post" as="button" class="text-sm font-medium transition hover:opacity-75" :style="{ color: 'var(--app-text-soft)' }">
                    Esci
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
