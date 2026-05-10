<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppShell from '../Layouts/AppShell.vue';

const props = defineProps({
    projects: {
        type: Array,
        default: () => [],
    },
});

const runningCount = computed(() => props.projects.filter((project) => project.source_update?.status === 'running').length);
const pendingCount = computed(() => props.projects.filter((project) => project.source_update?.status === 'pending').length);
const failedCount = computed(() => props.projects.filter((project) => project.source_update?.status === 'fail').length);

const cards = computed(() => [
    {
        title: 'Projects',
        value: String(props.projects.length),
        detail: 'Configured for project-by-project migration',
    },
    {
        title: 'Source Updates',
        value: runningCount.value > 0 ? `${runningCount.value} running` : pendingCount.value > 0 ? `${pendingCount.value} pending` : 'Idle',
        detail: 'Git cache refresh state across projects',
    },
    {
        title: 'Failures',
        value: String(failedCount.value),
        detail: 'Latest source-update failures needing attention',
    },
]);

const recentProjects = computed(() => props.projects.slice(0, 5));

const statusClass = (status) => {
    if (status === 'running') return 'border-blue-200 bg-blue-50 text-blue-700';
    if (status === 'pending') return 'border-amber-200 bg-amber-50 text-amber-700';
    if (status === 'success') return 'border-emerald-200 bg-emerald-50 text-emerald-700';
    if (status === 'fail') return 'border-red-200 bg-red-50 text-red-700';
    return 'border-zinc-200 bg-zinc-50 text-zinc-600';
};
</script>

<template>
    <AppShell>
        <div class="grid gap-4 md:grid-cols-3">
            <article
                v-for="card in cards"
                :key="card.title"
                class="rounded border border-zinc-200 bg-white p-5"
            >
                <div class="text-sm font-medium text-zinc-500">{{ card.title }}</div>
                <div class="mt-2 text-2xl font-semibold">{{ card.value }}</div>
                <p class="mt-3 text-sm leading-6 text-zinc-600">{{ card.detail }}</p>
            </article>
        </div>

        <div class="mt-8 rounded border border-zinc-200 bg-white">
            <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-5 py-4">
                <div>
                    <h2 class="text-base font-semibold">Project Activity</h2>
                    <p class="mt-1 text-sm text-zinc-500">Current source-update state for configured projects.</p>
                </div>
                <Link
                    href="/services/source-updates"
                    class="rounded bg-zinc-950 px-3 py-2 text-sm font-medium text-white hover:bg-zinc-800"
                >
                    Open Source Updates
                </Link>
            </div>

            <div v-if="recentProjects.length === 0" class="m-5 rounded border border-dashed border-zinc-300 p-6 text-sm text-zinc-500">
                No projects are configured yet.
            </div>

            <div v-else class="divide-y divide-zinc-200">
                <div
                    v-for="project in recentProjects"
                    :key="project.id"
                    class="flex items-center justify-between gap-4 px-5 py-4"
                >
                    <div>
                        <div class="font-medium">{{ project.name }}</div>
                        <div class="mt-1 text-xs text-zinc-500">{{ project.slug }}</div>
                    </div>
                    <span
                        class="inline-flex rounded border px-2 py-1 text-xs font-medium"
                        :class="statusClass(project.source_update?.status)"
                    >
                        {{ project.source_update?.status || 'not requested' }}
                    </span>
                </div>
            </div>
        </div>
    </AppShell>
</template>
