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
    <div class="app-shell">
        <nav class="app-nav sticky top-0 z-40">
            <!-- Primary Navigation Menu -->
            <div class="app-frame">
                <div class="flex h-16 justify-between">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="flex shrink-0 items-center">
                            <Link :href="route('dashboard')">
                                <div class="app-logo-badge">
                                    <ApplicationLogo class="block h-9 w-auto fill-current text-primary-700 dark:text-primary-300" />
                                </div>
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
                                            class="app-button-secondary rounded-full px-3 py-2 text-sm font-medium leading-4">
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
                            class="inline-flex items-center justify-center rounded-xl p-2 text-secondary-500 transition duration-150 ease-in-out hover:bg-secondary-100/80 hover:text-secondary-700 focus:outline-none dark:hover:bg-secondary-800/70 dark:hover:text-secondary-200">
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
            }" class="app-surface-soft mx-4 mb-4 rounded-3xl sm:hidden">
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
                <div class="border-t pb-1 pt-4" :style="{ borderColor: 'var(--app-border)' }">
                    <div class="px-4">
                        <div class="text-base font-medium" :style="{ color: 'var(--app-text)' }">
                            {{ $page.props.auth.user.name }}
                        </div>
                        <div class="text-sm font-medium" :style="{ color: 'var(--app-text-soft)' }">
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
        <div class="app-frame">
            <div class="app-surface-soft rounded-3xl px-6 py-4 sm:px-8 mt-2">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-semibold" :style="{ color: 'var(--app-text)' }">Lista attiva:</span>
                        <select v-model="activeListId" @change="e => changeActiveList(e.target.value)"
                            class="app-select min-w-[11rem] pr-8 text-sm">
                            <option v-for="list in allLists" :key="list.id" :value="list.id">
                                {{ list.name }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>
        </div>


        <div class="app-shell pb-10">
            <!-- Page Heading -->
            <header class="app-frame" v-if="$slots.header">
                <div class="app-panel px-6 py-6 sm:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main class="app-frame">
                <slot />
            </main>
        </div>
    </div>
</template>
