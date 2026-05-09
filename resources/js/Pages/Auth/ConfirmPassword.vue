<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Conferma Password" />
        <div class="mb-4 text-sm text-center" :style="{ color: 'var(--app-text-soft)' }">
            Questa è un'area protetta dell'applicazione. Conferma la tua password prima di continuare.
        </div>
        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel for="password" value="Password" />
                <TextInput id="password" type="password" class="app-input mt-2" v-model="form.password" required autocomplete="current-password" autofocus />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>
            <PrimaryButton class="w-full app-button-primary" :class="{ 'opacity-60': form.processing }" :disabled="form.processing">
                Conferma
            </PrimaryButton>
        </form>
    </GuestLayout>
</template>
