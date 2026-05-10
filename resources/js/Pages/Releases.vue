<script setup>
import { ref } from 'vue';
import AppShell from '../Layouts/AppShell.vue';

const props = defineProps({
    projects: {
        type: Array,
        default: () => [],
    },
});

const openBranches = ref({});

const toggleBranch = (project, branch) => {
    const key = `${project.slug}:${branch.ref}`;
    openBranches.value[key] = !openBranches.value[key];
};

const isOpen = (project, branch) => {
    return Boolean(openBranches.value[`${project.slug}:${branch.ref}`]);
};

const dateLabel = (value) => {
    if (!value) return '-';

    return new Intl.DateTimeFormat(undefined, {
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

const shortSha = (value) => {
    return value ? value.slice(0, 10) : '-';
};

const sameSha = (left, right) => {
    if (!left || !right) return false;

    return left.startsWith(right) || right.startsWith(left);
};

const branchLocations = (branch, environments) => {
    const commits = branch.commits || [];

    return (environments || []).filter((environment) => {
        return commits.some((commit) => sameSha(commit.sha, environment.sha));
    });
};

const commitLocations = (commit, environments) => {
    return (environments || []).filter((environment) => sameSha(commit.sha, environment.sha));
};

const locationText = (project, environment) => {
    if (environment.name === 'production') {
        return project.slug === 'qa-fling' ? 'QA server' : 'production';
    }

    if (environment.name === 'stage' || environment.name === 'staging') {
        return 'stage';
    }

    return environment.label || environment.name;
};

const locationClass = (environment) => {
    if (environment.name === 'production') {
        return 'border-amber-300 bg-amber-50 text-amber-800';
    }

    if (environment.name === 'stage' || environment.name === 'staging') {
        return 'border-emerald-300 bg-emerald-50 text-emerald-800';
    }

    return 'border-sky-300 bg-sky-50 text-sky-800';
};

const pointerTitle = (environment) => {
    if (environment.status === 'ok') {
        return `${environment.label}: ${shortSha(environment.sha)} ${environment.slot || ''}`.trim();
    }

    return environment.error || `${environment.label}: ${environment.status}`;
};
</script>

<template>
    <AppShell>
        <div class="space-y-6">
            <section
                v-for="project in props.projects"
                :key="project.id"
                class="rounded border border-zinc-200 bg-white"
            >
                <div class="border-b border-zinc-200 px-5 py-4">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h2 class="text-base font-semibold">{{ project.name }}</h2>
                            <p class="mt-1 text-sm text-zinc-500">{{ project.slug }}</p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="environment in project.environments"
                                :key="environment.name"
                                class="rounded border px-2.5 py-1 text-xs font-medium"
                                :class="environment.status === 'ok' ? 'border-zinc-300 text-zinc-700' : 'border-amber-300 bg-amber-50 text-amber-800'"
                                :title="pointerTitle(environment)"
                            >
                                {{ environment.label }}: {{ shortSha(environment.sha) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div v-if="project.branches.length === 0" class="p-5 text-sm text-zinc-500">
                    No branch data found. Run source update first.
                </div>

                <div v-else class="divide-y divide-zinc-200">
                    <article
                        v-for="branch in project.branches"
                        :key="branch.ref"
                        class="bg-white"
                    >
                        <button
                            type="button"
                            class="grid w-full grid-cols-[minmax(0,1fr)_auto] items-center gap-4 px-5 py-4 text-left hover:bg-zinc-50"
                            @click="toggleBranch(project, branch)"
                        >
                            <span class="min-w-0">
                                <span class="mb-2 flex flex-wrap items-center gap-2">
                                    <span
                                        v-for="environment in branchLocations(branch, project.environments)"
                                        :key="environment.name"
                                        class="rounded border px-2 py-1 text-xs font-semibold"
                                        :class="locationClass(environment)"
                                        :title="pointerTitle(environment)"
                                    >
                                        {{ locationText(project, environment) }}
                                    </span>
                                    <span
                                        v-if="branch.overlap"
                                        class="rounded border border-zinc-300 bg-zinc-50 px-2 py-1 text-xs font-medium text-zinc-600"
                                    >
                                        overlaps
                                    </span>
                                </span>

                                <span class="block truncate font-medium text-zinc-900">{{ branch.ref }}</span>
                                <span class="mt-1 block truncate text-sm text-zinc-500">{{ branch.message }}</span>
                            </span>

                            <span class="flex items-center gap-4 text-sm text-zinc-500">
                                <span class="hidden font-mono text-xs sm:inline">{{ shortSha(branch.sha) }}</span>
                                <span class="hidden sm:inline">{{ dateLabel(branch.date) }}</span>
                                <span class="text-lg leading-none">{{ isOpen(project, branch) ? '-' : '+' }}</span>
                            </span>
                        </button>

                        <div v-if="isOpen(project, branch)" class="border-t border-zinc-200 bg-zinc-50 px-5 py-4">
                            <div class="mb-3 flex flex-wrap items-center justify-between gap-3 text-xs text-zinc-500">
                                <span>
                                    Comparing {{ branch.ref }} at {{ shortSha(branch.sha) }}
                                    <template v-if="branch.compare_to">
                                        to {{ branch.compare_to.ref }} at {{ shortSha(branch.compare_to.sha) }}
                                    </template>
                                </span>
                                <span>{{ branch.commits.length }} commit{{ branch.commits.length === 1 ? '' : 's' }}</span>
                            </div>

                            <div class="overflow-x-auto rounded border border-zinc-200 bg-white">
                                <table class="w-full min-w-[760px] border-collapse text-left text-sm">
                                    <thead class="bg-zinc-50 text-xs uppercase text-zinc-500">
                                        <tr>
                                            <th class="px-4 py-3 font-semibold">Commit</th>
                                            <th class="px-4 py-3 font-semibold">Date</th>
                                            <th class="px-4 py-3 font-semibold">Author</th>
                                            <th class="px-4 py-3 font-semibold">Message</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-200">
                                        <tr
                                            v-for="commit in branch.commits"
                                            :key="`${branch.ref}:${commit.sha}`"
                                            class="align-top"
                                        >
                                            <td class="px-4 py-3 font-mono text-xs text-zinc-600">{{ shortSha(commit.sha) }}</td>
                                            <td class="px-4 py-3 text-zinc-600">{{ dateLabel(commit.date) }}</td>
                                            <td class="px-4 py-3 text-zinc-600">{{ commit.author }}</td>
                                            <td class="px-4 py-3">
                                                <div class="mb-1 flex flex-wrap gap-2">
                                                    <span
                                                        v-for="environment in commitLocations(commit, project.environments)"
                                                        :key="environment.name"
                                                        class="rounded border px-2 py-0.5 text-xs font-medium"
                                                        :class="locationClass(environment)"
                                                        :title="pointerTitle(environment)"
                                                    >
                                                        {{ locationText(project, environment) }}
                                                    </span>
                                                    <span
                                                        v-if="commit.cherry"
                                                        class="rounded border border-purple-300 bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-800"
                                                    >
                                                        cherry
                                                    </span>
                                                </div>
                                                <div class="text-zinc-800">{{ commit.message }}</div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </article>
                </div>
            </section>
        </div>
    </AppShell>
</template>
