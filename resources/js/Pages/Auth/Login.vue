<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />
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
                <InputLabel for="password" value="Password" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-black dark:text-white"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>
            <div class="flex items-center">
                <Checkbox name="remember" v-model:checked="form.remember" />
                <span class="ms-2 text-sm text-gray-600 dark:text-gray-300">Ricordami</span>
            </div>
            <div class="flex flex-col gap-2 mt-2">
                <PrimaryButton
                    class="w-full py-3 px-4 rounded-lg bg-green-500 text-white font-semibold text-lg shadow hover:bg-green-600 transition"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    <span class="w-full block text-center">Accedi</span>
                </PrimaryButton>
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm text-green-600 underline hover:text-green-800 text-center mt-2"
                >
                    Password dimenticata?
                </Link>
            </div>
        </form>
        <div class="mt-6 text-center">
            <span class="text-sm text-gray-600 dark:text-gray-300">Non hai un account?</span>
            <Link :href="route('register')" class="ml-2 text-green-600 underline hover:text-green-800">Registrati</Link>
        </div>
    </GuestLayout>
</template>
