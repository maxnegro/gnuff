<script setup>
import { ref, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const page = usePage();
const allLists = computed(() => {
    // Unifica owned e shared come in ProductList/Index.vue
    const owned = page.props.owned || [];
    const shared = page.props.shared || [];
    const all = [...owned, ...shared];
    const seen = new Set();
    return all.filter(l => {
        if (seen.has(l.id)) return false;
        seen.add(l.id);
        return true;
    });
});
const activeList = computed(() => page.props.active_list || null);
const activeListId = ref(activeList.value ? activeList.value.id : null);

// Sincronizza activeListId con la lista attiva server-side
import { watch } from 'vue';
watch(
    () => page.props.active_list,
    (newActive) => {
        activeListId.value = newActive ? newActive.id : null;
    }
);

// Aggiorna la lista attiva lato server e aggiorna la pagina
async function changeActiveList(listId) {
    router.post(`/lists/${listId}/active`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Inertia aggiornerà le props, quindi aggiorniamo anche il v-model
            activeListId.value = listId;
        }
    });
}
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
</script>

<template>
    <div>
        <nav class="border-b border-gray-100 bg-white">
            <!-- Primary Navigation Menu -->
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="flex shrink-0 items-center">
                            <Link :href="route('dashboard')">
                                <ApplicationLogo class="block h-9 w-auto fill-current text-gray-800" />
                            </Link>
                        </div>

                        <!-- Navigation Links -->
                        <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                            <NavLink :href="route('dashboard')" :active="route().current('dashboard')" @click="showingNavigationDropdown = false">
                                Dashboard
                            </NavLink>
                            <NavLink :href="route('scanner')" :active="route().current('scanner')" @click="showingNavigationDropdown = false">
                                Scanner
                            </NavLink>
                            <NavLink :href="route('product.list')" :active="route().current('product.list')" @click="showingNavigationDropdown = false">
                                Prodotti
                            </NavLink>

                            <NavLink :href="route('lists.index')" :active="route().current('lists.index')" @click="showingNavigationDropdown = false">
                                Liste
                            </NavLink>

                        </div>
                    </div>

                    <div class="hidden sm:ms-6 sm:flex sm:items-center">
                        <!-- Settings Dropdown -->
                        <div class="relative ms-3">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <span class="inline-flex rounded-md">
                                        <button type="button"
                                            class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none">
                                            {{ $page.props.auth.user.name }}

                                            <svg class="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </span>
                                </template>

                                <template #content>
                                    <DropdownLink :href="route('profile.edit')">
                                        Profile
                                    </DropdownLink>
                                    <DropdownLink :href="route('logout')" method="post" as="button">
                                        Log Out
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                    </div>

                    <!-- Hamburger -->
                    <div class="-me-2 flex items-center sm:hidden">
                        <button @click="
                            showingNavigationDropdown =
                            !showingNavigationDropdown
                            "
                            class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none">
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path :class="{
                                    hidden: showingNavigationDropdown,
                                    'inline-flex':
                                        !showingNavigationDropdown,
                                }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16" />
                                <path :class="{
                                    hidden: !showingNavigationDropdown,
                                    'inline-flex':
                                        showingNavigationDropdown,
                                }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Responsive Navigation Menu -->
            <div :class="{
                block: showingNavigationDropdown,
                hidden: !showingNavigationDropdown,
            }" class="sm:hidden">
                <div class="space-y-1 pb-3 pt-2">
                    <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')" @click="showingNavigationDropdown = false">
                        Dashboard
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('scanner')" :active="route().current('scanner')" @click="showingNavigationDropdown = false">
                        Scanner
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('product.list')" :active="route().current('product.list')" @click="showingNavigationDropdown = false">
                        Prodotti
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('lists.index')" :active="route().current('lists.index')" @click="showingNavigationDropdown = false">
                        Liste
                    </ResponsiveNavLink>
                </div>

                <!-- Responsive Settings Options -->
                <div class="border-t border-gray-200 pb-1 pt-4">
                    <div class="px-4">
                        <div class="text-base font-medium text-gray-800">
                            {{ $page.props.auth.user.name }}
                        </div>
                        <div class="text-sm font-medium text-gray-500">
                            {{ $page.props.auth.user.email }}
                        </div>
                    </div>

                    <div class="mt-3 space-y-1">
                        <ResponsiveNavLink :href="route('profile.edit')">
                            Profile
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('logout')" method="post" as="button">
                            Log Out
                        </ResponsiveNavLink>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Barra selezione lista attiva -->
        <div class="w-full flex justify-center bg-white dark:bg-zinc-900 border-b border-gray-200">
            <div class="flex items-center gap-2 px-4 py-2">
                <span class="font-semibold">Lista attiva:</span>
                <select v-model="activeListId" @change="e => changeActiveList(e.target.value)"
                    class="rounded border px-2 py-1 bg-gray-50 dark:bg-zinc-800 text-black dark:text-white min-w-[10rem] pr-8">
                    <option v-for="list in allLists" :key="list.id" :value="list.id">
                        {{ list.name }}
                    </option>
                </select>
            </div>
        </div>


        <div class="min-h-screen bg-gray-100">
            <!-- Page Heading -->
            <header class="bg-white shadow" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
