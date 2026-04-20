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
        <Head title="Forgot Password" />
        <div class="mb-4 text-sm text-gray-600 dark:text-gray-300 text-center">
            Password dimenticata? Inserisci la tua email e riceverai un link per reimpostarla.
        </div>
        <div v-if="status" class="mb-4 text-sm font-medium text-green-600 text-center">
            {{ status }}
        </div>
        <form @submit.prevent="submit" class="flex flex-col gap-4">
            <div>
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-black dark:text-white"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>
            <div>
                <PrimaryButton
                    class="w-full py-3 px-4 rounded-lg bg-green-500 text-white font-semibold text-lg shadow hover:bg-green-600 transition"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    <span class="w-full block text-center">Invia link di reset</span>
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
