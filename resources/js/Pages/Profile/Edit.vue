<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Profilo" />

    <AuthenticatedLayout>


        <div class="py-8 sm:py-12">
            <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                <div class="app-panel p-6 sm:p-8">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-2xl"
                    />
                </div>

                <div class="app-panel p-6 sm:p-8">
                    <UpdatePasswordForm class="max-w-2xl" />
                </div>

                <div class="app-panel p-6 sm:p-8">
                    <DeleteUserForm class="max-w-2xl" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
