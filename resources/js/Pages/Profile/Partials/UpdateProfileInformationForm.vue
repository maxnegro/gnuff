<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-semibold" :style="{ color: 'var(--app-text)' }">
                Informazioni Profilo
            </h2>
            <p class="mt-2 text-sm" :style="{ color: 'var(--app-text-soft)' }">
                Aggiorna le informazioni del profilo e l'indirizzo email del tuo account.
            </p>
        </header>

        <form @submit.prevent="form.patch(route('profile.update'))" class="mt-6 space-y-5">
            <div>
                <InputLabel for="name" value="Nome" />
                <TextInput id="name" type="text" class="app-input mt-2" v-model="form.name" required autofocus autocomplete="name" />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Email" />
                <TextInput id="email" type="email" class="app-input mt-2" v-model="form.email" required autocomplete="username" />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="text-sm" :style="{ color: 'var(--app-text-soft)' }">
                    Il tuo indirizzo email non è verificato.
                    <Link :href="route('verification.send')" method="post" as="button" class="font-semibold text-primary-600 dark:text-primary-300 hover:underline">
                        Clicca qui per reinviare l'email di verifica.
                    </Link>
                </p>
                <div v-show="status === 'verification-link-sent'" class="mt-2 rounded-2xl px-4 py-3 text-sm font-medium text-primary-900 dark:text-primary-100" :style="{ background: 'color-mix(in srgb, var(--app-bg-accent) 100%, transparent)' }">
                    Un nuovo link di verifica è stato inviato al tuo indirizzo email.
                </div>
            </div>

            <div class="flex items-center gap-4 pt-2">
                <PrimaryButton class="app-button-primary" :disabled="form.processing">Salva</PrimaryButton>
                <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0" leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                    <p v-if="form.recentlySuccessful" class="text-sm" :style="{ color: 'var(--app-text-soft)' }">Salvato.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
