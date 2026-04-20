<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />
        <form @submit.prevent="submit" class="flex flex-col gap-4">
            <div>
                <InputLabel for="name" value="Nome" />
                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-black dark:text-white"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>
            <div>
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-black dark:text-white"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>
            <div>
                <InputLabel for="password" value="Password" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-black dark:text-white"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>
            <div>
                <InputLabel for="password_confirmation" value="Conferma Password" />
                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-black dark:text-white"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>
            <div class="flex flex-col gap-2 mt-2">
                <PrimaryButton
                    class="w-full py-3 px-4 rounded-lg bg-green-500 text-white font-semibold text-lg shadow hover:bg-green-600 transition"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    <span class="w-full block text-center">Registrati</span>
                </PrimaryButton>
                <div class="text-center mt-2">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Hai già un account?</span>
                    <Link :href="route('login')" class="ml-2 text-green-600 underline hover:text-green-800">Accedi</Link>
                </div>
            </div>
        </form>
    </GuestLayout>
</template>
