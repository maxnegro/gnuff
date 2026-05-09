<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Password dimenticata" />
        <div class="mb-4 text-sm text-center" :style="{ color: 'var(--app-text-soft)' }">
            Password dimenticata? Inserisci la tua email e riceverai un link per reimpostarla.
        </div>
        <div v-if="status" class="mb-4 rounded-2xl px-4 py-3 text-center text-sm font-medium text-primary-900 dark:text-primary-100" :style="{ background: 'color-mix(in srgb, var(--app-bg-accent) 100%, transparent)' }">
            {{ status }}
        </div>
        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel for="email" value="Email" />
                <TextInput id="email" type="email" class="app-input mt-2" v-model="form.email" required autofocus autocomplete="username" />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>
            <PrimaryButton class="w-full app-button-primary" :class="{ 'opacity-60': form.processing }" :disabled="form.processing">
                Invia link di reset
            </PrimaryButton>
        </form>
    </GuestLayout>
</template>
