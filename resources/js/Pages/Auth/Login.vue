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
        <Head title="Accedi" />
        <div v-if="status" class="mb-4 rounded-2xl px-4 py-3 text-center text-sm font-medium text-primary-900 dark:text-primary-100" :style="{ background: 'color-mix(in srgb, var(--app-bg-accent) 100%, transparent)' }">
            {{ status }}
        </div>
        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    type="email"
                    class="app-input mt-2"
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
                    class="app-input mt-2"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>
            <div class="flex items-center gap-2">
                <Checkbox name="remember" v-model:checked="form.remember" />
                <span class="text-sm" :style="{ color: 'var(--app-text-soft)' }">Ricordami</span>
            </div>
            <div class="space-y-3 pt-2">
                <PrimaryButton
                    class="w-full app-button-primary"
                    :class="{ 'opacity-60': form.processing }"
                    :disabled="form.processing"
                >
                    Accedi
                </PrimaryButton>
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="block text-center text-sm font-medium transition hover:opacity-75" :style="{ color: 'var(--app-text-soft)' }"
                >
                    Password dimenticata?
                </Link>
            </div>
        </form>
        <div class="mt-8 text-center text-sm" :style="{ color: 'var(--app-text-soft)' }">
            Non hai un account?
            <Link :href="route('register')" class="font-semibold text-primary-600 dark:text-primary-300 hover:underline">Registrati</Link>
        </div>
    </GuestLayout>
</template>
