<script setup>
import { Link } from '@inertiajs/vue3';

const navItems = [
    { label: 'Dashboard', href: '/' },
    { label: 'Projects', href: '#', disabled: true },
    { label: 'Releases', href: '/releases' },
    { label: 'Source Updates', href: '/services/source-updates' },
    { label: 'Deployments', href: '#', disabled: true },
    { label: 'Logs', href: '#', disabled: true },
    { label: 'Settings', href: '#', disabled: true },
];

const isActive = (href) => href !== '#' && window.location.pathname === href;
</script>

<template>
    <main class="min-h-screen bg-zinc-50 text-zinc-950">
        <header class="border-b border-zinc-200 bg-white">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
                <div>
                    <h1 class="text-xl font-semibold">Launchpad</h1>
                    <p class="text-sm text-zinc-500">Deployment control for Fling projects</p>
                </div>
                <span class="rounded border border-emerald-200 bg-emerald-50 px-3 py-1 text-sm font-medium text-emerald-700">
                    Local
                </span>
            </div>
        </header>

        <div class="mx-auto flex max-w-7xl gap-6 px-6 py-8">
            <aside class="hidden w-56 shrink-0 md:block">
                <nav class="rounded border border-zinc-200 bg-white p-2">
                    <template v-for="item in navItems" :key="item.label">
                        <span
                            v-if="item.disabled"
                            class="block rounded px-3 py-2 text-sm font-medium text-zinc-400"
                        >
                            {{ item.label }}
                        </span>
                        <Link
                            v-else
                            :href="item.href"
                            class="block rounded px-3 py-2 text-sm font-medium"
                            :class="isActive(item.href) ? 'bg-zinc-950 text-white' : 'text-zinc-700 hover:bg-zinc-100 hover:text-zinc-950'"
                        >
                            {{ item.label }}
                        </Link>
                    </template>
                </nav>
            </aside>

            <section class="min-w-0 flex-1">
                <slot />
            </section>
        </div>
    </main>
</template>
