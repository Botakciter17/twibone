<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
const page = usePage();
</script>

<template>
    <div>
        <div class="min-h-screen bg-slate-50 text-slate-900">
            <nav class="border-b border-slate-200 bg-white sticky top-0 z-30 shadow-xs">
                <!-- Primary Navigation Menu -->
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between items-center">
                        <div class="flex items-center">
                            <!-- Logo -->
                            <div class="flex shrink-0 items-center">
                                <Link :href="route('admin.campaigns.index')" class="flex items-center gap-2.5 font-bold text-slate-900 group">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-900 text-white font-black text-base shadow-xs group-hover:bg-slate-800 transition">
                                        T
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold tracking-tight leading-none text-slate-900">Twibbon Self-Host</span>
                                        <span class="text-[11px] font-medium text-slate-500 leading-none mt-1">Admin Panel</span>
                                    </div>
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div class="hidden space-x-6 sm:-my-px sm:ms-10 sm:flex h-16">
                                <NavLink
                                    :href="route('admin.campaigns.index')"
                                    :active="route().current('admin.campaigns.index')"
                                >
                                    Daftar Twibbon
                                </NavLink>
                                <NavLink
                                    :href="route('admin.campaigns.create')"
                                    :active="route().current('admin.campaigns.create')"
                                >
                                    + Buat Twibbon
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:ms-6 sm:flex sm:items-center gap-3">
                            <!-- User Dropdown -->
                            <div class="relative ms-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-lg">
                                            <button
                                                type="button"
                                                class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50 hover:text-slate-900 focus:outline-none"
                                            >
                                                <div class="h-6 w-6 rounded-full bg-slate-200 text-slate-700 font-bold flex items-center justify-center text-[11px]">
                                                    {{ $page.props.auth.user.name?.charAt(0) || 'A' }}
                                                </div>
                                                <span>{{ $page.props.auth.user.name }}</span>
                                                <svg
                                                    class="h-3.5 w-3.5 text-slate-400"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink :href="route('profile.edit')">
                                            Profil Admin
                                        </DropdownLink>
                                        <DropdownLink
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                        >
                                            Keluar (Logout)
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="inline-flex items-center justify-center rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none min-h-[44px] min-w-[44px]"
                            >
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex': !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex': showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div
                    :class="{
                        block: showingNavigationDropdown,
                        hidden: !showingNavigationDropdown,
                    }"
                    class="sm:hidden border-b border-slate-200 bg-white"
                >
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink
                            :href="route('admin.campaigns.index')"
                            :active="route().current('admin.campaigns.index')"
                        >
                            Daftar Twibbon
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('admin.campaigns.create')"
                            :active="route().current('admin.campaigns.create')"
                        >
                            + Buat Twibbon
                        </ResponsiveNavLink>
                    </div>

                    <div class="border-t border-slate-200 pb-2 pt-3">
                        <div class="px-4">
                            <div class="text-sm font-semibold text-slate-800">
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="text-xs text-slate-500">
                                {{ $page.props.auth.user.email }}
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">
                                Profil Admin
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                            >
                                Keluar (Logout)
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Flash Message Banner -->
            <div v-if="page.props.flash?.success" class="mx-auto max-w-7xl px-4 pt-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between rounded-xl bg-emerald-50 p-4 text-emerald-900 border border-emerald-200 shadow-xs">
                    <div class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-xs sm:text-sm font-medium">{{ page.props.flash.success }}</span>
                    </div>
                </div>
            </div>
            <div v-if="page.props.flash?.error" class="mx-auto max-w-7xl px-4 pt-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between rounded-xl bg-red-50 p-4 text-red-900 border border-red-200 shadow-xs">
                    <div class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-red-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-xs sm:text-sm font-medium">{{ page.props.flash.error }}</span>
                    </div>
                </div>
            </div>

            <!-- Page Heading -->
            <header class="bg-white border-b border-slate-200" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
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
