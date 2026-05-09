<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Reimposta Password" />
        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel for="email" value="Email" />
                <TextInput id="email" type="email" class="app-input mt-2" v-model="form.email" required autofocus autocomplete="username" />
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
            <PrimaryButton class="w-full app-button-primary" :class="{ 'opacity-60': form.processing }" :disabled="form.processing">
                Reimposta Password
            </PrimaryButton>
        </form>
    </GuestLayout>
</template>
