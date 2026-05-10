<template>
    <button
        class="inline-flex items-center justify-center rounded-2xl border border-transparent px-4 py-2.5 text-sm font-semibold transition duration-150 ease-in-out hover:translate-y-[-1px] focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50"
        :class="'bg-primary-600 text-white hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-800 focus:ring-offset-white dark:bg-slate-800 dark:text-slate-100 dark:ring-offset-slate-900 dark:hover:bg-slate-700 dark:focus:bg-slate-700 dark:active:bg-slate-600 dark:border dark:border-slate-700'"
    >
        <slot />
    </button> </template>