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
        <Head title="Registrati" />
        <form @submit.prevent="submit" class="space-y-5">
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
            <div>
                <InputLabel for="password" value="Password" />
                <TextInput id="password" type="password" class="app-input mt-2" v-model="form.password" required autocomplete="new-password" />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>
            <div>
                <InputLabel for="password_confirmation" value="Conferma Password" />
                <TextInput id="password_confirmation" type="password" class="app-input mt-2" v-model="form.password_confirmation" required autocomplete="new-password" />
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>
            <div class="space-y-3 pt-2">
                <PrimaryButton class="w-full app-button-primary" :class="{ 'opacity-60': form.processing }" :disabled="form.processing">
                    Registrati
                </PrimaryButton>
                <div class="text-center text-sm" :style="{ color: 'var(--app-text-soft)' }">
                    Hai già un account?
                    <Link :href="route('login')" class="font-semibold text-primary-600 dark:text-primary-300 hover:underline">Accedi</Link>
                </div>
            </div>
        </form>
    </GuestLayout>
</template>
