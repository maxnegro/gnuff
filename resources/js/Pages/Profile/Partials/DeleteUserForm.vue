<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-semibold" :style="{ color: 'var(--app-text)' }">
                Elimina Account
            </h2>
            <p class="mt-2 text-sm" :style="{ color: 'var(--app-text-soft)' }">
                Una volta eliminato il tuo account, tutte le risorse e i dati verranno eliminati definitivamente. Prima di eliminare il tuo account, scarica tutti i dati che desideri conservare.
            </p>
        </header>

        <DangerButton @click="confirmUserDeletion">Elimina Account</DangerButton>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-semibold" :style="{ color: 'var(--app-text)' }">
                    Sei sicuro di voler eliminare il tuo account?
                </h2>
                <p class="mt-2 text-sm" :style="{ color: 'var(--app-text-soft)' }">
                    Una volta eliminato il tuo account, tutte le risorse e i dati verranno eliminati definitivamente. Inserisci la tua password per confermare.
                </p>
                <div class="mt-6">
                    <InputLabel for="password" value="Password" class="sr-only" />
                    <TextInput id="password" ref="passwordInput" v-model="form.password" type="password" class="app-input w-full" placeholder="Password" @keyup.enter="deleteUser" />
                    <InputError :message="form.errors.password" class="mt-2" />
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeModal">Annulla</SecondaryButton>
                    <DangerButton :class="{ 'opacity-60': form.processing }" :disabled="form.processing" @click="deleteUser">
                        Elimina Account
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>
