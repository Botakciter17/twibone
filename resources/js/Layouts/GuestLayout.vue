<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-slate-50 px-4 py-8 text-slate-800">
        <div class="mb-6 flex flex-col items-center">
            <Link href="/" class="flex flex-col items-center gap-2">
                <ApplicationLogo class="h-12 w-12" />
                <span class="text-xl font-bold tracking-tight text-slate-900">Twibbon Self-Host</span>
            </Link>
        </div>

        <div class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
            <slot />
        </div>
    </div>
</template>
