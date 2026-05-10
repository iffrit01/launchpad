<script setup>
const props = defineProps({
    projects: {
        type: Array,
        default: () => [],
    },
});

const runningCount = props.projects.filter((project) => project.source_update?.status === 'running').length;
const pendingCount = props.projects.filter((project) => project.source_update?.status === 'pending').length;

const cards = [
    {
        title: 'Projects',
        value: String(props.projects.length),
        detail: 'Configured for project-by-project migration',
    },
    {
        title: 'Source Updates',
        value: runningCount > 0 ? `${runningCount} running` : pendingCount > 0 ? `${pendingCount} pending` : 'Idle',
        detail: 'One state row per project, with rerun support',
    },
    {
        title: 'Deployments',
        value: 'Planned',
        detail: 'Backed by Deployer 8 and queued workers',
    },
];

const statusClass = (status) => {
    if (status === 'running') return 'border-blue-200 bg-blue-50 text-blue-700';
    if (status === 'pending') return 'border-amber-200 bg-amber-50 text-amber-700';
    if (status === 'success') return 'border-emerald-200 bg-emerald-50 text-emerald-700';
    if (status === 'fail') return 'border-red-200 bg-red-50 text-red-700';
    return 'border-zinc-200 bg-zinc-50 text-zinc-600';
};
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
                    Scaffold online
                </span>
            </div>
        </header>

        <section class="mx-auto max-w-7xl px-6 py-8">
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

            <div class="mt-8 rounded border border-zinc-200 bg-white p-5">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-base font-semibold">Source update states</h2>
                    <a href="/source-updates" class="text-sm font-medium text-zinc-700 hover:text-zinc-950">
                        JSON
                    </a>
                </div>

                <div v-if="projects.length === 0" class="mt-4 rounded border border-dashed border-zinc-300 p-6 text-sm text-zinc-500">
                    No projects are configured yet.
                </div>

                <div v-else class="mt-4 overflow-hidden rounded border border-zinc-200">
                    <table class="w-full border-collapse text-left text-sm">
                        <thead class="bg-zinc-50 text-xs uppercase text-zinc-500">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Project</th>
                                <th class="px-4 py-3 font-semibold">Status</th>
                                <th class="px-4 py-3 font-semibold">Attempts</th>
                                <th class="px-4 py-3 font-semibold">Requested</th>
                                <th class="px-4 py-3 font-semibold">Finished</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200">
                            <tr v-for="project in projects" :key="project.id">
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ project.name }}</div>
                                    <div class="text-xs text-zinc-500">{{ project.slug }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex rounded border px-2 py-1 text-xs font-medium"
                                        :class="statusClass(project.source_update?.status)"
                                    >
                                        {{ project.source_update?.status || 'not requested' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-zinc-600">{{ project.source_update?.attempts || 0 }}</td>
                                <td class="px-4 py-3 text-zinc-600">{{ project.source_update?.requested_at || '-' }}</td>
                                <td class="px-4 py-3 text-zinc-600">{{ project.source_update?.finished_at || '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
</template>
