<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-semibold" :style="{ color: 'var(--app-text)' }">
                Aggiorna Password
            </h2>
            <p class="mt-2 text-sm" :style="{ color: 'var(--app-text-soft)' }">
                Assicurati che il tuo account utilizzi una password lunga e casuale per rimanere al sicuro.
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="mt-6 space-y-5">
            <div>
                <InputLabel for="current_password" value="Password Attuale" />
                <TextInput id="current_password" ref="currentPasswordInput" v-model="form.current_password" type="password" class="app-input mt-2" autocomplete="current-password" />
                <InputError :message="form.errors.current_password" class="mt-2" />
            </div>

            <div>
                <InputLabel for="password" value="Nuova Password" />
                <TextInput id="password" ref="passwordInput" v-model="form.password" type="password" class="app-input mt-2" autocomplete="new-password" />
                <InputError :message="form.errors.password" class="mt-2" />
            </div>

            <div>
                <InputLabel for="password_confirmation" value="Conferma Password" />
                <TextInput id="password_confirmation" v-model="form.password_confirmation" type="password" class="app-input mt-2" autocomplete="new-password" />
                <InputError :message="form.errors.password_confirmation" class="mt-2" />
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
